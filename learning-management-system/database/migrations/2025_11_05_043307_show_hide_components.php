<?php

defined( 'ABSPATH' ) || exit;

/**
 * Migration class template used by the wp cli to create migration classes.
 *
 * @since  2.1.0
 */

use Masteriyo\Database\Migration;

class ShowHideComponents extends Migration {
	/**
	 * Run the migration.
	 *
	 * @since 1.16.0
	 */
	public function up() {
		$settings = get_option( 'masteriyo_settings', array() );

		$components_visibility_data = array();

		if ( isset( $settings['course_archive']['components_visibility'] ) && is_array( $settings['course_archive']['components_visibility'] ) ) {
			$components_visibility_data = $settings['course_archive']['components_visibility'];
		} elseif ( isset( $settings['course_archive']['components_visibility'] ) && is_array( $settings['course_archive']['components_visibility'] ) ) {
			$components_visibility_data = $settings['course_archive']['components_visibility'];
		} elseif ( function_exists( 'masteriyo_get_setting' ) ) {
			$components_visibility_data = masteriyo_get_setting( 'course_archive.components_visibility' );
		}

		if ( empty( $components_visibility_data ) ) {
			return;
		}

		if ( empty( $components_visibility_data['single_course_visibility'] ) || ! $components_visibility_data['single_course_visibility'] ) {
			return;
		}

		if ( isset( $settings['single_course']['components_visibility'] ) ) {
			return;
		}

		if ( ! isset( $settings['single_course'] ) || ! is_array( $settings['single_course'] ) ) {
			$settings['single_course'] = array();
		}

		if ( isset( $components_visibility_data['author'] ) && ! $components_visibility_data['author'] ) {
			$components_visibility_data['author_avatar'] = 0;
			$components_visibility_data['author_name']   = 0;
		}

		if ( isset( $components_visibility_data['metadata'] ) && ! $components_visibility_data['metadata'] ) {
			foreach ( array(
				'course_duration',
				'students_count',
				'lessons_count',
				'seats_for_students',
				'date_updated',
				'date_started',
			) as $field ) {
				$components_visibility_data[ $field ] = 0;
			}
		}

		if ( isset( $components_visibility_data['card_footer'] ) && ! $components_visibility_data['card_footer'] ) {
			$components_visibility_data['price']         = 0;
			$components_visibility_data['enroll_button'] = 0;
		}
		$settings['course_archive']['components_visibility'] = $components_visibility_data;
		$settings['single_course']['components_visibility']  = $components_visibility_data;
		update_option( 'masteriyo_settings', $settings );
	}




	/**
	 * Reverse the migrations.
	 */
	public function down() {
	}
}
