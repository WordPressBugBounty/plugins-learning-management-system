<?php

/**
 * Brevo integration settings class.
 *
 * @package Masteriyo\Addons\BrevoIntegration
 *
 * @since 2.14.4 [Free]
 */

namespace Masteriyo\Addons\BrevoIntegration;

defined( 'ABSPATH' ) || exit;


use Masteriyo\EmailMarketingAndCRM\IntegrationSettings;

/**
 * Brevo integration settings class.
 *
 * @since 2.14.4 [Free]
 */
class BrevoIntegrationSettings extends IntegrationSettings {

	/**
	 * The settings data.
	 *
	 * @since 2.14.4 [Free]
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
	 * @since 2.14.4 [Free]
	 *
	 * @return string
	 */
	protected static function get_option_name() {
		return 'masteriyo_brevo_integration_settings';
	}

	/**
	 * Get the Brevo API key.
	 *
	 * @since 2.14.4 [Free]
	 *
	 * @return string The Brevo API key.
	 */
	public static function get_api_key() {
		return static::get( 'api_key' );
	}
}
