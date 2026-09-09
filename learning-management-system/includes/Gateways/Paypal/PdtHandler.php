<?php
/**
 * Class Paypal_PDT_Handler file.
 *
 * @package Masteriyo\Gateways
 */

namespace Masteriyo\Gateways\Paypal;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Constants;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Gateways\Paypal\Response;
use Masteriyo\Pro\Enums\SubscriptionStatus;
use Masteriyo\Models\Order\Order;

/**
 * Handle PDT Responses from PayPal.
 */
class PdtHandler extends Response {

	/**
	 * Identity token for PDT support
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $identity_token;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $sandbox Whether to use sandbox mode or not.
	 * @param string $identity_token Identity token for PDT support.
	 */
	public function __construct( $sandbox = false, $identity_token = '' ) {
		add_action( 'masteriyo_thankyou_paypal', array( $this, 'check_response' ) );

		$this->identity_token = $identity_token;
		$this->sandbox        = $sandbox;
	}

	/**
	 * Validate a PDT transaction to ensure its authentic.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $transaction TX ID.
	 * @return bool|array False or result array if successful and valid.
	 */
	protected function validate_transaction( $transaction ) {
		$pdt = array(
			'body'        => array(
				'cmd' => '_notify-synch',
				'tx'  => $transaction,
				'at'  => $this->identity_token,
			),
			'timeout'     => 60,
			'httpversion' => '1.1',
			'user-agent'  => 'Masteriyo/' . Constants::get( 'MASTERIYO_VERSION' ),
		);

		// Post back to get a response.
		$response = wp_safe_remote_post( $this->sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr', $pdt );

		if ( is_wp_error( $response ) || strpos( $response['body'], 'SUCCESS' ) !== 0 ) {
			return false;
		}

		// Parse transaction result data.
		$transaction_result  = array_map( 'masteriyo_clean', array_map( 'urldecode', explode( "\n", $response['body'] ) ) );
		$transaction_results = array();

		foreach ( $transaction_result as $line ) {
			$line                            = explode( '=', $line );
			$transaction_results[ $line[0] ] = isset( $line[1] ) ? $line[1] : '';
		}

		if ( ! empty( $transaction_results['charset'] ) && function_exists( 'iconv' ) ) {
			foreach ( $transaction_results as $key => $value ) {
				$transaction_results[ $key ] = iconv( $transaction_results['charset'], 'utf-8', $value );
			}
		}

		return $transaction_results;
	}

	/**
	 * Decide an order's fate exclusively from the PayPal-authenticated PDT response.
	 *
	 * Browser-supplied query parameters (st/amt/cm) must never reach this
	 * decision — see issue #1284.
	 *
	 * @param array  $transaction_result Trusted fields parsed from the _notify-synch reply.
	 * @param string $submitted_tx       The tx query parameter the browser returned with.
	 * @param string $order_total        The order's total.
	 * @param string $order_currency     The order's currency code.
	 * @return array `action` is one of 'complete', 'hold', 'invalid'; `message` is the order note.
	 */
	public static function get_transaction_verdict( $transaction_result, $submitted_tx, $order_total, $order_currency ) {
		$txn_id = isset( $transaction_result['txn_id'] ) ? (string) $transaction_result['txn_id'] : '';

		if ( '' === $txn_id || ! hash_equals( $txn_id, (string) $submitted_tx ) ) {
			return array(
				'action'  => 'invalid',
				'message' => __( 'Validation error: PDT transaction ID does not match the PayPal response.', 'learning-management-system' ),
			);
		}

		$currency = isset( $transaction_result['mc_currency'] ) ? $transaction_result['mc_currency'] : '';

		if ( $currency !== $order_currency ) {
			return array(
				'action'  => 'hold',
				/* translators: %s: currency code. */
				'message' => sprintf( __( 'Validation error: PayPal currencies do not match (code %s).', 'learning-management-system' ), $currency ),
			);
		}

		$amount = isset( $transaction_result['mc_gross'] ) ? $transaction_result['mc_gross'] : '';

		// (float) turns '100.00garbage' into 100 and '' into 0, so a malformed or
		// missing gross must be rejected before it is ever cast.
		if ( ! is_numeric( $amount ) ) {
			return array(
				'action'  => 'hold',
				/* translators: %s: Amount. */
				'message' => sprintf( __( 'Validation error: PayPal gross amount is missing or not numeric (gross %s).', 'learning-management-system' ), $amount ),
			);
		}

		if ( number_format( (float) $order_total, 2, '.', '' ) !== number_format( (float) $amount, 2, '.', '' ) ) {
			return array(
				'action'  => 'hold',
				/* translators: %s: Amount. */
				'message' => sprintf( __( 'Validation error: PayPal amounts do not match (gross %s).', 'learning-management-system' ), $amount ),
			);
		}

		$status = strtolower( isset( $transaction_result['payment_status'] ) ? $transaction_result['payment_status'] : '' );

		if ( OrderStatus::COMPLETED !== $status ) {
			if ( '' === $status ) {
				return array(
					'action'  => 'hold',
					'message' => __( 'Validation error: the PayPal PDT response did not report a payment status.', 'learning-management-system' ),
				);
			}

			if ( OrderStatus::PENDING !== $status ) {
				return array(
					'action'  => 'hold',
					/* translators: %s: PayPal payment status. */
					'message' => sprintf( __( 'Payment %s via PDT.', 'learning-management-system' ), $status ),
				);
			}

			$pending_reason = isset( $transaction_result['pending_reason'] ) ? $transaction_result['pending_reason'] : '';

			return array(
				'action'  => 'hold',
				'message' => 'authorization' === $pending_reason
					? __( 'Payment authorized. Change payment status to processing or complete to capture funds.', 'learning-management-system' )
					/* translators: %s: Pending reason. */
					: sprintf( __( 'Payment pending (%s).', 'learning-management-system' ), $pending_reason ),
			);
		}

		return array(
			'action'  => 'complete',
			'message' => __( 'PDT payment completed.', 'learning-management-system' ),
		);
	}

	/**
	 * Check Response for PDT.
	 */
	public function check_response() {
		if ( empty( $_REQUEST['tx'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// The submitted tx is only a lookup key; every field that decides the
		// order's fate comes from the authenticated _notify-synch response (#1284).
		$transaction        = masteriyo_clean( wp_unslash( $_REQUEST['tx'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$transaction_result = $this->validate_transaction( $transaction );

		if ( ! $transaction_result ) {
			Paypal::log( 'Received invalid response from PayPal PDT' );
			return;
		}

		$order = empty( $transaction_result['custom'] ) ? false : $this->get_paypal_order( $transaction_result['custom'] );

		if ( ! $order || ! $order->needs_payment() ) {
			return false;
		}

		$verdict = self::get_transaction_verdict( $transaction_result, $transaction, $order->get_total(), $order->get_currency() );

		if ( 'invalid' === $verdict['action'] ) {
			Paypal::log( 'PDT validation failed: ' . $verdict['message'], 'error' );
			return;
		}

		$status = strtolower( $transaction_result['payment_status'] ?? '' );
		$txn_id = masteriyo_clean( $transaction_result['txn_id'] );

		Paypal::log( 'PDT Transaction Status: ' . masteriyo_print_r( $status, true ) );

		$order->add_meta_data( '_paypal_status', $status );

		// Decide reuse before assigning the ID: payment_on_hold() saves the
		// order, so setting a reused ID first would persist it on this order
		// too and break the cross-order uniqueness of _transaction_id.
		$txn_id_reused = $this->is_transaction_id_used_by_another_order( $txn_id, $order->get_id() );

		if ( ! $txn_id_reused ) {
			$order->set_transaction_id( $txn_id );
		}

		if ( 'complete' === $verdict['action'] ) {
			if ( $txn_id_reused ) {
				Paypal::log( 'Payment error: PDT transaction ' . $txn_id . ' is already recorded on another order.', 'error' );
				$this->payment_on_hold( $order, __( 'Validation error: PayPal transaction ID is already used on another order.', 'learning-management-system' ) );
			} else {
				// Log paypal transaction fee and payment type.
				if ( ! empty( $transaction_result['mc_fee'] ) ) {
					$order->add_meta_data( 'PayPal Transaction Fee', masteriyo_clean( $transaction_result['mc_fee'] ) );
				}
				if ( ! empty( $transaction_result['payment_type'] ) ) {
					$order->add_meta_data( 'Payment type', masteriyo_clean( $transaction_result['payment_type'] ) );
				}

				$completed = $this->payment_complete( $order, $txn_id, $verdict['message'] );

				// Only a verified, matching, unused payment that actually completed
				// may activate a subscription — recurring access is granted from its
				// status alone, and payment_complete() can refuse (lock timeout).
				if ( $completed && masteriyo_starts_with( $transaction_result['txn_type'] ?? '', 'subscr_' ) ) {
					$this->update_subscription( $order, $transaction_result );
				}
			}
		} else {
			Paypal::log( 'Payment error: ' . $verdict['message'], 'error' );
			$this->payment_on_hold( $order, $verdict['message'] );
		}
	}

	/**
	 * Whether a PayPal transaction ID is already recorded on a different order.
	 *
	 * One genuine low-value transaction must not be replayable against other
	 * orders (#1284).
	 *
	 * @param string $txn_id   Trusted PayPal transaction ID.
	 * @param int    $order_id The order being paid.
	 * @return bool
	 */
	protected function is_transaction_id_used_by_another_order( $txn_id, $order_id ) {
		global $wpdb;

		// `_transaction_id` is a generic meta key other plugins use too, so only
		// another *order* carrying it counts as a conflict.
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT pm.post_id FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = '_transaction_id' AND pm.meta_value = %s AND pm.post_id != %d AND p.post_type = 'mto-order'
				LIMIT 1",
				$txn_id,
				$order_id
			)
		);
	}

	/**
	 * Update subscription.
	 *
	 * @since 2.6.10
	 * @param Order $order Order Object.
	 * @param array $transaction_result PDT data.
	 */
	protected function update_subscription( $order, $transaction_result ) {
		if ( ! masteriyo_order_has_recurring_courses( $order ) ) {
			Paypal::log( "Order: #{$order->get_id()} does not contain recurring courses" );
			return;
		}
		$subscription = masteriyo_get_order_subscription( $order );

		// The statuses below are pro's. `masteriyo_get_order_subscription()` already
		// answers null without pro, so the second test only restates that for the
		// analysis — a free site has no subscription to be paid.
		if ( ! $subscription || ! masteriyo_service_provider_exists( 'subscription' ) ) {
			Paypal::log( "Order: #{$order->get_id()} does not have a subscription" );
			return;
		}

		$txn_type = masteriyo_strtolower( $transaction_result['txn_type'] );
		if ( 'subscr_payment' === $txn_type ) {
			$subscription->set_props(
				array(
					'status'          => SubscriptionStatus::ACTIVE,
					'transaction_id'  => $transaction_result['txn_id'],
					'subscription_id' => $transaction_result['subscr_id'],
				)
			);
			$subscription->save();
			Paypal::log( "Subscription: #{$subscription->get_id()} updated" );
		}
	}
}
