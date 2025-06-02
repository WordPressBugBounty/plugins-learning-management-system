<?php
/**
 * Google reCAPTCHA domain enums.
 *
 * @since x.x.x
 * @package Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Google reCAPTCHA domain enum class.
 *
 * @since x.x.x
 */
class RecaptchaDomain {
	/**
	 * reCAPTCHA google.com.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const GOOGLE_COM = 'google.com';

	/**
	 * reCAPTCHA recaptcha.net.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const RECAPTCHA_NET = 'recaptcha.net';


	/**
	 * Return all the Google reCAPTCHA domains.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Google reCAPTCHA domain list.
			 *
			 * @since x.x.x
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
