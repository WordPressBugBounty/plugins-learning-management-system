<?php

defined( 'ABSPATH' ) || exit;


/**
 * Migration class template used by the wp cli to create migration classes.
 *
 * @since 1.15.0
 */

use Masteriyo\Database\Migration;

class GlobalSettingRestructure extends Migration {
	/**
	 * Run the migration.
	 *
	 * @since 1.15.0
	 */
	public function up() {
		$settings              = get_option( 'masteriyo_settings' );
		$is_new_global_setting = masteriyo_array_get( $settings, 'advance.editor', null );

		if ( null !== $is_new_global_setting ) {
			return;
		}

		$new_login_session_limit_setting = masteriyo_array_get( $settings, 'advance.limit_login_session', 0 );

		masteriyo_array_set( $settings, 'authentication.limit_login_session', $new_login_session_limit_setting );
		masteriyo_array_set( $settings, 'advance.limit_login_session', '' );

		$new_qr_login_setting = array(
			'enable'            => masteriyo_array_get( $settings, 'advance.qr_login.enable', false ),
			'attention_message' => masteriyo_array_get( $settings, 'advance.qr_login.attention_message', 'Attention: Possession of the QR code or login link grants login access to anyone.' ),
		);
		masteriyo_array_set( $settings, 'authentication.qr_login', $new_qr_login_setting );
		masteriyo_array_set( $settings, 'advance.qr_login', '' );

		$new_authentication_email_verification_settings = array(
			'enable' => masteriyo_array_get( $settings, 'advance.email_verification.enable', true ),
		);

		masteriyo_array_set( $settings, 'authentication.email_verification', $new_authentication_email_verification_settings );
		masteriyo_array_set( $settings, 'advance.email_verification', '' );
		//courses page
		//add courses page->display->'template'and add courses page->display->'layout'
		$new_courses_page_display_template = array(
			'custom_template' => masteriyo_array_get(
				$settings,
				'course_archive.custom_template',
				array(
					'enable'          => false,
					'template_source' => 'elementor',
					'template_id'     => 0,
				)
			),
			'layout'          => masteriyo_array_get( $settings, 'course_archive.layout', 'default' ),
		);

		masteriyo_array_set(
			$settings,
			'course_archive.display.template',
			$new_courses_page_display_template
		);
		masteriyo_array_set(
			$settings,
			'course_archive.custom_template',
			array()
		);
		masteriyo_array_set(
			$settings,
			'course_archive.layout',
			''
		);

		//single course page
		//add single course page->display->'template' && add single_course page->display->'layout'
		$new_single_course_page_display_template = array(
			'custom_template' => masteriyo_array_get(
				$settings,
				'single_course.custom_template',
				array(
					'enable'          => false,
					'template_source' => 'elementor',
					'template_id'     => 0,
				)
			),
			'layout'          => masteriyo_array_get( $settings, 'single_course.layout', 'default' ),
		);
		masteriyo_array_set(
			$settings,
			'single_course.display.template',
			$new_single_course_page_display_template
		);
		masteriyo_array_set(
			$settings,
			'single_course.custom_template',
			array()
		);
		masteriyo_array_set(
			$settings,
			'single_course.layout',
			''
		);

		// add 'editor' in advance settings
		$new_advance_editor_setting = masteriyo_array_get( $settings, 'general.editor.default_editor', 'classic_editor' );
		masteriyo_array_set(
			$settings,
			'advance.editor.default_editor',
			$new_advance_editor_setting
		);
		masteriyo_array_set(
			$settings,
			'general.editor',
			null
		);
		// error_log( 'up' . print_r( $settings, true ) );

		update_option( 'masteriyo_settings', $settings );
	}

	/**
	 * Reverse the migrations.
	 *
	 * @since 1.15.0
	 */
	public function down() {
		$settings = get_option( 'masteriyo_settings' );

		// Check if the settings were already reverted
		$is_old_setting = masteriyo_array_get( $settings, 'general.editor', null );

		if ( null !== $is_old_setting ) {
			return;
		}

		$new_login_session_limit_setting = masteriyo_array_get( $settings, 'authentication.limit_login_session' );
		masteriyo_array_set( $settings, 'advance.limit_login_session', $new_login_session_limit_setting );
		masteriyo_array_set( $settings, 'authentication.limit_login_session', '' );

		$new_qr_login_setting = array(
			'enable'            => masteriyo_array_get( $settings, 'authentication.qr_login.enable', false ),
			'attention_message' => masteriyo_array_get( $settings, 'authentication.qr_login.attention_message', 'Attention: Possession of the QR code or login link grants login access to anyone.' ),
		);
		masteriyo_array_set( $settings, 'advance.qr_login', $new_qr_login_setting );
		masteriyo_array_set( $settings, 'authentication.qr_login', array() );

		// Move email_verification back to advance settings
		$new_advance_email_verification = array(
			'enable' => masteriyo_array_get( $settings, 'authentication.email_verification.enable' ),
		);
		masteriyo_array_set( $settings, 'advance.email_verification', $new_advance_email_verification );
		masteriyo_array_set( $settings, 'authentication.email_verification', array() );

		// Restore courses page display settings
		$new_courses_page_display_custom_template = masteriyo_array_get(
			$settings,
			'course_archive.display.template.custom_template',
			array(
				'enable'          => false,
				'template_source' => 'elementor',
				'template_id'     => 0,
			)
		);
		masteriyo_array_set(
			$settings,
			'course_archive.custom_template',
			$new_courses_page_display_custom_template
		);

		$new_courses_page_layout = masteriyo_array_get( $settings, 'course_archive.display.template.layout' );

		masteriyo_array_set( $settings, 'course_archive.layout', $new_courses_page_layout );
		masteriyo_array_set( $settings, 'course_archive.display.template', null );

		// Restore single course page display settings
		$new_single_course_page_display_custom_template = masteriyo_array_get(
			$settings,
			'single_course.display.template.custom_template',
			array(
				'enable'          => false,
				'template_source' => 'elementor',
				'template_id'     => 0,
			)
		);
		masteriyo_array_set(
			$settings,
			'single_course.custom_template',
			$new_single_course_page_display_custom_template
		);
		masteriyo_array_set(
			$settings,
			'single_course.display.template.custom_template',
			array()
		);

		$new_single_course_layout = masteriyo_array_get( $settings, 'single_course.display.template.layout', 'default' );

		masteriyo_array_set( $settings, 'single_course.layout', $new_single_course_layout );
		masteriyo_array_set( $settings, 'single_course.display.template.layout', '' );

		// Restore the editor setting back to 'general'
		$new_editor_setting = masteriyo_array_get( $settings, 'advance.editor.default_editor', 'classic_editor' );
		masteriyo_array_set( $settings, 'general.editor.default_editor', $new_editor_setting );
		masteriyo_array_set( $settings, 'advance.editor', null );

		// error_log( 'down' . print_r( $settings, true ) );
		update_option( 'masteriyo_settings', $settings );
	}
}
