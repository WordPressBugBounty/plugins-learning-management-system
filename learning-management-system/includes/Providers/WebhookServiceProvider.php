<?php
/**
 * Webhook service provider.
 *
 * @since 1.6.9
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use Exception;
use Masteriyo\Models\Webhook;
use Masteriyo\Query\WebhookQuery;
use Masteriyo\Enums\WebhookStatus;
use Masteriyo\Jobs\WebhookDeliveryJob;
use Masteriyo\Repository\WebhookRepository;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Masteriyo\RestApi\Controllers\Version1\WebhooksController;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

/**
 * Webhook service provider.
 *
 * @since 1.6.9
 */
class WebhookServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * Check if the service provider provides a specific service.
	 *
	 * @since 1.6.9
	 *
	 * @param string $id Service identifier.
	 * @return bool True if the service is provided, false otherwise.
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(
				'webhook',
				'webhook.store',
				'webhook.rest',
				'mto-webhook',
				'mto-webhook.store',
				'mto-webhook.rest',
			),
			true
		);
	}

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.6.9
	 */
	public function register(): void {
		$this->getContainer()->add( 'webhook.store', WebhookRepository::class );

		$this->getContainer()->add( 'webhook.rest', WebhooksController::class )
			->addArgument( 'permission' );
		$this->getContainer()->add( 'webhook', Webhook::class )
			->addArgument( 'webhook.store' );

		// Register based on post type.
		$this->getContainer()->add( 'mto-webhook.store', WebhookRepository::class );

		$this->getContainer()->add( 'mto-webhook.rest', WebhooksController::class )
			->addArgument( 'permission' );

		$this->getContainer()->add( 'mto-webhook', Webhook::class )
			->addArgument( 'mto-webhook.store' );
	}

	/**
	 * This method is called after all service providers are registered.
	 *
	 * @since 1.6.9
	 */
	public function boot(): void {
		( new WebhookDeliveryJob() )->init();
		add_action( 'init', array( $this, 'register_webhook_listeners' ) );
	}

	/**
	 * Initializes the webhook system by hooking up active webhooks etc.
	 *
	 * @since 1.6.9
	 */
	public function register_webhook_listeners() {
		if ( ! is_blog_installed() ) {
			return;
		}

		$query    = new WebhookQuery(
			array(
				'status'   => array( WebhookStatus::ACTIVE ),
				'paginate' => false,
				'per_page' => -1,
				'limit'    => -1,
			)
		);

		$webhooks  = $query->get_webhooks();
		$listeners = masteriyo_get_webhook_listeners();

		/**
		 * Filters boolean: True if action scheduler should be used for delivering webhooks. False otherwise.
		 *
		 * Default is False.
		 *
		 * @since 1.6.9
		 *
		 * @param boolean $queue Default is False.
		 */
		$queue = apply_filters( 'masteriyo_use_job_queue_for_webhooks', true );

		foreach ( $webhooks as $webhook ) {
			foreach ( $webhook->get_events() as $event_name ) {
				if ( ! isset( $listeners[ $event_name ] ) ) {
					continue;
				}

				$listener         = $listeners[ $event_name ];
				$deliver_callback = function( $webhook, $payload ) use ( $event_name ) {
					try {
						masteriyo_send_webhook( $event_name, $webhook, $payload );
					} catch ( Exception $e ) {
						masteriyo_get_logger()->error(
							sprintf(
								'Webhook delivery failed (sync): event="%s", webhook_id=%d, error="%s".',
								$event_name,
								$webhook['id'] ?? 0,
								$e->getMessage()
							),
							array( 'source' => 'webhooks-delivery' )
						);
						error_log( 'Webhook: ' . $e->getMessage() );
					}
				};

				if ( $queue ) {
					$deliver_callback = function( $webhook, $payload ) use ( $event_name ) {
						// AS enforces a 191-char JSON limit on args. Store the full
						// payload in a transient and pass only the transient key to AS.
						$transient_key = 'masteriyo_webhook_' . wp_generate_uuid4();
						set_transient(
							$transient_key,
							array(
								'event_name' => $event_name,
								'webhook'    => $webhook,
								'payload'    => $payload,
							),
							HOUR_IN_SECONDS
						);

						masteriyo_get_logger()->info(
							sprintf(
								'Queuing async webhook delivery: event="%s", webhook_id=%d, transient_key="%s".',
								$event_name,
								$webhook['id'] ?? 0,
								$transient_key
							),
							array( 'source' => 'webhooks-delivery' )
						);

						$as_job_id = as_enqueue_async_action(
							WebhookDeliveryJob::HOOK,
							array( 'transient_key' => $transient_key ),
							'masteriyo-webhooks'
						);

						masteriyo_get_logger()->info(
							sprintf(
								'AS job enqueued: action_id=%d, transient_key="%s".',
								(int) $as_job_id,
								$transient_key
							),
							array( 'source' => 'webhooks-delivery' )
						);
					};
				}

				call_user_func( array( $listener, 'setup' ), $deliver_callback, $webhook );
			}
		}
	}
}