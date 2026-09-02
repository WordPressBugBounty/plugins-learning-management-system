<?php
/**
 * Order Item type.
 *
 * @since 2.6.10
 */
namespace Masteriyo\Enums;

defined( 'ABSPATH' ) || exit;

class OrderItemType {
	const COURSE = 'course';

	const TAX = 'tax';

	const SHIPPING = 'shipping';

	const FEE = 'free';

	const COUPON = 'coupon';


	public static function all() {
		return array(
			self::COURSE,
			self::TAX,
			self::SHIPPING,
			self::FEE,
			self::COUPON,
		);
	}
}
