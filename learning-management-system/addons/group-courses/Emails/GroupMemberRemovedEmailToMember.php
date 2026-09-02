<?php
/**
 * Group member removed email to the removed member class.
 *
 * @package Masteriyo\Emails
 */

namespace Masteriyo\Addons\GroupCourses\Emails;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Group member removed email to the removed member class.
 *
 * @package Masteriyo\Emails
 */
class GroupMemberRemovedEmailToMember extends Email {
	/**
	 * Email method ID.
	 *
	 * @var String
	 */
	protected $id = 'group-member-removed-email';

	/**
	 * HTML template path.
	 *
	 * @var string
	 */
	protected $html_template = 'group-courses/emails/group-member-removed.php';

	/**
	 * Send this email.
	 *
	 * @param int $student_id User ID.
	 * @param int $group_id Group ID.
	 */
	public function trigger( $student_id, $group_id ) {
		$student = masteriyo_get_user( $student_id );
		$group   = masteriyo_get_group( $group_id );

		// Bail early if student or group doesn't exist.
		if ( is_wp_error( $student ) || is_null( $student ) || is_wp_error( $group ) || is_null( $group ) ) {
			return;
		}

		if ( empty( $student->get_email() ) ) {
			return;
		}

		$this->set_recipients( $student->get_email() );

		$this->set( 'email_heading', $this->get_heading() );
		$this->set( 'student', $student );
		$this->set( 'group', $group );

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
	 * @return bool
	 */
	public function is_enabled() {
		$enabled = masteriyo_string_to_bool( masteriyo_get_setting( 'emails.student.group_member_removed.enable' ) );

		/**
		 * Filters boolean-like value: 'yes' if group member removed email should be disabled, otherwise 'no'.
		 *
		 * @param string $is_disabled 'yes' if group member removed email should be disabled, otherwise 'no'.
		 */
		$is_disabled = masteriyo_string_to_bool( apply_filters( 'masteriyo_disable_group_member_removed_email_to_member', $enabled ? 'no' : 'yes' ) );

		return ! $is_disabled;
	}

	/**
	 * Get placeholders.
	 *
	 * @return array
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		/** @var \Masteriyo\Models\User $student */
		$student = $this->get( 'student' );

		if ( $student ) {
			$full_name = trim( sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) );

			$placeholders['{student_display_name}'] = $student->get_display_name();
			$placeholders['{student_first_name}']   = empty( $student->get_first_name() ) ? $student->get_display_name() : $student->get_first_name();
			$placeholders['{student_last_name}']    = empty( $student->get_last_name() ) ? $student->get_display_name() : $student->get_last_name();
			$placeholders['{student_name}']         = empty( $full_name ) ? $student->get_display_name() : $full_name;
			$placeholders['{student_username}']     = $student->get_username();
			$placeholders['{student_nicename}']     = $student->get_nicename();
			$placeholders['{student_nickname}']     = $student->get_nickname();
			$placeholders['{student_email}']        = $student->get_email();
		}

		/** @var \Masteriyo\Addons\GroupCourses\Models\Group $group */
		$group = $this->get( 'group' );

		if ( $group ) {
			$placeholders['{group_name}'] = $group->get_title();
		}

		return $placeholders;
	}

	/**
	 * Return subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter group member removed email subject to the member.
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_setting( 'emails.student.group_member_removed.subject' ) );
		$subject = empty( trim( $subject ) ) ? masteriyo_get_default_email_contents()['student']['group_member_removed']['subject'] : $subject;

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter group member removed email heading to the member.
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.student.group_member_removed.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Get email content.
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_get_setting( 'emails.student.group_member_removed.content' );

		if ( empty( trim( $content ) ) ) {
			$content = masteriyo_get_default_email_contents()['student']['group_member_removed']['content'];
		}

		$content = $this->format_string( $content );

		$this->set( 'content', $content );

		return parent::get_content();
	}

	/**
	 * Return additional content.
	 *
	 * @return string
	 */
	public function get_additional_content() {
		/**
		 * Filter group member removed email additional content to the member.
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.student.group_member_removed.additional_content' ) );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get the reply_to_name.
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		/**
		 * Filter group member removed email reply_to_name.
		 *
		 * @param string $reply_to_name.
		 */
		$reply_to_name = apply_filters( $this->get_full_id() . '_reply_to_name', masteriyo_get_setting( 'emails.student.group_member_removed.reply_to_name' ) );

		return ! empty( trim( $reply_to_name ) ) ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @return string
	 */
	public function get_reply_to_address( $reply_to_address = '' ) {
		/**
		 * Filter group member removed email reply_to_address.
		 *
		 * @param string $reply_to_address.
		 */
		$reply_to_address = apply_filters( $this->get_full_id() . '_reply_to_address', masteriyo_get_setting( 'emails.student.group_member_removed.reply_to_address' ) );

		return ! empty( $reply_to_address ) ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @return string
	 */
	public function get_from_name() {
		/**
		 * Filter group member removed email from_name.
		 *
		 * @param string $from_name.
		 */
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.student.group_member_removed.from_name' ) );

		return ! empty( trim( $from_name ) ) ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		/**
		 * Filter group member removed email from_address.
		 *
		 * @param string $from_address.
		 */
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.student.group_member_removed.from_address' ) );

		return ! empty( trim( $from_address ) ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
