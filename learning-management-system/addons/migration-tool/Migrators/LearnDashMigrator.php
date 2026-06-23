<?php
/**
 * LearnDash migrator.
 *
 * @since x.x.x
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
 * Steps: users → courses → enrollments → orders → lesson_progress → quiz_attempts
 *
 * @since x.x.x
 */
class LearnDashMigrator extends AbstractLMSMigrator {

	/**
	 * @since x.x.x
	 * @return class-string
	 */
	protected static function get_lms_class(): string {
		return LearnDash::class;
	}

	/**
	 * @since x.x.x
	 */
	public function get_slug(): string {
		return 'sfwd-lms';
	}

	/**
	 * @since x.x.x
	 */
	public function get_label(): string {
		return 'LearnDash';
	}

	/**
	 * @since x.x.x
	 */
	public function get_plugin_file(): string {
		return 'sfwd-lms/sfwd_lms.php';
	}

	/**
	 * @since x.x.x
	 */
	public function get_steps(): array {
		return array( 'users', 'courses', 'enrollments', 'orders', 'lesson_progress', 'quiz_attempts' );
	}
}
