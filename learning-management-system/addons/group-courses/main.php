<?php
/**
 * Addon Name: Group Course
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Group Course let instructors sell courses to student groups, facilitating collective learning and shared educational experiences.
 * Author: Masteriyo
 * Author URI: https://masteriyo.com
 * Addon Type: Feature
 * Plan: Free
 */

use Masteriyo\Pro\Addons;

define( 'MASTERIYO_GROUP_COURSES_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_GROUP_COURSES_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_GROUP_COURSES_ADDON_DIR', dirname( __FILE__ ) );
define( 'MASTERIYO_GROUP_COURSES_TEMPLATES', dirname( __FILE__ ) . '/templates' );
define( 'MASTERIYO_GROUP_COURSES_ADDON_SLUG', 'group-courses' );
define( 'MASTERIYO_GROUP_COURSES_ADDON_ASSETS_URL', plugins_url( 'assets', MASTERIYO_GROUP_COURSES_ADDON_FILE ) );

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_GROUP_COURSES_ADDON_SLUG ) ) {
	return;
}

require_once __DIR__ . '/helper/group-courses.php';

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_GROUP_COURSES_ADDON_SLUG ) ) {
	return;
}

add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once dirname( __FILE__ ) . '/config/providers.php' );
	}
);

add_action(
	'masteriyo_before_init',
	function() {
		masteriyo( 'addons.group-courses' )->init();
	}
);
