<?php
/**
 * TutorLMS migrator.
 *
 * @since x.x.x
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
 *
 * @since x.x.x
 */
class TutorLMSMigrator extends AbstractLMSMigrator {

	/**
	 * Plugin basename of Tutor LMS Pro.
	 *
	 * @since x.x.x
	 */
	const PRO_PLUGIN_FILE = 'tutor-pro/tutor-pro.php';

	/**
	 * Steps backed by Tutor LMS Pro. Skipped unless Tutor Pro is active.
	 *
	 * @since x.x.x
	 */
	const PRO_STEPS = array( 'google_meet' );

	/**
	 * @since x.x.x
	 * @return class-string
	 */
	protected static function get_lms_class(): string {
		return TutorLMS::class;
	}

	/**
	 * @since x.x.x
	 */
	public function get_slug(): string {
		return 'tutor';
	}

	/**
	 * @since x.x.x
	 */
	public function get_label(): string {
		return 'Tutor LMS';
	}

	/**
	 * @since x.x.x
	 */
	public function get_plugin_file(): string {
		return 'tutor/tutor.php';
	}

	/**
	 * @since x.x.x
	 */
	public function get_steps(): array {
		return array( 'users', 'courses', 'enrollments', 'orders', 'reviews', 'announcement', 'questions_n_answers', 'progress', 'quiz_attempts', 'google_meet', 'wishlists' );
	}

	/**
	 * Tutor Pro steps are available only while Tutor LMS Pro is active.
	 *
	 * Once Tutor Pro is deactivated its data (e.g. Google Meet meetings) is treated as
	 * absent — the step is skipped with a zero count rather than counted and silently
	 * dropped during migration.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 * @return bool
	 */
	public function is_step_available( string $step ): bool {
		if ( ! in_array( $step, self::PRO_STEPS, true ) ) {
			return true;
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
	 * @since x.x.x
	 * @param string $step Step name.
	 * @return string[]
	 */
	public function get_addons_to_activate( string $step ): array {
		$map = array(
			'wishlists'   => 'wishlist',
			'google_meet' => 'google-meet',
		);

		return isset( $map[ $step ] ) ? array( $map[ $step ] ) : array();
	}
}
