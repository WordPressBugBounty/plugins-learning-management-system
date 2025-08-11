<?php

/**
 * BuddyPress Group settings class
 *
 * @package Masteriyo\Addons\BuddyPress
 *
 * @since 1.15.0
 */

namespace Masteriyo\Addons\BuddyPress\Classes;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Addons\BuddyPress\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BuddyPressGroupSettings extends \BP_Group_Extension {


	/**
	 * Constructor
	 *
	 * @since 1.15.0
	 *
	 */
	public function __construct() {
		$args = array(
			'slug'            => 'masteriyo-group-course-settings',
			'name'            => __( 'Masteriyo Course Settings', 'learning-management-system' ),
			'enable_nav_item' => false,
		);
		parent::init( $args );
	}

	public function display( $group_id = null ) {}

	/**
	 * settings_screen() is the catch-all method for displaying the content
	 * of the edit, create, and Dashboard admin panels
	 *
	 * @param int $group_id
	 *
	 * @since 1.15.0
	 */
	public function settings_screen( $group_id = null ) {
		$group_status = groups_get_groupmeta( $group_id, 'bp_course_attached', true );
		$activities   = maybe_unserialize( groups_get_groupmeta( $group_id, '_masteriyo_bp_group_activities', true ) );

		$course_query = new \WP_Query(
			array(
				'post_type'      => 'mto-course',
				's'              => '',
				'posts_per_page' => -1,
			)
		);

		if ( ( isset( $course_query->posts ) ) && ( ! empty( $course_query->posts ) ) ) {
			$courses = array_map(
				function ( $post ) {
					return (object) array(
						'ID'         => $post->ID,
						'post_title' => $post->post_title,
					);
				},
				$course_query->posts
			);
		}

		if ( ! empty( $courses ) ) { ?>
			<div class="bp-masteriyo-group-course">
				<h4><?php echo esc_html( __( 'Groups', 'learning-management-system' ) ); ?></h4>
				<select name="bp_group_course" id="bp-group-course">
					<option value="-1"><?php echo esc_html( __( 'Select a course', 'learning-management-system' ) ); ?></option>
					<?php
					foreach ( $courses as $course ) {
						$group_attached = get_post_meta( $course->ID, 'bp_course_group', true );
						if ( ! empty( $group_attached ) && ( '-1' != $group_attached ) && $course->ID != $group_status ) {
							continue;
						}
						?>
						<option value="<?php echo esc_html( $course->ID ); ?>" <?php echo esc_html( ( $course->ID == $group_status ) ) ? 'selected' : ''; ?>><?php echo esc_html( $course->post_title ); ?></option>
						<?php
					}
					?>
				</select>
			</div><br><br />
			<?php
		}
		?>

		<div class="bp-masteriyo-course-activity-checkbox">

			<h4><?php echo esc_html( __( 'Course Activities', 'learning-management-system' ) ); ?></h4>

			<p> <?php echo esc_html( __( 'Which Masteriyo LMS activity should be displayed in this group?', 'learning-management-system' ) ); ?></p>

			<div class="masteriyo-bp-group-activities">

				<label title="<?php esc_attr_e( 'Select to track when a user starts a course', 'learning-management-system' ); ?>">
					<input type="checkbox" name="masteriyo_bp_group_activities[user_course_start]" value="1"
						<?php
						echo esc_html( $this->is_checked( 'user_course_start', $activities ) )
						?>
						> <?php echo esc_html( __( 'Start Course', 'learning-management-system' ) ); ?>
				</label>

				<label title="<?php esc_attr_e( 'Select to track when a user completes a course', 'learning-management-system' ); ?>">
					<input type="checkbox" name="masteriyo_bp_group_activities[user_course_end]" value="1"
						<?php
						echo esc_html( $this->is_checked( 'user_course_end', $activities ) )
						?>
						> <?php echo esc_html( __( 'Complete Course', 'learning-management-system' ) ); ?>
				</label>

				<label title="<?php esc_attr_e( 'Select to track when an instructor creates a new lesson', 'learning-management-system' ); ?>">
					<input type="checkbox" name="masteriyo_bp_group_activities[add_new_lesson]" value="1"
						<?php
						echo esc_html( $this->is_checked( 'add_new_lesson', $activities ) )
						?>
						> <?php echo esc_html( __( 'Create lesson', 'learning-management-system' ) ); ?>
				</label>

				<label title="<?php esc_attr_e( 'Select to track when a user completes a lesson', 'learning-management-system' ); ?>">
					<input type="checkbox" name="masteriyo_bp_group_activities[user_lesson_end]" value="1"
						<?php
						echo esc_html( $this->is_checked( 'user_lesson_end', $activities ) )
						?>
						> <?php echo esc_html( __( 'User completes a lesson', 'learning-management-system' ) ); ?>
				</label>

				<label title="<?php esc_attr_e( 'Select to track when a user completes a quiz', 'learning-management-system' ); ?>">
					<input type="checkbox" name="masteriyo_bp_group_activities[user_quiz_end]" value="1"
						<?php
						echo esc_html( $this->is_checked( 'user_quiz_end', $activities ) )
						?>
						> <?php echo esc_html( __( 'User completes a quiz', 'learning-management-system' ) ); ?>
				</label>

			</div>
		</div><br />

		<?php

	}

	/**
	 * settings_screen_save() contains the catch-all logic for saving
	 * settings from the edit, create, and Dashboard admin panels
	 *
	 * @param int $group_id
	 *
	 * @since 1.15.0
	 */
	public function settings_screen_save( $group_id = null ) {

		$tutor_bp_course_activities = isset( $_POST['masteriyo_bp_group_activities'] ) ? $_POST['masteriyo_bp_group_activities'] : array();

		groups_update_groupmeta( $group_id, '_masteriyo_bp_group_activities', $tutor_bp_course_activities );

		$old_course_id = groups_get_groupmeta( $group_id, 'bp_course_attached', true );

		if ( isset( $_POST['bp_group_course'] ) && ( $_POST['bp_group_course'] ) != '-1' ) {

			if ( ! empty( $old_course_id ) && $old_course_id != $_POST['bp_group_course'] ) {
				delete_post_meta( $old_course_id, 'bp_course_group' );
				groups_delete_groupmeta( $group_id, 'bp_course_attached' );
				Helper::bp_masteriyo_remove_members_group( $old_course_id, $group_id );
			}

			update_post_meta( $_POST['bp_group_course'], 'bp_course_group', $group_id );
			groups_add_groupmeta( $group_id, 'bp_course_attached', $_POST['bp_group_course'] );

			Helper::bp_masteriyo_add_members_group( $_POST['bp_group_course'], $group_id );

			Helper::bp_masteriyo_course_teacher_group_admin( $_POST['bp_group_course'], $group_id );
		} else {
			delete_post_meta( $old_course_id, 'bp_course_group' );
			groups_delete_groupmeta( $group_id, 'bp_course_attached' );
		}
	}

	/**
	 * @param $value
	 * @param $array
	 *
	 * @since 1.15.0
	 *
	 * @return string
	 *
	 * Checked based on given value
	 */
	public function is_checked( $value, $array ) {
		$checked = '';
		if ( is_array( $array ) && array_key_exists( $value, $array ) ) {
			$checked = 'checked';
		}
		return $checked;
	}
}
