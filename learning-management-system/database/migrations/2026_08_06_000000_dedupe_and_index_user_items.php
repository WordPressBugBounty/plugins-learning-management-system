<?php

defined( 'ABSPATH' ) || exit;

/**
 * Dedupe `masteriyo_user_items` and add the indexes the Enrollments surface needs.
 *
 * Enrollment is a check-then-act across two call sites, so concurrent requests can each insert
 * a duplicate (user_id, item_id, item_type) row that a later revoke can't fully clear.
 *
 * Runs at bootstrap on every request, so this is bounded and resumable rather than a single
 * unbounded pass: two plain indexes are added upfront (safe even with duplicates present), then
 * dedupe runs in bounded batches (`masteriyo_user_items_migration_time_budget` filter, default
 * 10s) — keeping one row per duplicate group — until none remain, at which point a UNIQUE index
 * is added. If the time budget runs out, or an index add silently fails verification, `up()`
 * returns false so the next request resumes automatically.
 *
 * `idx_user_item_dedupe_scan` is deliberately a distinct index from `idx_user_item_unique`
 * (not the same index later upgraded), so `add_index_if_missing()` can't mistake one for the
 * other and skip adding it.
 *
 * user_id/item_type are prefixed well under their real max length to keep the composite key
 * under the 767-byte limit on older InnoDB row formats.
 *
 * @since 2.31.0
 */

use Masteriyo\Database\Migration;

class DedupeAndIndexUserItems extends Migration {
	/**
	 * Duplicate groups resolved per dedupe pass.
	 */
	const BATCH_SIZE = 200;

	/**
	 * Run the migration.
	 *
	 * @return void|false False if bounded work is left unfinished (still-remaining
	 *                     duplicates, or a failed index verification); the migrator
	 *                     treats that as "not ran" and retries on the next request.
	 */
	public function up() {
		$table      = "{$this->prefix}masteriyo_user_items";
		$meta_table = "{$this->prefix}masteriyo_user_itemmeta";

		if ( ! $this->table_exists( $table ) ) {
			return;
		}

		// The heavy table work runs only on admin/cron/CLI requests so its cost never
		// lands on a visitor; yielding `false` here parks the queue for this request
		// only. Ordinary schema migrations stay unconditional in the runner.
		if ( ! is_admin() && ! wp_doing_cron() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return false;
		}

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 );
		}
		ignore_user_abort( true );

		$this->add_index_if_missing(
			$table,
			'idx_user_item_type_list',
			'INDEX `idx_user_item_type_list` (item_type(100), id)'
		);

		$this->add_index_if_missing(
			$table,
			'idx_user_item_dedupe_scan',
			'INDEX `idx_user_item_dedupe_scan` (user_id(20), item_id, item_type(100))'
		);

		/**
		 * Filters the wall-clock time budget (seconds) this migration spends deduping
		 * per request before yielding to the next request. Every pass resolves at
		 * least one batch regardless of the budget, so forcing 0 yields after exactly
		 * one batch — useful to exercise the resume path in tests.
		 *
		 * @since 2.31.0
		 *
		 * @param float $seconds Time budget in seconds.
		 */
		$time_budget = (float) apply_filters( 'masteriyo_user_items_migration_time_budget', 10 );

		$started          = microtime( true );
		$removed          = 0;
		$exhausted_budget = false;

		while ( true ) {
			// Checked after each batch, never before the first: a budget shorter than one batch's runtime would otherwise spin forever with zero work done.
			$batch    = $this->dedupe_batch( $table, $meta_table, self::BATCH_SIZE );
			$removed += $batch['removed'];

			// Fewer groups than the batch limit means that pass drained everything there was to find.
			if ( $batch['groups'] < self::BATCH_SIZE ) {
				break;
			}

			if ( ( microtime( true ) - $started ) >= $time_budget ) {
				$exhausted_budget = true;
				break;
			}
		}

		if ( $removed > 0 && function_exists( 'masteriyo_get_logger' ) ) {
			masteriyo_get_logger()->info(
				sprintf( 'Removed %d duplicate masteriyo_user_items row(s) so far.', $removed ),
				array( 'source' => 'dedupe-user-items-migration' )
			);
		}

		if ( $exhausted_budget ) {
			update_option( 'masteriyo_user_items_migration_incomplete', 1 );
			return false;
		}

		$this->add_index_if_missing(
			$table,
			'idx_user_item_unique',
			'UNIQUE INDEX `idx_user_item_unique` (user_id(20), item_id, item_type(100))'
		);

		if ( ! $this->index_exists( $table, 'idx_user_item_unique' ) || ! $this->index_exists( $table, 'idx_user_item_type_list' ) ) {
			update_option( 'masteriyo_user_items_migration_incomplete', 1 );

			/*
			 * Dedupe is done (budget wasn't exhausted) yet the ALTER didn't stick — this is
			 * the CANNOT-finish case (e.g. the DB user lacks ALTER/INDEX privilege), not a
			 * big-table yield. Unbounded `false` here would re-run forever and block every
			 * future migration behind it, so after a few real attempts the migration parks
			 * itself: recorded as ran, with the incomplete option left set so the stalled
			 * admin notice keeps telling the truth about the missing index.
			 */
			/*
			 * A duplicate inserted between the dedupe pass and the ALTER fails the ALTER
			 * too — transient, not CANNOT-finish: the next request's dedupe pass clears
			 * it, so it must not consume one of the three real attempts. Bounded by its
			 * own counter, though — a table busy enough to lose this race ten times
			 * would otherwise re-run dedupe on every admin request forever.
			 */
			if ( $this->connection->get_var( "SELECT 1 FROM `{$table}` GROUP BY user_id, item_id, item_type HAVING COUNT(*) > 1 LIMIT 1" ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$races = (int) get_option( 'masteriyo_user_items_index_races', 0 ) + 1;

				if ( $races <= 10 ) {
					update_option( 'masteriyo_user_items_index_races', $races, false );
					return false;
				}
			}

			$attempts = (int) get_option( 'masteriyo_user_items_index_attempts', 0 ) + 1;

			if ( $attempts < 3 ) {
				update_option( 'masteriyo_user_items_index_attempts', $attempts, false );
				return false;
			}

			delete_option( 'masteriyo_user_items_index_attempts' );
			delete_option( 'masteriyo_user_items_index_races' );

			if ( function_exists( 'masteriyo_get_logger' ) ) {
				masteriyo_get_logger()->error(
					'Giving up creating the user_items unique index after repeated failures; duplicate protection stays off until it is added manually.',
					array( 'source' => 'dedupe-user-items-migration' )
				);
			}

			return;
		}

		delete_option( 'masteriyo_user_items_index_attempts' );
		delete_option( 'masteriyo_user_items_index_races' );
		delete_option( 'masteriyo_user_items_migration_incomplete' );
	}

	/**
	 * Reverse the migration.
	 */
	public function down() {
		$table = "{$this->prefix}masteriyo_user_items";

		foreach ( array( 'idx_user_item_unique', 'idx_user_item_type_list', 'idx_user_item_dedupe_scan' ) as $name ) {
			if ( $this->index_exists( $table, $name ) ) {
				$result = $this->connection->query( "ALTER TABLE `{$table}` DROP INDEX `{$name}`" );

				if ( false === $result && function_exists( 'masteriyo_get_logger' ) ) {
					masteriyo_get_logger()->error(
						sprintf( 'Failed to drop index %s: %s', $name, $this->connection->last_error ),
						array( 'source' => 'dedupe-user-items-migration' )
					);
				}
			}
		}

		delete_option( 'masteriyo_user_items_migration_incomplete' );
	}

	/**
	 * Resolve at most `$limit` duplicate (user_id, item_id, item_type) groups, keeping
	 * one row per group — the lowest-id 'active' row if any group member is active,
	 * else the group's lowest id — and deleting the rest, plus their itemmeta.
	 *
	 * Scoped to groups that actually have duplicates (HAVING COUNT(*) > 1), so a pass
	 * over a clean table does no extra work.
	 *
	 * @param string $table      `masteriyo_user_items` table name.
	 * @param string $meta_table `masteriyo_user_itemmeta` table name.
	 * @param int    $limit      Max duplicate groups to resolve in this pass.
	 *
	 * @return array{groups: int, removed: int} Groups seen and rows removed this pass.
	 */
	private function dedupe_batch( $table, $meta_table, $limit ) {
		$wpdb = $this->connection;

		// Table names here are prefixed constants built above, never request input — nothing to prepare a placeholder for besides the LIMIT below.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( 'DROP TEMPORARY TABLE IF EXISTS tmp_user_item_winners' );
		$wpdb->query(
			$wpdb->prepare(
				"CREATE TEMPORARY TABLE tmp_user_item_winners AS
					SELECT
						user_id,
						item_id,
						item_type,
						COALESCE( MIN( CASE WHEN status = 'active' THEN id END ), MIN( id ) ) AS keep_id
					FROM {$table}
					GROUP BY user_id, item_id, item_type
					HAVING COUNT(*) > 1
					LIMIT %d",
				$limit
			)
		);

		$groups = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM tmp_user_item_winners' );

		if ( 0 === $groups ) {
			$wpdb->query( 'DROP TEMPORARY TABLE IF EXISTS tmp_user_item_winners' );

			return array(
				'groups'  => 0,
				'removed' => 0,
			);
		}

		$wpdb->query( 'DROP TEMPORARY TABLE IF EXISTS tmp_user_item_remove_ids' );
		$wpdb->query(
			"CREATE TEMPORARY TABLE tmp_user_item_remove_ids AS
				SELECT ui.id AS id
				FROM {$table} ui
				INNER JOIN tmp_user_item_winners w
					ON ui.user_id = w.user_id
					AND ui.item_id = w.item_id
					AND ui.item_type = w.item_type
				WHERE ui.id <> w.keep_id"
		);

		// The winner keeps any meta it lacks from the rows about to go — losing a
		// purchase row's _order_id/_source would delink the surviving enrollment
		// from its order and misclassify it. The winner's own values always win;
		// MIN() makes it deterministic when two losers disagree.
		$wpdb->query(
			"INSERT INTO {$meta_table} ( user_item_id, meta_key, meta_value )
				SELECT w.keep_id, m.meta_key, MIN( m.meta_value )
				FROM {$meta_table} m
				INNER JOIN tmp_user_item_remove_ids r ON r.id = m.user_item_id
				INNER JOIN {$table} ui ON ui.id = r.id
				INNER JOIN tmp_user_item_winners w
					ON w.user_id = ui.user_id
					AND w.item_id = ui.item_id
					AND w.item_type = ui.item_type
				WHERE NOT EXISTS (
					SELECT 1 FROM {$meta_table} k
					WHERE k.user_item_id = w.keep_id AND k.meta_key = m.meta_key
				)
				GROUP BY w.keep_id, m.meta_key"
		);

		$wpdb->query(
			"DELETE FROM {$meta_table} WHERE user_item_id IN ( SELECT id FROM tmp_user_item_remove_ids )"
		);

		$removed = (int) $wpdb->query(
			"DELETE FROM {$table} WHERE id IN ( SELECT id FROM tmp_user_item_remove_ids )"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$wpdb->query( 'DROP TEMPORARY TABLE IF EXISTS tmp_user_item_remove_ids' );
		$wpdb->query( 'DROP TEMPORARY TABLE IF EXISTS tmp_user_item_winners' );

		return array(
			'groups'  => $groups,
			'removed' => max( 0, $removed ),
		);
	}

	/**
	 * Add an index if it does not already exist. `$definition` is the full
	 * `ADD ...` fragment (e.g. `UNIQUE INDEX \`name\` (cols)`), so unique and
	 * plain indexes share one code path.
	 *
	 * @param string $table      Full (prefixed) table name.
	 * @param string $name       Index name, used only for the existence check.
	 * @param string $definition Full ADD-index SQL fragment.
	 */
	private function add_index_if_missing( $table, $name, $definition ) {
		if ( $this->index_exists( $table, $name ) ) {
			return;
		}

		$result = $this->connection->query( "ALTER TABLE `{$table}` ADD {$definition}" );

		// wpdb::query() never throws — it returns false and records the message in last_error. A try/catch here would never fire, so a failed ALTER would be silent.
		if ( false === $result && function_exists( 'masteriyo_get_logger' ) ) {
			masteriyo_get_logger()->error(
				sprintf( 'Failed to add index %s: %s', $name, $this->connection->last_error ),
				array( 'source' => 'dedupe-user-items-migration' )
			);
		}
	}

	/**
	 * @param string $table Full (prefixed) table name.
	 */
	private function table_exists( $table ) {
		return (bool) $this->connection->query( "SHOW TABLES LIKE '{$table}'" );
	}

	/**
	 * Avoid querying information_schema, which requires global database privileges.
	 *
	 * @param string $table Full (prefixed) table name.
	 * @param string $name  Index name.
	 */
	private function index_exists( $table, $name ) {
		$results = $this->connection->get_results( "SHOW INDEX FROM `{$table}`" );

		if ( ! is_array( $results ) ) {
			return false;
		}

		foreach ( $results as $row ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( isset( $row->Key_name ) && $row->Key_name === $name ) {
				return true;
			}
		}

		return false;
	}
}
