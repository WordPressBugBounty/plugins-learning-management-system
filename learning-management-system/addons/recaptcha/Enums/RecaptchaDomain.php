<?php
/**
 * Google reCAPTCHA domain enums.
 *
 * @since 1.18.2
 * @package Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Google reCAPTCHA domain enum class.
 *
 * @since 1.18.2
 */
class RecaptchaDomain {
	/**
	 * reCAPTCHA google.com.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	const GOOGLE_COM = 'google.com';

	/**
	 * reCAPTCHA recaptcha.net.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	const RECAPTCHA_NET = 'recaptcha.net';


	/**
	 * Return all the Google reCAPTCHA domains.
	 *
	 * @since 1.18.2
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Google reCAPTCHA domain list.
			 *
			 * @since 1.18.2
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
