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
				'course_bundles_page_id'          => 'absint',
				'course_thankyou_page'            => array(
					'display_type' => 'string',
					'page_id'      => 'absint',
					'custom_url'   => 'string',
				),
				'after_checkout_page'             => array(
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
				'enable_adding_notes'                => 'masteriyo_string_to_bool',
				'use_masteriyo_player_for_youtube'   => 'masteriyo_string_to_bool',
				'use_masteriyo_player_for_vimeo'     => 'masteriyo_string_to_bool',
				'show_brand_logo'                    => 'masteriyo_string_to_bool',
				'seek_time'                          => 'absint',
				'video_completion_percentage'        => 'absint',
				'youtube_api_key'                    => 'sanitize_text_field',
				'unmuted_autoplay'                   => 'masteriyo_string_to_bool',
			),
			'styling'       => array(
				'primary_color'                => 'sanitize_text_field',
				'primary_color_for_learn_page' => 'sanitize_text_field',
				'button_color'                 => 'sanitize_text_field',
				'button_hover_color'           => 'sanitize_text_field',
				'theme'                        => 'sanitize_text_field',
			),

		),
		'learn_page'     => array(
			'general' => array(
				'logo'                      => 'absint',
				'auto_load_next_content'    => 'masteriyo_string_to_bool',
				'enable_content_protection' => 'masteriyo_string_to_bool',
				'lesson_video_url_type'     => 'sanitize_text_field',
			),
			'display' => array(
				'enable_questions_answers' => 'masteriyo_string_to_bool',
				'enable_focus_mode'        => 'masteriyo_string_to_bool',
				'show_sidebar'             => 'masteriyo_string_to_bool',
				'show_header'              => 'masteriyo_string_to_bool',
				'enable_lesson_comment'    => 'masteriyo_string_to_bool',
				'auto_approve_comments'    => 'masteriyo_string_to_bool',
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
			'filters_and_sorting'   => array(
				'enable_ajax'                    => 'masteriyo_string_to_bool',
				'enable_filters'                 => 'masteriyo_string_to_bool',
				'enable_category_filter'         => 'masteriyo_string_to_bool',
				'enable_difficulty_level_filter' => 'masteriyo_string_to_bool',
				'enable_price_type_filter'       => 'masteriyo_string_to_bool',
				'enable_price_filter'            => 'masteriyo_string_to_bool',
				'enable_rating_filter'           => 'masteriyo_string_to_bool',
				'enable_sorting'                 => 'masteriyo_string_to_bool',
				'enable_date_sorting'            => 'masteriyo_string_to_bool',
				'enable_price_sorting'           => 'masteriyo_string_to_bool',
				'enable_rating_sorting'          => 'masteriyo_string_to_bool',
				'enable_course_title_sorting'    => 'masteriyo_string_to_bool',
			),
			'components_visibility' => array(
				'single_course_visibility' => 'masteriyo_string_to_bool',
				'thumbnail'                => 'masteriyo_string_to_bool',
				'difficulty_badge'         => 'masteriyo_string_to_bool',
				'featured_ribbon'          => 'masteriyo_string_to_bool',
				'categories'               => 'masteriyo_string_to_bool',
				'course_title'             => 'masteriyo_string_to_bool',
				'author'                   => 'masteriyo_string_to_bool',
				'author_avatar'            => 'masteriyo_string_to_bool',
				'author_name'              => 'masteriyo_string_to_bool',
				'rating'                   => 'masteriyo_string_to_bool',
				'course_description'       => 'masteriyo_string_to_bool',
				'metadata'                 => 'masteriyo_string_to_bool',
				'course_duration'          => 'masteriyo_string_to_bool',
				'students_count'           => 'masteriyo_string_to_bool',
				'lessons_count'            => 'masteriyo_string_to_bool',
				'card_footer'              => 'masteriyo_string_to_bool',
				'price'                    => 'masteriyo_string_to_bool',
				'enroll_button'            => 'masteriyo_string_to_bool',
				'seats_for_students'       => 'masteriyo_string_to_bool',
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
			'display'               => array(
				'enable_review'                     => 'masteriyo_string_to_bool',
				'enable_review_visibility_control'  => 'masteriyo_string_to_bool',
				'enable_review_enrolled_users_only' => 'masteriyo_string_to_bool',
				'auto_approve_reviews'              => 'masteriyo_string_to_bool',
				'review_after_course_completion'    => 'masteriyo_string_to_bool',
				'course_visibility'                 => 'masteriyo_string_to_bool',
				'show_curriculum'                   => 'masteriyo_string_to_bool',
			),
			'related_courses'       => array(
				'enable'            => 'masteriyo_string_to_bool',

				/**
				 * Pro.
				 *
				 * @since 2.3.1
				 */
				'limit'             => 'absint',
				'related_attribute' => 'sanitize_text',
			),
			'components_visibility' => array(
				'single_course_visibility' => 'masteriyo_string_to_bool',
				'thumbnail'                => 'masteriyo_string_to_bool',
				'difficulty_badge'         => 'masteriyo_string_to_bool',
				'featured_ribbon'          => 'masteriyo_string_to_bool',
				'categories'               => 'masteriyo_string_to_bool',
				'course_title'             => 'masteriyo_string_to_bool',
				'author_avatar'            => 'masteriyo_string_to_bool',
				'author_name'              => 'masteriyo_string_to_bool',
				'rating'                   => 'masteriyo_string_to_bool',
				'course_description'       => 'masteriyo_string_to_bool',
				'course_duration'          => 'masteriyo_string_to_bool',
				'students_count'           => 'masteriyo_string_to_bool',
				'lessons_count'            => 'masteriyo_string_to_bool',
				'price'                    => 'masteriyo_string_to_bool',
				'enroll_button'            => 'masteriyo_string_to_bool',
				'seats_for_students'       => 'masteriyo_string_to_bool',
			),
			'custom_template'       => array(
				'enable'          => 'masteriyo_string_to_bool',
				'template_source' => 'sanitize_title',
				'template_id'     => 'absint',
			),
			'layout'                => 'sanitize_text_field',
		),
		'quiz'           => array(
			'display' => array(
				'quiz_completion_button' => 'masteriyo_string_to_bool',
				'quiz_review_visibility' => 'masteriyo_string_to_bool',
				'quiz_previous_page'     => 'masteriyo_string_to_bool',
			),
			'styling' => array(
				'questions_display_per_page' => 'absint',
			),
			'general' => array(
				'grading'                   => 'sanitize_key',
				'quiz_access'               => 'sanitize_text_field',
				'automatically_submit_quiz' => 'masteriyo_string_to_bool',
			),
		),
		'payments'       => array(
			'enabled'         => 'sanitize_text_field',
			'currency'        => array(
				'number_of_decimals' => 'absint',
			),
			'offline'         => array(
				'enable' => 'masteriyo_string_to_bool',
			),
			'paypal'          => array(
				'enable'                  => 'masteriyo_string_to_bool',
				'ipn_email_notifications' => 'masteriyo_string_to_bool',
				'sandbox'                 => 'masteriyo_string_to_bool',
				'debug'                   => 'masteriyo_string_to_bool',
			),
			// Each of these settings is a bare boolean, so the callback belongs on the key
			// itself. Nesting it under an `enable` the data never had made sanitize() look up
			// a path holding an array where it wanted a callable, and silently skip.
			'checkout_fields' => array(
				'address_1'         => 'masteriyo_string_to_bool',
				'address_2'         => 'masteriyo_string_to_bool',
				'company'           => 'masteriyo_string_to_bool',
				'country'           => 'masteriyo_string_to_bool',
				'customer_note'     => 'masteriyo_string_to_bool',
				'attachment_upload' => 'masteriyo_string_to_bool',
				'phone'             => 'masteriyo_string_to_bool',
				'postcode'          => 'masteriyo_string_to_bool',
				'state'             => 'masteriyo_string_to_bool',
				'city'              => 'masteriyo_string_to_bool',
			),
		),
		'emails'         => array(
			'general'    => array(
				'enable'            => 'masteriyo_string_to_bool',
				'from_name'         => 'sanitize_text_field',
				'from_email'        => 'sanitize_email',
				'content'           => 'wp_kses_post',
				'body_bg_color'     => 'masteriyo_sanitize_css_color',
				'body_text_color'   => 'masteriyo_sanitize_css_color',
				'header_bg_color'   => 'masteriyo_sanitize_css_color',
				'button_bg_color'   => 'masteriyo_sanitize_css_color',
				'button_text_color' => 'masteriyo_sanitize_css_color',
			),
			'admin'      => array(
				'new_order'                => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'instructor_registration'  => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'student_registration'     => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'course_start'             => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'course_completion'        => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'instructor_apply'         => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'new_lesson_comment'       => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'new_lesson_comment_reply' => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'new_quiz_attempt'         => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'manual_course_completion' => array(
					'enable' => 'masteriyo_string_to_bool',
				),
			),
			'instructor' => array(
				'instructor_registration'   => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'course_start'              => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'course_completion'         => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'instructor_apply_approved' => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'new_lesson_comment'        => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'new_lesson_comment_reply'  => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'new_quiz_attempt'          => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'new_question'              => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'manual_course_completion'  => array(
					'enable' => 'masteriyo_string_to_bool',
				),
			),
			'student'    => array(
				'student_registration'         => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'automatic_registration'       => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'completed_order'              => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'onhold_order'                 => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'cancelled_order'              => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'completed_course'             => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'instructor_apply_rejected'    => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'group_course_enroll'          => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'group_joining'                => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'group_member_removed'         => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'group_published'              => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'zoom_session_reminder'        => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
					'reminder_offset'  => 'absint',
				),
				'google_meet_session_reminder' => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
					'reminder_offset'  => 'absint',
				),
				'new_question_reply'           => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'new_lesson_comment_reply'     => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'manual_course_completion'     => array(
					'enable' => 'masteriyo_string_to_bool',
				),
			),
			'everyone'   => array(
				'password_reset'                => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'email_verification'            => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
				'two_factor_authentication_otp' => array(
					'enable'           => 'masteriyo_string_to_bool',
					'subject'          => 'sanitize_text_field',
					'from_address'     => 'sanitize_text_field',
					'from_name'        => 'sanitize_text_field',
					'reply_to_address' => 'sanitize_text_field',
					'reply_to_name'    => 'sanitize_text_field',
					'to_address'       => 'sanitize_text_field',
					'content'          => 'wp_kses_post',
				),
			),
		),
		'authentication' => array(
			'email_verification'        => array(
				'enable' => 'masteriyo_string_to_bool',
			),
			'limit_login_session'       => 'sanitize_number_field',
			'qr_login'                  => array(
				'enable'            => 'masteriyo_string_to_bool',
				'attention_message' => 'sanitize_text_field',
			),
			'two_factor_authentication' => array(
				'enable' => 'masteriyo_string_to_bool',
			),
		),
		'advance'        => array(
			'permalinks'        => array(
				'category_base'           => 'sanitize_title',
				'tag_base'                => 'sanitize_title',
				'difficulty_base'         => 'sanitize_title',
				'single_course_permalink' => 'sanitize_text',
			),
			'checkout'          => array(
				'pay'                        => 'sanitize_title',
				'order_received'             => 'sanitize_title',
				'add_payment_method'         => 'sanitize_title',
				'delete_payment_method'      => 'sanitize_title',
				'set_default_payment_method' => 'sanitize_title',
			),
			'debug'             => array(
				'enable_logger' => 'masteriyo_string_to_bool',
			),
			'uninstall'         => array(
				'remove_data' => 'masteriyo_string_to_bool',
			),
			'tracking'          => array(
				'allow_usage'                   => 'masteriyo_string_to_bool',
				'subscribe_updates'             => 'masteriyo_string_to_bool',
				'email'                         => 'sanitize_email',
				'enable_user_activity_tracking' => 'masteriyo_string_to_bool',
				'server_sync_interval_duration' => 'absint',
				'idle_time_threshold'           => 'absint',
			),
			'gdpr'              => array(
				'enable'  => 'masteriyo_string_to_bool',
				'message' => 'sanitize_text_field',
			),
			'openai'            => array(
				'api_key'          => 'sanitize_text_field',
				'enable'           => 'masteriyo_string_to_bool',
				'model'            => 'sanitize_text_field',
				'reasoning_effort' => 'sanitize_text_field',
				'grading_feedback' => 'masteriyo_string_to_bool',
			),
			'password_strength' => array(
				'enable' => 'masteriyo_string_to_bool',
			),
		),
		'accounts_page'  => array(
			'display' => array(
				'enable_assignments_page'    => 'masteriyo_string_to_bool',
				'enable_certificate_page'    => 'masteriyo_string_to_bool',
				'enable_gradebook_page'      => 'masteriyo_string_to_bool',
				'enable_history_page'        => 'masteriyo_string_to_bool',
				'enable_quiz_attempts_page'  => 'masteriyo_string_to_bool',
				'enable_subscriptions_page'  => 'masteriyo_string_to_bool',
				'enable_zoom_session'        => 'masteriyo_string_to_bool',
				'enable_invoice'             => 'masteriyo_string_to_bool',
				'enable_instructor_apply'    => 'masteriyo_string_to_bool',
				'instructor_max_attempts'    => 'absint',
				'enable_profile_page'        => 'masteriyo_string_to_bool',
				'enable_edit_profile'        => 'masteriyo_string_to_bool',
				'enable_google_meet'         => 'masteriyo_string_to_bool',
				'enable_calendar'            => 'masteriyo_string_to_bool',
				'enable_session_information' => 'masteriyo_string_to_bool',
				'layout'                     => array(
					'enable_header_footer' => 'masteriyo_string_to_bool',
				),
			),
		),
		'notification'   => array(
			'student' => array(
				'course_enroll'       => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'course_complete'     => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'created_order'       => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'completed_order'     => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'onhold_order'        => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'cancelled_order'     => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'quiz_attempt'        => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'course_qa'           => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'lesson_comment'      => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'assignment_reply'    => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'course_announcement' => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'zoom'                => array(
					'enable'  => 'masteriyo_string_to_bool',
					'content' => 'wp_kses_post',
				),
				'content_drip'        => array(
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
	protected $data = array();


	protected $manual_modifications = array();

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
	 * Mark a setting as manually modified
	 *
	 * @param string $prop Setting property
	 */
	public function mark_as_modified( $prop ) {
		$this->manual_modifications[ $prop ] = true;
	}

	/**
	 * Check if a setting has been manually modified
	 *
	 * @param string $prop Setting property
	 * @return bool
	 */
	protected function is_manually_modified( $prop ) {
		return isset( $this->manual_modifications[ $prop ] );
	}


	/**
	 * Set default values.
	 *
	 * @since 1.3.4
	 */
	protected function set_default_values() {
		if ( empty( trim( strval( $this->get( 'emails.general.from_email' ) ) ) ) && ! $this->is_manually_modified( 'emails.general.from_email' ) ) {
			$this->set( 'emails.general.from_email', get_bloginfo( 'admin_email' ) );
		}

		if ( empty( trim( strval( $this->get( 'emails.general.from_name' ) ) ) ) && ! $this->is_manually_modified( 'emails.general.from_name' ) ) {
			$this->set( 'emails.general.from_name', get_bloginfo( 'name' ) );
		}

		if ( empty( trim( strval( $this->get( 'emails.general.header_logo.url' ) ) ) ) && ! $this->is_manually_modified( 'emails.general.header_logo.url' ) ) {
			$this->set( 'emails.general.header_logo.url', masteriyo_get_plugin_url() . '/assets/img/masteriyo-email-template-logo.png' );
		}

		if ( empty( trim( strval( $this->get( 'emails.general.header_bg_img.url' ) ) ) ) && ! $this->is_manually_modified( 'emails.general.header_bg_img.url' ) ) {
			$this->set( 'emails.general.header_bg_img.url', masteriyo_get_plugin_url() . '/assets/img/email-template-header-bg-img.png' );
		}

		if ( empty( trim( strval( $this->get( 'emails.general.footer_text' ) ) ) ) && ! $this->is_manually_modified( 'emails.general.footer_text' ) ) {
			$this->set( 'emails.general.footer_text', masteriyo_get_email_footer_text() );
		}

		$emails_settings = $this->get( 'emails' );
		foreach ( masteriyo_get_default_email_contents() as $key => $emails_setting ) {
			if ( 'general' === $key ) {
				continue;
			}

			$role_wise_settings = $this->get( "emails.{$key}" );

			foreach ( $emails_setting as $role_key => $role_wise_setting ) {
				$reply_to_email_key = "emails.{$key}.{$role_key}.reply_to_address";
				$reply_to_name_key  = "emails.{$key}.{$role_key}.reply_to_name";
				$from_name_key      = "emails.{$key}.{$role_key}.from_name";
				$from_address_key   = "emails.{$key}.{$role_key}.from_address";

				if ( empty( trim( strval( $this->get( $reply_to_email_key ) ) ) ) ) {
					$this->set( $reply_to_email_key, get_bloginfo( 'admin_email' ) );
				}

				if ( empty( trim( strval( $this->get( $reply_to_name_key ) ) ) ) ) {
					$this->set( $reply_to_name_key, get_bloginfo( 'name' ) );
				}

				if ( empty( trim( strval( $this->get( $from_name_key ) ) ) ) ) {
					$this->set( $from_name_key, $this->get( 'emails.general.from_name' ) );
				}

				if ( empty( trim( strval( $this->get( $from_address_key ) ) ) ) ) {
					$this->set( $from_address_key, $this->get( 'emails.general.from_email' ) );
				}

				$to_address_key = "emails.{$key}.{$role_key}.to_address";

				if ( empty( trim( strval( $this->get( $to_address_key ) ) ) ) ) {
					$to_address = '{admin_email}';

					if ( 'student' === $key ) {
						$to_address = '{student_email}';
					} elseif ( 'instructor' === $key ) {
						$to_address = '{instructor_email}';
					} elseif ( 'everyone' === $key ) {
						$to_address = '{user_email}';
					}

					$this->set( $to_address_key, $to_address );
				}

				$content_key = "emails.{$key}.{$role_key}.content";

				if ( empty( trim( strval( $this->get( $content_key ) ) ) ) ) {
					if ( isset( ( masteriyo_get_default_email_contents() )[ $key ][ $role_key ]['content'] ) ) {
						$content = ( masteriyo_get_default_email_contents() )[ $key ][ $role_key ]['content'];
						$this->set( $content_key, $content );
					}
				}

				if ( empty( trim( strval( $this->get( "emails.{$key}.{$role_key}.subject" ) ) ) ) ) {
					if ( isset( ( masteriyo_get_default_email_contents() )[ $key ][ $role_key ]['subject'] ) ) {
						$subject = ( masteriyo_get_default_email_contents() )[ $key ][ $role_key ]['subject'];
						$this->set( "emails.{$key}.{$role_key}.subject", $subject );
					}
				}
			}
		}

		$notification_settings = $this->get( 'notification' );
		foreach ( $notification_settings as $key => $notification_setting ) {

			$role_wise_settings = $this->get( "notification.{$key}" );

			foreach ( $role_wise_settings as $role_key => $role_wise_setting ) {

				$content_key = "notification.{$key}.{$role_key}.content";

				if ( empty( trim( strval( $this->get( $content_key ) ) ) ) ) {
					$content = ( masteriyo_get_default_notification_contents() )[ $key ][ $role_key ]['content'];
					$this->set( $content_key, $content );
				}
			}
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
			$this->mark_as_modified( $prop );
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
		/** @var Masteriyo\Models\Setting $setting */
		$setting    = masteriyo( 'setting' );
		$this->data = $setting->get_data();
	}
}
