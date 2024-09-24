<?php
/**
 * Addon Name: Certificate Builder
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Provide certificates to encourage students in completing the course.
 * Author: Masteriyo
 * Version: 1.13.0
 * Author URI: https://masteriyo.com
 * Plan: Free
 */

use Masteriyo\Addons\Certificate\CertificateAddon;
use Masteriyo\Pro\Addons;

define( 'MASTERIYO_CERTIFICATE_BUILDER_ADDON_FILE', __FILE__ );
define( 'MASTERIYO_CERTIFICATE_BUILDER_ADDON_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_CERTIFICATE_BUILDER_ADDON_DIR', dirname( __FILE__ ) );
define( 'MASTERIYO_CERTIFICATE_ASSETS', dirname( __FILE__ ) . '/assets' );
define( 'MASTERIYO_CERTIFICATE_TEMPLATES', dirname( __FILE__ ) . '/templates' );
define( 'MASTERIYO_CERTIFICATE_ADDON_SLUG', 'certificate' );

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_CERTIFICATE_ADDON_SLUG ) ) {
	return;
}

require_once dirname( __FILE__ ) . '/helper.php';
/**
 * Include service providers for certificate.
 */
add_filter(
	'masteriyo_service_providers',
	function( $providers ) {
		return array_merge( $providers, require_once dirname( __FILE__ ) . '/config/providers.php' );
	}
);

/**
 * Initialize Masteriyo Certificate.
 */
CertificateAddon::instance()->init();

