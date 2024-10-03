<?php

/**
 * Abstract Setting API Class
 *
 * Admin Settings API used by Integrations, Shipping Methods, and Payment Gateways.
 *
 * @since 1.0.0
 *
 * @package  Masteriyo\Models
 */

namespace Masteriyo\Models;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Database\Model;
use Masteriyo\Repository\SettingRepository;

/**
 * Setting class.r
 */
class Setting extends Model {


	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'setting';

	/**
	 * Callbacks for sanitize.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $sanitize_callbacks = array(
		'general'        => array(
			'pages'         => array(
				'account_page_id'                 => 'absint',
				'courses_page_id'                 => 'absint',
				'checkout_page_id'                => 'absint',
				'learn_page_id'                   => 'absint',
				'instructor_registration_page_id' => 'absint',
				'course_thankyou_page'            => array(
					'display_type' => 'string',
					'page_id'      => 'absint',
					'custom_url'   => 'string',
				),
				'instructors_list_page_id'        => 'absint',
			),
			'course_access' => array(
				'enable_course_content_access_without_enrollment' => 'masteriyo_string_to_bool',
				'restrict_instructors' => 'masteriyo_string_to_bool',
			),
			'registration'  => array(
				'enable_student_registration'    => 'masteriyo_string_to_bool',
				'enable_instructor_registration' => 'masteriyo_string_to_bool',
				'enable_guest_checkout'          => 'masteriyo_string_to_bool',
			),
			'editor'        => array(
				'default_editor' => 'string',
			),
			'player'        => array(
				'enable_watch_full_video'            => 'masteriyo_string_to_bool',
				'enable_watch_full_video_every_time' => 'masteriyo_string_to_bool',
				'use_masteriyo_player_for_youtube'   => 'masteriyo_string_to_bool',
				'use_masteriyo_player_for_vimeo'     => 'masteriyo_string_to_bool',
				'seek_time'                          => 'absint',
			),
		),
		'learn_page'     => array(
			'general' => array(
				'logo'                   => 'absint',
				'auto_load_next_content' => 'masteriyo_string_to_bool',
				'lesson_video_url_type'  => 'sanitize_text_field',

			),
			'display' => array(
				'enable_questions_answers' => 'masteriyo_string_to_bool',
				'enable_focus_mode'        => 'masteriyo_string_to_bool',
				'show_sidebar'             => 'masteriyo_string_to_bool',
				'show_header'              => 'masteriyo_string_to_bool',

			),
		),
		'payments'       => array(
			'currency' => array(
				'number_of_decimals' => 'absint',
			),
		),
		'course_archive' => array(
			'display'               => array(
				'view_mode'     => 'sanitize_title',
				'enable_search' => 'masteriyo_string_to_bool',
				'per_page'      => 'absint',
				'per_row'       => 'absint',
				'order_by'      => 'sanitize_text_filed',
				'order'         => 'sanitize_text_filed',
			),
			'components_visibility' => array(
				'thumbnail'          => 'masteriyo_string_to_bool',
				'difficulty_badge'   => 'masteriyo_string_to_bool',
				'featured_ribbon'    => 'masteriyo_string_to_bool',
				'categories'         => 'masteriyo_string_to_bool',
				'course_title'       => 'masteriyo_string_to_bool',
				'author'             => 'masteriyo_string_to_bool',
				'author_avatar'      => 'masteriyo_string_to_bool',
				'author_name'        => 'masteriyo_string_to_bool',
				'rating'             => 'masteriyo_string_to_bool',
				'course_description' => 'masteriyo_string_to_bool',
				'metadata'           => 'masteriyo_string_to_bool',
				'course_duration'    => 'masteriyo_string_to_bool',
				'students_count'     => 'masteriyo_string_to_bool',
				'lessons_count'      => 'masteriyo_string_to_bool',
				'card_footer'        => 'masteriyo_string_to_bool',
				'price'              => 'masteriyo_string_to_bool',
				'enroll_button'      => 'masteriyo_string_to_bool',
				'seats_for_students' => 'masteriyo_string_to_bool',
			),
			'custom_template'       => array(
				'enable'          => 'masteriyo_string_to_bool',
				'template_source' => 'sanitize_title',
				'template_id'     => 'absint',
			),
			'layout'                => 'sanitize_text_field',
			'course_card_styles'    => array(
				'button_size'            => 'absint',
				'button_radius'          => 'absint',
				'course_title_font_size' => 'absint',
				'highlight_side'         => 'sanitize_title',
			),
		),
		'single_course'  => array(
			'display'         => array(
				'enable_review'                     => 'masteriyo_string_to_bool',
				'enable_review_enrolled_users_only' => 'masteriyo_string_to_bool',
				'auto_approve_reviews'              => 'masteriyo_string_to_bool',
				'course_visibility'                 => 'masteriyo_string_to_bool',
			),
			'related_courses' => array(
				'enable' => 'masteriyo_string_to_bool',
			),
			'custom_template' => array(
				'enable'          => 'masteriyo_string_to_bool',
				'template_source' => 'sanitize_title',
				'template_id'     => 'absint',
			),
			'layout'          => 'sanitize_text_field',
		),
		'advance'        => array(
			'permalinks' => array(
				'category_base'           => 'sanitize_title',
				'tag_base'                => 'sanitize_title',
				'difficulty_base'         => 'sanitize_title',
				'single_course_permalink' => 'sanitize_text',
			),

			'checkout'   => array(
				'pay'                        => 'sanitize_title',
				'order_received'             => 'sanitize_title',
				'add_payment_method'         => 'sanitize_title',
				'delete_payment_method'      => 'sanitize_title',
				'set_default_payment_method' => 'sanitize_title',
			),
		),
		'quiz'           => array(
			'display' => array(
				'quiz_completion_button' => 'masteriyo_string_to_bool',
				'quiz_review_visibility' => 'masteriyo_string_to_bool',
			),
			'styling' => array(
				'questions_display_per_page' => 'absint',
			),
			'general' => array(
				'quiz_access'               => 'sanitize_text_field',
				'automatically_submit_quiz' => 'masteriyo_string_to_bool',
			),
		),
		'payments'       => array(
			'offline'         => array(
				'enable' => 'masteriyo_string_to_bool',
			),
			'paypal'          => array(
				'enable'                  => 'masteriyo_string_to_bool',
				'ipn_email_notifications' => 'masteriyo_string_to_bool',
				'sandbox'                 => 'masteriyo_string_to_bool',
				'debug'                   => 'masteriyo_string_to_bool',
			),
			'checkout_fields' => array(
				'address_1'         => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'address_2'         => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'company'           => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'country'           => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'customer_note'     => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'attachment_upload' => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'phone'             => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'postcode'          => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'state'             => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'city'              => array(
					'enable' => 'masteriyo_string_to_bool',
				),
			),
		),
		'emails'         => array(
			'general'    => array(
				'enable' => 'masteriyo_string_to_bool',
			),
			'admin'      => array(
				'new_order'        => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'instructor_apply' => array(
					'enable' => 'masteriyo_string_to_bool',
				),
			),
			'instructor' => array(
				'instructor_registration'   => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'instructor_apply_approved' => array(
					'enable' => 'masteriyo_string_to_bool',
				),
			),
			'student'    => array(
				'student_registration'      => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'completed_order'           => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'onhold_order'              => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'cancelled_order'           => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'completed_course'          => array(
					'enable' => 'masteriyo_string_to_bool',
				),
				'instructor_apply_rejected' => array(
					'enable' => 'masteriyo_string_to_bool',
				),
			),
		),
		'advance'        => array(
			'debug'               => array(
				'template_debug' => 'masteriyo_string_to_bool',
				'debug'          => 'masteriyo_string_to_bool',
				'enable_logger'  => 'masteriyo_string_to_bool',
			),
			'uninstall'           => array(
				'remove_data' => 'masteriyo_string_to_bool',
			),
			'tracking'            => array(
				'allow_usage' => 'masteriyo_string_to_bool',
			),
			'gdpr'                => array(
				'enable'  => 'masteriyo_string_to_bool',
				'message' => 'sanitize_text_field',
			),
			'openai'              => array(
				'api_key' => 'sanitize_text_field',
			),
			'email_verification'  => array(
				'enable' => 'masteriyo_string_to_bool',
			),
			'qr_login'            => array(
				'enable'            => 'masteriyo_string_to_bool',
				'attention_message' => 'sanitize_text_field',
			),
			'limit_login_session' => 'sanitize_number_field',
		),
		'accounts_page'  => array(
			'display' => array(
				'enable_history_page'     => 'masteriyo_string_to_bool',
				'enable_invoice'          => 'masteriyo_string_to_bool',
				'enable_profile_page'     => 'masteriyo_string_to_bool',
				'enable_instructor_apply' => 'masteriyo_string_to_bool',
				'enable_edit_profile'     => 'masteriyo_string_to_bool',
				'enable_certificate_page' => 'masteriyo_string_to_bool',
				'layout'                  => array(
					'enable_header_footer' => 'masteriyo_string_to_bool',
				),
			),
		),
		'notification'   => array(
			'student' => array(
				'course_enroll'   => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'course_complete' => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'created_order'   => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'completed_order' => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'onhold_order'    => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'cancelled_order' => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
			),
		),
	);

	/**
	 * The posted settings data. When empty, $_POST data will be used.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $data = array();
	/**
	 * Get the setting if ID
	 *
	 * @since 1.0.0
	 *
	 * @param SettingRepository $setting_repository Setting Repository,
	 */
	public function __construct( SettingRepository $setting_repository ) {
		$this->data       = masteriyo_get_default_settings();
		$this->repository = $setting_repository;
		$this->set_default_values();
	}

	/**
	 * Set default values.
	 *
	 * @since 1.3.4
	 */
	protected function set_default_values() {
		if ( empty( trim( strval( $this->get( 'email.general.from_email' ) ) ) ) ) {
			$this->set( 'emails.general.from_email', get_bloginfo( 'admin_email' ) );
		}

		if ( empty( trim( strval( $this->get( 'email.general.from_name' ) ) ) ) ) {
			$this->set( 'emails.general.from_name', get_bloginfo( 'name' ) );
		}
	}

	/**
	 * Get data.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Set data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data
	 */
	public function set_data( $data ) {
		$data_dot_arr = masteriyo_array_dot( $data );

		foreach ( $data_dot_arr as $prop => $value ) {
			$this->set( $prop, $value );
		}

		$this->set_default_values();
	}

	/**
	 * Sanitize the settings
	 *
	 * @since 1.0.0
	 *
	 * @param string $prop    Name of prop to set.
	 * @param mixed  $value   Value of the prop.
	 *
	 * @return mixed
	 */
	public function sanitize( $prop, $value ) {
			$callback = masteriyo_array_get( $this->sanitize_callbacks, $prop );

		if ( is_callable( $callback ) ) {
			$value = call_user_func_array( $callback, array( $value ) );
		}

			return $value;
	}

		/**
		 * Sets a prop for a setter method.
		 *
		 * @since 1.0.0
		 * @param string $prop    Name of prop to set.
		 * @param mixed  $value   Value of the prop.
		 */
	public function set( $prop, $value ) {
		$value = $this->sanitize( $prop, $value );
		masteriyo_array_set( $this->data, $prop, $value );
	}

		/**
		 * Gets a prop for a getter method.
		 *
		 * @since  1.0.0
		 * @param  string $prop Name of prop to get.
		 * @param  string $context What the value is for. Valid values are 'view' and 'edit'. What the value is for. Valid values are view and edit.
		 * @return mixed
		 */
	public function get( $prop, $context = 'view' ) {
		if ( empty( $prop ) ) {
			$value = $this->data;
		} else {
			$value = masteriyo_array_get( $this->data, $prop );
		}

		if ( 'view' === $context ) {
			/**
			 * Filters setting value.
			 *
			 * @since 1.0.0
			 *
			 * @param mixed $value Setting value.
			 * @param string $prop Setting name.
			 * @param Masteriyo\Models\Setting $setting Setting object.
			 */
			$value = apply_filters( 'masteriyo_get_setting_value', $value, $prop, $this );
		}

		return $value;
	}

		/**
		 * Reset defaults.
		 *
		 * @since 1.4.2
		 */
	public function reset() {
		$setting    = masteriyo( 'setting' );
		$this->data = $setting->get_data();
	}
}
