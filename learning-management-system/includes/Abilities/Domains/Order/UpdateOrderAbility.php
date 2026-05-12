<?php
/**
 * Update Order ability.
 *
 * @package Masteriyo\Abilities\Domains\Order
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Order;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: update an existing order.
 *
 * @since x.x.x
 */
class UpdateOrderAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'order.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'update';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'orders';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/order-update';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Update Order', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Update an existing order. Requires id (order ID). Optionally accepts status (pending, completed, cancelled, refunded). Returns the updated order object.', 'learning-management-system' );
	}
}
