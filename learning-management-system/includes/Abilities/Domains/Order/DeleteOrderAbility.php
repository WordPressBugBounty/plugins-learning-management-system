<?php
/**
 * Delete Order ability.
 *
 * @package Masteriyo\Abilities\Domains\Order
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Order;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: delete an order.
 *
 * @since x.x.x
 */
class DeleteOrderAbility extends RestProxyAbility {

	/** {@inheritdoc} */
	protected function controller_service(): string {
		return 'order.rest';
	}

	/** {@inheritdoc} */
	protected function verb(): string {
		return 'delete';
	}

	/** {@inheritdoc} */
	protected function rest_base(): string {
		return 'orders';
	}

	/**
	 * {@inheritdoc}
	 * Hard-delete (force=true) is intentionally removed from the AI-callable schema.
	 * Permanent order deletion removes financial records and breaks GDPR audit trails.
	 * Soft-delete (trash) is AI-callable; hard-delete is not.
	 */
	public function get_input_schema(): array {
		$schema = parent::get_input_schema();
		unset( $schema['properties']['force'] );
		return $schema;
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/order-delete';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Delete Order', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Delete an order. Requires id (order ID). Optionally accepts force (boolean, permanently removes the order when true). Returns the deleted order object.', 'learning-management-system' );
	}
}
