<?php
/**
 * Google reCAPTCHA domain enums.
 *
 * @since 2.3.0
 * @package Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Google reCAPTCHA domain enum class.
 *
 * @since 2.3.0
 */
class RecaptchaDomain {
	/**
	 * reCAPTCHA google.com.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const GOOGLE_COM = 'google.com';

	/**
	 * reCAPTCHA recaptcha.net.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const RECAPTCHA_NET = 'recaptcha.net';


	/**
	 * Return all the Google reCAPTCHA domains.
	 *
	 * @since 2.3.0
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Google reCAPTCHA domain list.
			 *
			 * @since 2.3.0
			 *
			 * @param string[] $statuses Google reCAPTCHA domain list.
			 */
			apply_filters(
				'masteriyo_pro_recaptcha_domains',
				array(
					self::GOOGLE_COM,
					self::RECAPTCHA_NET,
				)
			)
		);
	}
}
