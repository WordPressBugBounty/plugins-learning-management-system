<?php
/**
 * Fluent CRM Integration helper functions.
 *
 * @since 1.14.0
 * @package Masteriyo\Addons\FluentCRM
 */
//phpcs:ignoreFile
namespace Masteriyo\Addons\FluentCRM;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Query\CourseProgressQuery;
use Masteriyo\Query\UserCourseQuery;

class Helper {
	/**
	 * Return if Fluent CRM is active.
	 *
	 * @since 1.14.0
	 *
	 * @return boolean
	 */
	public static function is_fluent_crm_active() {
		return in_array( 'fluent-crm/fluent-crm.php', get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Return if Fluent CRM PRO is active.
	 *
	 * @since 1.14.0
	 *
	 * @return boolean
	 */
	public static function is_fluent_crm_pro_active() {
		return in_array( 'fluentcampaign-pro/fluentcampaign-pro.php', get_option( 'active_plugins', array() ), true );
	}

	/**
	 * Get the trigger source for the trigger.
	 *
	 * @since 1.14.0
	 *
	 * @param string $triggerName The trigger name.
	 *
	 * @return string|boolean The trigger source, or false if not found.
	 */
	public static function masteriyo_get_trigger_source( $triggerName ) {
		$maps = [
			'masteriyo_course_complete_after' => 'course',
			'masteriyo_course_progress_status_changed' => 'course',
			'masteriyo_new_user_course' => 'course'
		];

		return isset( $maps[ $triggerName ] ) ? $maps[ $triggerName ] : false;
	}


	/**
	 * Get item listing for the integration.
	 *
	 * @since 1.14.0
	 *
	 * @param array  $items The integration items.
	 * @param string $search The search term.
	 * @param string $course_price The course price | free | paid.
	 *
	 * @return array The items for the integration.
	 */
	public static function get_courses( $items = array(), $search = '', $course_price = '' ) {

		$meta_query = array();

  	if ( $course_price === 'paid' ) {
		$meta_query[] = array(
			'key'     => '_regular_price',
			'value'   => 0,
			'compare' => '>',
			'type'    => 'NUMERIC',
		);
	  } elseif ( $course_price === 'free' ) {
		$meta_query[] = array(
			'key'     => '_regular_price',
			'value'   => 0,
			'compare' => '=',
			'type'    => 'NUMERIC',
		);
	 }

		$course_query = new \WP_Query(
			array(
				'post_type' => 'mto-course',
				's'         => $search,
				'posts_per_page'  => -1,
				'meta_query'     => $meta_query,
			)
		);

		if ( ( isset( $course_query->posts ) ) && ( ! empty( $course_query->posts ) ) ) {
			$items = array_map(
				function ( $post ) {
					return (object) array(
						'id'    => $post->ID,
						'title' => $post->post_title,
					);
				},
				$course_query->posts
			);
		}

		return $items;
	}

	/**
	 * Deletes a user's enrollment in a course.
	 *
	 * @since 1.14.0
	 *
	 * @param int $userId user id.
	 * @param int $courseId course id.
	 *
	 * @return \WP_Error|\WP_REST_Response The response object, or a WP_Error object on failure.
	 */
	public static function masteriyo_remove_user_from_course( $userId, $courseId ) {
		$course = masteriyo_get_course( absint( $courseId ) );

		if ( is_null( $course ) ) {
			wp_die( esc_html__( 'Course does not exist!', 'learning-management-system' ), esc_html__( 'Retake Course', 'learning-management-system' ) );
			return;
		}

		$query = new UserCourseQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => $userId,
			)
		);

		$user_course = current( $query->get_user_courses() );

		if ( $user_course ) {
			global $wpdb;

			$user_item_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}masteriyo_user_items
					WHERE user_id = %d
					AND item_id = %d
					AND item_type = 'user_course'",
					$userId,
					$course->get_id()
				)
			);

			if ( empty( $user_item_id ) ) {
				wp_die( esc_html__( 'Failed to restart the course.', 'learning-management-system' ), esc_html__( 'Retake Course', 'learning-management-system' ) );
			}

			$wpdb->delete(
				"{$wpdb->prefix}masteriyo_user_items",
				array(
					'id' => $user_item_id,
				)
			);

			$user_activity_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$wpdb->prefix}masteriyo_user_activities
					WHERE item_id = %d
					AND user_id = %d
					AND activity_type = 'course_progress'
					AND activity_status IS NOT NULL
					AND parent_id = 0",
					$course->get_id(),
					$userId
				)
			);

			if ( empty( $user_activity_id ) ) {
				wp_die( esc_html__( 'Failed to restart the course.', 'learning-management-system' ), esc_html__( 'Retake Course', 'learning-management-system' ) );
			}

			$wpdb->delete(
				"{$wpdb->prefix}masteriyo_user_activities",
				array(
					'parent_id' => $user_activity_id,
				)
			);
			$wpdb->delete(
				"{$wpdb->prefix}masteriyo_user_activities",
				array(
					'id' => $user_activity_id,
				)
			);
			$wpdb->delete(
				"{$wpdb->prefix}masteriyo_quiz_attempts",
				array(
					'course_id' => $user_course->get_course_id(),
					'user_id'   => $userId,
				)
			);

			$user_course->set_date_start( current_time( 'mysql', true ) );
			$user_course->save();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if the user is enrolled in a course.
	 *
	 * @since 1.14.0
	 *
	 * @param array $courseIds The course IDs.
	 * @param mixed $subscriber The subscriber.
	 *
	 * @return boolean
	 */
	public static function is_in_courses( $courseIds, $subscriber ) {
		if ( ! $courseIds ) {
			return false;
		}

		$userId = $subscriber->getWpUserId();
		if ( ! $userId ) {
			return false;
		}

		if( is_array($courseIds) ) {
			$courseIds = $courseIds['0'];
		}

		$enrolled = masteriyo_is_user_enrolled_in_course( $courseIds, $userId );

		if ( $enrolled ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the user has completed a course.
	 *
	 * @since 1.14.0
	 *
	 * @param array $courseIds The course IDs.
	 * @param mixed $subscriber The subscriber.
	 *
	 * @return boolean
	 */
	public static function is_courses_completed( $courseIds, $subscriber ) {

		if ( ! $courseIds ) {
			return false;
		}

		$userId = $subscriber->getWpUserId();
		// $userId = $subscriber;
		if ( ! $userId ) {
			return false;
		}

		foreach ( $courseIds as $courseId ) {
			$query      = new CourseProgressQuery(
				array(
					'user_id'   => $userId,
					'course_id' => $courseId,
					'status'    => CourseProgressStatus::COMPLETED,
				)
			);
			$progresses = $query->get_course_progress();

			if ( !empty($progresses ) ) {
				return true;
			}
		}
		return false;
	}
}
