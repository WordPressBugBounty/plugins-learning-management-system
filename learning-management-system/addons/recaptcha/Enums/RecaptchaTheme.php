<?php
/**
 * Google reCAPTCHA theme enums.
 *
 * @since 2.3.0
 * @package Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Google reCAPTCHA theme enum class.
 *
 * @since 2.3.0
 */
class RecaptchaTheme {
	/**
	 * reCAPTCHA light.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const LIGHT = 'light';

	/**
	 * reCAPTCHA dark.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const DARK = 'dark';

	/**
	 * Return all the Google reCAPTCHA themes.
	 *
	 * @since 2.3.0
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Google reCAPTCHA theme list.
			 *
			 * @since 2.3.0
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
