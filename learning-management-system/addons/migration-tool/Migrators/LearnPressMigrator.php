<?php
/**
 * LearnPress migrator.
 *
 * @package Masteriyo\Addons\MigrationTool\Migrators
 */

namespace Masteriyo\Addons\MigrationTool\Migrators;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\LMS\LearnPress;

/**
 * Class LearnPressMigrator.
 *
 * Thin adapter that wires LearnPress into the MigratorInterface contract.
 * All migration logic lives in the LearnPress static class.
 */
class LearnPressMigrator extends AbstractLMSMigrator {

	/**
	 * @return class-string
	 */
	protected static function get_lms_class(): string {
		return LearnPress::class;
	}

	public function get_slug(): string {
		return 'learnpress';
	}

	public function get_label(): string {
		return 'LearnPress';
	}

	public function get_plugin_file(): string {
		return 'learnpress/learnpress.php';
	}

	public function get_steps(): array {
		return array(
			'users',
			'courses',
			'enrollments',
			'orders',
			'reviews',
			'quiz_results',
			'lesson_progress',
		);
	}
}
