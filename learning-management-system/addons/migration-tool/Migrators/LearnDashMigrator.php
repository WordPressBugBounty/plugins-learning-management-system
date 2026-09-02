<?php
/**
 * LearnDash migrator.
 *
 * @package Masteriyo\Addons\MigrationTool\Migrators
 */

namespace Masteriyo\Addons\MigrationTool\Migrators;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\LMS\LearnDash;

/**
 * Class LearnDashMigrator.
 *
 * Thin adapter that wires LearnDash into the MigratorInterface / AbstractLMSMigrator
 * contract. All migration logic lives in the LearnDash static class.
 *
 * Steps: users → courses → enrollments → lesson_progress → quiz_attempts → orders → assignments
 */
class LearnDashMigrator extends AbstractLMSMigrator {

	/**
	 * @return class-string
	 */
	protected static function get_lms_class(): string {
		return LearnDash::class;
	}

	public function get_slug(): string {
		return 'sfwd-lms';
	}

	public function get_label(): string {
		return 'LearnDash';
	}

	public function get_plugin_file(): string {
		return 'sfwd-lms/sfwd_lms.php';
	}

	public function get_steps(): array {
		return array( 'users', 'courses', 'enrollments', 'lesson_progress', 'quiz_attempts', 'orders', 'assignments' );
	}

	/**
	 * Return Masteriyo addon slugs to activate for addon-backed LearnDash steps.
	 *
	 * LearnDash assignments are built into the core sfwd-lms plugin (sfwd-assignment CPT)
	 * — no separate addon check is needed; the slug is returned whenever data exists.
	 *
	 * @param string $step Step name.
	 * @return string[]
	 */
	public function get_addons_to_activate( string $step ): array {
		if ( 'assignments' === $step ) {
			return array( 'assignment' );
		}

		return array();
	}
}
