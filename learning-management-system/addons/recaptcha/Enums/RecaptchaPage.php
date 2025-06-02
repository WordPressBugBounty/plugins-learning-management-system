<?php
/**
 * Google reCAPTCHA page enums.
 *
 * @since x.x.x
 * @package Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Google reCAPTCHA page enum class.
 *
 * @since x.x.x
 */
class RecaptchaPage {
	/**
	 * reCAPTCHA all.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const ALL = 'all';

	/**
	 * reCAPTCHA form.
	 *
	 * @since x.x.x
	 * @var string
	 */
	const FORM = 'form';

	/**
	 * Return all the Google reCAPTCHA pages.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			/**
			 * Filters Google reCAPTCHA page list.
			 *
			 * @since x.x.x
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
