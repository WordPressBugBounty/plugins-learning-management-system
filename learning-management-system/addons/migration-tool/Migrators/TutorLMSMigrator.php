<?php
/**
 * TutorLMS migrator.
 *
 * @package Masteriyo\Addons\MigrationTool\Migrators
 */

namespace Masteriyo\Addons\MigrationTool\Migrators;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\LMS\TutorLMS;

/**
 * Class TutorLMSMigrator.
 *
 * Thin adapter that wires TutorLMS into the MigratorInterface contract.
 * All migration logic lives in the original TutorLMS static class.
 */
class TutorLMSMigrator extends AbstractLMSMigrator {

	/**
	 * Plugin basename of Tutor LMS Pro.
	 */
	const PRO_PLUGIN_FILE = 'tutor-pro/tutor-pro.php';

	/**
	 * Steps backed by Tutor LMS Pro. Skipped unless Tutor Pro is active.
	 */
	const PRO_STEPS = array( 'google_meet' );

	/**
	 * @return class-string
	 */
	protected static function get_lms_class(): string {
		return TutorLMS::class;
	}

	public function get_slug(): string {
		return 'tutor';
	}

	public function get_label(): string {
		return 'Tutor LMS';
	}

	public function get_plugin_file(): string {
		return 'tutor/tutor.php';
	}

	public function get_steps(): array {
		/**
		 * Filters the ordered migration steps for this migrator.
		 *
		 * Contributed steps are appended after the shared ones.
		 *
		 * @param string[] $steps Ordered step names.
		 * @param string   $slug  Migrator slug, e.g. 'tutor'.
		 */
		return (array) apply_filters(
			'masteriyo_migration_tool_steps',
			array( 'users', 'courses', 'enrollments', 'orders', 'reviews', 'announcement', 'questions_n_answers', 'progress', 'quiz_attempts', 'google_meet', 'wishlists' ),
			$this->get_slug()
		);
	}

	/**
	 * Tutor Pro steps are available only while Tutor LMS Pro is active.
	 *
	 * Once Tutor Pro is deactivated its data (e.g. Google Meet meetings) is treated as
	 * absent — the step is skipped with a zero count rather than counted and silently
	 * dropped during migration.
	 *
	 * @param string $step Step name.
	 * @return bool
	 */
	public function is_step_available( string $step ): bool {
		if ( ! in_array( $step, self::PRO_STEPS, true ) ) {
			return parent::is_step_available( $step );
		}
		return $this->is_source_plugin_active( self::PRO_PLUGIN_FILE ) || defined( 'TUTOR_PRO_VERSION' );
	}

	/**
	 * Return the Masteriyo addon slug to activate for the given step.
	 *
	 * The google_meet step reaches this point only when Tutor Pro is active and data
	 * exists (see is_step_available()), so the slug is returned unconditionally —
	 * guaranteeing the migrated data has a destination addon to live in.
	 *
	 * @param string $step Step name.
	 * @return string[]
	 */
	public function get_addons_to_activate( string $step ): array {
		$map = array(
			'wishlists'   => 'wishlist',
			'google_meet' => 'google-meet',
		);

		/**
		 * Filters the Masteriyo addon slugs to activate for a migration step.
		 *
		 * @param string[] $addons Addon slugs.
		 * @param string   $step   Step name.
		 * @param string   $slug   Migrator slug, e.g. 'tutor'.
		 */
		return (array) apply_filters(
			'masteriyo_migration_tool_addons_to_activate',
			isset( $map[ $step ] ) ? array( $map[ $step ] ) : array(),
			$step,
			$this->get_slug()
		);
	}
}
