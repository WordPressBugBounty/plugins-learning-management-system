<?php
/**
 * Migrator interface.
 *
 * @since x.x.x
 * @package Masteriyo\Addons\MigrationTool\Contracts
 */

namespace Masteriyo\Addons\MigrationTool\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Interface MigratorInterface.
 *
 * All LMS migrators must implement this interface so the controller
 * can treat every LMS identically — no LMS-specific branching in the controller.
 *
 * @since x.x.x
 */
interface MigratorInterface {

	/**
	 * Unique slug that matches the LMS plugin directory key.
	 *
	 * @since x.x.x
	 *
	 * @return string e.g. 'learnpress', 'sfwd-lms', 'tutor'
	 */
	public function get_slug(): string;

	/**
	 * Human-readable label shown in the UI.
	 *
	 * @since x.x.x
	 *
	 * @return string e.g. 'LearnPress'
	 */
	public function get_label(): string;

	/**
	 * Plugin file used by is_plugin_active() to detect installation.
	 *
	 * @since x.x.x
	 *
	 * @return string e.g. 'learnpress/learnpress.php'
	 */
	public function get_plugin_file(): string;

	/**
	 * Ordered list of migration step names.
	 *
	 * @since x.x.x
	 *
	 * @return string[] e.g. ['courses', 'orders', 'reviews']
	 */
	public function get_steps(): array;

	/**
	 * Whether the given step's source data may be migrated.
	 *
	 * Free/core steps are always available. Steps backed by a separate source Pro
	 * plugin (e.g. Tutor Pro) are available only while that Pro plugin is active —
	 * when it is deactivated its data is treated as absent so the step is skipped
	 * cleanly (count 0) rather than half-migrated.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 * @return bool
	 */
	public function is_step_available( string $step ): bool;

	/**
	 * Count total source items for a step. Fast COUNT query — no records loaded.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 * @return int
	 */
	public function count_source_items( string $step ): int;

	/**
	 * Return one batch of source IDs starting after $cursor.
	 *
	 * Self-cleaning steps (those that delete or rename the source row on migration)
	 * must ignore $cursor and always query from the top — processed rows vanish from
	 * the WHERE clause automatically. Non-self-cleaning steps must use:
	 * WHERE id > $cursor ORDER BY id LIMIT $limit
	 *
	 * $exclude contains IDs that have already failed and must be skipped. Self-cleaning
	 * steps must apply AND id NOT IN ($exclude) to prevent failed rows (which were never
	 * cleaned) from permanently blocking later items. Cursor-based steps can ignore it —
	 * failed IDs never reappear in cursor-based queries.
	 *
	 * @since x.x.x
	 * @param string $step    Step name.
	 * @param int    $limit   Batch size.
	 * @param int    $cursor  Last processed ID (0 = first batch).
	 * @param int[]  $exclude IDs to skip (already-failed items).
	 * @return int[]
	 */
	public function get_source_ids( string $step, int $limit, int $cursor, array $exclude = array() ): array;

	/**
	 * Migrate exactly one item. Called inside START TRANSACTION / COMMIT.
	 * Must be idempotent — safe to call twice for the same item_id.
	 *
	 * @since x.x.x
	 * @param string $step    Step name.
	 * @param int    $item_id Source item ID.
	 * @throws \Exception Triggers ROLLBACK; item added to failed list.
	 */
	public function migrate_item( string $step, int $item_id ): void;

	/**
	 * Called once after a step fully completes. Perform bulk recalculation here
	 * instead of per-item. No-op by default.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 */
	public function finalize_step( string $step ): void;

	/**
	 * Return Masteriyo addon slugs to activate when this step has data to migrate.
	 *
	 * Called once at step start (cursor = 0, completed = 0) only when item count > 0.
	 * A pro step reaches this point only when its source Pro plugin is active (see
	 * is_step_available()), so the matching slug is returned unconditionally — the
	 * presence of data is sufficient proof the feature was used.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 * @return string[] Masteriyo addon slugs to activate (e.g. ['assignment', 'zoom']).
	 */
	public function get_addons_to_activate( string $step ): array;
}
