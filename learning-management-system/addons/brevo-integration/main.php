<?php
/**
 * Addon Name: Brevo Integration
 * Addon URI: https://masteriyo.com/wordpress-lms/
 * Description: Seamlessly integrate Brevo with Masteriyo LMS for efficient and automated email marketing within your learning management system.
 * Author: Masteriyo
 * Addon Type: integration
 * Author URI: https://masteriyo.com
 * Plan: Free
 */

use Masteriyo\Addons\BrevoIntegration\BrevoIntegrationAddon;
use Masteriyo\Pro\Addons;

define( 'MASTERIYO_BREVO_INTEGRATION_FILE', __FILE__ );
define( 'MASTERIYO_BREVO_INTEGRATION_BASENAME', plugin_basename( __FILE__ ) );
define( 'MASTERIYO_BREVO_INTEGRATION_DIR', dirname( __FILE__ ) );
define( 'MASTERIYO_BREVO_INTEGRATION_TEMPLATES', dirname( __FILE__ ) . '/templates' );
define( 'MASTERIYO_BREVO_INTEGRATION_SLUG', 'brevo-integration' );
define( 'MASTERIYO_BREVO_INTEGRATION_BASE_URL', 'https://api.sendinblue.com/v3/' );

// Bail early if the addon is not active.
if ( ! ( new Addons() )->is_active( MASTERIYO_BREVO_INTEGRATION_SLUG ) ) {
	return;
}

// Initiate Brevo Integration addon.
BrevoIntegrationAddon::instance()->init();
