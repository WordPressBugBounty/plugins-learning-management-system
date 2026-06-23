<?php
/**
 * WooCommerce enrollment email to student.
 *
 * Sent when a WC order that contains a Masteriyo course is completed.
 * Cannot reuse CompletedOrderEmailToStudent because that class calls
 * masteriyo_get_order(), which returns null for WC order IDs.
 *
 * @package Masteriyo\Addons\WcIntegration\Emails
 *
 * @since x.x.x
 */

namespace Masteriyo\Addons\WcIntegration\Emails;

use Masteriyo\Abstracts\Email;

defined( 'ABSPATH' ) || exit;

/**
 * WC enrollment email to student.
 *
 * @since x.x.x
 *
 * @package Masteriyo\Addons\WcIntegration\Emails
 */
class WcEnrollmentEmailToStudent extends Email {

	/**
	 * Email method ID.
	 *
	 * @since x.x.x
	 *
	 * @var string
	 */
	protected $id = 'wc-enrollment/to/student';

	/**
	 * HTML template path (reuses the free plugin's completed-order template).
	 *
	 * @since x.x.x
	 *
	 * @var string
	 */
	protected $html_template = 'emails/student/completed-order.php';

	/**
	 * Send the enrollment email.
	 *
	 * @since x.x.x
	 *
	 * @param \Masteriyo\Models\UserCourse $user_course Enrolled user-course record.
	 * @param object                       $wc_order    WooCommerce order object (WC_Order).
	 */
	public function trigger( $user_course, $wc_order ) {
		if ( ! $user_course || ! $wc_order ) {
			return;
		}

		$course_id = $user_course->get_course_id();
		$meta_key  = 'masteriyo_wc_enrollment_email_sent_' . $course_id;

		// Guard: send only once per course enrollment.
		if ( get_user_meta( $user_course->get_user_id(), $meta_key, true ) ) {
			return;
		}

		$student = masteriyo_get_user( $user_course->get_user_id() );

		if ( is_wp_error( $student ) || is_null( $student ) ) {
			return;
		}

		if ( empty( $student->get_email() ) ) {
			return;
		}

		$course = masteriyo_get_course( $course_id );

		$order_item_course = null;
		foreach ( $wc_order->get_items() as $item ) {
			if ( $item->get_meta( '_masteriyo_course_id' ) ) {
				$order_item_course = $item;
				break;
			}
		}

		$this->set_recipients( $student->get_email() );
		$this->set( 'user_course', $user_course );
		$this->set( 'customer', $student );
		$this->set( 'course', $course );
		$this->set( 'wc_order', $wc_order );
		$this->set( 'order', $wc_order );
		$this->set( 'order_item_course', $order_item_course ?? $course );

		$this->send(
			$this->get_recipients(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);

		update_user_meta( $user_course->get_user_id(), $meta_key, true );
	}

	/**
	 * Return true if this email type is enabled.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.student.completed_order.enable' ) );
	}

	/**
	 * Return the email subject.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_subject() {
		$subject = apply_filters(
			$this->get_full_id() . '_subject',
			masteriyo_get_default_email_contents()['student']['completed_order']['subject']
		);

		return $this->format_string( $subject );
	}

	/**
	 * Get email content.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_content() {
		$content = masteriyo_string_translation(
			'emails.student.completed_order.content',
			'masteriyo-email-message',
			masteriyo_get_default_email_contents()['student']['completed_order']['content']
		);
		$content = $this->format_string( $content );

		$this->set( 'content', $content );

		return parent::get_content();
	}

	/**
	 * Get placeholders.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public function get_placeholders() {
		$placeholders = parent::get_placeholders();

		$customer = $this->get( 'customer' );
		$course   = $this->get( 'course' );
		$wc_order = $this->get( 'wc_order' );

		if ( $customer ) {
			$first   = $customer->get_first_name();
			$last    = $customer->get_last_name();
			$trimmed = trim( "$first $last" );
			$name    = $trimmed ? $trimmed : $customer->get_display_name();

			$placeholders['{billing_first_name}'] = $first;
			$placeholders['{billing_last_name}']  = $last;
			$placeholders['{billing_name}']       = $name;
			$placeholders['{billing_email}']      = $customer->get_email();
			$placeholders['{account_login_link}'] = wp_kses_post(
				'<a href="' . $this->get_account_url() . '" style="text-decoration: none;">' . __( 'Login to Your Account', 'learning-management-system' ) . '</a>'
			);
		}

		if ( $course ) {
			$placeholders['{course_name}'] = $course->get_name();
		}

		if ( $wc_order ) {
			$placeholders['{total_price}'] = masteriyo_price( $wc_order->get_total() );
			$placeholders['{order_id}']    = $wc_order->get_order_number();
			$placeholders['{order_date}']  = $wc_order->get_date_created()
				? gmdate( 'd M Y', $wc_order->get_date_created()->getTimestamp() )
				: '';
			$placeholders['{order_table}'] = '';
		}

		return $placeholders;
	}

	/**
	 * Return additional content.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function get_additional_content() {
		$additional_content = apply_filters(
			$this->get_full_id() . '_additional_content',
			masteriyo_get_setting( 'emails.student.completed_order.additional_content' )
		);
		$additional_content = masteriyo_string_translation(
			'emails.student.completed_order.additional_content',
			'masteriyo-email-message',
			$additional_content
		);

		return $this->format_string( $additional_content );
	}
}
