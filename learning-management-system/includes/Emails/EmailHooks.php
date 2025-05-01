<?php
/**
 * EmailHooks class.
 *
 * @package Masteriyo\Emails
 *
 * @since 1.0.0
 */

namespace Masteriyo\Emails;

use Masteriyo\Emails\Admin\CourseCompletionEmailToAdmin;
use Masteriyo\Emails\Admin\CourseStartEmailToAdmin;
use Masteriyo\Enums\UserStatus;
use Masteriyo\Emails\ResetPasswordEmail;
use Masteriyo\Emails\Admin\InstructorApplyEmailToAdmin;
use Masteriyo\Emails\Admin\InstructorRegistrationEmailToAdmin;
use Masteriyo\Emails\Admin\NewOrderEmailToAdmin;
use Masteriyo\Emails\Admin\NewQuizAttemptEmailToAdmin;
use Masteriyo\Emails\Instructor\InstructorApplyApprovedEmailToInstructor;
use Masteriyo\Emails\Student\OnHoldOrderEmailToStudent;
use Masteriyo\Emails\Admin\NewWithdrawRequestEmailToAdmin;
use Masteriyo\Emails\Admin\StudentRegistrationEmailToAdmin;
use Masteriyo\Emails\Instructor\CourseCompletionEmailToInstructor;
use Masteriyo\Emails\Instructor\CourseStartEmailToInstructor;
use Masteriyo\Emails\Student\CancelledOrderEmailToStudent;
use Masteriyo\Emails\Student\CompletedOrderEmailToStudent;
use Masteriyo\Emails\Student\StudentRegistrationEmailToStudent;
use Masteriyo\Emails\Instructor\WithdrawRequestApprovedEmailToInstructor;
use Masteriyo\Emails\Instructor\InstructorRegistrationEmailToInstructor;
use Masteriyo\Emails\Instructor\NewQuizAttemptEmailToInstructor;
use Masteriyo\Emails\Instructor\WithdrawRequestPendingEmailToInstructor;
use Masteriyo\Emails\Instructor\WithdrawRequestRejectedEmailToInstructor;
use Masteriyo\Emails\Student\AutomaticRegistrationEmailToStudent;
use Masteriyo\Emails\Student\CourseCompletionEmailToStudent;
use Masteriyo\Emails\Student\InstructorApplyRejectedEmailToStudent;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\InstructorApplyStatus;
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
	 * @since 1.0.0
	 */
	public static function init() {
		add_action( 'masteriyo_after_password_reset_email', array( __CLASS__, 'schedule_password_reset_request_email' ), 10, 3 );
		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_automatic_registration_email_to_student' ), 10, 3 );

		// Apply for instructor from student profile.
		add_action( 'masteriyo_update_user', array( __CLASS__, 'schedule_instructor_apply_rejected_email_to_student' ), 10, 2 );

		add_action( 'masteriyo_order_status_completed', array( __CLASS__, 'schedule_completed_order_email_to_student' ), 10, 2 );
		add_action( 'masteriyo_order_status_on-hold', array( __CLASS__, 'schedule_onhold_order_email_to_student' ), 10, 2 );
		add_action( 'masteriyo_order_status_cancelled', array( __CLASS__, 'schedule_cancelled_order_email_to_student' ), 10, 2 );

		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_student_registration_email_to_student' ), 10, 2 );

		add_action( 'masteriyo_after_user_registration_complete', array( __CLASS__, 'schedule_student_registration_email_to_student' ), 10, 2 );

		// Email Verification Email.
		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_email_verification_email' ), 11, 2 );

		add_action( 'masteriyo_after_order_object_save', array( __CLASS__, 'schedule_new_order_email_to_admin' ), 10, 3 ); // For the offline payment method.
		add_action( 'masteriyo_order_status_completed', array( __CLASS__, 'schedule_new_order_email_to_admin_on_completion' ), 10, 2 ); // For the other than offline payment method.

		add_action( 'masteriyo_apply_for_instructor', array( __CLASS__, 'schedule_instructor_apply_email_to_admin' ), 10, 1 );
		add_action( 'masteriyo_new_withdraw', array( __CLASS__, 'schedule_new_withdraw_request_email_to_admin' ) );
		add_action( 'masteriyo_course_progress_status_changed', array( __CLASS__, 'schedule_course_completion_email_to_admin' ), 10, 4 );
		add_action( 'masteriyo_after_learn_page_process', array( __CLASS__, 'schedule_course_start_email_to_admin' ), 10, 1 );
		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_instructor_registration_email_to_admin' ), 10, 2 );
		add_action( 'masteriyo_update_quiz_attempt', array( __CLASS__, 'schedule_new_quiz_attempt_email_to_admin' ), 10, 2 );
		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_student_registration_email_to_admin' ), 10, 2 );

		add_action( 'masteriyo_update_user', array( __CLASS__, 'schedule_instructor_apply_approved_email_to_instructor' ), 10, 2 );
		add_action( 'masteriyo_new_user', array( __CLASS__, 'schedule_instructor_registration_email_to_instructor' ), 10, 2 );
		add_action( 'masteriyo_after_user_registration_complete', array( __CLASS__, 'schedule_instructor_registration_email_to_instructor' ), 10, 2 );
		add_action( 'masteriyo_new_withdraw', array( __CLASS__, 'schedule_withdraw_request_pending_email_to_instructor' ) );
		add_action( 'masteriyo_withdraw_status_approved', array( __CLASS__, 'schedule_withdraw_request_approved_email_to_instructor' ), 10, 2 );
		add_action( 'masteriyo_withdraw_status_rejected', array( __CLASS__, 'schedule_withdraw_request_rejected_email_to_instructor' ), 10, 2 );
		add_action( 'masteriyo_course_progress_status_changed', array( __CLASS__, 'schedule_course_completion_email_to_instructor' ), 10, 4 );
		add_action( 'masteriyo_after_learn_page_process', array( __CLASS__, 'schedule_course_start_email_to_instructor' ), 10, 1 );
		add_action( 'masteriyo_update_quiz_attempt', array( __CLASS__, 'schedule_new_quiz_attempt_email_to_instructor' ), 10, 2 );

		add_action( 'masteriyo_course_progress_status_changed', array( __CLASS__, 'schedule_course_completion_email_to_student' ), 10, 4 );
	}

	/**
	 * Schedule password reset request email.
	 *
	 * @since 1.6.1
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
	 * Schedule automatic registration email to student.
	 *
	 * @since 1.15.0
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

		$email = new AutomaticRegistrationEmailToStudent();

		if ( ! $email->is_enabled() ) {
			return;
		}

		$email->trigger( $user, isset( $args['password'] ) ? $args['password'] : '', isset( $args['reset_key'] ) ? $args['reset_key'] : '' );
	}

	/**
	 * Schedule password reset request email.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Models\User $user User object.
	 * @param string $password_generated The generated password.
	 * @param string $reset_key The password reset key for the user.
	 */
	public static function schedule_password_reset_request_email_after_customer_creation( $user, $password_generated, $reset_key ) {
		if ( ! $password_generated ) {
			return;
		}

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
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Order $order Order object.
	 * @param \Masteriyo\Repository\OrderRepository $repository THe data store persisting the data.
	 * @param bool $is_new Whether the order is newly created.
	 *
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
	 * @since 1.17.1
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
	 * @since 1.15.0
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
	 * Schedule course completion email to admin.
	 *
	 * @since 1.15.0
	 *
	 * @param integer $id Course progress ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @param \Masteriyo\Models\CourseProgress $course_progress The course progress object.
	 */
	public static function schedule_course_completion_email_to_admin( $id, $old_status, $new_status, $course_progress ) {
		$email = new CourseCompletionEmailToAdmin();

		if ( ! $email->is_enabled() || CourseProgressStatus::COMPLETED !== $new_status ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'course_progress' => $course_progress ), 'learning-management-system' );
		} else {
			$email->trigger( $course_progress );
		}
	}

	/**
	 * Schedule course start email to admin.
	 *
	 * @since 1.15.0
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
	 * Schedule new instructor registration email to admin.
	 *
	 * @since 1.15.0
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
	 * Schedule new quiz attempt email to admin.
	 *
	 * @since 1.15.0
	 *
	 * @param integer $id The quiz attempt ID.
	 * @param \Masteriyo\Models\QuizAttempt $quiz_attempt The quiz attempt object.
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
	 * Schedule new student registration email to admin.
	 *
	 * @since 1.15.0
	 *
	 * @param int $user_id User ID.
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
	 * Schedule course completion email to instructor.
	 *
	 * @since 1.15.0
	 *
	 * @param integer $id Course progress ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @param \Masteriyo\Models\CourseProgress $course_progress The course progress object.
	 */
	public static function schedule_course_completion_email_to_instructor( $id, $old_status, $new_status, $course_progress ) {
		$email = new CourseCompletionEmailToInstructor();

		if ( ! $email->is_enabled() || CourseProgressStatus::COMPLETED !== $new_status ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'course_progress' => $course_progress ), 'learning-management-system' );
		} else {
			$email->trigger( $course_progress );
		}
	}

	/**
	 * Schedule course start email to instructor.
	 *
	 * @since 1.15.0
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
	 * Schedule new quiz attempt email to instructor.
	 *
	 * @since 1.15.0
	 *
	 * @param integer $id Quiz attempt ID.
	 * @param \Masteriyo\Models\QuizAttempt $quiz_attempt Quiz attempt object.
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
	 * Schedule course completion email to student.
	 *
	 * @since 1.15.0
	 *
	 * @param integer $id Course progress ID.
	 * @param string $old_status Old status.
	 * @param string $new_status New status.
	 * @param \Masteriyo\Models\CourseProgress $course_progress The course progress object.
	 */
	public static function schedule_course_completion_email_to_student( $id, $old_status, $new_status, $course_progress ) {
		$email = new CourseCompletionEmailToStudent();

		if ( ! $email->is_enabled() || CourseProgressStatus::COMPLETED !== $new_status ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action( $email->get_schedule_handle(), array( 'course_progress' => $course_progress ), 'learning-management-system' );
		} else {
			$email->trigger( $course_progress );
		}
	}
}
