<?php

/**
 * Brevo integration settings class.
 *
 * @package Masteriyo\Addons\BrevoIntegration
 *
 * @since 1.13.3
 */

namespace Masteriyo\Addons\BrevoIntegration;

use Masteriyo\EmailMarketingAndCRM\IntegrationSettings;

/**
 * Brevo integration settings class.
 *
 * @since 1.13.3
 */
class BrevoIntegrationSettings extends IntegrationSettings {

	/**
	 * The settings data.
	 *
	 * @since 1.13.3
	 *
	 * @var array
	 */
	protected static $data = array(
		'enable_forced_email_subscription' => false,
		'is_connected'                     => false,
		'api_key'                          => '',
		'list'                             => '',
		'subscriber_consent_message'       => 'I would like to receive the newsletters.',
	);

	/**
	 * Get the option name for the settings.
	 *
	 * @since 1.13.3
	 *
	 * @return string
	 */
	protected static function get_option_name() {
		return 'masteriyo_brevo_integration_settings';
	}

	/**
	 * Get the Brevo API key.
	 *
	 * @since 1.13.3
	 *
	 * @return string The Brevo API key.
	 */
	public static function get_api_key() {
		return static::get( 'api_key' );
	}
}
