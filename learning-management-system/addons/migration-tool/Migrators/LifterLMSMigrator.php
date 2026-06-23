<?php
/**
 * LifterLMS migrator.
 *
 * @since x.x.x
 * @package Masteriyo\Addons\MigrationTool\Migrators
 */

namespace Masteriyo\Addons\MigrationTool\Migrators;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\LMS\LifterLMS;

/**
 * Class LifterLMSMigrator.
 *
 * Thin adapter that wires LifterLMS into the MigratorInterface contract.
 * All migration logic lives in the original LifterLMS static class.
 *
 * @since x.x.x
 */
class LifterLMSMigrator extends AbstractLMSMigrator {

	/**
	 * @since x.x.x
	 * @return class-string
	 */
	protected static function get_lms_class(): string {
		return LifterLMS::class;
	}

	/**
	 * @since x.x.x
	 */
	public function get_slug(): string {
		return 'lifterlms';
	}

	/**
	 * @since x.x.x
	 */
	public function get_label(): string {
		return 'LifterLMS';
	}

	/**
	 * @since x.x.x
	 */
	public function get_plugin_file(): string {
		return 'lifterlms/lifterlms.php';
	}

	/**
	 * @since x.x.x
	 */
	public function get_steps(): array {
		return array( 'users', 'courses', 'enrollments', 'orders', 'reviews', 'lesson_progress', 'quiz_attempts' );
	}
}
