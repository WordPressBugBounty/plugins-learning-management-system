<?php

defined( 'ABSPATH' ) || exit;

/**
 * Add composite indexes that speed up analytics dashboard queries.
 *
 * The analytics controller runs multi-column WHERE clauses on these tables.
 * Single-column indexes force MySQL to pick one and scan the rest; composite
 * indexes let it satisfy the full condition in a single index range scan.
 *
 * wp_masteriyo_user_activities
 *   idx_analytics_completions  (item_id, activity_type, completed_at)
 *     → course / completion time-series queries
 *   idx_analytics_parent_act   (parent_id, activity_type, completed_at)
 *     → lesson/quiz completion queries that JOIN on parent_id
 *   idx_analytics_parent_crt   (parent_id, activity_type, created_at)
 *     → quiz attempt queries (all statuses, keyed by created_at)
 *
 * wp_masteriyo_user_items
 *   idx_analytics_enrollments  (item_id, status, date_start)
 *     → enrollment count/time-series queries
 */

use Masteriyo\Database\Migration;

class AddAnalyticsCompositeIndexes extends Migration {
	/**
	 * Run the migration.
	 */
	public function up() {
		$activities = "{$this->prefix}masteriyo_user_activities";
		$user_items = "{$this->prefix}masteriyo_user_items";

		if ( ! $this->table_exists( $activities ) || ! $this->table_exists( $user_items ) ) {
			return;
		}

		$indexes = array(
			array(
				'table' => $activities,
				'name'  => 'idx_analytics_completions',
				'cols'  => '(item_id, activity_type, completed_at)',
			),
			array(
				'table' => $activities,
				'name'  => 'idx_analytics_parent_act',
				'cols'  => '(parent_id, activity_type, completed_at)',
			),
			array(
				'table' => $activities,
				'name'  => 'idx_analytics_parent_crt',
				'cols'  => '(parent_id, activity_type, created_at)',
			),
			array(
				'table' => $user_items,
				'name'  => 'idx_analytics_enrollments',
				'cols'  => '(item_id, status(191), date_start)',
			),
		);

		foreach ( $indexes as $idx ) {
			if ( ! $this->index_exists( $idx['table'], $idx['name'] ) ) {
				$result = $this->connection->query(
					"ALTER TABLE `{$idx['table']}` ADD INDEX `{$idx['name']}` {$idx['cols']}"
				);

				// wpdb::query() never throws — it returns false and records the message in
				// last_error, which wpdb::flush() clears at the start of every query. A
				// try/catch here would never fire, so a failed ALTER would be silent.
				if ( false === $result ) {
					// A failure here is not fatal: the index is an optimisation, so the
					// migration is still recorded as run and analytics simply stays slower.
					masteriyo_get_logger()->error(
						sprintf( 'Failed to add index %s: %s', $idx['name'], $this->connection->last_error ),
						array( 'source' => 'analytics-composite-indexes-migration' )
					);
				}
			}
		}
	}

	/**
	 * Reverse the migrations.
	 */
	public function down() {
		$activities = "{$this->prefix}masteriyo_user_activities";
		$user_items = "{$this->prefix}masteriyo_user_items";

		$indexes = array(
			array(
				'table' => $activities,
				'name'  => 'idx_analytics_completions',
			),
			array(
				'table' => $activities,
				'name'  => 'idx_analytics_parent_act',
			),
			array(
				'table' => $activities,
				'name'  => 'idx_analytics_parent_crt',
			),
			array(
				'table' => $user_items,
				'name'  => 'idx_analytics_enrollments',
			),
		);

		foreach ( $indexes as $idx ) {
			if ( $this->index_exists( $idx['table'], $idx['name'] ) ) {
				$result = $this->connection->query(
					"ALTER TABLE `{$idx['table']}` DROP INDEX `{$idx['name']}`"
				);

				// See up(): wpdb::query() reports failure by return value, not by throwing.
				if ( false === $result ) {
					masteriyo_get_logger()->error(
						sprintf( 'Failed to drop index %s: %s', $idx['name'], $this->connection->last_error ),
						array( 'source' => 'analytics-composite-indexes-migration' )
					);
				}
			}
		}
	}

	/**
	 * @param string $table Full (prefixed) table name.
	 */
	private function table_exists( string $table ): bool {
		return (bool) $this->connection->query( "SHOW TABLES LIKE '{$table}'" );
	}

	/**
	 * @param string $table Full (prefixed) table name.
	 * @param string $name  Index name.
	 */
	private function index_exists( string $table, string $name ): bool {
		// Avoid querying information_schema which requires global database privileges.
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
