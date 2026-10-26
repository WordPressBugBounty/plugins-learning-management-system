<?php
/**
 * Copies a source LMS post so the migration can convert the copy and leave the original alone.
 *
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool;

defined( 'ABSPATH' ) || exit;

class PostCloner {

	/**
	 * Copy a post with its meta and term relationships into a new post, and map it.
	 *
	 * The caller's overrides, normally the Masteriyo post type, apply before the
	 * insert. Rows are written directly: wp_insert_post() would fire the source
	 * plugin's save hooks for a post it still believes is its own.
	 *
	 * Calling this twice for the same source returns the existing copy.
	 *
	 * @param string $lms       Migrator slug, e.g. 'tutor'.
	 * @param int    $source_id Source post ID.
	 * @param array<string, int|string> $overrides Column values for the copy, e.g. a new post_parent.
	 * @return int New post ID.
	 * @throws \Exception When the source post does not exist or the insert fails.
	 */
	public static function clone_post( string $lms, int $source_id, array $overrides = array() ): int {
		global $wpdb;

		$existing = MigrationMap::target( $lms, MigrationMap::POST, $source_id );
		if ( $existing && get_post( $existing ) ) {
			self::place( $existing, $overrides );
			return $existing;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = (array) $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $source_id ) );
		if ( empty( $row ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'Post %d not found.', $source_id ) );
		}

		unset( $row['ID'] );
		$row['guid'] = '';
		$row         = array_merge( $row, array_intersect_key( $overrides, $row ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( false === $wpdb->insert( $wpdb->posts, $row ) || ! $wpdb->insert_id ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'Could not copy post %d: %s', $source_id, $wpdb->last_error ) );
		}

		$new_id = (int) $wpdb->insert_id;

		// The slug wp_insert_post would have given: unique within the copy's own type.
		$slug = wp_unique_post_slug( $row['post_name'], $new_id, $row['post_status'], $row['post_type'], (int) $row['post_parent'] );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->posts,
			array(
				'guid'      => home_url( '?p=' . $new_id ),
				'post_name' => $slug,
			),
			array( 'ID' => $new_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
				 SELECT %d, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d",
				$new_id,
				$source_id
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->term_relationships} (object_id, term_taxonomy_id, term_order)
				 SELECT %d, term_taxonomy_id, term_order FROM {$wpdb->term_relationships} WHERE object_id = %d",
				$new_id,
				$source_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		clean_post_cache( $new_id );

		MigrationMap::record( $lms, MigrationMap::POST, $source_id, $new_id );

		return $new_id;
	}

	/**
	 * Move a copy with no live owning course to the parent and position the caller asks for.
	 *
	 * A copy kept from a course the site deleted still hangs off the old section
	 * or course; when the course is copied again, the copy has to land in the new one.
	 *
	 * @param int                       $post_id   The copy.
	 * @param array<string, int|string> $overrides The clone overrides; only post_parent and menu_order apply.
	 */
	public static function place( int $post_id, array $overrides ): void {
		global $wpdb;

		$placement = array_intersect_key(
			$overrides,
			array(
				'post_parent' => 0,
				'menu_order'  => 0,
			)
		);
		if ( ! $placement ) {
			return;
		}

		$owner = (int) get_post_meta( $post_id, '_course_id', true );
		if ( $owner && get_post( $owner ) ) {
			return;
		}

		$post = get_post( $post_id );
		foreach ( $placement as $column => $value ) {
			if ( (int) $post->$column === (int) $value ) {
				unset( $placement[ $column ] );
			}
		}
		if ( ! $placement ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $wpdb->posts, array_map( 'intval', $placement ), array( 'ID' => $post_id ), '%d', array( '%d' ) );
		clean_post_cache( $post_id );
	}

	/**
	 * Copy a comment into a new comment, and map it.
	 *
	 * Meta is copied too. The caller sets the new type, post and parent through
	 * $overrides; comment_parent in particular has to be the copied parent, not the
	 * source one.
	 *
	 * @param string $lms       Migrator slug.
	 * @param int    $source_id Source comment ID.
	 * @param array<string, int|string> $overrides Column values for the copy.
	 * @return int New comment ID.
	 * @throws \Exception When the source comment does not exist or the insert fails.
	 */
	public static function clone_comment( string $lms, int $source_id, array $overrides = array() ): int {
		global $wpdb;

		$existing = MigrationMap::target( $lms, MigrationMap::COMMENT, $source_id );
		if ( $existing && get_comment( $existing ) ) {
			return $existing;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = (array) $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->comments} WHERE comment_ID = %d", $source_id ) );
		if ( empty( $row ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'Comment %d not found.', $source_id ) );
		}

		unset( $row['comment_ID'] );
		$row = array_merge( $row, array_intersect_key( $overrides, $row ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( false === $wpdb->insert( $wpdb->comments, $row ) || ! $wpdb->insert_id ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'Could not copy comment %d: %s', $source_id, $wpdb->last_error ) );
		}

		$new_id = (int) $wpdb->insert_id;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->commentmeta} (comment_id, meta_key, meta_value)
				 SELECT %d, meta_key, meta_value FROM {$wpdb->commentmeta} WHERE comment_id = %d",
				$new_id,
				$source_id
			)
		);

		clean_comment_cache( $new_id );
		wp_update_comment_count( (int) $row['comment_post_ID'] );

		MigrationMap::record( $lms, MigrationMap::COMMENT, $source_id, $new_id );

		return $new_id;
	}
}
