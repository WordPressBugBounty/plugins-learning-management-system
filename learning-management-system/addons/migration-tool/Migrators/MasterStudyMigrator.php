<?php
/**
 * MasterStudy migrator.
 *
 * @since x.x.x
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
 *
 * @since x.x.x
 */
class MasterStudyMigrator extends AbstractLMSMigrator {

	/**
	 * @since x.x.x
	 * @return class-string
	 */
	protected static function get_lms_class(): string {
		return MasterStudy::class;
	}

	/**
	 * @since x.x.x
	 */
	public function get_slug(): string {
		return 'masterstudy';
	}

	/**
	 * @since x.x.x
	 */
	public function get_label(): string {
		return 'MasterStudy';
	}

	/**
	 * @since x.x.x
	 */
	public function get_plugin_file(): string {
		return 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php';
	}

	/**
	 * @since x.x.x
	 */
	public function get_steps(): array {
		return array( 'users', 'courses', 'enrollments', 'orders', 'reviews', 'lesson_progress', 'quiz_attempts', 'wishlists' );
	}

	/**
	 * Return Masteriyo addon slugs to activate for addon-backed MasterStudy steps.
	 *
	 * Wishlists are stored in core MasterStudy user meta — activate whenever data exists.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 * @return string[]
	 */
	public function get_addons_to_activate( string $step ): array {
		if ( 'wishlists' === $step ) {
			return array( 'wishlist' );
		}

		return array();
	}
}
