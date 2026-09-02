<?php
/**
 * Google reCAPTCHA version enums.
 *
 * @since 2.3.0
 * @package Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Google reCAPTCHA version enum class.
 *
 * @since 2.3.0
 */
class RecaptchaVersion {
	/**
	 * reCAPTCHA v2 I am not a robot.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const V2_I_AM_NOT_A_ROBOT = 'v2_i_am_not_a_robot';

	/**
	 * reCAPTCHA v2 no interaction.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const V2_NO_INTERACTION = 'v2_no_interaction';

	/**
	 * reCAPTCHA v3 I am not a robot.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	const V3 = 'v3';

	/**
	 * Return all the Google reCAPTCHA versions.
	 *
	 * @since 2.3.0
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Google reCAPTCHA version list.
			 *
			 * @since 2.3.0
			 *
			 * @param string[] $statuses Google reCAPTCHA version list.
			 */
			apply_filters(
				'masteriyo_pro_recaptcha_versions',
				array(
					self::V2_I_AM_NOT_A_ROBOT,
					self::V2_NO_INTERACTION,
					self::V3,
				)
			)
		);
	}
}
