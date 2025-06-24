<?php
/**
 * Google reCAPTCHA size enums.
 *
 * @since 1.18.2
 * @package Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Google reCAPTCHA size enum class.
 *
 * @since 1.18.2
 */
class RecaptchaSize {
	/**
	 * reCAPTCHA google.com.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	const NORMAL = 'normal';

	/**
	 * reCAPTCHA google.net.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	const COMPACT = 'compact';

	/**
	 * Return all the Google reCAPTCHA sizes.
	 *
	 * @since 1.18.2
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Google reCAPTCHA size list.
			 *
			 * @since 1.18.2
			 *
			 * @param string[] $statuses Google reCAPTCHA size list.
			 */
			apply_filters(
				'masteriyo_pro_recaptcha_sizes',
				array(
					self::NORMAL,
					self::COMPACT,
				)
			)
		);
	}
}
