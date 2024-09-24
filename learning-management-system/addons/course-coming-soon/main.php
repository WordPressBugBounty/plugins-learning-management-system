<?php
/**
 * Addon Name: Courses Coming Soon
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: A feature that allows teachers to share upcoming courses and content with their users.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: feature
 * Plan: Free
 */

use Masteriyo\Pro\Addons;

define( 'MASTERIYO_COURSE_COMING_SOON_FILE', __FILE__ );
define( 'MASTERIYO_COURSE_COMING_SOON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_COURSE_COMING_SOON_DIR', __DIR__ );
define( 'MASTERIYO_COURSE_COMING_SOON_TEMPLATES', dirname( __FILE__ ) . '/templates' );
define( 'MASTERIYO_COURSE_COMING_SOON_SLUG', 'course-coming-soon' );

if ( ! ( new Addons() )->is_active( MASTERIYO_COURSE_COMING_SOON_SLUG ) ) {
	return;
}

/**
 * Include service providers for Google Classroom.
 */
add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once __DIR__ . '/config/providers.php' );
	}
);

/**
 * Initialize Masteriyo Google Classroom Integration.
 */
add_action(
	'masteriyo_before_init',
	function() {
		masteriyo( 'addons.course-coming-soon' )->init();
	}
);
