<?php
/**
 * List Orders ability.
 *
 * @package Masteriyo\Abilities\Domains\Order
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Order;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: retrieve a paginated list of orders.
 *
 * @since x.x.x
 */
class ListOrdersAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'order.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'list';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'orders';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/order-list';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'List Orders', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Retrieve a paginated list of course orders. Optionally filter by status, customer_id, or date range. Returns data array and pagination metadata (total, total_pages, page, per_page).', 'learning-management-system' );
	}
}
