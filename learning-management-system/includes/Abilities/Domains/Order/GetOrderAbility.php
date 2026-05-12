<?php
/**
 * Get Order ability.
 *
 * @package Masteriyo\Abilities\Domains\Order
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Order;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a single order by ID.
 *
 * @since x.x.x
 */
class GetOrderAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'order.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'get';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'orders';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/order-get';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Get Order', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Retrieve a single order by its ID. Requires id (order ID). Returns the full order object including status, total, customer details, and line items.', 'learning-management-system' );
	}
}
