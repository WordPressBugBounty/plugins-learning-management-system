<?php
/**
 * ResetPasswordEmail class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.15.0
 */

namespace Masteriyo\Emails\Student;

use Masteriyo\Abstracts\Email;
use Masteriyo\Addons\Certificate\PDF\CertificatePDF;
use Mpdf\Output\Destination;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * ResetPasswordEmail Class. Used for sending password reset email.
 *
 * @since 1.15.0
 *
 * @package Masteriyo\Emails
 */
class CourseCompletionEmailToStudent extends Email {
	/**
	 * Email method ID.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	protected $id = 'course-completion/to/student';

	/**
	 * HTML template path.
	 *
	 * @since 1.15.0
	 *
	 * @var string
	 */
	public $html_template = 'emails/student/course-completion.php';

	/**
	 * Send this email.
	 *
	 * @since 1.15.0
	 *
	 * @param int $user_id User id.
	 * @param int $course_id Course Id.
	 */
	public function trigger( $course_progress ) {
		$student = masteriyo_get_user( $course_progress->get_user_id() );
		$course  = masteriyo_get_course( $course_progress->get_course_id() );

		// Bail early if student doesn't exist.
		if ( is_wp_error( $student ) || is_null( $student ) ) {
			return;
		}

		if ( empty( $student->get_email() ) ) {
			return;
		}

		$student_email = $student->get_email();

		$this->set_recipients( $student_email );

		$this->set( 'email_heading', $this->get_heading() );
		$this->set( 'student', $student );
		$this->set( 'course', $course );

		$certificate_email_enabled = get_post_meta( $course->get_id(), '_certificate_email_enabled', true );

		if ( $certificate_email_enabled ) {
			add_filter(
				'masteriyo_email_attachments',
				function( $id ) use ( $course_progress ) {
					$course_id  = $course_progress->get_course_id();
					$student_id = $course_progress->get_user_id();

					$certificate_id = masteriyo_get_course_certificate_id( $course_id );

					if ( ! $certificate_id ) {
						return;
					}

					$certificate = masteriyo_get_certificate( $certificate_id );

					$certificate_html_content = $certificate->get_html_content();

					$certificate_pdf = new CertificatePDF( $course_id, $student_id, $certificate_html_content );

					$temp = tempnam( sys_get_temp_dir(), 'certificate' );
					$temp = "$temp.pdf";

					$certificate_pdf->prepare_pdf( false );

					$certificate_pdf->mpdf->Output( $temp, Destination::FILE );

					return $temp;
				}
			);
		}

		$this->send(
			$this->get_recipients(),
			$this->get_subject(),
			$this->get_content(),
			$this->get_headers(),
			$this->get_attachments()
		);

	}

	/**
	 * Generates a PDF certificate for the given course progress.
	 *
	 * This method creates a PDF certificate for the student who has completed the given course.
	 * It retrieves the course ID, student ID, and the HTML content of the certificate,
	 * then generates the PDF file and returns the temporary file path.
	 *
	 * @param \Masteriyo\Models\CourseProgress $course_progress The course progress object.
	 * @return string The temporary file path of the generated PDF certificate.
	 */
	public function get_certificate_pdf( $course_progress ) {

		$course_id  = $course_progress->get_course_id();
		$student_id = $course_progress->get_user_id();

		$certificate_id = masteriyo_get_course_certificate_id( $course_id );

			$certificate = masteriyo_get_certificate( $certificate_id );

			$certificate_html_content = $certificate->get_html_content();

			$certificate_pdf = new CertificatePDF( $course_id, $student_id, $certificate_html_content );

			$temp = tempnam( sys_get_temp_dir(), 'certificate' );
			$temp = "$temp.pdf";

			$certificate_pdf->prepare_pdf( false );

			$certificate_pdf->mpdf->Output( $temp, Destination::FILE );
			/**
		 * Filters email attachments.
		 *
		 * @since 1.15.0
		 *
		 * @param array $attachments Absolute paths of attachments.
		 * @param string $headers Email object id.
		 * @param \Masteriyo\Emails\Email $email Email class object.
		 */
		return $temp;
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
		$subject = apply_filters( $this->get_full_id(), masteriyo_get_default_email_contents()['student']['course_completion']['subject'] );

		return $this->format_string( $subject );
	}

	/**
	 * Return true if it is enabled.
	 *
	 * @since 1.15.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'emails.student.course_completion.enable' ) );
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
		 * Filter instructor registration email heading to instructor.
		 *
		 * @since 1.15.0
		 *
		 * @param string $heading.
		 */
		$heading = apply_filters( $this->get_full_id() . '_heading', masteriyo_get_setting( 'emails.student.course_completion.heading' ) );

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
		 * Filter instructor registration email additional content to instructor.
		 *
		 * @since 1.15.0
		 *
		 * @param string $additional_content.
		 */
		$additional_content = apply_filters( $this->get_full_id() . '_additional_content', masteriyo_get_setting( 'emails.student.course_completion.additional_content' ) );
		$additional_content = masteriyo_string_translation( 'emails.student.course_completion.additional_content', 'masteriyo-email-message', $additional_content );

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
		$content = masteriyo_string_translation( 'emails.student.course_completion.content', 'masteriyo-email-message', masteriyo_get_default_email_contents()['student']['course_completion']['content'] );
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

		/** @var \Masteriyo\Models\User|null $student */
		$student = $this->get( 'student' );

		/** @var \Masteriyo\Models\Course|null $course */
		$course = $this->get( 'course' );

		$certificate = $this->get( 'certificate_download_url' );

		if ( $student ) {
			$placeholders = $placeholders + array(
				'{student_display_name}'                => $student->get_display_name(),
				'{student_first_name}'                  => empty( $student->get_first_name() ) ? $student->get_display_name() : $student->get_first_name(),
				'{student_last_name}'                   => empty( $student->get_last_name() ) ? $student->get_display_name() : $student->get_last_name(),
				'{student_name}'                        => sprintf( '%s %s', $student->get_first_name(), $student->get_last_name() ) ?? $student->get_display_name(),
				'{student_username}'                    => $student->get_username(),
				'{student_nicename}'                    => $student->get_nicename(),
				'{student_nickname}'                    => $student->get_nickname(),
				'{student_email}'                       => $student->get_email(),
				'{account_login_link}'                  => wp_kses_post(
					'<a href="' . $this->get_account_url() . '" style="text-decoration: none;">Login to Your Account</a>'
				),
				'{course_completion_celebration_image}' => $this->get_celebration_image(),
			);
		}

		if ( $course ) {
			$placeholders = $placeholders + array(
				'{course_name}' => $course->get_name(),
				'{course_url}'  => $course->get_permalink(),
			);
		}
		if ( $certificate ) {
			$placeholders = $placeholders + array(
				'{certificate_download_url}' => $certificate,
			);
		}

		return $placeholders;
	}

	/**
	 * Retrieves the HTML or URL for the celebration image for course completion.
	 *
	 * @since 1.15.0
	 *
	 * @return string The celebration image HTML or URL.
	 */
	private function get_celebration_image() {
		/**
		 * Retrieves the HTML for the course completion celebration image.
		 *
		 * @since 1.15.0
		 *
		 * @return string The HTML for the celebration image.
		 */
		return apply_filters(
			'masteriyo_student_course_completion_email_celebration_image',
			sprintf(
				'<img src="%s" alt="celebration image">',
				esc_url( masteriyo_get_plugin_url() . '/assets/img/new-order-celebration.png' )
			)
		);
	}
}
