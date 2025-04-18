<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Notification utilities.
 */

/**
 * Get notification.
 *
 * @since 1.4.1
 *
 * @param int|Masteriyo\Models\Notification $notification Notification ID or object.
 * @return Masteriyo\Models\Notification
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

/**
 * Set notification.
 *
 * @since 1.7.1
 *
 * @param int|Masteriyo\Models\Notification $notification Notification ID or object.
 * @return Masteriyo\Models\Notification
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
		$notification_obj->set_topic_url( $course->get_preview_course_link() );
		$notification_obj->set_post_id( $course->get_id() );

		$notification_obj->save();

	} catch ( \Exception $e ) {
		$notification_obj = null;
	}

	return $notification_obj;
}



