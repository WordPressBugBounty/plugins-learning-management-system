<?php
/**
 * Create Order ability.
 *
 * @package Masteriyo\Abilities\Domains\Order
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Order;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: create a new course order.
 *
 * @since x.x.x
 */
class CreateOrderAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'order.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'create';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'orders';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/order-create';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Create Order', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Create a new course purchase order. Requires customer_id and course_ids (array of course IDs). Optionally accepts payment_method and coupon_code. Returns the created order object with its ID and total.', 'learning-management-system' );
	}
}
