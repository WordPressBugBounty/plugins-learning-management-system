<?php
/**
 * Google reCAPTCHA theme enums.
 *
 * @since 1.18.2
 * @package Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Google reCAPTCHA theme enum class.
 *
 * @since 1.18.2
 */
class RecaptchaTheme {
	/**
	 * reCAPTCHA light.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	const LIGHT = 'light';

	/**
	 * reCAPTCHA dark.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	const DARK = 'dark';

	/**
	 * Return all the Google reCAPTCHA themes.
	 *
	 * @since 1.18.2
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Google reCAPTCHA theme list.
			 *
			 * @since 1.18.2
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
