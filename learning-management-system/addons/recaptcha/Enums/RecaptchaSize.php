<?php
/**
 * Google reCAPTCHA size enums.
 *
 * @since x.x.x
 * @package Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Google reCAPTCHA size enum class.
 *
 * @since x.x.x
 */
class RecaptchaSize {
	/**
	 * reCAPTCHA google.com.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const NORMAL = 'normal';

	/**
	 * reCAPTCHA google.net.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const COMPACT = 'compact';

	/**
	 * Return all the Google reCAPTCHA sizes.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Google reCAPTCHA size list.
			 *
			 * @since x.x.x
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
