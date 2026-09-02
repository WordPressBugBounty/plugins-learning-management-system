<?php
/**
 * Instructor registration to admin email class.
 *
 * @package Masteriyo\Emails
 *
 * @since 2.6.10
 */

namespace Masteriyo\Emails\Admin;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\Email;

class InstructorRegistrationEmailToAdmin extends Email {

	/**
	 * Email method ID.
	 *
	 * @since 2.6.10
	 *
	 * @var string
	 */
	protected $id = 'instructor-registration/to/admin';

	/**
	 * HTML template path.
	 *
	 * @since 2.6.10
	 *
	 * @var string
	 */
	protected $html_template = 'emails/admin/instructor-registration.php';

	/**
	 * Send this email.
	 *
	 * @since 2.6.10
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

		$to_address = explode( ',', $this->format_string( masteriyo_get_setting( 'emails.admin.instructor_registration.to_address' ) ) ?? $admin_email );
		$this->set_recipients( $to_address );
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
	 * @since 2.6.10
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.admin.instructor_registration.enable' ) );
	}

	/**
	 * Return subject.
	 *
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter instructor registration email subject to admin.
		 *
		 * @since 2.6.10
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_setting( 'emails.admin.instructor_registration.subject' ) );
		$subject = is_string( $subject ) ? trim( $subject ) : '';
		$subject = empty( $subject ) ? masteriyo_get_default_email_contents()['admin']['instructor_registration']['subject']
		: $subject;

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter instructor registration email heading to admin.
		 *
		 * @since 2.6.10
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.admin.instructor_registration.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Return additional content.
	 *
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_additional_content() {

		/**
		 * Filter instructor registration email additional content to admin.
		 *
		 * @since 2.6.10
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
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation( 'emails.admin.instructor_registration.content', 'masteriyo-email-message', masteriyo_get_setting( 'emails.admin.instructor_registration.content' ) );
		$content = is_string( $content ) ? trim( $content ) : '';

		if ( empty( $content ) ) {
			$content = masteriyo_get_default_email_contents()['admin']['instructor_registration']['content'];
		}

		$content = $this->format_string( $content );

		$this->set( 'content', $content );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since 2.6.10
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
					'<a href="' . admin_url( 'admin.php?page=masteriyo#/users/instructors/' ) . $instructor->get_id() . '" class="email-template--button">Review Application</a>'
				),
			);
		}

		return $placeholders;
	}

	/**
	 * Get the reply_to_name.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		/**
		 * Filter student registration email reply_to_name to admin.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_name.
		 */
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.admin.instructor_registration.reply_to_name' ) );
		$reply_to_name = is_string( $reply_to_name ) ? trim( $reply_to_name ) : '';

		return ! empty( $reply_to_name ) ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_reply_to_address( $reply_to_address = '' ) {
		/**
		 * Filter student registration email reply_to_address to admin.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_address.
		 */
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.admin.instructor_registration.reply_to_address' ) );

		return ! empty( $reply_to_address ) ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_from_name() {
		/**
		 * Filter student registration email from_name to admin.
		 *
		 * @since 2.8.0
		 *
		 * @param string $from_name.
		 */
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.admin.instructor_registration.from_name' ) );
		$from_name = is_string( $from_name ) ? trim( $from_name ) : '';

		return ! empty( $from_name ) ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		/**
		 * Filter student registration email from_address to admin.
		 *
		 * @since 2.8.0
		 *
		 * @param string $from_address.
		 */
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.admin.instructor_registration.from_address' ) );
		$from_address = is_string( $from_address ) ? trim( $from_address ) : '';

		return ! empty( $from_address ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
