<?php
/**
 * Resource handler for Order data.
 *
 * @since 2.8.3
 */

namespace Masteriyo\Resources;

defined( 'ABSPATH' ) || exit;

/**
 * Resource handler for Order data.
 *
 * @since 2.8.3
 */
class OrderResource {

	/**
	 * Transform the resource into an array.
	 *
	 * @since 2.8.3
	 *
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array<string, mixed>
	 */
	public static function to_array( $order, $context = 'view' ) {

		$user = masteriyo_get_user( $order->get_customer_id( $context ) );

		if ( ! is_wp_error( $user ) ) {
			$author = array(
				'id'           => $user->get_id(),
				'display_name' => $user->get_display_name(),
				'avatar_url'   => $user->profile_image_url(),
			);
		}

		$course_items = $order->get_items( 'course' );

		if ( ! empty( $course_items ) ) {
			$main_item = current( $course_items );
			$main_type = 'course';
		} else {
			$course_bundle_items = $order->get_items( 'course-bundle' );
			if ( ! empty( $course_bundle_items ) ) {
				$main_item = current( $course_bundle_items );
				$main_type = 'course-bundle';
			} else {
				$main_item = null;
				$main_type = null;
			}
		}

		if ( 'course' === $main_type ) {
			$course = masteriyo_get_course( $main_item->get_course_id() );
		} elseif ( 'course-bundle' === $main_type ) {
			$course = masteriyo_get_bundle_product( $main_item->get_course_bundle_id() );
		} else {
			$course = null;
		}

		$data = array(
			'id'                   => $order->get_id(),
			'first_name'           => $user->get_display_name(),
			'parent_id'            => $order->get_parent_id(),
			'status'               => $order->get_status(),
			'currency'             => $order->get_currency(),
			'version'              => $order->get_version(),
			'prices_include_tax'   => $order->get_prices_include_tax(),
			'date_created'         => masteriyo_rest_prepare_date_response( $order->get_date_created( $context ) ),
			'date_modified'        => masteriyo_rest_prepare_date_response( $order->get_date_modified( $context ) ),
			'total'                => $order->get_total(),
			'expiry_date'          => masteriyo_rest_prepare_date_response( $order->get_expiry_date( $context ) ),
			'customer_id'          => $order->get_customer_id(),
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => $order->get_payment_method_title(),
			'transaction_id'       => $order->get_transaction_id(),
			'date_paid'            => masteriyo_rest_prepare_date_response( $order->get_date_paid() ),
			'date_completed'       => masteriyo_rest_prepare_date_response( $order->get_date_completed() ),
			'created_via'          => $order->get_created_via(),
			'customer_ip_address'  => $order->get_customer_ip_address(),
			'customer_user_agent'  => $order->get_customer_user_agent(),
			'order_key'            => $order->get_order_key(),
			'customer_note'        => $order->get_customer_note(),
			'cart_hash'            => $order->get_cart_hash(),
			'discount_total'       => $order->get_discount_total(),
			'billing_first_name'   => $order->get_billing_first_name(),
			'billing_last_name'    => $order->get_billing_last_name(),
			'billing_company'      => $order->get_billing_company(),
			'billing_address_1'    => $order->get_billing_address_1(),
			'billing_address_2'    => $order->get_billing_address_2(),
			'billing_city'         => $order->get_billing_city(),
			'billing_postcode'     => $order->get_billing_postcode(),
			'billing_country'      => $order->get_billing_country(),
			'billing_state'        => $order->get_billing_state(),
			'billing_email'        => $order->get_billing_email(),
			'billing_phone'        => $order->get_billing_phone(),
			'author'               => $author,
			'course'               => $course ? CourseResource::to_array( $course ) : null,
			'user'                 => UserResource::to_array( $user ),
		);

		/**
		 * Filter order data array resource.
		 *
		 * @since 2.8.3
		 *
		 * @param array $data Order data.
		 * @param \Masteriyo\Models\Order\Order $order Order object.
		 * @param string $context What the value is for. Valid values are view and edit.
		 */
		return apply_filters( 'masteriyo_order_resource_array', $data, $order, $context );
	}
}
