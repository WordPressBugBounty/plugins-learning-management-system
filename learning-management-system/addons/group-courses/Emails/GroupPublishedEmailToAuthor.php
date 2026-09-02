<?php
/**
 * Group published email to author class.
 *
 * @package Masteriyo\Emails
 *
 * @since 2.30.0
 */

namespace Masteriyo\Addons\GroupCourses\Emails;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Group published email to author class. Used for sending group activation notification to group creators.
 *
 * @since 2.30.0
 *
 * @package Masteriyo\Emails
 */
class GroupPublishedEmailToAuthor extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 2.30.0
	 *
	 * @var String
	 */
	protected $id = 'group-published-email';

	/**
	 * HTML template path.
	 *
	 * @since 2.30.0
	 *
	 * @var string
	 */
	protected $html_template = 'group-courses/emails/group-published.php';

	/**
	 * Send this email.
	 *
	 * @since 2.30.0
	 *
	 * @param int $author_id Group author ID.
	 * @param int $group_id Group ID.
	 */
	public function trigger( $author_id, $group_id ) {
		$author = masteriyo_get_user( $author_id );
		$group  = masteriyo_get_group( $group_id );

		// Bail early if author or group doesn't exist.
		if ( is_wp_error( $author ) || is_null( $author ) || is_wp_error( $group ) || is_null( $group ) ) {
			return;
		}

		if ( empty( $author->get_email() ) ) {
			return;
		}

		$this->set_recipients( $author->get_email() );

		$this->set( 'email_heading', $this->get_heading() );
		$this->set( 'author', $author );
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
	 * @since 2.30.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$enabled = masteriyo_string_to_bool( masteriyo_get_setting( 'emails.student.group_published.enable' ) );

		/**
		 * Filters boolean-like value: 'yes' if group published email should be disabled, otherwise 'no'.
		 *
		 * @since 2.30.0
		 *
		 * @param string $is_disabled 'yes' if group published email should be disabled, otherwise 'no'.
		 */
		$is_disabled = masteriyo_string_to_bool( apply_filters( 'masteriyo_disable_group_published_email_to_author', $enabled ? 'no' : 'yes' ) );

		return ! $is_disabled;
	}

	/**
	 * Get placeholders.
	 *
	 * @since 2.30.0
	 *
	 * @return array
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		/** @var \Masteriyo\Models\User $author */
		$author = $this->get( 'author' );

		if ( $author ) {
			$placeholders['{author_display_name}']    = $author->get_display_name();
			$placeholders['{author_first_name}']      = empty( $author->get_first_name() ) ? $author->get_display_name() : $author->get_first_name();
			$placeholders['{author_last_name}']       = empty( $author->get_last_name() ) ? $author->get_display_name() : $author->get_last_name();
			$placeholders['{author_name}']            = sprintf( '%s %s', $author->get_first_name(), $author->get_last_name() ) ?? $author->get_display_name();
			$placeholders['{author_username}']        = $author->get_username();
			$placeholders['{author_nicename}']        = $author->get_nicename();
			$placeholders['{author_nickname}']        = $author->get_nickname();
			$placeholders['{author_email}']           = $author->get_email();
			$placeholders['{account_login_link}']     = wp_kses_post(
				'<a href="' . $this->get_account_url() . '" class="email-template--button">Manage Your Groups</a>'
			);
			$placeholders['{groups_management_link}'] = wp_kses_post(
				'<a href="' . masteriyo_get_page_permalink( 'account' ) . '#/groups" class="email-template--button">Go to Groups</a>'
			);
		}

		/** @var \Masteriyo\Addons\GroupCourses\Models\Group $group */
		$group = $this->get( 'group' );

		if ( $group ) {
			$placeholders['{group_name}']         = $group->get_title();
			$placeholders['{group_member_count}'] = count( $group->get_emails() );
		}

		return $placeholders;
	}

	/**
	 * Return subject.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_subject() {
		/**
		 * Filter group published email subject to the author.
		 *
		 * @since 2.30.0
		 *
		 * @param string $subject.
		 */
		$subject = apply_filters( $this->get_full_id() . '_subject', masteriyo_get_setting( 'emails.student.group_published.subject' ) );
		$subject = empty( trim( $subject ) ) ? masteriyo_get_default_email_contents()['student']['group_published']['subject'] : $subject;

		return $this->format_string( $subject );
	}

	/**
	 * Return heading.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_heading() {
		/**
		 * Filter group published email heading to the author.
		 *
		 * @since 2.30.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.student.group_published.heading' ) );

		return $this->format_string( $heading );
	}

	/**
	 * Get email content.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_get_setting( 'emails.student.group_published.content' );

		if ( empty( trim( $content ) ) ) {
			$content = masteriyo_get_default_email_contents()['student']['group_published']['content'];
		}

		$content = $this->format_string( $content );

		$this->set( 'content', $content );

		return parent::get_content();
	}

	/**
	 * Return additional content.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_additional_content() {
		/**
		 * Filter group published email additional content to the author.
		 *
		 * @since 2.30.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.student.group_published.additional_content' ) );

		return $this->format_string( $additional_content );
	}

	/**
	 * Get the reply_to_name.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		/**
		 * Filter group published email reply_to_name.
		 *
		 * @since 2.30.0
		 *
		 * @param string $reply_to_name.
		 */
		$reply_to_name = apply_filters( $this->get_full_id() . 'reply_to_name', masteriyo_get_setting( 'emails.student.group_published.reply_to_name' ) );

		return ! empty( trim( $reply_to_name ) ) ? wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES ) : parent::get_reply_to_name();
	}

	/**
	 * Get the reply_to_address.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_reply_to_address( $reply_to_address = '' ) {
		/**
		 * Filter group published email reply_to_address.
		 *
		 * @since 2.30.0
		 *
		 * @param string $reply_to_address.
		 */
		$reply_to_address = apply_filters( $this->get_full_id() . 'reply_to_address', masteriyo_get_setting( 'emails.student.group_published.reply_to_address' ) );

		return ! empty( $reply_to_address ) ? sanitize_email( $reply_to_address ) : parent::get_reply_to_address();
	}

	/**
	 * Get the from_name.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_from_name() {
		/**
		 * Filter group published email from_name.
		 *
		 * @since 2.30.0
		 *
		 * @param string $from_name.
		 */
		$from_name = apply_filters( $this->get_full_id() . '_from_name', masteriyo_get_setting( 'emails.student.group_published.from_name' ) );

		return ! empty( trim( $from_name ) ) ? wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES ) : parent::get_from_name();
	}

	/**
	 * Get the from_address.
	 *
	 * @since 2.30.0
	 *
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		/**
		 * Filter group published email from_address.
		 *
		 * @since 2.30.0
		 *
		 * @param string $from_address.
		 */
		$from_address = apply_filters( $this->get_full_id() . '_from_address', masteriyo_get_setting( 'emails.student.group_published.from_address' ) );

		return ! empty( trim( $from_address ) ) ? sanitize_email( $from_address ) : parent::get_from_address();
	}
}
