<?php

/**
 * Masteriyo BuddyPress Integration setup.
 *
 * @package Masteriyo\BuddyPress
 *
 * @since 1.15.0
 */

namespace Masteriyo\Addons\BuddyPress;

use Masteriyo\Addons\BuddyPress\Classes\BuddyPressGroupSettings;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Query\UserCourseQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo BuddyPress Integration class.
 *
 * @class Masteriyo\Addons\BuddyPress
 */
class BuddyPressAddon {


	private $buddypress_group_settings;

	/**
	 * Initialize the application.
	 *
	 * @since 1.15.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.15.0
	 */
	public function init_hooks() {
		add_action( 'bp_init', array( $this, 'load_group_extension' ) );

		add_action( 'masteriyo_new_user_course', array( $this, 'bp_masteriyo_add_user_group_access' ), 10, 2 );
		add_action( 'masteriyo_order_status_completed', array( $this, 'bp_masteriyo_completed_order_add_user_group_access' ), 10, 2 );

		add_action( 'masteriyo_course_progress_status_changed', array( $this, 'bp_masteriyo_remove_user_group_access' ), 10, 4 );

		add_action( 'masteriyo_new_course_progress_item', array( $this, 'bp_masteriyo_user_lesson_end_activity' ), 10, 2 );

		add_action( 'masteriyo_course_progress_status_changed', array( $this, 'bp_masteriyo_user_course_end_activity' ), 10, 4 );

		add_action( 'masteriyo_course_progress_item_completion_status_changed', array( $this, 'bp_masteriyo_user_quiz_end_activity' ), 10, 3 );

		add_action( 'masteriyo_new_lesson', array( $this, 'bp_masteriyo_new_lesson_activity' ), 10, 2 );

	}

	/**
	 * Remove users to buddypress groups on masteriyo group access update
	 *
	 * @since 1.15.0
	 *
	 * @param $user_id
	 * @param $group_id
	 */
	public function bp_masteriyo_remove_user_group_access( $id, $old_status, $new_status, $user_course ) {

		if ( ! masteriyo_is_current_user_student( $user_course->get_id() ) || CourseProgressStatus::COMPLETED !== $new_status ) {
			return;
		}

		$course_id = $user_course->get_course_id();
		$user_id   = $user_course->get_user_id();

		$group_id = get_post_meta( $course_id, 'bp_course_group', true );

		Helper::bp_masteriyo_user_course_access_update( $user_id, $course_id, true );

	}

	/**
	 * Add users to buddypress groups on masteriyo order update to completed
	 *
	 * @since 1.15.0
	 *
	 * @param $user_course_id
	 * @param $user_course
	 */
	public function bp_masteriyo_completed_order_add_user_group_access( $user_course_id, $user_course ) {

		$course_items = $user_course->get_items( 'course' );

		$data = array();

		foreach ( $course_items as $course_item ) {
			$data[] = array(
				'course_id' => $course_item->get_course_id(),
			);
		}

		foreach ( $data as $data_entry ) {
			$query = new UserCourseQuery(
				array(
					'course_id' => $data_entry['course_id'],
					'user_id'   => $user_course->get_customer_id(),
				)
			);

			$user_courses = $query->get_user_courses();

			$user_course = current( $user_courses );

			$course_id = $user_course->get_course_id();
			$user_id   = $user_course->get_user_id();

			$group_id = get_post_meta( $course_id, 'bp_course_group', true );

			Helper::bp_masteriyo_user_course_access_update( $user_id, $course_id, false );
		}
	}

	/**
	 * Handle completion status change of a course progress item.
	 *
	 * @since 1.15.0
	 *
	 * @param integer $id Lesson Id.
	 * @param \Masteriyo\Models\UserCourse $user_course
	 */
	public function bp_masteriyo_new_lesson_activity( $id, $lesson ) {

		$course_id = $lesson->get_course_id();
		$user_id   = $lesson->geT_author_id();

		$group_attached = get_post_meta( $course_id, 'bp_course_group', true );

		if ( empty( $group_attached ) ) {
			return;
		}
		if ( ! Helper::bp_masteriyo_group_activity_is_on( 'add_new_lesson', $group_attached ) ) {
			return;
		}

		global $bp;

		$user_link = bp_core_get_userlink( $user_id );

		$lesson_title     = get_the_title( $id );
		$lesson_link      = get_permalink( $id );
		$lesson_link_html = '<strong>' . $lesson_title . '</strong>';

		$course_title     = get_the_title( $course_id );
		$course_link      = get_permalink( $course_id );
		$course_link_html = '<a href="' . esc_url( $course_link ) . '">' . $course_title . '</a>';
		$args             = array(
			'type'              => 'add_new_lesson',
			'user_id'           => $user_id,
			'action'            => apply_filters(
				'bp_masteriyo_new_lesson_activity',
				sprintf(
					/* translators: %1$s: user link, %2$s: lesson link, %3$s: course link */
					__( '%1$s added new lesson %2$s to the course %3$s', 'learning-management-system' ),
					$user_link,
					$lesson_link_html,
					$course_link_html
				),
				$user_id,
				$course_id
			),
			'item_id'           => $group_attached,
			'secondary_item_id' => $course_id,
			'component'         => $bp->groups->id,
		);

		$activity_recorded = Helper::bp_masteriyo_record_activity( $args );
		if ( $activity_recorded ) {
			bp_activity_add_meta( $activity_recorded, 'bp_masteriyo_group_activity_markup_courseid', $course_id );
		}
	}

	/**
	 * Handle completion status change of a course progress item.
	 *
	 * @since 1.15.0
	 *
	 * @param \Masteriyo\Models\CourseProgressItem $progress_item
	 * @param string $old_status
	 * @param string $new_status
	 */
	public function bp_masteriyo_user_quiz_end_activity( $progress_item, $old_status, $new_status ) {

		if (
			'quiz' !== $progress_item->get_item_type() ||
			'completed' !== $new_status ||
			$old_status === $new_status
		) {
			return;
		}

		$quiz = masteriyo_get_quiz( $progress_item->get_item_id() );

		if ( is_null( $quiz ) ) {
			return;
		}

		$user_id   = $progress_item->get_user_id();
		$quiz_id   = $quiz->get_id();
		$course_id = $progress_item->get_course_id();

		$group_attached = get_post_meta( $course_id, 'bp_course_group', true );

		if ( empty( $group_attached ) ) {
			return;
		}

		if ( ! Helper::bp_masteriyo_group_activity_is_on( 'user_quiz_end', $group_attached ) ) {
			return;
		}

		global $bp;

		$user_link = bp_core_get_userlink( $user_id );

		$course_title     = get_the_title( $course_id );
		$course_link      = get_permalink( $course_id );
		$course_link_html = '<a href="' . esc_url( $course_link ) . '">' . $course_title . '</a>';

		$lesson_title     = get_the_title( $quiz_id );
		$lesson_link      = get_permalink( $quiz_id );
		$lesson_link_html = '<strong>' . $lesson_title . '</strong>';

		$args = array(
			'type'              => 'completed_quiz',
			'user_id'           => $user_id,
			'action'            => apply_filters(
				'bp_masteriyo_user_quiz_end_activity',
				sprintf(
						/* translators: %1$s: user link, %2$s: quiz link, %3$s: course link */
					__( '%1$s completed the quiz %2$s for course %3$s', 'learning-management-system' ),
					$user_link,
					$lesson_link_html,
					$course_link_html
				),
				$user_id,
				$course_id
			),
			'item_id'           => $group_attached,
			'secondary_item_id' => $course_id,
			'component'         => $bp->groups->id,
		);

		$activity_recorded = Helper::bp_masteriyo_record_activity( $args );
		if ( $activity_recorded ) {
			bp_activity_add_meta( $activity_recorded, 'bp_masteriyo_group_activity_markup_courseid', $course_id );
		}
	}

	/**
	 * Load BuddyPress classes
	 *
	 * @since 1.15.0
	 *
	 * @return void
	 */
	public function bp_masteriyo_user_lesson_end_activity( $progress_id, $progress_item ) {

		if ( 'lesson' !== $progress_item->get_item_type() || ! $progress_item->get_completed() ) {
			return;
		}

		$lesson = masteriyo_get_lesson( $progress_item->get_item_id() );

		if ( is_null( $lesson ) ) {
			return;
		}

		$user_id   = $progress_item->get_user_id();
		$lesson_id = $lesson->get_id();
		$course_id = $progress_item->get_course_id();

		$group_attached = get_post_meta( $course_id, 'bp_course_group', true );

		if ( empty( $group_attached ) ) {
			return;
		}

		if ( ! Helper::bp_masteriyo_group_activity_is_on( 'user_lesson_end', $group_attached ) ) {
			return;
		}

		global $bp;

		$user_link = bp_core_get_userlink( $user_id );

		$course_title     = get_the_title( $course_id );
		$course_link      = get_permalink( $course_id );
		$course_link_html = '<a href="' . esc_url( $course_link ) . '">' . $course_title . '</a>';

		$lesson_title     = get_the_title( $lesson_id );
		$lesson_link      = get_permalink( $lesson_id );
		$lesson_link_html = '<strong>' . $lesson_title . '</strong>';

		$args = array(
			'type'              => 'completed_lesson',
			'user_id'           => $user_id,
			'action'            => apply_filters(
				'bp_masteriyo_user_lesson_end_activity',
				sprintf(
					/* translators: %1$s: user link, %2$s: lesson link, %3$s: course link */
					__( '%1$s completed the lesson %2$s for course %3$s', 'learning-management-system' ),
					$user_link,
					$lesson_link_html,
					$course_link_html
				),
				$user_id,
				$course_id
			),
			'item_id'           => $group_attached,
			'secondary_item_id' => $course_id,
			'component'         => $bp->groups->id,
		);

		$activity_recorded = Helper::bp_masteriyo_record_activity( $args );
		if ( $activity_recorded ) {
			bp_activity_add_meta( $activity_recorded, 'bp_masteriyo_group_activity_markup_courseid', $course_id );
		}
	}

	/**
	 * Load BuddyPress classes group extension.
	 *
	 * @since 1.15.0
	 *
	 * @param array $sources Video sources.
	 * @param \Masteriyo\Models\Lesson $lesson Lesson object.
	 * @return array
	 */
	public function load_group_extension() {

		if ( bp_is_active( 'groups' ) ) {
			$this->buddypress_group_settings = new BuddyPressGroupSettings();
		}

		if ( bp_is_active( 'groups' ) && masteriyo_is_current_user_admin() ) {
			bp_register_group_extension( 'Masteriyo\Addons\BuddyPress\Classes\BuddyPressGroupSettings' );
		}
	}

	/**
	 * Add users to buddypress groups on masteriyo group access update (Users > Edit)
	 *
	 * @since 1.15.0
	 *
	 * @param $user_id
	 * @param $group_id
	 */
	public function bp_masteriyo_add_user_group_access( $user_course_id, $user_course ) {

		$course_id = $user_course->get_course_id();
		$user_id   = $user_course->get_user_id();

		$group_id = get_post_meta( $course_id, 'bp_course_group', true );

		$query = new UserCourseQuery(
			array(
				'course_id' => $course_id,
				'user_id'   => $user_id,
			)
		);

		$activity = current( $query->get_user_courses() );
		$status   = $activity ? $activity->get_status() : '';

		if ( 'inactive' === $status ) {
			return;
		}

		Helper::bp_masteriyo_user_course_access_update( $user_id, $course_id, false );

	}

	/**
	 * Handle completion status change of a course progress item.
	 *
	 * @since 1.15.0
	 *
	 * @param integer $id
	 * @param string $old_status
	 * @param string $new_status
	 * @param \Masteriyo\Models\UserCourse $user_course
	 */
	public function bp_masteriyo_user_course_end_activity( $id, $old_status, $new_status, $user_course ) {

		if ( CourseProgressStatus::COMPLETED !== $new_status ) {
			return;
		}

		$course_id = $user_course->get_course_id();
		$user_id   = $user_course->get_user_id();

		$group_attached = get_post_meta( $course_id, 'bp_course_group', true );
		if ( empty( $group_attached ) ) {
			return;
		}
		if ( ! Helper::bp_masteriyo_group_activity_is_on( 'user_course_end', $group_attached ) ) {
			return;
		}

		global $bp;

		$user_link         = bp_core_get_userlink( $user_id );
		$course_title      = get_the_title( $course_id );
		$course_link       = get_permalink( $course_id );
		$course_link_html  = '<a href="' . esc_url( $course_link ) . '">' . $course_title . '</a>';
		$args              = array(
			'type'              => 'completed_course',
			'user_id'           => $user_id,
			'action'            => apply_filters(
				'bp_masteriyo_user_course_end_activity',
				sprintf(
					/* translators: %1$s: user link, %2$s: course link */
					__( '%1$s completed the course %2$s', 'learning-management-system' ),
					$user_link,
					$course_link_html
				),
				$user_id,
				$course_id
			),

			'item_id'           => $group_attached,
			'secondary_item_id' => $course_id,
			'component'         => $bp->groups->id,
		);
		$activity_recorded = Helper::bp_masteriyo_record_activity( $args );
		if ( $activity_recorded ) {
			bp_activity_add_meta( $activity_recorded, 'bp_masteriyo_group_activity_markup_courseid', $course_id );
		}
	}
}
