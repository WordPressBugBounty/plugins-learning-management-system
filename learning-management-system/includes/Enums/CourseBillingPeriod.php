<?php
/**
 * Course billing period enums.
 *
 * @since 2.6.10
 * @package Masteriyo\Enums
 */

namespace Masteriyo\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * Course billing period enum class.
 *
 * @since 2.6.10
 */
class CourseBillingPeriod {
	/**
	 * Course billing period daily.
	 *
	 * @since 2.6.10
	 * @var string
	 */
	const DAY = 'day';

	/**
	 * Course billing period weekly.
	 *
	 * @since 2.6.10
	 * @var string
	 */
	const WEEK = 'week';

	/**
	 * Course billing period monthly.
	 *
	 * @since 2.6.10
	 * @var string
	 */
	const MONTH = 'month';

	/**
	 * Course billing period yearly.
	 *
	 * @since 2.6.10
	 * @var string
	 */
	const YEAR = 'year';

	/**
	 * Return course billing periods.
	 *
	 * @since 2.6.10
	 *
	 * @return array
	 */
	public static function all() {
		return array_unique(
			apply_filters(
				'masteriyo_course_billing_periods',
				array(
					self::DAY,
					self::WEEK,
					self::MONTH,
					self::YEAR,
				)
			)
		);
	}

	/**
	 * Localize and return the period.
	 *
	 * @since 2.6.10
	 *
	 * @param string $period
	 * @return string
	 */
	public static function label( $period ) {
		$map = array(
			self::DAY   => __( 'day', 'learning-management-system' ),
			self::WEEK  => __( 'week', 'learning-management-system' ),
			self::MONTH => __( 'month', 'learning-management-system' ),
			self::YEAR  => __( 'year', 'learning-management-system' ),
		);

		$result = isset( $map[ $period ] ) ? $map[ $period ] : __( 'n/a', 'learning-management-system' );

		/**
		 * Filter billing period localize.
		 *
		 * @since 2.6.10
		 *
		 * @param string $result
		 * @param string $period
		 */
		return apply_filters( 'masteriyo_course_billing_period_label', $result, $period );
	}

	/**
	 * Return separator between pricing and billing period.
	 *
	 * @since 2.6.10
	 *
	 * @return string
	 */
	public static function separator() {
		/**
		 * Filter billing period separator.
		 *
		 * @since 2.6.10
		 *
		 * @param string $separator
		 */
		return apply_filters( 'masteriyo_course_billing_period_separator', '/' );
	}
}
