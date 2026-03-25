<?php
/**
 * New order webhook event listener class.
 *
 * @since 2.8.3
 */

namespace Masteriyo\Listeners\Webhook;

use Masteriyo\Abstracts\Listener;
use Masteriyo\Resources\OrderResource;
use Masteriyo\Resources\WebhookResource;

defined( 'ABSPATH' ) || exit;

/**
 * New order webhook event listener class.
 *
 * @since 2.8.3
 */
class NewOrderListener extends Listener {

	/**
	 * Event name the listener is listening to.
	 *
	 * @since 2.8.3
	 */
	protected $event_name = 'order.created';

	/**
	 * Get event label.
	 *
	 * @since 2.8.3
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'New Order', 'learning-management-system' );
	}

	/**
	 * Setup the webhook event.
	 *
	 * @since 2.8.3
	 *
	 * @param callable $deliver_callback
	 * @param \Masteriyo\Models\Webhook $webhook
	 */
	public function setup( $deliver_callback, $webhook ) {
		add_action(
			'masteriyo_new_order',
			function( $id, $order ) use ( $deliver_callback, $webhook ) {
				if ( ! $this->can_deliver( $webhook, $order->get_id() ) ) {
					return;
				}

				call_user_func_array(
					$deliver_callback,
					array(
						WebhookResource::to_array( $webhook ),
						$this->get_payload( $order, $webhook ),
					)
				);
			},
			10,
			2
		);
	}

	/**
	 * Get payload data for the currently triggered webhook.
	 *
	 * @since 2.8.3
	 *
	 * @param \Masteriyo\Models\Order\Order $order
	 * @param \Masteriyo\Models\Webhook $webhook
	 *
	 * @return array
	 */
	protected function get_payload( $order, $webhook ) {
		$data = OrderResource::to_array( $order );

		/**
		 * Filters the payload data for the currently triggered webhook.
		 *
		 * @since 2.8.3
		 *
		 * @param array $data The payload data.
		 * @param \Masteriyo\Models\Webhook $webhook
		 */
		return apply_filters( "masteriyo_webhook_payload_for_{$this->event_name}", $data, $webhook );
	}
}
