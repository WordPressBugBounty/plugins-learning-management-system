<?php

namespace Masteriyo\Addons\Stripe\Client\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for Stripe client implementations.
 */
interface StripeClientInterface {

	/**
	 * Get account link.
	 * @param array $args Request arguments.
	 * @return array|\WP_Error
	 */
	public function get_account_link( array $args );

	/**
	 * Get account details.
	 * @return array|\WP_Error
	 */
	public function get_account_details();

	/**
	 * Create payment intent.
	 * @param array $data Payment intent data.
	 * @return array|\WP_Error
	 */
	public function create_payment_intent( array $data );

	/**
	 * Update payment intent.
	 * @param string $intent_id Payment intent ID.
	 * @param array $data Update data.
	 * @return array|\WP_Error
	 */
	public function update_payment_intent( string $intent_id, array $data );

	/**
	 * Retrieve payment intent.
	 * @param string $intent_id Payment intent ID.
	 * @return array|\WP_Error
	 */
	public function retrieve_payment_intent( string $intent_id );

	/**
	 * Create customer.
	 * @param array $data Customer data.
	 * @return array|\WP_Error
	 */
	public function create_customer( array $data );

	/**
	 * Retrieve customer.
	 * @param string $customer_id Customer ID.
	 * @return array|\WP_Error
	 */
	public function retrieve_customer( string $customer_id );

	/**
	 * Update customer.
	 * @param string $customer_id Customer ID.
	 * @param array $data Update data.
	 * @return array|\WP_Error
	 */
	public function update_customer( string $customer_id, array $data );

	/**
	 * Create price.
	 * @param array $data Price data.
	 * @return array|\WP_Error
	 */
	public function create_price( array $data );

	/**
	 * Retrieve price.
	 * @param string $price_id Price ID.
	 * @return array|\WP_Error
	 */
	public function retrieve_price( string $price_id );

	/**
	 * Create subscription.
	 * @param array $data Subscription data.
	 * @return array|\WP_Error
	 */
	public function create_subscription( array $data );

	/**
	 * Retrieve subscription.
	 * @param string $subscription_id Subscription ID.
	 * @return array|\WP_Error
	 */
	public function retrieve_subscription( string $subscription_id );

	/**
	 * Update subscription.
	 * @param string $subscription_id Subscription ID.
	 * @param array $data Update data.
	 * @return array|\WP_Error
	 */
	public function update_subscription( string $subscription_id, array $data );
}
