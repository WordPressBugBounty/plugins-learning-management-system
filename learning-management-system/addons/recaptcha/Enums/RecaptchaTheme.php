<?php
/**
 * Google reCAPTCHA theme enums.
 *
 * @since x.x.x
 * @package Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Google reCAPTCHA theme enum class.
 *
 * @since x.x.x
 */
class RecaptchaTheme {
	/**
	 * reCAPTCHA light.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const LIGHT = 'light';

	/**
	 * reCAPTCHA dark.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const DARK = 'dark';

	/**
	 * Return all the Google reCAPTCHA themes.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Google reCAPTCHA theme list.
			 *
			 * @since x.x.x
			 *
			 * @param string[] $statuses Google reCAPTCHA theme list.
			 */
			apply_filters(
				'masteriyo_pro_recaptcha_themes',
				array(
					self::LIGHT,
					self::DARK,
				)
			)
		);
	}
}
