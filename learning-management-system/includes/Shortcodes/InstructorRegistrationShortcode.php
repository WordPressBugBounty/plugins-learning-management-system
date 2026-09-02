<?php
/**
 * Instructor registration shortcode.
 *
 * @since 1.2.0
 * @class InstructorRegistrationShortcode
 * @package Masteriyo\Shortcodes
 */

namespace Masteriyo\Shortcodes;

use Masteriyo\Abstracts\Shortcode;
use Masteriyo\Enums\InstructorApplyStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Instructor registration shortcode.
 * @since 1.2.0
 */
class InstructorRegistrationShortcode extends Shortcode {

	/**
	 * Shortcode tag.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	protected $tag = 'masteriyo_instructor_registration';

	/**
	 * Get shortcode content.
	 *
	 * @since  1.2.0
	 *
	 * @return string
	 */
	public function get_content() {
		$template_path = $this->get_template_path();

		/**
		 * Render the template.
		 */
		return $this->get_rendered_html(
			array_merge(
				$this->get_attributes(),
				$this->get_template_args()
			),
			$template_path
		);
	}

	/**
	 * Get template path to render.
	 *
	 * @since  1.2.0
	 *
	 * @return string
	 */
	protected function get_template_path() {
		// Render signup page if registration is enable.
		$is_registration_enable = masteriyo_get_setting( 'general.registration.enable_instructor_registration' );

		if ( is_user_logged_in() ) {
			$this->template_args = $this->get_logged_in_template_args();

			return masteriyo( 'template' )->locate( 'account/instructor-registration-status.php' );
		}

		if ( ! $is_registration_enable ) {
			return masteriyo( 'template' )->locate( 'account/form-login.php' );
		}

		return masteriyo( 'template' )->locate( 'account/instructor-registration.php' );
	}

	/**
	 * Get the state and message for the logged-in visitor (issue #570).
	 *
	 * @return array
	 */
	protected function get_logged_in_template_args() {
		$user   = masteriyo_get_current_user();
		$user   = ( $user && ! is_wp_error( $user ) ) ? $user : null;
		$status = $user ? $user->get_instructor_apply_status() : '';

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() ) {
			// Mirror the same truthiness get_template_path() uses to pick the logged-out template.
			$registration_enabled = (bool) masteriyo_get_setting( 'general.registration.enable_instructor_registration' );

			$role_notice = masteriyo_is_current_user_admin()
				? __( 'You are previewing this page as a site administrator.', 'learning-management-system' )
				: __( 'You are previewing this page as a site manager.', 'learning-management-system' );

			$visitor_notice = $registration_enabled
				? __( 'Logged-out visitors see the instructor registration form here.', 'learning-management-system' )
				: __( 'Instructor registration is turned off, so logged-out visitors see the login form here.', 'learning-management-system' );

			return array(
				'state'   => 'admin',
				'message' => $role_notice . ' ' . $visitor_notice,
			);
		}

		if ( masteriyo_is_current_user_instructor() || InstructorApplyStatus::APPROVED === $status ) {
			return array(
				'state'   => 'instructor',
				'message' => __( 'You are already registered as an instructor.', 'learning-management-system' ),
			);
		}

		if ( InstructorApplyStatus::APPLIED === $status ) {
			return array(
				'state'   => 'applied',
				'message' => __( 'Your instructor application is pending review.', 'learning-management-system' ),
			);
		}

		$apply_enabled = masteriyo_string_to_bool( masteriyo_get_setting( 'accounts_page.display.enable_instructor_apply' ) );

		// Same gate as the account profile screen: only students may apply.
		if ( $user && $apply_enabled && masteriyo_is_current_user_student() && masteriyo_can_user_apply_for_instructor( $user ) ) {
			return array(
				'state'   => 'can_apply',
				'message' => InstructorApplyStatus::REJECTED === $status
					? __( 'Your previous application was rejected. You can apply again to become an instructor.', 'learning-management-system' )
					: __( 'Apply to become an instructor on this site.', 'learning-management-system' ),
			);
		}

		return array(
			'state'   => 'closed',
			'message' => __( 'Instructor applications are currently closed for your account.', 'learning-management-system' ),
		);
	}
}
