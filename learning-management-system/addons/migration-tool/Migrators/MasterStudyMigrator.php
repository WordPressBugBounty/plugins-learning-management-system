<?php
/**
 * MasterStudy migrator.
 *
 * @package Masteriyo\Addons\MigrationTool\Migrators
 */

namespace Masteriyo\Addons\MigrationTool\Migrators;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\LMS\MasterStudy;

/**
 * Class MasterStudyMigrator.
 *
 * Thin adapter that wires MasterStudy into the MigratorInterface contract.
 * All migration logic lives in the original MasterStudy static class.
 */
class MasterStudyMigrator extends AbstractLMSMigrator {

	/**
	 * @return class-string
	 */
	protected static function get_lms_class(): string {
		return MasterStudy::class;
	}

	public function get_slug(): string {
		return 'masterstudy';
	}

	public function get_label(): string {
		return 'MasterStudy';
	}

	public function get_plugin_file(): string {
		return 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php';
	}

	public function get_steps(): array {
		/**
		 * Filters the ordered migration steps for this migrator.
		 *
		 * Contributed steps are appended after the shared ones.
		 *
		 * @param string[] $steps Ordered step names.
		 * @param string   $slug  Migrator slug, e.g. 'masterstudy'.
		 */
		return (array) apply_filters(
			'masteriyo_migration_tool_steps',
			array( 'users', 'courses', 'enrollments', 'orders', 'reviews', 'lesson_progress', 'quiz_attempts', 'wishlists' ),
			$this->get_slug()
		);
	}

	/**
	 * Return Masteriyo addon slugs to activate for addon-backed MasterStudy steps.
	 *
	 * Wishlists are stored in core MasterStudy user meta — activate whenever data exists.
	 *
	 * @param string $step Step name.
	 * @return string[]
	 */
	public function get_addons_to_activate( string $step ): array {
		/**
		 * Filters the Masteriyo addon slugs to activate for a migration step.
		 *
		 * @param string[] $addons Addon slugs.
		 * @param string   $step   Step name.
		 * @param string   $slug   Migrator slug, e.g. 'masterstudy'.
		 */
		return (array) apply_filters(
			'masteriyo_migration_tool_addons_to_activate',
			'wishlists' === $step ? array( 'wishlist' ) : array(),
			$step,
			$this->get_slug()
		);
	}
}
