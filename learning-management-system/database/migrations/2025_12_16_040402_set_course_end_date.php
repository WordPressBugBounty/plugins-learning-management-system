<?php

defined( 'ABSPATH' ) || exit;

/**
 * Migration class template used by the wp cli to create migration classes.
 *
 * @since  2.1.0
 */

use Masteriyo\Database\Migration;

class SetCourseEndDate extends Migration {
	/**
	 * Run the migration.
	 *
	 * @since 2.1.0
	 */
	public function up() {

		global $wpdb;

		$course_ids = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mto-course'"
		);

		foreach ( $course_ids as $course_id ) {

			$end_date = get_post_meta( $course_id, '_end_date', true );

			if ( ! empty( $end_date ) ) {
				update_post_meta( $course_id, '_enable_end_date', 'yes' );
			}
		}
	}



	/**
	 * Reverse the migrations.
	 */
	public function down() {
	}
}
