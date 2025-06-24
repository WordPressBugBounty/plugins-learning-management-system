<?php
/**
 * Google reCAPTCHA page enums.
 *
 * @since 1.18.2
 * @package Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Google reCAPTCHA page enum class.
 *
 * @since 1.18.2
 */
class RecaptchaPage {
	/**
	 * reCAPTCHA all.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	const ALL = 'all';

	/**
	 * reCAPTCHA form.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	const FORM = 'form';

	/**
	 * Return all the Google reCAPTCHA pages.
	 *
	 * @since 1.18.2
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Google reCAPTCHA page list.
			 *
			 * @since 1.18.2
			 *
			 * @param string[] $statuses Google reCAPTCHA page list.
			 */
			apply_filters(
				'masteriyo_pro_recaptcha_pages',
				array(
					self::ALL,
					self::FORM,
				)
			)
		);
	}
}
