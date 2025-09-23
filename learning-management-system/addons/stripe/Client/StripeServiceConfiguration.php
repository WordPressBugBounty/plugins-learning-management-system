<?php

namespace Masteriyo\Addons\Stripe\Client;

use Masteriyo\Addons\Stripe\Setting;
use Masteriyo\Addons\Stripe\Client\Exceptions\StripeServiceConfigurationException;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration class for Stripe service.
 */
final class StripeServiceConfiguration {

	/**
	 * @var string Service URL.
	 */
	private $url;

	/**
	 * @var string Account id.
	 */
	private $account_id;

	/**
	 * @var int Request timeout in seconds.
	 */
	private $timeout;

	/**
	 * @var bool Whether to verify SSL.
	 */
	private $ssl_verify;

	/**
	 * @var int Maximum retry attempts.
	 */
	private $max_retries;

	/**
	 * Mode (test or live).
	 *
	 * @var string
	 */
	private $mode;

	/**
	 * Constructor.
	 */
	public function __construct( $options = array() ) {
		$this->url         = $options['url'] ?? ( $this->is_development() ? 'https://stripe-staging.masteriyo.com' : 'https://stripe.masteriyo.com' );
		$this->timeout     = $options['timeout'] ?? 30;
		$this->ssl_verify  = $options['ssl_verify'] ?? ! $this->is_development();
		$this->max_retries = $options['max_retries'] ?? 3;
		$this->mode        = $options['mode'] ?? ( Setting::is_sandbox_enable() ? 'test' : 'live' );
		$this->account_id  = $options['account_id'] ?? Setting::get_stripe_user_id();
	}

	/**
	 * Get Stripe service URL.
	 * @return string
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Get timeout.
	 * @return int
	 */
	public function get_timeout() {
		return $this->timeout;
	}

	/**
	 * Get SSL verify setting.
	 * @return bool
	 */
	public function get_ssl_verify() {
		return $this->ssl_verify;
	}

	/**
	 * Get max retries.
	 * @return int
	 */
	public function get_max_retries() {
		return $this->max_retries;
	}

	/**
	 * Is development.
	 *
	 * @return boolean
	 */
	private function is_development() {
		return defined( 'MASTERIYO_DEVELOPMENT' ) && MASTERIYO_DEVELOPMENT;
	}

	/**
	 * Get mode.
	 *
	 * @return string
	 */
	public function get_mode() {
		return $this->mode;
	}

	/**
	 * Get account id.
	 *
	 * @return string
	 */
	public function get_account_id() {
		return $this->account_id;
	}

	/**
	 * Get all configuration as array.
	 * @return array
	 */
	public function to_array() {
		return array(
			'url'         => $this->url,
			'timeout'     => $this->timeout,
			'ssl_verify'  => $this->ssl_verify,
			'max_retries' => $this->max_retries,
			'mode'        => $this->mode,
			'account_id'  => $this->account_id,
		);
	}
}
