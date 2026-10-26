<?php
/**
 * Migrator interface.
 *
 * @package Masteriyo\Addons\MigrationTool\Contracts
 */

namespace Masteriyo\Addons\MigrationTool\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Interface MigratorInterface.
 *
 * All LMS migrators must implement this interface so the controller
 * can treat every LMS identically — no LMS-specific branching in the controller.
 */
interface MigratorInterface {

	/**
	 * Unique slug that matches the LMS plugin directory key.
	 *
	 * @return string e.g. 'learnpress', 'sfwd-lms', 'tutor'
	 */
	public function get_slug(): string;

	/**
	 * Human-readable label shown in the UI.
	 *
	 * @return string e.g. 'LearnPress'
	 */
	public function get_label(): string;

	/**
	 * Plugin file used by is_plugin_active() to detect installation.
	 *
	 * @return string e.g. 'learnpress/learnpress.php'
	 */
	public function get_plugin_file(): string;

	/**
	 * Ordered list of migration step names.
	 *
	 * @return string[] e.g. ['courses', 'orders', 'reviews']
	 */
	public function get_steps(): array;

	/**
	 * Whether the given step's source data may be migrated.
	 *
	 * Free/core steps are always available. Steps backed by a separate source Pro
	 * plugin (e.g. Tutor Pro, MasterStudy Pro) are available only while that Pro
	 * plugin is active — when it is deactivated its data is treated as absent so the
	 * step is skipped cleanly (count 0) rather than half-migrated.
	 *
	 * @param string $step Step name.
	 * @return bool
	 */
	public function is_step_available( string $step ): bool;

	/**
	 * Count total source items for a step. Fast COUNT query — no records loaded.
	 *
	 * @param string $step Step name.
	 * @return int
	 */
	public function count_source_items( string $step ): int;

	/**
	 * Return one batch of source IDs starting after $cursor.
	 *
	 * The migration copies and never changes the source, so every step pages with
	 * WHERE id > $cursor ORDER BY id LIMIT $limit and leaves out rows already in the
	 * migration map (MigrationMap::unmapped_sql()). A failed ID never reappears
	 * within a run, so $exclude may be ignored; across runs it is retried because
	 * nothing maps it.
	 *
	 * @param string $step    Step name.
	 * @param int    $limit   Batch size.
	 * @param int    $cursor  Last processed ID (0 = first batch).
	 * @param int[]  $exclude IDs that already failed in this run.
	 * @return int[]
	 */
	public function get_source_ids( string $step, int $limit, int $cursor, array $exclude = array() ): array;

	/**
	 * Copy exactly one item into Masteriyo. Called inside START TRANSACTION / COMMIT.
	 *
	 * Reads the source row, writes Masteriyo rows, and records the pair in the
	 * migration map; it never renames, deletes or otherwise changes the source. A
	 * row that cannot be used is recorded with target 0 so it is not fetched again.
	 *
	 * @param string $step    Step name.
	 * @param int    $item_id Source item ID.
	 * @throws \Exception Triggers ROLLBACK; item added to failed list.
	 */
	public function migrate_item( string $step, int $item_id ): void;

	/**
	 * Called once after a step fully completes. Perform bulk recalculation here
	 * instead of per-item. No-op by default.
	 *
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
	 * @param string $step Step name.
	 * @return string[] Masteriyo addon slugs to activate (e.g. ['assignment', 'zoom']).
	 */
	public function get_addons_to_activate( string $step ): array;
}
