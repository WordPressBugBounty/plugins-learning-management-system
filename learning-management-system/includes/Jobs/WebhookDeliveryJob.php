<?php
/**
 * Webhook delivery job handler class.
 *
 * @since 1.6.9
 */

namespace Masteriyo\Jobs;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Webhook delivery job handler class.
 *
 * @since 1.6.9
 */
class WebhookDeliveryJob {

	/**
	 * Hook to run the job.
	 *
	 * @since 1.6.9
	 */
	const HOOK = 'masteriyo/job/webhook';

	/**
	 * Initialize.
	 *
	 * @since 1.6.9
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.6.9
	 */
	protected function init_hooks() {
		add_action( self::HOOK, array( $this, 'deliver_webhook' ), 10, 1 );
	}

	/**
	 * Deliver webhook data.
	 *
	 * @since 1.6.9
	 *
	 * @param string $transient_key Transient key storing event_name, webhook, and payload.
	 */
	public function deliver_webhook( $transient_key ) {
		masteriyo_get_logger()->info(
			sprintf(
				'Webhook job started: transient_key="%s".',
				$transient_key
			),
			array( 'source' => 'webhooks-delivery' )
		);

		$data = get_transient( $transient_key );

		if ( ! $data ) {
			masteriyo_get_logger()->warning(
				sprintf(
					'Webhook job: data not found or expired for transient_key="%s". Skipping.',
					$transient_key
				),
				array( 'source' => 'webhooks-delivery' )
			);
			return;
		}

		delete_transient( $transient_key );

		$event_name = $data['event_name'];
		$webhook    = $data['webhook'];
		$payload    = $data['payload'];

		masteriyo_get_logger()->info(
			sprintf(
				'Webhook job delivering: event="%s", webhook_id=%d, url="%s".',
				$event_name,
				$webhook['id'] ?? 0,
				$webhook['delivery_url'] ?? 'unknown'
			),
			array( 'source' => 'webhooks-delivery' )
		);

		try {
			masteriyo_send_webhook( $event_name, $webhook, $payload );

			masteriyo_get_logger()->info(
				sprintf(
					'Webhook job completed successfully: event="%s", webhook_id=%d.',
					$event_name,
					$webhook['id'] ?? 0
				),
				array( 'source' => 'webhooks-delivery' )
			);
		} catch ( Exception $e ) {
			masteriyo_get_logger()->error(
				sprintf(
					'Webhook job failed: event="%s", webhook_id=%d, error="%s".',
					$event_name,
					$webhook['id'] ?? 0,
					$e->getMessage()
				),
				array( 'source' => 'webhooks-delivery' )
			);
			error_log( 'Webhook: ' . $e->getMessage() );
		}
	}
}