<?php
/**
 * Source-to-target record map for the migration tool.
 *
 * Every record the migration copies is written here with the source LMS, the kind of
 * record and both IDs. The map is what lets a copy run leave the source alone: the
 * batch queries skip anything already mapped, and dependent steps resolve the copied
 * course, lesson or quiz a source row points at.
 *
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool;

defined( 'ABSPATH' ) || exit;

// A custom table has no WP API to read it through, and nothing here is worth caching.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

class MigrationMap {

	/**
	 * Table name without the site prefix.
	 */
	const TABLE = 'masteriyo_migration_map';

	/**
	 * Entity kinds shared by every migrator. Post IDs are global across post types, so
	 * one kind covers courses, sections, lessons, quizzes and the rest; the same holds
	 * for comments and users. Rows from an LMS's own tables use the step name instead.
	 */
	const POST    = 'post';
	const COMMENT = 'comment';
	const USER    = 'user';

	/**
	 * The prefixed table name.
	 *
	 * @return string
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	/**
	 * Create the table when it is missing. Safe to call on every session start.
	 *
	 * @return void
	 */
	public static function ensure_table(): void {
		global $wpdb;

		$table = self::table();

		// A probe rather than SHOW TABLES, which cannot see the temporary table the
		// WordPress test library turns every CREATE TABLE into.
		$suppressed = $wpdb->suppress_errors();
		$exists     = false !== $wpdb->query( $wpdb->prepare( 'SELECT 1 FROM %i LIMIT 1', $table ) );
		$wpdb->suppress_errors( $suppressed );

		if ( $exists ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

		dbDelta(
			"CREATE TABLE {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				lms VARCHAR(32) NOT NULL,
				entity VARCHAR(32) NOT NULL,
				source_id BIGINT UNSIGNED NOT NULL,
				target_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				session_id VARCHAR(16) NOT NULL DEFAULT '',
				created_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY source (lms, entity, source_id),
				KEY target (lms, entity, target_id)
			) {$collate};"
		);
	}

	/**
	 * Record that a source row has been copied.
	 *
	 * A target of 0 records a deliberate skip, so a row the migration cannot use is
	 * not fetched again on the next run. Recording the same source twice keeps the
	 * newer target.
	 *
	 * @param string $lms        Migrator slug, e.g. 'tutor'.
	 * @param string $entity     Entity kind, e.g. self::POST or a step name.
	 * @param int    $source_id  Source row ID.
	 * @param int    $target_id  Masteriyo row ID, or 0 for a skipped row.
	 * @param string $session_id Migration session that made the copy.
	 * @return void
	 */
	public static function record( string $lms, string $entity, int $source_id, int $target_id = 0, string $session_id = '' ): void {
		global $wpdb;

		$table = self::table();

		if ( '' === $session_id ) {
			$session_id = self::current_session();
		}

		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO %i (lms, entity, source_id, target_id, session_id, created_at)
				 VALUES (%s, %s, %d, %d, %s, %s)
				 ON DUPLICATE KEY UPDATE target_id = VALUES(target_id), session_id = VALUES(session_id)',
				$table,
				$lms,
				$entity,
				$source_id,
				$target_id,
				$session_id,
				current_time( 'mysql', true )
			)
		);
	}

	/**
	 * The Masteriyo ID a source row was copied to, or 0 when it has not been copied.
	 *
	 * @param string $lms       Migrator slug.
	 * @param string $entity    Entity kind.
	 * @param int    $source_id Source row ID.
	 * @return int
	 */
	public static function target( string $lms, string $entity, int $source_id ): int {
		global $wpdb;

		$table = self::table();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT target_id FROM %i WHERE lms = %s AND entity = %s AND source_id = %d',
				$table,
				$lms,
				$entity,
				$source_id
			)
		);
	}

	/**
	 * The Masteriyo ID a source row was copied to, or an exception when it was not.
	 *
	 * For a dependent step: an enrollment on a course that has not been copied cannot
	 * be copied either, and saying so is better than enrolling into nothing.
	 *
	 * @param string $lms       Migrator slug.
	 * @param string $entity    Entity kind.
	 * @param int    $source_id Source row ID.
	 * @param string $what      Noun for the message, e.g. 'course'.
	 * @return int
	 * @throws \Exception When the source row has no copy.
	 */
	public static function require_target( string $lms, string $entity, int $source_id, string $what ): int {
		$target = self::target( $lms, $entity, $source_id );

		if ( ! $target ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'The %s %d has not been copied yet.', $what, $source_id ) );
		}

		return $target;
	}

	/**
	 * Whether a source row has a map entry, copied or skipped.
	 *
	 * @param string $lms       Migrator slug.
	 * @param string $entity    Entity kind.
	 * @param int    $source_id Source row ID.
	 * @return bool
	 */
	public static function is_mapped( string $lms, string $entity, int $source_id ): bool {
		global $wpdb;

		$table = self::table();

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT 1 FROM %i WHERE lms = %s AND entity = %s AND source_id = %d',
				$table,
				$lms,
				$entity,
				$source_id
			)
		);
	}

	/**
	 * The table and ID column an entity's targets live in, when the batch queries
	 * can check that a copy still exists. Deleting a course takes its enrollments
	 * with it, and a rerun has to copy them again.
	 *
	 * @param string $entity Entity kind.
	 * @return array{0: non-empty-string, 1: 'ID'|'comment_ID'|'id'}|null
	 */
	private static function target_table( string $entity ): ?array {
		global $wpdb;

		switch ( $entity ) {
			case self::POST:
			case 'assignment_submissions': // Tutor comments that become reply posts.
				return array( $wpdb->posts, 'ID' );
			case self::COMMENT:
				return array( $wpdb->comments, 'comment_ID' );
			case 'enrollments':
				return array( $wpdb->prefix . 'masteriyo_user_items', 'id' );
			case 'lesson_progress':
				return array( $wpdb->prefix . 'masteriyo_user_activities', 'id' );
			case 'quiz_attempts':
				return array( $wpdb->prefix . 'masteriyo_quiz_attempts', 'id' );
		}

		return null;
	}

	/**
	 * Whether a source post needs no copy: it was skipped, or its copy still exists.
	 *
	 * The batch queries list a post whose copy the site deleted again; a step that
	 * checks the map by hand has to look past the row to the post.
	 *
	 * @param string $lms       Migrator slug.
	 * @param int    $source_id Source post ID.
	 * @return bool
	 */
	public static function is_settled_post( string $lms, int $source_id ): bool {
		if ( ! self::is_mapped( $lms, self::POST, $source_id ) ) {
			return false;
		}

		$target = self::target( $lms, self::POST, $source_id );

		return 0 === $target || (bool) get_post( $target );
	}

	/**
	 * The source row a Masteriyo record was copied from, or 0.
	 *
	 * @param string $lms       Migrator slug.
	 * @param string $entity    Entity kind.
	 * @param int    $target_id Masteriyo row ID.
	 * @return int
	 */
	public static function source( string $lms, string $entity, int $target_id ): int {
		global $wpdb;

		$table = self::table();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT source_id FROM %i WHERE lms = %s AND entity = %s AND target_id = %d AND target_id > 0 LIMIT 1',
				$table,
				$lms,
				$entity,
				$target_id
			)
		);
	}

	/**
	 * Source IDs to their targets, for many rows at once. Unmapped sources are absent.
	 *
	 * @param string $lms        Migrator slug.
	 * @param string $entity     Entity kind.
	 * @param int[]  $source_ids Source row IDs.
	 * @return array<int, int>
	 */
	public static function targets( string $lms, string $entity, array $source_ids ): array {
		global $wpdb;

		$source_ids = array_values( array_unique( array_map( 'intval', $source_ids ) ) );
		if ( empty( $source_ids ) ) {
			return array();
		}

		$table        = self::table();
		$placeholders = implode( ',', array_fill( 0, count( $source_ids ), '%d' ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders only, one %d per ID.
				"SELECT source_id, target_id FROM %i WHERE lms = %s AND entity = %s AND target_id > 0 AND source_id IN ({$placeholders})",
				$table,
				$lms,
				$entity,
				...$source_ids
			)
		);

		$map = array();
		foreach ( (array) $rows as $row ) {
			$map[ (int) $row->source_id ] = (int) $row->target_id;
		}

		return $map;
	}

	/**
	 * SQL fragment excluding mapped source rows: "AND col NOT IN (...)".
	 *
	 * For a step's count and batch queries, so a second run sees only what is new.
	 *
	 * @param string $column SQL column holding the source ID, e.g. 'ID' or 'p.ID'.
	 * @param string $lms    Migrator slug.
	 * @param string $entity Entity kind.
	 * @return string
	 */
	public static function unmapped_sql( string $column, string $lms, string $entity ): string {
		global $wpdb;

		if ( ! preg_match( '/^[A-Za-z0-9_.]+$/', $column ) ) {
			return '';
		}

		$table = self::table();

		// A row whose copy the site deleted is copied again; a skip (target 0) stays skipped.
		$target = self::target_table( $entity );
		if ( $target ) {
			return $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $column is guarded above.
				"AND {$column} NOT IN (SELECT m.source_id FROM %i m LEFT JOIN %i t ON t.%i = m.target_id WHERE m.lms = %s AND m.entity = %s AND (m.target_id = 0 OR t.%i IS NOT NULL))",
				$table,
				$target[0],
				$target[1],
				$lms,
				$entity,
				$target[1]
			);
		}

		// The column may be qualified (p.ID), which %i would quote as one name.
		return $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $column is guarded above.
			"AND {$column} NOT IN (SELECT source_id FROM %i WHERE lms = %s AND entity = %s)",
			$table,
			$lms,
			$entity
		);
	}

	/**
	 * SQL subquery of the Masteriyo IDs this migrator has copied to: "SELECT target_id ...".
	 *
	 * For a finalize step that recomputes rows, so it touches only this run's copies
	 * and never a record the site made natively.
	 *
	 * @param string $lms    Migrator slug.
	 * @param string $entity Entity kind.
	 * @return string
	 */
	public static function targets_sql( string $lms, string $entity ): string {
		global $wpdb;

		$table = self::table();

		return $wpdb->prepare(
			'SELECT target_id FROM %i WHERE lms = %s AND entity = %s AND target_id > 0',
			$table,
			$lms,
			$entity
		);
	}

	/**
	 * Number of mapped rows for an entity, skips included.
	 *
	 * @param string $lms    Migrator slug.
	 * @param string $entity Entity kind.
	 * @return int
	 */
	public static function count( string $lms, string $entity ): int {
		global $wpdb;

		$table = self::table();

		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE lms = %s AND entity = %s', $table, $lms, $entity )
		);
	}

	/**
	 * Remember which session is writing, so records need not carry it around.
	 *
	 * @param string $session_id Session ID, or '' to clear.
	 * @return void
	 */
	public static function set_current_session( string $session_id ): void {
		self::current_session( $session_id );
	}

	/**
	 * The session recorded on new rows.
	 *
	 * @param string|null $set New value when given.
	 * @return string
	 */
	private static function current_session( ?string $set = null ): string {
		static $session = '';

		if ( null !== $set ) {
			$session = $set;
		}

		return $session;
	}
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
