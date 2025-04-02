<?php
/**
 * Instructor registration to admin email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.15.0
 */

namespace Masteriyo\Emails\Admin;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class InstructorRegistrationEmailToAdmin extends Email {

	/**
	 * Email method ID.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $id = 'instructor-registration/to/admin';

	/**
	 * HTML template path.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $html_template = 'emails/admin/instructor-registration.php';

	/**
	 * Send this email.
	 *
	 * @since 1.15.0
	 *
	 * @param int $id Instructor ID.
	 */
	public function trigger( $id ) {
		$admin_email = get_bloginfo( 'admin_email' );

		// Bail early if order doesn't exist.
		if ( empty( $admin_email ) ) {
			return;
		}

		$instructor = masteriyo_get_user( $id );

		// Bail early if instructor doesn't exist.
		if ( is_wp_error( $instructor ) ) {
			return;
		}

		$this->set_recipients( $admin_email );
		$this->set( 'instructor', $instructor );

		$this->send(
			$this->get_recipients(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);
	}

	/**
	 * Return true if it is enabled.
	 *
	 * @since 1.15.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.admin.instructor_registration.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter instructor registration email subject to admin.
		 *
		 * @since 1.15.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_default_email_contents()['admin']['instructor_registration']['subject'] );

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter instructor registration email heading to admin.
		 *
		 * @since 1.15.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.admin.instructor_registration.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter instructor registration email additional content to admin.
		 *
		 * @since 1.15.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.admin.instructor_registration.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.admin.instructor_registration.additional_content', 'masteriyo-email-message', $additional_content );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get email content.
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.admin.instructor_registration.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['admin']['instructor_registration']['content'] );
		$content = $this->format_string( $content );

		$this->set( 'content', $content );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since 1.15.0
	 *
	 * @return array
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		/** @var \Masteriyo\Models\User $instructor */
		$instructor = $this->get( 'instructor' );

		if ( $instructor ) {
			$name         = trim( sprintf( '%s %s', $instructor->get_first_name(), $instructor->get_last_name() ) );
			$placeholders = $placeholders + array(
				'{instructor_display_name}'    => $instructor->get_display_name(),
				'{instructor_first_name}'      => $instructor->get_first_name(),
				'{instructor_last_name}'       => $instructor->get_last_name(),
				'{instructor_username}'        => $instructor->get_username(),
				'{instructor_nicename}'        => $instructor->get_nicename(),
				'{instructor_nickname}'        => $instructor->get_nickname(),
				'{instructor_email}'           => $instructor->get_email(),
				'{instructor_name}'            => ! empty( $name ) ? $name : $instructor->get_display_name(),
				'{instructor_registered_date}' => gmdate( 'd M Y', $instructor->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'{review_application_link}'    => wp_kses_post(
					'<a href="' . admin_url( 'admin.php?page=masteriyo#/users/instructors/' ) . $instructor->get_id() . '" style="text-decoration: none;">Review Application</a>'
				),
			);
		}

		return $placeholders;
	}
}
