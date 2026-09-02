<?php
/**
 * EmailHooks class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.0.0
 */

namespace Masteriyo\Emails;

use Masteriyo\Enums\UserStatus;
use Masteriyo\Emails\ResetPasswordEmail;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Emails\Admin\NewOrderEmailToAdmin;
use Masteriyo\Emails\Admin\CourseStartEmailToAdmin;
use Masteriyo\Emails\Admin\InstructorApplyEmailToAdmin;
use Masteriyo\Emails\Student\OnHoldOrderEmailToStudent;
use Masteriyo\Emails\Admin\CourseCompletionEmailToAdmin;
use Masteriyo\Emails\Instructor\InstructorApplyApprovedEmailToInstructor;
use Masteriyo\Emails\Admin\NewWithdrawRequestEmailToAdmin;
use Masteriyo\Emails\Student\CancelledOrderEmailToStudent;
use Masteriyo\Emails\Student\CompletedOrderEmailToStudent;
use Masteriyo\Emails\Admin\StudentRegistrationEmailToAdmin;
use Masteriyo\Emails\Instructor\CourseStartEmailToInstructor;
use Masteriyo\Emails\Admin\InstructorRegistrationEmailToAdmin;
use Masteriyo\Emails\Admin\NewQuizAttemptEmailToAdmin;
use Masteriyo\Emails\Student\StudentRegistrationEmailToStudent;
use Masteriyo\Emails\Instructor\CourseCompletionEmailToInstructor;
use Masteriyo\Emails\Student\CourseCompletionReminderEmailToStudent;
use Masteriyo\Emails\Instructor\InstructorRegistrationEmailToInstructor;
use Masteriyo\Emails\Instructor\WithdrawRequestPendingEmailToInstructor;
use Masteriyo\Emails\Instructor\WithdrawRequestApprovedEmailToInstructor;
use Masteriyo\Emails\Instructor\WithdrawRequestRejectedEmailToInstructor;
use Masteriyo\Emails\Instructor\NewQuizAttemptEmailToInstructor;
use Masteriyo\Emails\Student\AutomaticRegistrationEmailToStudent;
use Masteriyo\Emails\Student\CourseCompletionEmailToStudent;
use Masteriyo\Emails\Student\InstructorApplyRejectedEmailToStudent;
use Masteriyo\Emails\Student\GoogleMeetSessionReminderEmailToStudent;
use Masteriyo\Emails\Instructor\NewQuestionEmailToInstructor;
use Masteriyo\Emails\Student\NewQuestionReplyEmailToStudent;
use Masteriyo\Emails\Admin\NewLessonCommentEmailToAdmin;
use Masteriyo\Emails\Admin\NewLessonCommentReplyEmailToAdmin;
use Masteriyo\Emails\Instructor\NewLessonCommentEmailToInstructor;
use Masteriyo\Emails\Instructor\NewLessonCommentReplyEmailToInstructor;
use Masteriyo\Emails\Student\NewLessonCommentReplyEmailToStudent;
use Masteriyo\Enums\InstructorApplyStatus;
use Masteriyo\AddonsFramework\Addons;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Roles;

defined( 'ABSPATH' ) || exit;

/**
 * EmailHooks Class.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\Emails
 */
class EmailHooks {
	/**
	 * Register email hooks.
	 *
	 * @since 2.30.0
	 */
	public static function init() {
		add_action( 'masteriyo_after_password_reset_email', array( __CLASS__, 'schedule_password_reset_request_email' ), 10, 3 );
		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_automatic_registration_email_to_student' ), 10, 3 );

		add_action( 'masteriyo_after_order_object_save', array( __CLASS__, 'schedule_new_order_email_to_admin' ), 10, 3 ); // For the offline payment method.
		add_action( 'masteriyo_order_status_completed', array( __CLASS__, 'schedule_new_order_email_to_admin_on_completion' ), 10, 2 ); // For the other than offline payment method.

		// Apply for instructor from student profile.
		add_action( 'masteriyo_apply_for_instructor', array( __CLASS__, 'schedule_instructor_apply_email_to_admin' ), 10, 1 );
		add_action( 'masteriyo_update_user', array( __CLASS__, 'schedule_instructor_apply_approved_email_to_instructor' ), 10, 2 );
		add_action( 'masteriyo_update_user', array( __CLASS__, 'schedule_instructor_apply_rejected_email_to_student' ), 10, 2 );

		add_action( 'masteriyo_order_status_completed', array( __CLASS__, 'schedule_completed_order_email_to_student' ), 10, 2 );
		add_action( 'masteriyo_order_status_on-hold', array( __CLASS__, 'schedule_onhold_order_email_to_student' ), 10, 2 );
		add_action( 'masteriyo_order_status_cancelled', array( __CLASS__, 'schedule_cancelled_order_email_to_student' ), 10, 2 );

		add_action( 'masteriyo_update_quiz_attempt', array( __CLASS__, 'schedule_new_quiz_attempt_email_to_admin' ), 10, 2 );

		add_action( 'masteriyo_update_quiz_attempt', array( __CLASS__, 'schedule_new_quiz_attempt_email_to_instructor' ), 10, 2 );

		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_student_registration_email_to_student' ), 10, 2 );
		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_instructor_registration_email_to_instructor' ), 10, 2 );
		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_student_registration_email_to_admin' ), 10, 2 );
		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_instructor_registration_email_to_admin' ), 10, 2 );

		// for User Registration Integration.
		add_action( 'masteriyo_after_user_registration_complete', array( __CLASS__, 'schedule_instructor_registration_email_to_instructor' ), 10, 2 );
		add_action( 'masteriyo_after_user_registration_complete', array( __CLASS__, 'schedule_student_registration_email_to_student' ), 10, 2 );

		// Email Verification Email.
		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_email_verification_email' ), 11, 2 );

		add_action( 'masteriyo_after_learn_page_process', array( __CLASS__, 'schedule_course_start_email_to_admin' ), 10, 1 );
		add_action( 'masteriyo_after_learn_page_process', array( __CLASS__, 'schedule_course_start_email_to_instructor' ), 10, 1 );

		add_action( 'masteriyo_course_progress_status_changed', array( __CLASS__, 'schedule_course_completion_email_to_admin' ), 10, 4 );
		add_action( 'masteriyo_course_progress_status_changed', array( __CLASS__, 'schedule_course_completion_email_to_instructor' ), 10, 4 );
		add_action( 'masteriyo_course_progress_status_changed', array( __CLASS__, 'schedule_course_completion_email_to_student' ), 10, 4 );
		add_action( 'masteriyo/job/send_course_completion_reminder_email', array( __CLASS__, 'schedule_course_completion_remainder_email_to_student' ), 10, 2 );

		add_action( 'masteriyo_new_withdraw', array( __CLASS__, 'schedule_new_withdraw_request_email_to_admin' ) );
		add_action( 'masteriyo_new_withdraw', array( __CLASS__, 'schedule_withdraw_request_pending_email_to_instructor' ) );
		add_action( 'masteriyo_withdraw_status_approved', array( __CLASS__, 'schedule_withdraw_request_approved_email_to_instructor' ), 10, 2 );
		add_action( 'masteriyo_withdraw_status_rejected', array( __CLASS__, 'schedule_withdraw_request_rejected_email_to_instructor' ), 10, 2 );

		// Session reminder emails.
		add_action( 'masteriyo_new_google_meet', array( __CLASS__, 'schedule_google_meet_session_reminders' ) );
		add_action( 'masteriyo_update_google_meet', array( __CLASS__, 'reschedule_google_meet_session_reminders' ) );
		add_action( 'masteriyo_delete_google_meet', array( __CLASS__, 'unschedule_google_meet_session_reminders' ) );

		// Q&A notification emails.
		add_action( 'masteriyo_new_course_qa', array( __CLASS__, 'schedule_new_question_email_to_instructor' ), 10, 2 );
		add_action( 'masteriyo_new_course_qa', array( __CLASS__, 'schedule_new_question_reply_email_to_student' ), 10, 2 );

		// Lesson Comment notification emails.
		add_action( 'masteriyo_new_lesson_review', array( __CLASS__, 'schedule_new_lesson_comment_email_to_instructor' ), 10, 2 );
		add_action( 'masteriyo_new_lesson_review', array( __CLASS__, 'schedule_new_lesson_comment_reply_email_to_student' ), 10, 2 );
		add_action( 'masteriyo_new_lesson_review', array( __CLASS__, 'schedule_new_lesson_comment_email_to_admin' ), 10, 2 );
		add_action( 'masteriyo_new_lesson_review', array( __CLASS__, 'schedule_new_lesson_comment_reply_email_to_admin' ), 10, 2 );
		add_action( 'masteriyo_new_lesson_review', array( __CLASS__, 'schedule_new_lesson_comment_reply_email_to_instructor' ), 10, 2 );

		// Disable WordPress core comment notifications for Lesson Comments.
		add_filter( 'comment_notification_recipients', array( __CLASS__, 'disable_core_comment_notifications_for_lesson_comment' ), 10, 2 );
		add_filter( 'comment_moderation_recipients', array( __CLASS__, 'disable_core_comment_notifications_for_lesson_comment' ), 10, 2 );

		// Disable WordPress core comment notifications for Q&A.
		add_filter( 'comment_notification_recipients', array( __CLASS__, 'disable_core_comment_notifications_for_qa' ), 10, 2 );
		add_filter( 'comment_moderation_recipients', array( __CLASS__, 'disable_core_comment_notifications_for_qa' ), 10, 2 );
	}

	/**
	 * Schedule password reset request email.
	 *
	 * @since 1.6.1
	 * @since 2.5.29
	 *
	 * @param \WP_User $user WP User object.
	 * @param array $reset_key Password request key.
	 * @param array $data Form data.
	 */
	public static function schedule_password_reset_request_email( $user, $reset_key, $data ) {
		$email = new ResetPasswordEmail();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action(
				$email->get_schedule_handle(),
				array(
					'id'        => $user->get_id(),
					'reset_key' => $reset_key,
				),
				'masteriyo'
			);
		} else {
			$email->trigger( $user->get_id(), $reset_key );
		}
	}

	/**
	 * Schedule order completed email to student.
	 *
	 * @since 1.5.35
	 *
	 * @param int $order_id
	 * @param \Masteriyo\Models\Order $order
	 */
	public static function schedule_completed_order_email_to_student( $order_id, $order ) {
		$email = new CompletedOrderEmailToStudent();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $order_id ), 'learning-management-system' );
		} else {
			$email->trigger( $order_id );
		}
	}

	/**
	 * Schedule order onhold email to student.
	 *
	 * @since 1.5.35
	 *
	 * @param int $order_id
	 * @param \Masteriyo\Models\Order $order
	 */
	public static function schedule_onhold_order_email_to_student( $order_id, $order ) {
		$email = new OnHoldOrderEmailToStudent();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $order_id ), 'learning-management-system' );
		} else {
			$email->trigger( $order_id );
		}
	}

	/**
	 * Schedule order cancelled email to student.
	 *
	 * @since 1.5.35
	 *
	 * @param int $order_id
	 * @param \Masteriyo\Models\Order $order
	 */
	public static function schedule_cancelled_order_email_to_student( $order_id, $order ) {
		$email = new CancelledOrderEmailToStudent();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $order_id ), 'learning-management-system' );
		} else {
			$email->trigger( $order_id );
		}
	}

	/**
	 * Schedule new student registration email to student.
	 *
	 * @since 1.5.35
	 *
	 *  @param int $user_id User ID.
	 * @param \Masteriyo\Models\User $user User object.
	 */
	public static function schedule_student_registration_email_to_student( $user_id, $user ) {
		if ( ! $user->has_roles( 'masteriyo_student' ) || masteriyo_string_to_bool( $user->get_auto_create_user() ) ) {
			return;
		}

		if ( UserStatus::SPAM === $user->get_status() ) {
			return;
		}

		$email = new StudentRegistrationEmailToStudent();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $user->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $user_id );
		}
	}

	/**
	 * Schedule automatic registration email to student.
	 *
	 * @since 2.16.0
	 *
	 * @param int $user_id User ID.
	 * @param \Masteriyo\Models\User $user User object.
	 * @param array $args The list of additional arguments.
	 */
	public static function schedule_automatic_registration_email_to_student( $user_id, $user, $args ) {
		if ( ! $user->has_roles( 'masteriyo_student' ) || ! masteriyo_string_to_bool( $user->get_auto_create_user() ) ) {
			return;
		}

		if ( UserStatus::SPAM === $user->get_status() ) {
			return;
		}
		if ( masteriyo_is_email_verification_enabled() ) {
			$email_verification = new EmailVerificationEmail();
			if ( $email_verification->is_enabled() ) {
				$email_verification->trigger( $user );
			}
		}

		$email = new AutomaticRegistrationEmailToStudent();

		if ( ! $email->is_enabled() ) {
			return;
		}

		$email->trigger( $user, isset( $args['password'] ) ? $args['password'] : '', isset( $args['reset_key'] ) ? $args['reset_key'] : '' );
	}

	/**
	 * Schedule new instructor registration email to instructor.
	 *
	 * @since 1.5.35
	 *
	 *  @param int $user_id User ID.
	 * @param \Masteriyo\Models\User $user User object.
	 */
	public static function schedule_instructor_registration_email_to_instructor( $user_id, $user ) {
		if ( ! $user->has_roles( 'masteriyo_instructor' ) ) {
			return;
		}

		if ( UserStatus::SPAM === $user->get_status() ) {
			return;
		}

		$email = new InstructorRegistrationEmailToInstructor();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $user_id ), 'learning-management-system' );
		} else {
			$email->trigger( $user_id );
		}
	}

	/**
	 * Schedule new order email.
	 *
	 * @since 2.30.0
	 *
	 * @param \Masteriyo\Models\Order $order Order object.
	 * @param \Masteriyo\Repository\OrderRepository $repository THe data store persisting the data.
	 * @param bool $is_new Whether the order is newly created.
	 */
	public static function schedule_new_order_email_to_admin( $order, $repository, $is_new ) {
		if ( ! $is_new || 'Offline payment' !== $order->get_payment_method_title() ) {
			return;
		}

		$email = new NewOrderEmailToAdmin();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $order->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $order->get_id() );
		}
	}

	/**
	 * Schedule new order email to admin.
	 *
	 * @since 1.17.1 [free]
	 *
	 * @param \Masteriyo\Models\Order $order Order object.
	 * @param \Masteriyo\Repository\OrderRepository $repository THe data store persisting the data.
	 */
	public static function schedule_new_order_email_to_admin_on_completion( $order_id, $order ) {
		// Check if the payment method is offline.
		if ( 'Offline payment' === $order->get_payment_method_title() ) {
			return;
		}

		// Check if the email has already been sent.
		if ( 'yes' === get_post_meta( $order_id, '_completion_email_sent', true ) ) {
			return;
		}

		$email = new NewOrderEmailToAdmin();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $order->get_id() ), 'learning-management-system' );

		} else {
			$email->trigger( $order->get_id() );
		}

		// Mark the email as sent.
		update_post_meta( $order->get_id(), '_completion_email_sent', 'yes' );
	}

	/**
	 * Schedule new quiz attempt email to admin.
	 *
	 * @since 2.12.0
	 *
	 * @param \Masteriyo\Models\AssignmentReply $order Order object.
	 * @param \Masteriyo\Repository\AssignmentReplyRepository $repository THe data store persisting the data.
	 * @param boolean $create True if the order is created.
	 *
	 */
	public static function schedule_new_quiz_attempt_email_to_admin( $id, $quiz_attempt ) {
		$email = new NewQuizAttemptEmailToAdmin();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $quiz_attempt->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $quiz_attempt->get_id() );
		}
	}


	/**
	 * Schedule new quiz attempt email to instructor.
	 *
	 * @since 2.12.0
	 *
	 * @param \Masteriyo\Models\AssignmentReply $order Order object.
	 * @param \Masteriyo\Repository\AssignmentReplyRepository $repository THe data store persisting the data.
	 * @param boolean $create True if the order is created.
	 *
	 */
	public static function schedule_new_quiz_attempt_email_to_instructor( $id, $quiz_attempt ) {
		$email = new NewQuizAttemptEmailToInstructor();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $quiz_attempt->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $quiz_attempt->get_id() );
		}
	}

	/**
	 * Schedule new order email.
	 *
	 * @since 1.6.13
	 *
	 * @param \Masteriyo\Models\User $user User object.
	 *
	 */
	public static function schedule_instructor_apply_email_to_admin( $user ) {
		$email = new InstructorApplyEmailToAdmin();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( $user ) {
			$email->trigger( $user );
		}
	}

	/**
	 * Schedule approved email apply for instructor by student.
	 *
	 * @since 1.6.13
	 *
	 *  @param int $user_id User ID.
	 * @param \Masteriyo\Models\User $user User object.
	 */
	public static function schedule_instructor_apply_approved_email_to_instructor( $user_id, $user ) {
		if ( ! $user->has_roles( Roles::INSTRUCTOR ) ) {
			return;
		}

		$email_sent_meta_key = 'instructor_apply_approved_email_sent';
		$email_already_sent  = get_user_meta( $user_id, $email_sent_meta_key, true );

		// Check if the email has already been sent
		if ( 'yes' === $email_already_sent ) {
			return; // Stop if the email has already been sent
		}

		$email = new InstructorApplyApprovedEmailToInstructor();

		if ( ! $email->is_enabled() || InstructorApplyStatus::APPROVED !== $user->get_instructor_apply_status() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $user->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $user_id );
		}

		// Mark the email as sent by setting the custom user meta
		update_user_meta( $user_id, $email_sent_meta_key, 'yes' );
	}

	/**
	 * Schedule rejected email apply for instructor by student.
	 *
	 * @since 1.6.13
	 *
	 *  @param int $user_id User ID.
	 * @param \Masteriyo\Models\User $user User object.
	 */
	public static function schedule_instructor_apply_rejected_email_to_student( $user_id, $user ) {

		if ( ! $user->has_roles( array( Roles::STUDENT ) ) ) {
			if ( ! $user->has_roles( array( Roles::INSTRUCTOR ) ) ) {
				return;
			}
		}

		$email = new InstructorApplyRejectedEmailToStudent();

		if ( ! $email->is_enabled() || InstructorApplyStatus::REJECTED !== $user->get_instructor_apply_status() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $user->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $user_id );
		}
	}

	/**
	 * Return true if the action schedule is enabled for Email.
	 *
	 * @since 1.5.35
	 *
	 * @return boolean
	 */
	public static function is_email_schedule_enabled() {
		return masteriyo_is_email_schedule_enabled();
	}

	/**
	 * Schedule verification email to the student.
	 *
	 * @since 2.16.0
	 *
	 * @param int $user_id The ID of the user.
	 * @param \Masteriyo\Models\User $user Masteriyo\Database\Model object.
	 */
	public static function schedule_email_verification_email( $user_id, $user ) {
		if ( ! ( $user->has_roles( Roles::STUDENT ) || $user->has_roles( Roles::INSTRUCTOR ) ) ) {
			return;
		}

		if ( UserStatus::SPAM !== $user->get_status() ) {
			return;
		}

		$email = new EmailVerificationEmail();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $user->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $user_id );
		}
	}

	/**
	 * Schedule new student registration email to admin.
	 *
	 * @since 2.6.10
	 *
	 *  @param int $user_id User ID.
	 * @param \Masteriyo\Models\User $user User object.
	 */
	public static function schedule_student_registration_email_to_admin( $user_id, $user ) {
		if ( ! $user->has_roles( 'masteriyo_student' ) ) {
			return;
		}

		$email = new StudentRegistrationEmailToAdmin();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $user_id ), 'learning-management-system' );
		} else {
			$email->trigger( $user_id );
		}
	}

	/**
	 * Schedule new instructor registration email to admin.
	 *
	 * @since 2.6.10
	 *
	 * @param int $user_id User ID.
	 * @param \Masteriyo\Models\User $user User object.
	 */
	public static function schedule_instructor_registration_email_to_admin( $user_id, $user ) {
		if ( ! $user->has_roles( 'masteriyo_instructor' ) ) {
			return;
		}

		$email = new InstructorRegistrationEmailToAdmin();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'id' => $user_id ), 'learning-management-system' );
		} else {
			$email->trigger( $user_id );
		}
	}

	/**
	 * Schedule course start email to admin.
	 *
	 * @since 2.6.10
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	public static function schedule_course_start_email_to_admin( $course ) {
		$email = new CourseStartEmailToAdmin();

		if ( ! is_user_logged_in() || ! $course instanceof \Masteriyo\Models\Course || ! $email->is_enabled() ) {
			return;
		}

		$course_id = $course->get_id();
		$user_id   = get_current_user_id();

		$query = new UserCourseQuery(
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
			)
		);

		$user_course = current( $query->get_user_courses() );

		if ( empty( $user_course ) || ! $user_course instanceof \Masteriyo\Models\UserCourse ) {
			return;
		}

		$is_first_learn_page_visit = get_user_meta( $user_id, "masteriyo_course_{$course_id}_first_learn_page_visit", true );

		if ( 'no' === $is_first_learn_page_visit ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'user_course' => $user_course ), 'learning-management-system' );
		} else {
			$email->trigger( $user_course );
		}
	}

	/**
	 * Schedule course start email to instructor.
	 *
	 * @since 2.6.10
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	public static function schedule_course_start_email_to_instructor( $course ) {
		$email = new CourseStartEmailToInstructor();

		if ( ! is_user_logged_in() || ! $course instanceof \Masteriyo\Models\Course || ! $email->is_enabled() ) {
			return;
		}

		$course_id = $course->get_id();
		$user_id   = get_current_user_id();

		$query = new UserCourseQuery(
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
			)
		);

		$user_course = current( $query->get_user_courses() );

		if ( empty( $user_course ) || ! $user_course instanceof \Masteriyo\Models\UserCourse ) {
			return;
		}

		$is_first_learn_page_visit = get_user_meta( $user_id, "masteriyo_course_{$course_id}_first_learn_page_visit", true );

		if ( 'no' === $is_first_learn_page_visit ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'user_course' => $user_course ), 'learning-management-system' );
		} else {
			$email->trigger( $user_course );
		}
	}

	/**
	 * Schedule course completion email to student.
	 *
	 * @param integer $id Course progress ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @param \Masteriyo\Models\CourseProgress $course_progress The course progress object.
	 */
	public static function schedule_course_completion_email_to_student( $id, $old_status, $new_status, $course_progress ) {
		$email = new CourseCompletionEmailToStudent();

		$is_manual               = masteriyo_is_course_progress_completed_manually( $course_progress );
		$is_manual_email_enabled = masteriyo_is_student_manual_course_completion_email_enabled();

		if ( ! $email->is_enabled() || CourseProgressStatus::COMPLETED !== $new_status || ( $is_manual && ! $is_manual_email_enabled ) ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'course_progress' => $course_progress ), 'learning-management-system' );
		} else {
			$email->trigger( $course_progress );
		}
	}

	/**
	 * Schedule course completion email to instructor.
	 *
	 * @param integer $id Course progress ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @param \Masteriyo\Models\CourseProgress $course_progress The course progress object.
	 */
	public static function schedule_course_completion_email_to_instructor( $id, $old_status, $new_status, $course_progress ) {
		$email = new CourseCompletionEmailToInstructor();

		$is_manual               = masteriyo_is_course_progress_completed_manually( $course_progress );
		$is_manual_email_enabled = masteriyo_is_instructor_manual_course_completion_email_enabled();

		if ( ! $email->is_enabled() || CourseProgressStatus::COMPLETED !== $new_status || ( $is_manual && ! $is_manual_email_enabled ) ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'course_progress' => $course_progress ), 'learning-management-system' );
		} else {
			$email->trigger( $course_progress );
		}
	}

	/**
	 * Schedule course completion email to admin.
	 *
	 * @since 2.6.10
	 *
	 * @param integer $id Course progress ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @param \Masteriyo\Models\CourseProgress $course_progress The course progress object.
	 */
	public static function schedule_course_completion_email_to_admin( $id, $old_status, $new_status, $course_progress ) {
		$email = new CourseCompletionEmailToAdmin();

		$is_manual               = masteriyo_is_course_progress_completed_manually( $course_progress );
		$is_manual_email_enabled = masteriyo_is_admin_manual_course_completion_email_enabled();

		if ( ! $email->is_enabled() || CourseProgressStatus::COMPLETED !== $new_status || ( $is_manual && ! $is_manual_email_enabled ) ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'course_progress' => $course_progress ), 'learning-management-system' );
		} else {
			$email->trigger( $course_progress );
		}
	}


	/**
	 * Schedule course completion reminder email to student.
	 *
	 * @since 2.6.10
	 *
	 * @param int $user_id User id.
	 * @param int $course_id Course id.
	 */
	public static function schedule_course_completion_remainder_email_to_student( $user_id, $course_id ) {
		$email = new CourseCompletionReminderEmailToStudent();

		if ( ! $email->is_enabled() || ! get_user_by( 'ID', $user_id ) || ! in_array( 'masteriyo_student', get_userdata( $user_id )->roles, true ) ) {
			return;
		}

		if ( ! masteriyo_is_user_already_enrolled( $user_id, $course_id, 'active' ) || masteriyo_user_has_completed_course( $course_id, $user_id ) ) {
			as_unschedule_action(
				'masteriyo/job/send_course_completion_reminder_email',
				array(
					'user_id'   => $user_id,
					'course_id' => $course_id,
				),
				'masteriyo'
			);

			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action(
				$email->get_schedule_handle(),
				array(
					'user_id'   => $user_id,
					'course_id' => $course_id,
				),
				'masteriyo'
			);
		} else {
			$email->trigger( $user_id, $course_id );
		}
	}

	/**
	 * Schedule withdraw request rejected email to instructor.
	 *
	 * @since 1.6.14
	 * @param int $id Withdraw id.
	 * @param \Masteriyo\Addons\RevenueSharing\Models\Withdraw $withdraw Withdraw object.
	 */
	public static function schedule_withdraw_request_rejected_email_to_instructor( $id, $withdraw ) {
		$email = new WithdrawRequestRejectedEmailToInstructor();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'withdraw' => $withdraw ), 'learning-management-system' );
		} else {
			$email->trigger( $withdraw );
		}
	}


	/**
	 * Schedule new withdraw request email to admin.
	 *
	 * @since 1.6.14
	 * @param \Masteriyo\Addons\RevenueSharing\Models\Withdraw $withdraw Withdraw object.
	 */
	public static function schedule_new_withdraw_request_email_to_admin( $withdraw ) {
		$email = new NewWithdrawRequestEmailToAdmin();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'withdraw' => $withdraw ), 'learning-management-system' );
		} else {
			$email->trigger( $withdraw );
		}
	}

	/**
	 * Schedule withdraw request approved email to instructor.
	 *
	 * @since 1.6.14
	 * @param int $id Withdraw id.
	 * @param \Masteriyo\Addons\RevenueSharing\Models\Withdraw $withdraw Withdraw object.
	 */
	public static function schedule_withdraw_request_approved_email_to_instructor( $id, $withdraw ) {
		$email = new WithdrawRequestApprovedEmailToInstructor();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'withdraw' => $withdraw ), 'learning-management-system' );
		} else {
			$email->trigger( $withdraw );
		}
	}

	/**
	 * Schedule withdraw request pending email to instructor.
	 *
	 * @since 1.6.14
	 * @param \Masteriyo\Addons\RevenueSharing\Models\Withdraw $withdraw Withdraw object.
	 */
	public static function schedule_withdraw_request_pending_email_to_instructor( $withdraw ) {
		$email = new WithdrawRequestPendingEmailToInstructor();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'withdraw' => $withdraw ), 'learning-management-system' );
		} else {
			$email->trigger( $withdraw );
		}
	}


	/**
	 * Schedule google meet session reminder emails.
	 *
	 * @since 2.30.0
	 *
	 * @param int $session_id Google Meet session ID.
	 */
	public static function schedule_google_meet_session_reminders( $session_id ) {
		// Validate session ID.
		if ( ! is_numeric( $session_id ) || $session_id <= 0 ) {
			return;
		}

		$session = masteriyo_get_google_meet( absint( $session_id ) );
		if ( ! $session || ! $session->get_starts_at() ) {
			return;
		}

		$email = new GoogleMeetSessionReminderEmailToStudent();
		if ( ! $email->is_enabled() ) {
			return;
		}

		// Get reminder offset from settings (default 120 minutes).
		// This determines how many minutes before the session to send the reminder.
		$reminder_offset = masteriyo_get_setting( 'emails.student.google_meet_session_reminder.reminder_offset', 120 );
		$session_start   = strtotime( $session->get_starts_at() );
		$reminder_time   = $session_start - ( $reminder_offset * MINUTE_IN_SECONDS );
		$current_time    = time();

		$hook = 'masteriyo/job/send_google_meet_session_reminder_email';
		$args = array(
			'session_id' => $session_id,
		);

		// Unschedule any existing reminders for this session to avoid duplicates.
		as_unschedule_action( $hook, $args, 'masteriyo' );

		// Check if the session hasn't started yet
		if ( $session_start > $current_time ) {
			// If reminder time is in the past but session hasn't started.
			// schedule reminder to send immediately (5 seconds from now).
			if ( $reminder_time <= $current_time ) {
				$reminder_time = $current_time + 5;
			}

			// Schedule the reminder using Action Scheduler.
			as_schedule_single_action( $reminder_time, $hook, $args, 'masteriyo' );
		}
	}


	/**
	 * Reschedule google meet session reminder emails when session is updated.
	 *
	 * @since 2.30.0
	 *
	 * @param int $session_id Google Meet session ID.
	 */
	public static function reschedule_google_meet_session_reminders( $session_id ) {
		// Simply reschedule by calling the schedule method.
		self::schedule_google_meet_session_reminders( $session_id );
	}


	/**
	 * Unschedule google meet session reminder emails when session is deleted.
	 *
	 * @since 2.30.0
	 *
	 * @param int $session_id Google Meet session ID.
	 */
	public static function unschedule_google_meet_session_reminders( $session_id ) {
		$hook = 'masteriyo/job/send_google_meet_session_reminder_email';
		$args = array(
			'session_id' => $session_id,
		);

		as_unschedule_action( $hook, $args, 'masteriyo' );
	}

	/**
	 * Schedule new question email to instructor.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param int $id Question ID.
	 * @param \Masteriyo\Models\CourseQuestionAnswer $course_qa Question object.
	 */
	public static function schedule_new_question_email_to_instructor( $id, $course_qa ) {
		// Only send email for new questions (not replies)
		if ( $course_qa->is_answer() ) {
			return;
		}

		$email = new NewQuestionEmailToInstructor();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'question_id' => $course_qa->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $course_qa );
		}
	}

	/**
	 * Schedule new question reply email to student.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param int $id Reply ID.
	 * @param \Masteriyo\Models\CourseQuestionAnswer $course_qa Reply object.
	 */
	public static function schedule_new_question_reply_email_to_student( $id, $course_qa ) {
		// Only send email for replies (not new questions)
		if ( ! $course_qa->is_answer() ) {
			return;
		}

		// Don't send email if the replier is the same as the question creator
		$parent_question = masteriyo_get_course_qa( $course_qa->get_parent() );
		if ( $parent_question && $parent_question->get_user_id() === $course_qa->get_user_id() ) {
			return;
		}

		$email = new NewQuestionReplyEmailToStudent();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'reply_id' => $course_qa->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $course_qa );
		}
	}

	/**
	 * Disable WordPress core comment notifications for Q&A comments.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param string[] $emails     List of recipient email addresses.
	 * @param int      $comment_id Comment ID.
	 * @return string[]
	 */
	public static function disable_core_comment_notifications_for_qa( $emails, $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( ! $comment ) {
				return $emails;
		}

		// If this is a Q&A comment type, prevent notifications.
		if ( 'mto_course_qa' === (string) $comment->comment_type ) {
			return array(); // empty recipient list disables notifications.
		}

		return $emails;
	}

	/**
	 * Schedule new lesson comment email to instructor.
	 *
	 * @param int $id Comment ID.
	 * @param \Masteriyo\Models\LessonReview $comment Comment object.
	 */
	public static function schedule_new_lesson_comment_email_to_instructor( $id, $comment ) {
		// Only send email for new comments (not replies)
		if ( $comment->is_reply() ) {
			return;
		}

		$email = new NewLessonCommentEmailToInstructor();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'comment_id' => $comment->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $comment );
		}
	}

	/**
	 * Schedule new lesson comment reply email to student.
	 *
	 * @param int $id Reply ID.
	 * @param \Masteriyo\Models\LessonReview $reply Reply object.
	 */
	public static function schedule_new_lesson_comment_reply_email_to_student( $id, $reply ) {
		// Only send email for replies (not new comments)
		if ( ! $reply->is_reply() ) {
			return;
		}

		// Don't send email if the replier is the same as the comment author
		$parent_comment = masteriyo_get_lesson_review( $reply->get_parent() );
		if ( $parent_comment && (int) $parent_comment->get_author_id() === (int) $reply->get_author_id() ) {
			return;
		}

		$email = new NewLessonCommentReplyEmailToStudent();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'reply_id' => $reply->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $reply );
		}
	}

	/**
	 * Schedule new lesson comment email to admin.
	 *
	 * @param int $id Comment ID.
	 * @param \Masteriyo\Models\LessonReview $comment Comment object.
	 */
	public static function schedule_new_lesson_comment_email_to_admin( $id, $comment ) {
		// Only send email for new comments (not replies)
		if ( $comment->is_reply() ) {
			return;
		}

		$email = new NewLessonCommentEmailToAdmin();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'comment_id' => $comment->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $comment );
		}
	}

	/**
	 * Schedule new lesson comment reply email to admin.
	 *
	 * @param int $id Reply ID.
	 * @param \Masteriyo\Models\LessonReview $reply Reply object.
	 */
	public static function schedule_new_lesson_comment_reply_email_to_admin( $id, $reply ) {
		// Only send email for replies (not new comments)
		if ( ! $reply->is_reply() ) {
			return;
		}

		$email = new NewLessonCommentReplyEmailToAdmin();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'reply_id' => $reply->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $reply );
		}
	}

	/**
	 * Schedule new lesson comment reply email to instructor.
	 *
	 * @param int $id Reply ID.
	 * @param \Masteriyo\Models\LessonReview $reply Reply object.
	 */
	public static function schedule_new_lesson_comment_reply_email_to_instructor( $id, $reply ) {
		// Only send email for replies (not new comments)
		if ( ! $reply->is_reply() ) {
			return;
		}

		$email = new NewLessonCommentReplyEmailToInstructor();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'reply_id' => $reply->get_id() ), 'learning-management-system' );
		} else {
			$email->trigger( $reply );
		}
	}

	/**
	 * Disable WordPress core comment notifications for Lesson Comments.
	 *
	 * @param string[] $emails     List of recipient email addresses.
	 * @param int      $comment_id Comment ID.
	 * @return string[]
	 */
	public static function disable_core_comment_notifications_for_lesson_comment( $emails, $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( ! $comment ) {
			return $emails;
		}

		// If this is a Lesson Comment type, prevent notifications.
		if ( 'mto_lesson_review' === (string) $comment->comment_type ) {
			return array(); // empty recipient list disables notifications.
		}

		return $emails;
	}
}
