<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Notification utilities.
 */

if ( ! function_exists( 'masteriyo_get_default_notification_contents' ) ) {
	/**
	 * Returns the default notification contents used.
	 *
	 * The returned array is structured with the following keys:
	 * - `student`: Contains notification content for student-related notifications.
	 *
	 * Each of these top-level keys contains an array of notification types, with the notification
	 * type as the key and an array with a `content` key containing the default notification
	 * content.
	 *
	 * @since 2.14.0
	 *
	 * @return array The default notification contents used in Masteriyo.
	 */

	function masteriyo_get_default_notification_contents() {
		$data = array(
			'student' => array(
				'course_enroll'       => array(
					'content' => 'You have successfully enrolled into this course.',
				),
				'course_complete'     => array(
					'content' => 'You have successfully completed this course.',
				),
				'created_order'       => array(
					'content' => 'Your order is successfully created.',
				),
				'completed_order'     => array(
					'content' => 'Your order is completed.',
				),
				'onhold_order'        => array(
					'content' => 'Your order is on-hold.',
				),
				'cancelled_order'     => array(
					'content' => 'Your order is cancelled.',
				),
				'quiz_attempt'        => array(
					'content' => 'Your quiz attempt has been reviewed.',
				),
				'course_qa'           => array(
					'content' => 'You have received a reply to your question.',
				),
				'assignment_reply'    => array(
					'content' => 'Your assignment has been reviewed.',
				),
				'course_announcement' => array(
					'content' => 'You have a new announcement.',
				),
				'zoom'                => array(
					'content' => 'Zoom meeting is about to start 10 minutes from now.',
				),
				'content_drip'        => array(
					'content' => 'Content is now available.',
				),
				'lesson_comment'      => array(
					'content' => 'You have received a reply to your comment.',
				),
			),
		);

		return $data;
	}
}

if ( ! function_exists( 'masteriyo_get_notification' ) ) {
	/**
	 * Retrieves a notification object.
	 *
	 * @since 1.4.1
	 *
	 * @param int|Masteriyo\Models\Notification $notification Notification ID or object.
	 *
	 * @return Masteriyo\Models\Notification|null The notification object, or null if not found.
	 */
	function masteriyo_get_notification( $notification ) {
		if ( is_a( $notification, 'Masteriyo\Database\Model' ) ) {
			$id = $notification->get_id();
		} else {
			$id = absint( $notification );
		}

		try {
			$notification_obj = masteriyo( 'notification' );
			$store            = masteriyo( 'notification.store' );

			$notification_obj->set_id( $id );
			$store->read( $notification_obj );
		} catch ( \Exception $e ) {
			$notification_obj = null;
		}

		/**
		 * Filters notification object.
		 *
		 * @since 1.4.1
		 *
		 * @param Masteriyo\Models\Notification $notification_obj The notification object.
		 * @param int|Masteriyo\Models\Notification $notification Notification ID or object.
		 */
		return apply_filters( 'masteriyo_get_notification', $notification_obj, $notification );
	}
}

if ( ! function_exists( 'masteriyo_set_notification' ) ) {

	/**
	 * Set a notification.
	 *
	 * This function sets a notification based on the provided parameters. It creates a new notification object, sets its properties, and saves it to the database.
	 *
	 * @since 1.7.1
	 *
	 * @param int|Masteriyo\Models\Notification $id The ID of the notification or the notification object itself.
	 * @param Masteriyo\Models\UserCourse|null $user_course The user course object associated with the notification.
	 * @param array|null $result The result data containing the notification type and content.
	 *
	 * @return Masteriyo\Models\Notification The created notification object.
	 */
	function masteriyo_set_notification( $id = null, $user_course = null, $result = null ) {
		if ( ! isset( $result ) ) {
			return;
		}

		try {
			$notification_obj = masteriyo( 'notification' );

			if ( ! $user_course ) {
				return $notification_obj;
			}

			$course = $user_course->get_course();

			$notification_obj->set_user_id( $user_course->get_user_id() );
			$notification_obj->set_created_by( $course->get_author_id() );
			$notification_obj->set_title( $course->get_title() );

			$today        = new DateTime( 'now' );
			$today_string = $today->format( 'Y-m-d H:i:s' );

			$notification_obj->set_created_at( $today_string );
			$notification_obj->set_type( $result['type'] );
			$notification_obj->set_status( 'unread' );
			$notification_obj->set_description( $result['content'] );

			// The Account page can be renamed or moved, so build every account
			// link from the configured page rather than a hardcoded /account/.
			$account_url = masteriyo_get_account_url();

			switch ( $result['type'] ) {
				case 'created_order':
				case 'completed_order':
				case 'onhold_order':
				case 'cancelled_order':
					$order_id = $user_course->get_order_id();
					if ( $order_id ) {
						$notification_obj->set_topic_url( $account_url . '#/order-history/' . $order_id );
					}
					break;

				case 'quiz_attempt':
					if ( $id ) {
						$notification_obj->set_topic_url( $account_url . '#/quiz-attempts/' . $id );
					}
					break;

				case 'assignment_reply':
					if ( $id ) {
						$notification_obj->set_topic_url( $account_url . '#/assignments/' . $id );
					}
					break;

				case 'zoom':
					if ( $id ) {
						$notification_obj->set_topic_url( $account_url . '#/zoom/' . $id );
					}
					break;

				case 'lesson_comment':
					if ( $id ) {
						$comment = masteriyo_get_lesson_review( $id );
						if ( $comment ) {
							$lesson = masteriyo_get_lesson( $comment->get_lesson_id() );
							if ( $lesson ) {
								$notification_obj->set_topic_url( $lesson->get_learn_url() );
							}
						}
					}
					break;
				default:
					$notification_obj->set_topic_url( get_permalink( $course->get_id() ) );
					break;
			}

			$notification_obj->set_post_id( $course->get_id() );
			$notification_obj->save();

		} catch ( \Exception $e ) {
			$notification_obj = null;
		}

		return apply_filters( 'masteriyo_set_notification', $notification_obj );
	}


}

if ( ! function_exists( 'masteriyo_get_notification_by_user_post_id_and_type' ) ) {
	/**
	 * Get a notification by user ID, post ID, and type.
	 *
	 * @since 2.12.0
	 *
	 * @param int $user_id The ID of the user.
	 * @param int $post_id The ID of the post.
	 * @param string $type The type of the notification.
	 *
	 * @return Masteriyo\Models\Notification|null The notification object, or null if not found.
	 */
	function masteriyo_get_notification_by_user_post_id_and_type( $user_id, $post_id, $type ) {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}masteriyo_notifications WHERE user_id =%d AND post_id = %d AND type = %s;",
				absint( $user_id ),
				absint( $post_id ),
				$type
			)
		);

		if ( $result ) {
			return masteriyo_get_notification( absint( $result ) );
		}

		return null;
	}
}
