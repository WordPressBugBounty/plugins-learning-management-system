<?php
/**
 * Core Feature Name: Courses Coming Soon
 */

defined( 'ABSPATH' ) || exit;

// Constants (safe against double includes).
defined( 'MASTERIYO_COURSE_COMING_SOON_FILE' ) || define( 'MASTERIYO_COURSE_COMING_SOON_FILE', __FILE__ );
defined( 'MASTERIYO_COURSE_COMING_SOON_BASENAME' ) || define( 'MASTERIYO_COURSE_COMING_SOON_BASENAME', plugin_basename( __FILE__ ) );
defined( 'MASTERIYO_COURSE_COMING_SOON_DIR' ) || define( 'MASTERIYO_COURSE_COMING_SOON_DIR', __DIR__ );
defined( 'MASTERIYO_COURSE_COMING_SOON_TEMPLATES' ) || define( 'MASTERIYO_COURSE_COMING_SOON_TEMPLATES', __DIR__ . '/templates' );
defined( 'MASTERIYO_COURSE_COMING_SOON_SLUG' ) || define( 'MASTERIYO_COURSE_COMING_SOON_SLUG', 'course-coming-soon' );

/**
 * Register service providers for Course Coming Soon.
 */
add_filter(
	'masteriyo_service_providers',
	static function( $providers ) {
		$providers_file = __DIR__ . '/config/providers.php';

		if ( file_exists( $providers_file ) ) {
			$feature_providers = require $providers_file;

			if ( is_array( $feature_providers ) ) {
				$providers = array_merge( $providers, $feature_providers );
			}
		}

		return $providers;
	}
);

/**
 * Initialize the feature AFTER Masteriyo init (container/providers should be ready).
 */
add_action(
	'masteriyo_init',
	static function() {
		if ( ! function_exists( 'masteriyo' ) ) {
			return;
		}

		try {
			$feature = masteriyo( 'core-features.course-coming-soon' );
		} catch ( \Throwable $e ) {
			// Optional debug:
			// error_log('[Course Coming Soon] service not found: ' . $e->getMessage());
			return;
		}

		if ( is_object( $feature ) && method_exists( $feature, 'init' ) ) {
			$feature->init();

			/**
			 * Fires after the Course Coming Soon core feature has initialized.
			 */
			do_action( 'masteriyo_course_coming_soon_initialized' );
		}
	},
	20
);
