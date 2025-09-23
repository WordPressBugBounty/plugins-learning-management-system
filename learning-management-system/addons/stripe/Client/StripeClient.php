<?php
/**
 * Stripe client.
 */

namespace Masteriyo\Addons\Stripe\Client;

use Masteriyo\Addons\Stripe\Client\Contracts\StripeClientInterface;
use Masteriyo\Addons\Stripe\Client\Contracts\StripeServiceInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Stripe client.
 */
final class StripeClient implements StripeClientInterface {
	/**
	 * Service for Stripe API calls.
	 *
	 * @var StripeServiceInterface
	 */
	private $stripe_service;

	/**
	 * Constructor.
	 *
	 * @param StripeServiceInterface|null $stripe_service Stripe service instance.
	 * @param string $mode Mode (test/live).
	 */
	public function __construct( $stripe_service = null ) {
		$this->stripe_service = $stripe_service ?? new StripeService();
	}

	/**
	 * Create a new Stripe client instance.
	 *
	 * @param array $options Configuration options.
	 * @return self
	 */
	public static function create(): self {
		$stripe_service = new StripeService();
		return new self( $stripe_service );
	}

	/**
	 * Make a request through Stripe service.
	 *
	 * @param string $method HTTP method.
	 * @param string $endpoint API endpoint.
	 * @param array $data Request data.
	 * @param array $headers Additional headers.
	 * @return array|\WP_Error
	 */
	public function make_request( $method, $endpoint, $data = array(), $headers = array() ) {
		return $this->stripe_service->request( $method, $endpoint, $data, $headers );
	}

	/**
	 * Get account link.
	 *
	 * @param array $args Request arguments.
	 * @return array|\WP_Error
	 */
	public function get_account_link( array $args ) {
		return $this->make_request( 'POST', '/account/link', $args );
	}

	/**
	 * Get account details.
	 *
	 * @return array|\WP_Error
	 */
	public function get_account_details() {
		return $this->make_request( 'GET', '/account' );
	}

	/**
	 * Create payment intent.
	 *
	 * @param array $data Payment intent data.
	 * @return array|\WP_Error
	 */
	public function create_payment_intent( array $data ) {
		return $this->make_request( 'POST', '/payment_intents', $data );
	}

	/**
	 * Update payment intent.
	 *
	 * @param string $intent_id Payment intent ID.
	 * @param array $data Update data.
	 * @return array|\WP_Error
	 */
	public function update_payment_intent( string $intent_id, array $data ) {
		return $this->make_request( 'POST', "/payment_intents/{$intent_id}", $data );
	}

	/**
	 * Retrieve payment intent.
	 *
	 * @param string $intent_id Payment intent ID.
	 * @return array|\WP_Error
	 */
	public function retrieve_payment_intent( string $intent_id ) {
		return $this->make_request( 'GET', "/payment_intents/{$intent_id}" );
	}

	/**
	 * Create customer.
	 *
	 * @param array $data Customer data.
	 * @return array|\WP_Error
	 */
	public function create_customer( array $data ) {
		return $this->make_request( 'POST', '/customers', $data );
	}

	/**
	 * Retrieve customer.
	 *
	 * @param string $customer_id Customer ID.
	 * @return array|\WP_Error
	 */
	public function retrieve_customer( string $customer_id ) {
		return $this->make_request( 'GET', "/customers/{$customer_id}" );
	}

	/**
	 * Update customer.
	 *
	 * @param string $customer_id Customer ID.
	 * @param array $data Update data.
	 * @return array|\WP_Error
	 */
	public function update_customer( string $customer_id, array $data ) {
		return $this->make_request( 'POST', "/customers/{$customer_id}", $data );
	}

	/**
	 * Create price.
	 *
	 * @param array $data Price data.
	 * @return array|\WP_Error
	 */
	public function create_price( array $data ) {
		return $this->make_request( 'POST', '/prices', $data );
	}

	/**
	 * Retrieve price.
	 *
	 * @param string $price_id Price ID.
	 * @return array|\WP_Error
	 */
	public function retrieve_price( string $price_id ) {
		return $this->make_request( 'GET', "/prices/{$price_id}" );
	}

	/**
	 * Create subscription.
	 *
	 * @param array $data Subscription data.
	 * @return array|\WP_Error
	 */
	public function create_subscription( array $data ) {
		return $this->make_request( 'POST', '/subscriptions', $data );
	}

	/**
	 * Retrieve subscription.
	 *
	 * @param string $subscription_id Subscription ID.
	 * @return array|\WP_Error
	 */
	public function retrieve_subscription( string $subscription_id ) {
		return $this->make_request( 'GET', "/subscriptions/{$subscription_id}" );
	}

	/**
	 * Update subscription.
	 *
	 * @param string $subscription_id Subscription ID.
	 * @param array $data Update data.
	 * @return array|\WP_Error
	 */
	public function update_subscription( string $subscription_id, array $data ) {
		return $this->make_request( 'POST', "/subscriptions/{$subscription_id}", $data );
	}

	/**
	 * Cancel subscription.
	 *
	 * @param string $subscription_id Subscription ID.
	 * @return array|\WP_Error
	 */
	public function cancel_subscription( string $subscription_id ) {
		return $this->make_request( 'DELETE', "/subscriptions/{$subscription_id}" );
	}
}
