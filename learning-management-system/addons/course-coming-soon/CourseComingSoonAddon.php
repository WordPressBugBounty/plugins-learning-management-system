<?php

/**
 * Masteriyo Course Coming Soon setup.
 *
 * @package Masteriyo\CourseComingSoon
 *
 * @since 1.11.0
 */

namespace Masteriyo\Addons\CourseComingSoon;

use Masteriyo\DateTime;
use Masteriyo\Constants;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Database\Model;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Query\CourseProgressQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo Course Coming Soon class.
 *
 * @class Masteriyo\Addons\CourseComingSoon\CourseComingSoon
 */

class CourseComingSoonAddon {

	/**
	 * Initialize the application.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.11.0
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_rest_course_schema', array( $this, 'add_course_coming_soon_schema_to_course' ) );
		add_action( 'masteriyo_new_course', array( $this, 'save_course_coming_soon_data' ), 10, 2 );
		add_action( 'masteriyo_update_course', array( $this, 'save_course_coming_soon_data' ), 10, 2 );
		add_filter( 'masteriyo_rest_response_course_data', array( $this, 'append_course_coming_soon_data_in_response' ), 10, 4 );
		add_action( 'masteriyo_single_course_sidebar_content', array( $this, 'render_course_coming_soon_sidebar_content' ), 15 );

		add_filter( 'masteriyo_get_template', array( $this, 'change_template_for_course_coming_soon' ), 10, 5 );
		add_action( 'masteriyo_after_learn_page_process', array( $this, 'redirect' ) );
		add_filter( 'masteriyo_single_course_start_text', array( $this, 'change_enroll_btn_text' ), 10, 2 );
		add_filter( 'masteriyo_single_course_add_to_cart_text', array( $this, 'change_enroll_btn_text' ), 10, 2 );
		add_filter( 'masteriyo_single_course_continue_text', array( $this, 'change_enroll_btn_text' ), 10, 2 );
		add_filter( 'masteriyo_single_course_completed_text', array( $this, 'change_enroll_btn_text' ), 10, 2 );
		add_filter( 'masteriyo_start_course_url', array( $this, 'update_start_course_url' ), 10, 3 );

		add_filter( 'masteriyo_course_add_to_cart_text', array( $this, 'change_enroll_btn_text' ), 10, 2 );
		add_filter( 'masteriyo_course_add_to_cart_url', array( $this, 'update_add_to_cart_course_url' ), 10, 2 );

		add_filter( 'masteriyo_enroll_button_class', array( $this, 'disable_enroll_button' ), 10, 3 );

		add_action( 'masteriyo_before_single_course_highlights', array( $this, 'render_course_coming_soon_sidebar_content' ), 15 );

		add_action( 'masteriyo_elementor_course_coming_soon_widget', array( $this, 'render_course_coming_soon_sidebar_content' ), 10, 1 );
		add_filter( 'elementor_course_widgets', array( $this, 'append_custom_course_widgets' ), 10 );
	}

	/**
	 * Add course coming soon elementor widget.
	 *
	 * @since 1.12.2
	 *
	 * @param array $widgets
	 * @return array
	 */
	public function append_custom_course_widgets( $widgets ) {
		$widgets[] = new CourseComingSoonMetaWidget();
		return $widgets;
	}

	/**
	 * Add course coming soon fields to course schema.
	 *
	 * @since 1.11.0
	 *
	 * @param array $schema
	 * @return array
	 */
	public function add_course_coming_soon_schema_to_course( $schema ) {
		$schema = wp_parse_args(
			$schema,
			array(
				'course_coming_soon' => array(
					'description' => __( 'Course Coming Soon setting', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'enable'         => array(
								'description' => __( 'Enable course coming soon', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'ending_date'    => array(
								'description' => __( 'Course coming soon end date', 'learning-management-system' ),
								'type'        => 'string',
								'format'      => 'date',
								'context'     => array( 'view', 'edit' ),
							),
							'hide_meta_data' => array(
								'description' => __( 'Enable course meta data', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
							'hide_date_text' => array(
								'description' => __( 'Enable single course text', 'learning-management-system' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			)
		);

		return $schema;
	}

	/**
	 * Save course coming soon data.
	 *
	 * @since 1.11.0
	 *
	 * @param integer $id The course ID.
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	public function save_course_coming_soon_data( $id, $course ) {

		$request = masteriyo_current_http_request();

		if ( null === $request ) {
			return;
		}

		if ( ! isset( $request['course_coming_soon'] ) ) {
			return;
		}

		if ( isset( $request['course_coming_soon']['enable'] ) ) {
			$course->update_meta_data( '_course_coming_soon_enable', masteriyo_string_to_bool( $request['course_coming_soon']['enable'] ) );
		}

		if ( isset( $request['course_coming_soon']['ending_date'] ) ) {
			$ending_date = new DateTime( $request['course_coming_soon']['ending_date'] );
			if ( $ending_date ) {
				$course->update_meta_data( '_course_coming_soon_ending_date', $ending_date->date( DateTime::ISO8601 ) );
				$course->update_meta_data( '_course_coming_soon_timestamp', $ending_date->getTimestamp() );
			}
		}

		if ( isset( $request['course_coming_soon']['hide_meta_data'] ) ) {
			$course->update_meta_data( '_course_coming_soon_hide_meta_data', masteriyo_string_to_bool( $request['course_coming_soon']['hide_meta_data'] ) );
		}

		if ( isset( $request['course_coming_soon']['hide_date_text'] ) ) {
			$course->update_meta_data( '_course_coming_soon_hide_date_text', masteriyo_string_to_bool( $request['course_coming_soon']['hide_date_text'] ) );
		}

		$course->save_meta_data();
	}

	/**
	 * Append course coming soon to course response.
	 *
	 * @since 1.11.0
	 *
	 * @param array $data Course data.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @param \Masteriyo\RestApi\Controllers\Version1\CoursesController $controller REST courses controller object.
	 */
	public function append_course_coming_soon_data_in_response( $data, $course, $context, $controller ) {

		$course_meta = get_post_meta( $course->get_id() );

		$enable = isset( $course_meta['_course_coming_soon_enable'] ) ? $course_meta['_course_coming_soon_enable'] : null;

		if ( ! empty( $enable ) ) {
			$enable = end( $enable );
		} else {
			$enable = null;
		}

		$ending_date = isset( $course_meta['_course_coming_soon_ending_date'] ) ? $course_meta['_course_coming_soon_ending_date'] : null;

		if ( ! empty( $ending_date ) ) {
			$ending_date = end( $ending_date );
		} else {
			$ending_date = null;
		}

		$ending_date = $ending_date ? new DateTime( $ending_date ) : null;

		$hide_meta_data = isset( $course_meta['_course_coming_soon_hide_meta_data'] ) ? $course_meta['_course_coming_soon_hide_meta_data'] : null;

		$hide_date_text = isset( $course_meta['_course_coming_soon_hide_date_text'] ) ? $course_meta['_course_coming_soon_hide_date_text'] : null;

		if ( ! empty( $hide_meta_data ) ) {
			$hide_meta_data = end( $hide_meta_data );
		} else {
			$hide_meta_data = null;
		}

		if ( ! empty( $hide_date_text ) ) {
			$hide_date_text = end( $hide_date_text );
		} else {
			$hide_date_text = null;
		}

		$data['course_coming_soon'] = array(
			'enable'         => masteriyo_string_to_bool( $enable ),
			'ending_date'    => masteriyo_rest_prepare_date_response( $ending_date ),
			'hide_meta_data' => masteriyo_string_to_bool( $hide_meta_data ),
			'hide_date_text' => masteriyo_string_to_bool( $hide_date_text ),
		);

		return $data;
	}

	/**
	 * Change template for courses template in single course page.
	 *
	 * @since 1.11.0
	 *
	 * @param string $template Template path.
	 * @param string $template_name Template name.
	 * @param array $args Template arguments.
	 * @param string $template_path Template path from function parameter.
	 * @param string $default_path Default templates directory path.
	 *
	 * @return string
	 */
	public function change_template_for_course_coming_soon( $template, $template_name, $args, $template_path, $default_path ) {

		if ( 'course-coming-soon/courses.php' !== $template_name ) {
			return $template;
		}

		if ( file_exists( $template ) ) {
			return $template;
		}

		return trailingslashit( Constants::get( 'MASTERIYO_COURSE_COMING_SOON_TEMPLATES' ) ) . 'courses.php';
	}

	/**
	 * Render course coming soon sidebar content.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @return void
	 */
	public function render_course_coming_soon_sidebar_content( $course ) {

		if ( is_null( $course ) ) {
			return;
		}

		if ( ! $this->is_enabled( $course ) ) {
			return;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
				'status'    => array( CourseProgressStatus::PROGRESS ),
			)
		);

		$activity = current( $query->get_course_progress() );

		if ( $activity ) {
			return;
		}

		$satisfied = Helper::course_coming_soon_satisfied( $course );

		if ( $satisfied ) {
			return;
		}

		$course_meta = get_post_meta( $course->get_id() );

		$ending_date = $course_meta['_course_coming_soon_ending_date'];

		if ( ! empty( $ending_date ) ) {
			$ending_date = end( $ending_date );
		} else {
			$ending_date = null;
		}

		$display_ending_date = strtotime( $ending_date );
		$display_ending_date = gmdate( 'F j, Y', $display_ending_date );

		$ending_date = $ending_date ? new DateTime( $ending_date ) : null;
		$ending_date = masteriyo_rest_prepare_date_response( $ending_date );
		$ending_date = new DateTime( $ending_date );
		$ending_date = $ending_date->format( 'F j, Y H:i:s' );

		$hide_meta_data = $course_meta['_course_coming_soon_hide_meta_data'] ? $course_meta['_course_coming_soon_hide_meta_data'] : null;

		$hide_date_text = $course_meta['_course_coming_soon_hide_date_text'] ? $course_meta['_course_coming_soon_hide_date_text'] : null;

		if ( ! empty( $hide_meta_data ) ) {
			$hide_meta_data = end( $hide_meta_data );
		}

		if ( ! empty( $hide_date_text ) ) {
			$hide_date_text = end( $hide_date_text );
		}

		if ( $hide_meta_data ) {
			remove_action( 'masteriyo_single_course_sidebar_content', 'masteriyo_single_course_stats', 20 );
			remove_action( 'masteriyo_layout_1_single_course_aside_items', 'masteriyo_single_course_layout_1_stats', 20 );
			remove_action( 'masteriyo_course_archive_layout_1_meta_data', 'masteriyo_course_archive_layout_1_stats', 20 );
			remove_action( 'masteriyo_course_archive_layout_2_meta_data', 'masteriyo_course_archive_layout_2_stats', 20 );
		}

		if ( $course ) {
			masteriyo_get_template(
				'course-coming-soon/courses.php',
				array(
					'course'              => $course,
					'display_ending_date' => $display_ending_date,
					'ending_date'         => $ending_date,
					'hide_date_text'      => $hide_date_text,
				)
			);
		}
	}

	/**
	 * Disable enroll button.
	 *
	 * @since 1.11.0
	 *
	 * @param string[] $class An array of class names.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param \Masteriyo\Models\CourseProgress $progress Course progress object.
	 *
	 * @return string[]
	 */
	public function disable_enroll_button( $class, $course, $progress ) {

		if ( is_null( $course ) ) {
			return $class;
		}

		if ( ! $this->is_enabled( $course ) ) {
			return $class;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
				'status'    => array( CourseProgressStatus::PROGRESS ),
			)
		);

		$activity = current( $query->get_course_progress() );

		if ( $activity ) {
			return $class;
		}

		$satisfied = Helper::course_coming_soon_satisfied( $course );

		if ( $satisfied ) {
			return $class;
		}

		$class = array_filter(
			$class,
			function ( $c ) {
				return 'masteriyo-btn-primary' !== $c && 'masteriyo-btn' !== $c;
			}
		);

		$class[] = 'masteriyo-btn-disabled';
		$class[] = 'masteriyo-single-course--course-coming-soon-btn';

		return $class;
	}


	/**
	 * Return if course coming soon is enabled.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @return boolean
	 */
	public function is_enabled( $course ) {

		if ( ! $course ) {
			return false;
		}

		$course_meta = get_post_meta( $course->get_id() );

		if ( ! isset( $course_meta['_course_coming_soon_enable'] ) ) {
			return false;
		}

		$enable = $course_meta['_course_coming_soon_enable'];

		if ( ! empty( $enable ) ) {
			$enable = end( $enable );
		} else {
			$enable = null;
		}
		return masteriyo_string_to_bool( $enable );
	}

	/**
	 * Redirect to single course page when the learn page of course whose course coming soon is active.
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 *
	 * @since 1.11.0
	 */
	public function redirect( $course ) {
		if ( is_null( $course ) ) {
			return;
		}

		$satisfied = Helper::course_coming_soon_satisfied( $course );

		if ( $satisfied ) {
			return;
		}

		if ( ! $this->is_enabled( $course ) ) {
			return;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
				'status'    => array( CourseProgressStatus::PROGRESS ),
			)
		);

		$activity = current( $query->get_course_progress() );

		if ( $activity ) {
			return;
		}

		if ( masteriyo_is_learn_page() && $satisfied ) {
			return;
		}

		wp_safe_redirect( $course->get_permalink() );
		exit();
	}

	/**
	 * Prepend clock sign to enroll button if course coming soon is active.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	public function change_enroll_btn_text( $text, $course ) {

		if ( is_null( $course ) ) {
			return $text;
		}

		if ( ! $this->is_enabled( $course ) ) {
			return $text;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
				'status'    => array( CourseProgressStatus::PROGRESS ),
			)
		);

		$activity = current( $query->get_course_progress() );

		if ( $activity ) {
			return $text;
		}

		if ( is_null( $course ) ) {
			return $text;
		}

		$satisfied = Helper::course_coming_soon_satisfied( $course );

		if ( $satisfied ) {
			return $text;
		}

		$text = __( 'Coming Soon', 'learning-management-system' );

		return $text;
	}

	/**
	 * Update buy now url.
	 *
	 * @since 1.11.0
	 *
	 * @param string $url Buy Now URL.
	 * @param Masteriyo\Models\Course $course Course object.
	 * @return string
	 */
	public function update_add_to_cart_course_url( $url, $course ) {
		if ( is_null( $course ) ) {
			return $url;
		}

		if ( ! $this->is_enabled( $course ) ) {
			return $url;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
				'status'    => array( CourseProgressStatus::PROGRESS ),
			)
		);

		$activity = current( $query->get_course_progress() );

		if ( $activity ) {
			return $url;
		}

		$satisfied = Helper::course_coming_soon_satisfied( $course );

		if ( $satisfied ) {
			return $url;
		}

		return $course->get_permalink();
	}

	/**
	 * Update start course url.
	 *
	 * @since 1.11.0
	 *
	 * @param string $url Start course URL.
	 * @param Masteriyo\Models\Course $course Course object.
	 * @param boolean $append_first_lesson_or_quiz Whether to append first lesson or quiz or not.
	 * @return string
	 */
	public function update_start_course_url( $url, $course, $append_first_lesson_or_quiz ) {
		if ( is_null( $course ) ) {
			return $url;
		}

		if ( ! $this->is_enabled( $course ) ) {
			return $url;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
				'status'    => array( CourseProgressStatus::PROGRESS ),
			)
		);

		$activity = current( $query->get_course_progress() );

		if ( $activity ) {
			return $url;
		}

		$satisfied = Helper::course_coming_soon_satisfied( $course );

		if ( $satisfied ) {
			return $url;
		}

		return $course->get_permalink();
	}
}
