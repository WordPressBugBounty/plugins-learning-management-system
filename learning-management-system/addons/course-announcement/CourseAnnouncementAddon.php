<?php
/**
 * Course announcement addon.
 *
 * @since 1.6.16
 *
 * @package Masteriyo\Addons\CourseAnnouncement
 */

namespace Masteriyo\Addons\CourseAnnouncement;

use Masteriyo\Addons\CourseAnnouncement\Controllers\CourseAnnouncementController;
use Masteriyo\Addons\CourseAnnouncement\PostType\CourseAnnouncement;
use Masteriyo\PostType\PostType;
use Masteriyo\AddonsFramework\Addons;
use Masteriyo\Query\UserCourseQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Course announcement addon class.
 *
 * @since 1.6.16
 */
class CourseAnnouncementAddon {

	/**
	 * Init addon.
	 *
	 * @since 1.6.16
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 *
	 * @since 1.6.16
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_rest_api_get_rest_namespaces', array( $this, 'register_rest_namespaces' ) );
		add_filter( 'masteriyo_register_post_types', array( $this, 'register_post_types' ) );
		add_filter( 'masteriyo_admin_submenus', array( $this, 'add_course_announcement_submenu' ) );
		add_action( 'masteriyo_new_course_announcement', array( $this, 'schedule_new_announcement_notification_to_student' ), 10, 2 );

		/**
		 * Fires once the course announcement addon has initialised.
		 *
		 * The webhook listener that announces a new announcement ships only with
		 * pro and registers here, rather than being named from this file.
		 * Reaching this point already means the addon is active, because
		 * `main.php` returns before it otherwise.
		 *
		 * @param \Masteriyo\Addons\CourseAnnouncement\CourseAnnouncementAddon $addon The addon instance.
		 */
		do_action( 'masteriyo_course_announcement_addon_initialized', $this );
	}

	/**
	 * Register namespaces.
	 *
	 * @since 1.6.16
	 *
	 * @param array $namespaces Rest namespaces.
	 * @return array
	 */
	public function register_rest_namespaces( $namespaces ) {
		$namespaces['masteriyo/v1']['course-announcement'] = CourseAnnouncementController::class;
		return $namespaces;
	}

	/**
	 * Register post types.
	 *
	 * @since 1.6.16
	 *
	 * @param array $post_types
	 * @return array
	 */
	public function register_post_types( $post_types ) {
		$post_types['course-announcement'] = CourseAnnouncement::class;
		return $post_types;
	}

	/**
	 * Add course announcement submenu.
	 *
	 * @since 1.6.16
	 *
	 * @param array $submenus Submenus.
	 */
	public function add_course_announcement_submenu( $submenus ) {
		$submenus['course-announcements'] = array(
			'page_title' => __( 'Announcements', 'learning-management-system' ),
			'menu_title' => __( 'Announcements', 'learning-management-system' ),
			'capability' => 'edit_courses',
			'position'   => 60,
		);

		return $submenus;
	}

	/**
	 * Schedule announcement notification to student.
	 *
	 * @since 2.8.0
	 *
	 * @param int $id course announcement id.
	 * @param \Masteriyo\Models\CourseAnnouncement $object The course announcement object.
	 */
	public function schedule_new_announcement_notification_to_student( $id, $course_announcement ) {

		$result = masteriyo_get_setting( 'notification.student.course_announcement' );

		if ( isset( $result['enable'] ) && ! $result['enable'] ) {
			return;
		}

		$users = masteriyo_get_enrolled_users( $course_announcement->get_course_id() );

		if ( ! isset( $users ) ) {
			return;
		}

		foreach ( $users as $user ) {
			$query = new UserCourseQuery(
				array(
					'course_id' => $course_announcement->get_course_id(),
					'user_id'   => $user,
				)
			);

			$user_courses = $query->get_user_courses();

			masteriyo_set_notification( $id, current( $user_courses ), $result );
		}
	}
}
