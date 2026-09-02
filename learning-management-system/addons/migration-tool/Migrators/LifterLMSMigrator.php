<?php
/**
 * LifterLMS migrator.
 *
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
 */
class LifterLMSMigrator extends AbstractLMSMigrator {

	/**
	 * @return class-string
	 */
	protected static function get_lms_class(): string {
		return LifterLMS::class;
	}

	public function get_slug(): string {
		return 'lifterlms';
	}

	public function get_label(): string {
		return 'LifterLMS';
	}

	public function get_plugin_file(): string {
		return 'lifterlms/lifterlms.php';
	}

	public function get_steps(): array {
		return array( 'users', 'courses', 'enrollments', 'orders', 'reviews', 'lesson_progress', 'quiz_attempts' );
	}
}
