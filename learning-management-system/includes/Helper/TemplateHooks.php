<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Masteriyo Template Hooks
 *
 * Action/filter hooks used for Masteriyo functions/templates.
 *
 * @package Masteriyo\Templates
 * @version 1.0.0
 */


if ( ! ( function_exists( 'add_filter' ) && function_exists( 'add_action' ) ) ) {
	return;
}

add_action( 'masteriyo_template_enroll_button', 'masteriyo_template_enroll_button' );
add_action( 'masteriyo_template_course_retake_button', 'masteriyo_template_course_retake_button' );
add_filter( 'admin_body_class', 'masteriyo_add_admin_body_class' );
add_filter( 'body_class', 'masteriyo_add_body_class', 10, 2 );
add_filter( 'body_class', 'masteriyo_add_current_theme_slug_to_body_tag', 10, 2 );

/**
 * Course category archive.
 */
add_action( 'masteriyo_after_course_category_main_content', 'masteriyo_archive_navigation' );
add_action( 'masteriyo_course_category_description', 'masteriyo_course_category_description' );

/**
 * Instructor archive.
 */
add_action( 'masteriyo_after_instructor_archive_main_content', 'masteriyo_archive_navigation' );

/**
 * Course Archive.
 */
add_action( 'masteriyo_no_courses_found', 'masteriyo_template_no_courses_found' );
add_action( 'masteriyo_after_main_content', 'masteriyo_archive_navigation' );
add_action( 'masteriyo_after_search_section_content', 'masteriyo_template_courses_sorting_input', 20 );
add_action( 'masteriyo_before_courses_sorting_section_content', 'masteriyo_template_course_filter_sidebar_toggle', 10 );
add_action( 'masteriyo_before_course_archive_loop', 'masteriyo_render_course_filter_and_sorting_nonce_field' );
add_action( 'masteriyo_before_course_archive_loop', 'masteriyo_template_course_filters', 10 );
add_action( 'masteriyo_course_filter_form_content', 'masteriyo_template_course_categories_filter', 10 );
add_action( 'masteriyo_course_filter_form_content', 'masteriyo_template_course_difficulties_filter', 20 );
add_action( 'masteriyo_course_filter_form_content', 'masteriyo_template_course_price_type_filter', 30 );
add_action( 'masteriyo_course_filter_form_content', 'masteriyo_template_course_price_filter', 40 );
add_action( 'masteriyo_course_filter_form_content', 'masteriyo_template_course_rating_filter', 50 );
add_action( 'masteriyo_course_filter_form_content', 'masteriyo_template_clear_course_filter', 60 );
add_action( 'masteriyo_before_main_content', 'masteriyo_course_search_form' );
add_action( 'masteriyo_after_search_section_content', 'masteriyo_courses_view_mode' );
add_action( 'masteriyo_course_meta_data', 'masteriyo_archive_course_stats' );

/**
 * Course Archive Layout 1.
 *
 * @since 1.10.0
 */
add_action( 'masteriyo_after_layout_1_course_thumbnail', 'masteriyo_archive_course_badge' );
add_action( 'masteriyo_after_layout_1_course_title', 'masteriyo_course_expiration_info', 10, 1 );
add_action( 'masteriyo_course_archive_layout_1_meta_data', 'masteriyo_course_archive_layout_1_stats', 20, 1 );

/**
 * Course Archive Layout 2.
 *
 * @since 1.10.0
 */
add_action( 'masteriyo_after_layout_2_course_thumbnail', 'masteriyo_archive_course_badge' );
add_action( 'masteriyo_after_layout_2_course_description', 'masteriyo_course_expiration_info', 10, 1 );
add_action( 'masteriyo_course_archive_layout_2_meta_data', 'masteriyo_course_archive_layout_2_stats', 20, 1 );

/**
 * Single course.
 */
add_action( 'masteriyo_single_course_content', 'masteriyo_single_course_featured_image', 10 );
add_action( 'masteriyo_single_course_content', 'masteriyo_single_course_categories', 20 );
add_action( 'masteriyo_single_course_content', 'masteriyo_single_course_title', 35 );
add_action( 'masteriyo_single_course_content', 'masteriyo_single_course_badge', 40 );
add_action( 'masteriyo_single_course_content', 'masteriyo_course_expiration_info', 50 );
add_action( 'masteriyo_single_course_content', 'masteriyo_single_course_author_and_rating', 60 );
add_action( 'masteriyo_single_course_content', 'masteriyo_template_single_course_main_content', 70 );
add_action( 'masteriyo_single_course_main_content', 'masteriyo_single_course_tab_handles', 10 );
add_action( 'masteriyo_single_course_main_content', 'masteriyo_single_course_overview', 20 );
add_action( 'masteriyo_single_course_main_content', 'masteriyo_single_course_curriculum', 30 );
add_action( 'masteriyo_single_course_main_content', 'masteriyo_single_course_reviews', 40 );
add_action( 'masteriyo_after_single_course', 'masteriyo_single_course_modals', 10 );
add_action( 'masteriyo_after_single_course', 'masteriyo_template_single_course_related_courses', 20 );
add_action( 'masteriyo_template_course_review', 'masteriyo_template_course_review', 10, 3 );
add_action( 'masteriyo_template_course_review', 'masteriyo_template_single_course_review_replies', 20, 2 );
add_action( 'masteriyo_template_course_review_reply', 'masteriyo_template_course_review_reply' );
add_action( 'masteriyo_course_reviews_content', 'masteriyo_template_course_reviews_stats', 10, 3 );
add_action( 'masteriyo_course_reviews_content', 'masteriyo_template_course_reviews_filters', 15, 3 );
add_action( 'masteriyo_course_reviews_content', 'masteriyo_template_course_reviews_list', 20, 3 );
add_action( 'masteriyo_course_reviews_content', 'masteriyo_template_single_course_see_more_reviews_button', 30, 3 );
add_action( 'masteriyo_course_reviews_content', 'masteriyo_single_course_review_form', 30, 3 );
add_action( 'masteriyo_single_course_sidebar_content', 'masteriyo_single_course_price_and_enroll_button', 10 );
add_action( 'masteriyo_single_course_sidebar_content', 'masteriyo_single_course_progress_bar', 15 );
add_action( 'masteriyo_single_course_sidebar_content', 'masteriyo_single_course_stats', 20 );
add_action( 'masteriyo_single_course_sidebar_content', 'masteriyo_single_course_highlights', 30 );
add_action( 'masteriyo_single_course_curriculum_summary', 'masteriyo_template_single_course_curriculum_summary' );
add_action( 'masteriyo_single_course_curriculum_section_summary', 'masteriyo_template_single_course_curriculum_section_summary', 10, 2 );
add_action( 'masteriyo_single_course_curriculum_section_content', 'masteriyo_template_single_course_curriculum_section_content', 10, 2 );
add_action( 'masteriyo_after_add_to_cart_button', 'masteriyo_single_course_retake_button_modal' );

/**
 * Single course layout 1.
 *
 * @since 1.10.0
 */
add_action( 'masteriyo_layout_1_single_course_content', 'masteriyo_layout_1_single_course_header', 10, 1 );
add_action( 'masteriyo_layout_1_single_course_content', 'masteriyo_layout_1_single_course_main_content', 20, 1 );
add_action( 'masteriyo_before_layout_1_single_course_author_and_rating', 'masteriyo_single_course_badge', 10 );
add_action( 'masteriyo_layout_1_single_course_review_content', 'masteriyo_layout_1_single_course_review_count', 10, 2 );
add_action( 'masteriyo_layout_1_single_course_review_content', 'masteriyo_layout_1_single_course_user_review_content', 20, 2 );
add_action( 'masteriyo_layout_1_single_course_review_content', 'masteriyo_layout_1_single_course_review_form', 30, 2 );
add_action( 'masteriyo_layout_1_single_course_user_review_content', 'masteriyo_layout_1_single_course_review_filters', 10, 2 );
add_action( 'masteriyo_layout_1_single_course_user_review_content', 'masteriyo_layout_1_single_course_review_lists', 20, 2 );
add_action( 'masteriyo_layout_1_single_course_user_review_content', 'masteriyo_template_single_course_see_more_reviews_button', 30, 3 );
add_action( 'masteriyo_layout_1_single_course_review_list_after_reply_btn', 'masteriyo_layout_1_template_single_course_review_replies', 10, 2 );
add_action( 'masteriyo_layout_1_template_course_review_reply', 'masteriyo_layout_1_template_course_review_reply' );
add_action( 'masteriyo_after_single_course_price', 'masteriyo_layout_1_single_course_expiration_info', 10, 1 );
add_action( 'masteriyo_single_course_layout_1_template_enroll_button', 'masteriyo_template_enroll_button', 10, 1 );
add_action( 'masteriyo_single_course_layout_1_template_enroll_button', 'masteriyo_layout_1_single_course_retake_button', 15, 1 );
add_action( 'masteriyo_layout_1_single_course_aside_content', 'masteriyo_layout_1_single_course_price_and_enroll_button', 10, 1 );
add_action( 'masteriyo_layout_1_single_course_aside_content', 'masteriyo_layout_1_single_course_aside_items', 20, 1 );
add_action( 'masteriyo_layout_1_single_course_aside_items', 'masteriyo_layout_1_single_course_highlights', 10, 1 );
add_action( 'masteriyo_course_layout_1_meta_data', 'masteriyo_single_course_layout_1_progress' );
add_action( 'masteriyo_course_layout_1_meta_data', 'masteriyo_single_course_layout_1_stats' );

/**
 * Account page.
 */
add_action( 'masteriyo_account_sidebar_content', 'masteriyo_account_sidebar_content' );
add_action( 'masteriyo_account_main_content', 'masteriyo_account_main_content' );
add_action( 'masteriyo_account_edit-account_endpoint', 'masteriyo_account_edit_account_endpoint' );
add_action( 'masteriyo_account_view-account_endpoint', 'masteriyo_account_view_account_endpoint' );
add_action( 'masteriyo_account_courses_endpoint', 'masteriyo_account_courses_endpoint' );
add_action( 'masteriyo_account_order-history_endpoint', 'masteriyo_account_order_history_endpoint' );
add_action( 'masteriyo_account_view-order_endpoint', 'masteriyo_account_view_order_endpoint' );
add_action( 'masteriyo_before_account', 'masteriyo_session_info_update' );

/**
 * Emails.
 */
add_action( 'masteriyo_email_header', 'masteriyo_email_header' );
add_action( 'masteriyo_email_footer', 'masteriyo_email_footer' );
/* Intentionally are we are not using these hooks, we have given smart tags to the users. */
// add_action( 'masteriyo_email_order_details', 'masteriyo_email_order_details', 10, 2 );
// add_action( 'masteriyo_email_order_details', 'masteriyo_email_order_meta', 20, 2 );
// add_action( 'masteriyo_email_customer_details', 'masteriyo_email_customer_addresses', 20 );


/**
 * Checkout form.
 */
add_action( 'masteriyo_checkout_summary', 'masteriyo_checkout_order_summary', 10 );
add_action( 'masteriyo_checkout_summary', 'masteriyo_template_payment_wire_transfer', 15 );
add_action( 'masteriyo_checkout_summary', 'masteriyo_checkout_payment', 20 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_first_and_last_name', 10, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_email', 20, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_company', 30, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_country', 40, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_address_1', 50, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_address_2', 60, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_state', 70, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_city', 80, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_postcode', 90, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_phone_number', 100, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_customer_note', 110, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_attachment_upload', 115 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_create_user_checkbox', 120, 2 );
add_action( 'masteriyo_checkout_form_content', 'masteriyo_template_checkout_gdpr', 130, 2 );


/**
 * Course categories shortcode.
 *
 * @since 1.2.0
 */
add_action( 'masteriyo_template_shortcode_course_categories', 'masteriyo_template_shortcode_course_categories' );
add_action( 'masteriyo_template_shortcode_course_category', 'masteriyo_template_shortcode_course_category' );



/**
 * Instructors list shortcode.
 *
 * @since 1.6.16
 */
add_action( 'masteriyo_template_shortcode_instructors_list', 'masteriyo_template_shortcode_instructors_list' );
add_action( 'masteriyo_template_shortcode_instructors_list_item', 'masteriyo_template_shortcode_instructors_list_item' );


/**
 * Blocks in the single course.
 *
 * @since 1.12.2
 */
add_action( 'masteriyo_blocks_after_single_course', 'masteriyo_single_course_modals', 10 );
