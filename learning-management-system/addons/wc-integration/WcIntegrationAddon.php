<?php
/**
 * Masteriyo WooCommerce Integration setup.
 *
 * @package Masteriyo\WcIntegration
 *
 * @since 2.2.0
 */

namespace Masteriyo\Addons\WcIntegration;

use Masteriyo\Addons\WcIntegration\Emails\WcEnrollmentEmailToStudent;
use Masteriyo\Addons\WcIntegration\Enums\WcCourseProductType;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\PostType\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo WcIntegration class.
 *
 * @class Masteriyo\Addons\WcIntegration
 * @since 2.3.8 Renamed to WcIntegrationAddon
 */

class WcIntegrationAddon {

	/**
	 * Instance of Setting class.
	 *
	 * @since 2.2.0
	 *
	 * @var \Masteriyo\Addons\WcIntegration\Setting
	 */
	public $setting = null;

	/**
	 * The single instance of the class.
	 *
	 * @since 2.5.29
	 *
	 * @var \Masteriyo\Addons\WcIntegration\WcIntegrationAddon|null
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 2.5.29
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @since 2.5.29
	 *
	 * @return \Masteriyo\Addons\WcIntegration\WcIntegrationAddon Instance.
	 */
	final public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 2.5.29
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @since 2.5.29
	 */
	public function __wakeup() {}

	/**
	 * Initialize module.
	 *
	 * @since 2.2.0
	 */
	public function init() {
		$this->setting = new Setting();
		$this->setting->init();

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.2.0
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'localize_admin_scripts' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_public_scripts' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_masteriyo_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'display_masteriyo_tab_content' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_masteriyo_tab_icon' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_masteriyo_data' ), 10, 2 );
		add_filter( 'masteriyo_ajax_handlers', array( $this, 'register_ajax_handlers' ) );
		add_filter( 'masteriyo_course_add_to_cart_url', array( $this, 'change_add_to_cart_url' ), 10, 2 );

		// Handle WooCommerce order events to Masteriyo order events.
		add_action( 'woocommerce_new_order', array( $this, 'create_user_course' ), 10 );
		add_action( 'woocommerce_update_order', array( $this, 'create_user_course' ), 10 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'change_order_status' ), 10, 4 );
		add_action( 'product_type_selector', array( $this, 'add_course_product_type' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'register_course_product_class' ), 10, 4 );
		add_action( 'woocommerce_mto_course_add_to_cart', array( $this, 'use_simple_add_to_cart_template' ) );
		add_action( 'admin_footer', array( $this, 'print_inline_scripts' ) );

		// Update the start course for course connected with WC product.
		add_filter( 'masteriyo_can_start_course', array( $this, 'update_can_start_course' ), 10, 3 );

		add_action( 'profile_update', array( $this, 'add_student_role_to_wc_customer' ) );
		add_action( 'user_register', array( $this, 'add_student_role_to_wc_customer' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'add_custom_order_meta_data' ) );
		add_filter( 'woocommerce_order_item_needs_processing', array( $this, 'course_order_item_needs_processing' ), 10, 3 );
		add_action( 'woocommerce_thankyou', array( $this, 'maybe_redirect_to_enrolled_course' ), 1 );
		add_filter( 'page_template_hierarchy', array( $this, 'remove_wc_order_confirmation_template' ), 2 );

		if ( Helper::is_wc_subscriptions_active() ) {
			add_action( 'woocommerce_mto_course_recurring_add_to_cart', array( $this, 'use_simple_add_to_cart_template' ) );
			add_filter( 'woocommerce_is_subscription', array( $this, 'modify_is_subscription' ), 10, 3 );
			add_filter( 'wcs_admin_is_subscription_product_save_request', array( $this, 'modify_is_subscription_product_save_request' ), 10, 3 );
		}

		add_action( 'masteriyo_admin_notices', array( $this, 'guest_checkout_misconfiguration_notice' ) );
		add_action( 'rest_api_init', array( $this, 'register_wc_rest_routes' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'append_unified_orders_endpoint' ) );
		add_filter( 'post_row_actions', array( $this, 'add_product_row_action' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_product_row_action_script' ) );
		add_action( 'admin_footer', array( $this, 'render_product_row_action_modal' ) );

		add_action( 'masteriyo_rest_api_register_course_routes', array( $this, 'register_rest_api_course_routes' ), 10, 3 );
		add_action( 'masteriyo_update_course', array( $this, 'update_wc_product_price' ), 10, 2 );
		add_action( 'masteriyo_before_delete_course', array( $this, 'delete_wc_product' ), 10, 2 );
		add_action( 'masteriyo_after_trash_course', array( $this, 'update_wc_product_price' ), 10, 2 );
		add_action( 'masteriyo_course_restore', array( $this, 'update_wc_product_price' ), 10, 2 );
		add_action( 'masteriyo_rest_restore_course_item', array( $this, 'update_wc_product_price' ), 10, 2 );
		add_action( 'masteriyo_update_course_bundle', array( $this, 'update_wc_product_price' ), 10, 2 );
		add_filter( 'masteriyo_rest_response_course_data', array( $this, 'append_wd_integration_data_in_response' ), 10, 4 );

		add_filter( 'masteriyo_enroll_button_class', array( $this, 'add_add_to_cart_btn_class' ), 10, 3 );
		add_filter( 'masteriyo_enroll_bundle_button_class', array( $this, 'add_bundle_add_to_cart_btn_class' ), 10, 3 );

		add_filter( 'masteriyo_single_course_add_to_cart_text', array( $this, 'add_tot_cart_btn_text' ), 99, 2 );
		add_filter( 'masteriyo_course_add_to_cart_text', array( $this, 'add_tot_cart_btn_text' ), 99, 2 );
		add_filter( 'masteriyo_rest_response_course_bundle_data', array( $this, 'append_wd_integration_data_in_response' ), 10, 4 );
		add_filter( 'masteriyo_user_has_bought_bundle', array( $this, 'check_if_user_has_completed_order' ), 10, 2 );
		add_filter( 'masteriyo_enroll_button_class', array( $this, 'remove_password_protected_class' ), 15, 2 );
		add_filter( 'masteriyo_rest_pro_before_course_clone_response', array( $this, 'delete_woocommerce_product_id_on_clone' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'localize_backend_wc_data' ), 99 );

		// A guest order is placed with customer_id = 0, so create_user_course() bails on it.
		// WooCommerce creates the account after checkout; that is the moment enrollment
		// becomes possible. Without this, a guest pays and is never enrolled.
		add_action( 'woocommerce_created_customer', array( $this, 'maybe_enroll_guest_by_email' ), 10, 3 );
	}


	/**
	 * Delete the associated WooCommerce product when a course is cloned via the REST API.
	 *
	 * This function is hooked to the 'masteriyo_rest_pro_before_course_clone_response' action.
	 * It checks if a new course ID is provided and retrieves the associated WooCommerce product ID
	 * from the course meta. If a WooCommerce product ID exists, it deletes the corresponding product
	 * from the database.
	 *
	 * @since 3.0.2
	 *
	 * @param int $new_course_id The ID of the newly cloned course.
	 * @param \WP_REST_Response $response The REST API response object.
	 *
	 * @return \WP_REST_Response The original response object, unmodified.
	 */
	public function delete_woocommerce_product_id_on_clone( $new_course_id, $response ) {

		if ( empty( $new_course_id ) || ! $new_course_id ) {
			return $response;
		}

		$wc_product_id = get_post_meta( $new_course_id, '_wc_product_id', true );

		if ( $wc_product_id ) {
			update_post_meta( $new_course_id, '_wc_product_id', false );
		}

		return $response;
	}

	/**
	 * Remove 'masteriyo-password-protected' class from the enroll button classes array if the course is a WooCommerce product and the user can't start the course.
	 *
	 * @since 1.14.2 [Free]
	 *
	 * @param string[] $classes The array of classes for the enroll button.
	 * @param \Masteriyo\Models\Course $course The course object.
	 *
	 * @return string[]
	 */
	public function remove_password_protected_class( $classes, $course ) {
		if ( ! $course instanceof \Masteriyo\Models\Course ) {
			return $classes;
		}

		if ( ! masteriyo_can_start_course( $course ) && Helper::is_course_wc_product( $course->get_id() ) ) {
			return array_diff( $classes, array( 'masteriyo-password-protected' ) );
		}

		return $classes;
	}

	/**
	 * delete wc product.
	 *
	 * @since 1.13.3 [free]
	 *
	 * @param int $item_id
	 * @param  $item
	 * @return void
	 */
	public function delete_wc_product( $item_id, $item ) {
		global $wpdb;
		$product_id = get_post_meta( $item_id, '_wc_product_id', true );

		if ( PostType::COURSE === get_post_type( $product_id ) || PostType::COURSE_BUNDLE === get_post_type( $product_id ) ) {
			wp_delete_post( $product_id, true );
			$wpdb->delete( "{$wpdb->prefix}wc_product_meta_lookup", array( 'product_id' => $product_id ), array( '%d' ) );
			$wpdb->delete( "{$wpdb->prefix}wc_reserved_stock", array( 'product_id' => $product_id ), array( '%d' ) );
			$wpdb->delete( "{$wpdb->prefix}term_relationships", array( 'object_id' => $product_id ), array( '%d' ) );
			$wpdb->delete( "{$wpdb->prefix}wc_order_product_lookup", array( 'product_id' => $product_id ), array( '%d' ) );
			$wpdb->delete( "{$wpdb->prefix}woocommerce_downloadable_product_permissions", array( 'product_id' => $product_id ), array( '%d' ) );
		}
	}

	/**
	 * wc product price update when course/course_bundle is updated.
	 *
	 * @since 2.14.4
	 *
	 * @param int $course_id
	 * @param Masteriyo\Models\Course $item
	 * @return void
	 */
	public function update_wc_product_price( $item_id, $item ) {
		if ( empty( $item_id ) || empty( $item ) ) {
			return;
		}

		$product_id = get_post_meta( $item_id, '_wc_product_id', true );

		if ( ! $product_id ) {
			return;
		}

		$product = \wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}

		$regular_price = method_exists( $item, 'get_regular_price' ) ? $item->get_regular_price() : $item->regular_price;
		$sale_price    = method_exists( $item, 'get_sale_price' ) ? $item->get_sale_price() : $item->sale_price;
		$status        = method_exists( $item, 'get_status' ) ? $item->get_status() : $item->status;

		$product->set_regular_price( $regular_price );
		$product->set_sale_price( $sale_price );
		$product->set_status( $status );

		$post_password = $product->get_post_password();

		/**
		 * It is the fix for a bug where user not enter password for buying the course.
		 *
		 * @since 1.14.2 [Free]
		 */
		if ( ! empty( $post_password ) ) {
			$product->set_post_password( '' );
		}

		$product->save();
	}

	/**
	 * check if user has completed order for course bundle
	 *
	 * @param object $course_bundle
	 * @return void
	 *
	 * @since 2.14.0
	 */
	public function check_if_user_has_completed_order( $user_has_bought_bundle, $course_bundle ) {
		if ( ! function_exists( 'wc_get_product' ) || ! $course_bundle ) {
			return $user_has_bought_bundle;
		}

		$course_bundle_id = $course_bundle->get_id();
		$user_id          = get_current_user_id();

		if ( ! $user_id ) {
			return $user_has_bought_bundle;
		}

		// Recurring bundles: check live WCS subscription status before the cached order meta (which may be absent for recurring purchases).
		if ( CourseAccessMode::RECURRING === $course_bundle->get_access_mode()
			&& function_exists( 'wcs_get_users_subscriptions' )
		) {
			$product_id = absint( get_post_meta( $course_bundle_id, '_wc_product_id', true ) );

			// Cache per user for this request so the DB is hit once even when multiple recurring bundles appear on the same page.
			static $subscription_cache = array();
			if ( ! isset( $subscription_cache[ $user_id ] ) ) {
				$subscription_cache[ $user_id ] = wcs_get_users_subscriptions( $user_id );
			}

			foreach ( $subscription_cache[ $user_id ] as $subscription ) {
				// A WC_Subscription, so the status is WooCommerce's vocabulary, not Masteriyo's.
				if ( ! $subscription->has_status( 'active' ) ) {
					continue;
				}
				foreach ( $subscription->get_items() as $item ) {
					$item_product_id = absint( $item->get_product_id() );
					// Match by linked product ID, or reverse-lookup bundle ID on the product meta.
					if ( ( $product_id && $item_product_id === $product_id )
						|| ( $course_bundle_id && absint( get_post_meta( $item_product_id, '_masteriyo_course_bundle_id', true ) ) === $course_bundle_id )
					) {
						return true;
					}
				}
			}
			return false;
		}

		// Non-recurring bundles, or recurring when WCS is unavailable: use cached order list.
		$product_id = absint( get_post_meta( $course_bundle_id, '_wc_product_id', true ) );

		if ( empty( $product_id ) ) {
			return $user_has_bought_bundle;
		}

		$user_orders = get_user_meta( $user_id, '_user_product_orders_' . $product_id, true );

		if ( ! is_array( $user_orders ) || empty( $user_orders ) ) {
			return $user_has_bought_bundle;
		}

		foreach ( $user_orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order && OrderStatus::COMPLETED === $order->get_status() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enroll guest users by email after WooCommerce creates their account.
	 *
	 * When a guest completes a WooCommerce purchase, WC may create a customer account
	 * after checkout (woocommerce_created_customer). At that point the order was
	 * placed with customer_id = 0. This method finds all completed guest orders for the
	 * same billing email, assigns them to the new customer, and calls create_user_course()
	 * so the user gets enrolled in the purchased courses.
	 *
	 * @param int    $customer_id  Newly created WC customer (WordPress user) ID.
	 * @param array  $new_customer_data Array of new customer data.
	 * @param bool   $password_generated Whether a password was auto-generated.
	 */
	public function maybe_enroll_guest_by_email( $customer_id, $new_customer_data, $password_generated ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		$email = isset( $new_customer_data['user_email'] ) ? sanitize_email( $new_customer_data['user_email'] ) : '';

		if ( empty( $email ) ) {
			return;
		}

		// Find completed guest orders (customer_id = 0) placed with this billing email.
		$orders = wc_get_orders(
			array(
				'billing_email' => $email,
				'customer_id'   => 0,
				'status'        => array( 'wc-completed' ),
				'limit'         => -1,
			)
		);

		if ( empty( $orders ) ) {
			return;
		}

		foreach ( $orders as $order ) {
			// Assign order to the new customer.
			$order->set_customer_id( $customer_id );
			$order->save();

			// Now that the order has a customer ID, trigger enrollment.
			$this->create_user_course( $order->get_id() );
		}
	}

	/**
	 * Add order id to user if course bundle is ordered.
	 *
	 * @param number $order_id
	 * @return void
	 * @since 2.14.0
	 */
	public function add_custom_order_meta_data( $order_id ) {

		if ( ! $order_id ) {
			return;
		}

		$order = \wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		foreach ( $order->get_items() as $item_id => $item ) {
			/** @var \WC_Order_Item_Product $item */
			$product_id = $item->get_product_id();

			$product = \wc_get_product( $product_id );

			if ( $product ) {
				$product_type = $product->get_type();

				if ( in_array( $product_type, array( WcCourseProductType::COURSE_BUNDLE, WcCourseProductType::COURSE_BUNDLE_RECURRING ), true ) ) {
					$user_meta_key = '_user_product_orders_' . $product_id;
					$user_orders   = get_user_meta( $user_id, $user_meta_key, true );

					if ( ! is_array( $user_orders ) ) {
						$user_orders = array();
					}

					if ( in_array( $order_id, $user_orders ) ) {
						continue;
					}

					$user_orders[] = $order_id;

					update_user_meta( $user_id, $user_meta_key, $user_orders );
				}
			}
		}
	}

	/**
	 * Modifies the text of the "Add to Cart" button for a course that is connected to a WooCommerce product.
	 *
	 * If the course is connected to a WooCommerce product, the button text will be "Add to Cart" if the product is not in the cart, or "Go to Cart" if the product is already in the cart.
	 *
	 * @since 1.11.3 [Free]
	 *
	 * @param string $text The default button text.
	 * @param \Masteriyo\Models\Course $course The course object.
	 *
	 * @return string The modified button text.
	 */
	public function add_tot_cart_btn_text( $text, $course ) {
		if ( ! $course || masteriyo_can_start_course( $course ) || ! Helper::is_add_to_cart_enable() ) {
			return $text;
		}

		$is_added_to_cart = Helper::is_course_added_to_cart( $course->get_id() );

		if ( is_null( $is_added_to_cart ) ) {
			return $text;
		}

		if ( ! $is_added_to_cart ) {
			$text = Helper::get_enroll_btn_label_before();
		}

		if ( $is_added_to_cart ) {
			$text = Helper::get_enroll_btn_label_after();
		}

		return $text;
	}

	/**
	 * Adds the 'masteriyo-add-to-cart-btn' class to the enroll button if the course is connected to a WooCommerce product.
	 *
	 * @since 1.11.3 [Free]
	 *
	 * @param string[] $class An array of class names.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param \Masteriyo\Models\CourseProgress $progress Course progress object.
	 *
	 * @return string[] The modified class array.
	 */
	public function add_add_to_cart_btn_class( $class, $course, $progress ) {

		if ( masteriyo_can_start_course( $course ) ) {
			return $class;
		}

		if ( ! $course || ! Helper::is_add_to_cart_enable() ) {
			return $class;
		}

		$is_added_to_cart = Helper::is_course_added_to_cart( $course->get_id() );

		if ( is_null( $is_added_to_cart ) ) {
			return $class;
		}

		if ( ! $is_added_to_cart ) {
			$class[] = 'masteriyo-add-to-cart-btn';
		}

		return $class;
	}

	/**
	 * Adds the 'masteriyo-add-to-cart-btn' class to the bundle enroll button if the bundle is connected to a WooCommerce product.
	 *
	 * @param string[] $class An array of class names.
	 * @param \Masteriyo\Models\Course $course_bundle Course bundle object.
	 * @param bool $user_has_bought_bundle Whether the user has already purchased the bundle.
	 *
	 * @return string[] The modified class array.
	 */
	public function add_bundle_add_to_cart_btn_class( $class, $course_bundle, $user_has_bought_bundle ) {
		if ( $user_has_bought_bundle ) {
			return $class;
		}

		if ( ! $course_bundle || ! Helper::is_add_to_cart_enable() ) {
			return $class;
		}

		$is_added_to_cart = Helper::is_course_added_to_cart( $course_bundle->get_id() );

		if ( is_null( $is_added_to_cart ) ) {
			return $class;
		}

		if ( ! $is_added_to_cart ) {
			$class[] = 'masteriyo-add-to-cart-btn';
		}

		return $class;
	}

	/**
	 * Modify is subscription.
	 *
	 * @since 2.6.11
	 * @param boolean $is_subscription Is subscription.
	 * @param int $id Product id.
	 * @param \WC_Product $product WC Product object
	 * @return boolean
	 */
	public function modify_is_subscription( $is_subscription, $id, $product ) {
		if ( $product->is_type( WcCourseProductType::COURSE_RECURRING ) || $product->is_type( WcCourseProductType::COURSE_BUNDLE_RECURRING ) ) {
			$is_subscription = true;
		}
		return $is_subscription;
	}

	/**
	 * Mark request if for subscription.
	 *
	 * @since 2.6.11
	 * @param bool $is_subscription_product_save_request Is subscription product save request.
	 * @param int $post_id Post ID.
	 * @param array $product_types Product types.
	 */
	public function modify_is_subscription_product_save_request( $is_subscription_product_save_request, $post_id, $product_types ) {
		if ( isset( $_POST['product-type'] ) && in_array( sanitize_key( $_POST['product-type'] ), array( WcCourseProductType::COURSE_RECURRING, WcCourseProductType::COURSE_BUNDLE_RECURRING ), true ) ) { // phpcs:ignore
			$is_subscription_product_save_request = true;
		}
		return $is_subscription_product_save_request;
	}

	/**
	 * Print inline scripts.
	 *
	 * @since 2.6.11
	 */
	public function print_inline_scripts() {
		if ( 'product' !== get_post_type() ) {
			return;
		}
		$scripts = '
		(function($) {
			$( "div.downloadable_files" ).parent().addClass( "hide_if_mto_course hide_if_mto_course_recurring" ).hide();
			$( ".options_group.pricing" ).addClass( "show_if_mto_course" );
			$( ".options_group.pricing, ._subscription_sign_up_fee_field, ._subscription_trial_length_field" ).addClass( "hide_if_mto_course_recurring" );
			$( ".options_group.subscription_pricing" ).addClass( "show_if_mto_course_recurring" );
			$( ".options_group.show_if_simple.show_if_external.show_if_variable" ).addClass( "show_if_mto_course show_if_mto_course_recurring show_if_mto_course_bundle show_if_mto_course_bundle_recurring" );
			if ( $( \'#product-type\' ).val() === \'mto_course_recurring\' ) {
					$(\'option[value="mto_course_recurring"]\').show();
					$(\'option[value="mto_course"]\').hide();
			} else {
					$(\'option[value="mto_course_recurring"]\').hide();
					$(\'option[value="mto_course"]\').show();
				}
			})(jQuery);
			';

		/**
		 * Changes the inline scripts for the "Product" tab in the WooCommerce product editor.
		 * @since 2.14.0
		 */
		$scripts = apply_filters( 'print_inline_scripts_for_tab_product', $scripts );

		wp_print_inline_script_tag( $scripts );
	}

	/**
	 * Use simple add to cart template for Masteriyo course product type.
	 *
	 * @since 2.2.0
	 */
	public function use_simple_add_to_cart_template() {
		wc_get_template( 'single-product/add-to-cart/simple.php' );
	}

	/**
	 * Register custom course product class.
	 *
	 * @since 2.2.0
	 *
	 * @param string $class_name Class name.
	 * @param string $product_type Product type
	 * @return array
	 */
	public function register_course_product_class( $class_name, $product_type ) {
		if ( WcCourseProductType::COURSE === $product_type ) {
			$class_name = CourseProduct::class;
		}

		if ( WcCourseProductType::COURSE_RECURRING === $product_type && Helper::is_wc_subscriptions_active() ) {
			$class_name = \Masteriyo\Addons\WcIntegration\CourseRecurringProduct::class;
		}

		return $class_name;
	}

	/**
	 * Add course product type in the product type selector.
	 *
	 * @since 2.2.0
	 *
	 * @param array $types WooCommerce product types.
	 * @return array
	 */
	public function add_course_product_type( $types ) {
		$types[ WcCourseProductType::COURSE ] = sprintf(
			/* translators: %s: the product's name */
			__( '%s Course', 'learning-management-system' ),
			masteriyo_get_plugin_name()
		);

		if ( Helper::is_wc_subscriptions_active() ) {
			$types[ WcCourseProductType::COURSE_RECURRING ] = sprintf(
				/* translators: %s: the product's name */
				__( '%s Course (Recurring)', 'learning-management-system' ),
				masteriyo_get_plugin_name()
			);
		}

		return $types;
	}

	/**
	 * Convert WC status to Masteriyo status.
	 *
	 * @since 2.2.0
	 *
	 * @param string $status WC order status.
	 *
	 * @return string
	 */
	public function convert_wc_status( $status ) {
		$map = array(
			'processing'    => OrderStatus::PROCESSING,
			'pending'       => OrderStatus::PENDING,
			'cancelled'     => OrderStatus::CANCELLED,
			'on-hold'       => OrderStatus::ON_HOLD,
			'completed'     => OrderStatus::COMPLETED,
			'refunded'      => OrderStatus::REFUNDED,
			'failed'        => OrderStatus::FAILED,
			'wc-processing' => OrderStatus::PROCESSING,
			'wc-pending'    => OrderStatus::PENDING,
			'wc-cancelled'  => OrderStatus::CANCELLED,
			'wc-on-hold'    => OrderStatus::ON_HOLD,
			'wc-completed'  => OrderStatus::COMPLETED,
			'wc-refunded'   => OrderStatus::REFUNDED,
			'wc-failed'     => OrderStatus::FAILED,
		);

		$new_status = isset( $map[ $status ] ) ? $map[ $status ] : OrderStatus::PENDING;

		return $new_status;
	}

	/**
	 * Update user course status according to WooCommerce order status.
	 *
	 * @since 2.2.0
	 *
	 * @param int $wc_order_id WC order ID.
	 * @param string $from WC order from status.
	 * @param string $to WC order to status.
	 * @param \WC_Order $wc_order WC order object.
	 */
	public function change_order_status( $wc_order_id, $from, $to, $wc_order ) {
		if ( $from === $to ) {
			return;
		}

		// Bail without an order object, or on a guest order — its customer_id
		// of 0 makes UserCourseQuery match any user's enrollment.
		if ( ! $wc_order || ! absint( $wc_order->get_customer_id() ) ) {
			return;
		}

		// Return only WC_Order_Item_Product.
		$order_items = array_filter(
			$wc_order->get_items(),
			function( $order_item ) {
				return is_a( $order_item, 'WC_Order_Item_Product' );
			}
		);

		foreach ( $order_items as $order_item ) {
			$product = wc_get_product( $order_item->get_product_id() );
			// Bail early if product doesn't exist.
			if ( ! $product ) {
				continue;
			}

			$product_type = $product->get_type();

			if ( in_array( $product_type, array( WcCourseProductType::COURSE_BUNDLE, WcCourseProductType::COURSE_BUNDLE_RECURRING ), true ) ) {
				$course_bundle = masteriyo_get_bundle_product( $product->get_meta( '_masteriyo_course_bundle_id', true ) );
				if ( ! $course_bundle ) {
					continue;
				}
				$courses = $course_bundle->get_courses();

				foreach ( $courses as $course ) {
					$course = masteriyo_get_course( $course );
					// Bail early if course doesn't exist.
					if ( ! $course ) {
						continue;
					}
					$query        = new UserCourseQuery(
						array(
							'course_id' => $course->get_id(),
							'user_id'   => $wc_order->get_customer_id(),
						)
					);
					$user_courses = current( $query->get_user_courses() );
					if ( empty( $user_courses ) ) {
						continue;
					}

					if ( OrderStatus::COMPLETED === $to ) {
						$user_courses->set_status( UserCourseStatus::ACTIVE );
					} elseif ( in_array( $to, $this->setting->get( 'unenrollment_status' ), true ) ) {
						$user_courses->set_status( UserCourseStatus::INACTIVE );
						delete_user_meta( $user_courses->get_user_id(), 'masteriyo_wc_enrollment_email_sent_' . $user_courses->get_course_id() );
					} elseif ( in_array( $wc_order->get_status(), array_merge( $this->setting->get( 'unenrollment_status' ), array( OrderStatus::PROCESSING, 'checkout-draft' ) ), true ) ) {
						$user_courses->set_status( UserCourseStatus::INACTIVE );
						delete_user_meta( $user_courses->get_user_id(), 'masteriyo_wc_enrollment_email_sent_' . $user_courses->get_course_id() );
					}

					$user_courses->save();

					if ( OrderStatus::COMPLETED === $to ) {
						$this->send_wc_enrollment_email( $user_courses, $wc_order );
					}
				}
			} else {
				$course = masteriyo_get_course( $order_item->get_meta( '_masteriyo_course_id' ) );

				if ( ! $course ) {
					continue;
				}

				// Get user courses.
				$query = new UserCourseQuery(
					array(
						'course_id' => $course->get_id(),
						'user_id'   => $wc_order->get_customer_id(),
					)
				);

				$user_course = current( $query->get_user_courses() );

				if ( empty( $user_course ) ) {
					continue;
				}

				if ( OrderStatus::COMPLETED === $to ) {
					$user_course->set_status( UserCourseStatus::ACTIVE );
				} elseif ( in_array( $wc_order->get_status(), array_merge( $this->setting->get( 'unenrollment_status' ), array( OrderStatus::PROCESSING, 'checkout-draft' ) ), true ) ) {
					$user_course->set_status( UserCourseStatus::INACTIVE );
					delete_user_meta( $user_course->get_user_id(), 'masteriyo_wc_enrollment_email_sent_' . $user_course->get_course_id() );
				}

				$user_course->save();

				if ( OrderStatus::COMPLETED === $to ) {
					$this->send_wc_enrollment_email( $user_course, $wc_order );
				}
			}
		}
	}

	/**
	 * Change to WooCommerce Add to Cart URL.
	 *
	 * @since 2.2.0
	 *
	 * @param string $url
	 * @param Masteriyo\Models\Course $course
	 *
	 * @return string
	 */
	public function change_add_to_cart_url( $url, $course ) {
		// Bail early if WC is not active.
		if ( ! function_exists( 'wc_get_product' ) ) {
			return $url;
		}

		if ( $course && masteriyo_can_start_course( $course ) ) {
			return $url;
		}

		$product_id = get_post_meta( $course->get_id(), '_wc_product_id', true );
		$product    = wc_get_product( $product_id );

		if ( ! $product || ( $product && PostStatus::PUBLISH !== $product->get_status() ) ) {
			return $url;
		}

		// Redirect non-logged-in users to login when WC guest checkout is disabled,
		// or when Masteriyo guest checkout is enabled but WC account creation at checkout is off
		// (guest can buy but won't be enrolled — force login so enrollment works).
		// Masteriyo guest checkout disabled state is handled by Masteriyo itself.
		if ( ! is_user_logged_in() ) {
			$wc_guest_disabled       = 'yes' !== get_option( 'woocommerce_enable_guest_checkout' );
			$masteriyo_guest_enabled = function_exists( 'masteriyo_is_guest_checkout_enabled' ) && masteriyo_is_guest_checkout_enabled();
			$wc_any_account_creation = 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' )
				|| 'yes' === get_option( 'woocommerce_enable_delayed_account_creation' );
			$wc_signup_disabled      = $masteriyo_guest_enabled && ! $wc_any_account_creation;

			if ( $wc_guest_disabled || $wc_signup_disabled ) {
				return masteriyo_get_account_url() . '#/sign-in';
			}
		}

		$is_added_to_cart = Helper::is_course_added_to_cart( $course->get_id() );

		if ( is_null( $is_added_to_cart ) ) {
			return $url;
		}

		if ( ! $is_added_to_cart ) {
			// Build the URL manually so ?add-to-cart=ID is always present.
			// WC's add_to_cart_url() falls back to the product permalink for non-purchasable
			// products, stripping the param the JS relies on to extract the product ID.
			$url = add_query_arg( 'add-to-cart', $product_id, $product->get_permalink() );
		}

		if ( $is_added_to_cart ) {
			$url = \wc_get_cart_url();
		}

		return $url;
	}

	/**
	 * Register ajax handlers.
	 *
	 * @since 2.2.0
	 *
	 * @param array $handlers
	 * @return array
	 */
	public function register_ajax_handlers( $handlers ) {
		$handlers[] = ListCoursesAjaxHandler::class;
		$handlers[] = AddToCartAjaxHandler::class;
		return $handlers;
	}

	/**
	 * Save masteriyo data.
	 *
	 * @since 2.2.0
	 *
	 * @param int $product_id
	 * @param WP_Post $product
	 */
	public function save_masteriyo_data( $product_id, $product ) {
		// phpcs:disable
		if ( isset( $_POST['masteriyo_course_id'] ) ) {
			$course_id = absint( $_POST['masteriyo_course_id'] );
			$course    = masteriyo_get_course( $course_id );

			if ( $course ) {
				update_post_meta( $course_id, '_wc_product_id', $product_id );
				update_post_meta( $product_id, '_masteriyo_course_id', $course_id );
				delete_post_meta($product_id,'_masteriyo_course_bundle_id');
			}
		}

		if(masteriyo_bundles_enabled() && isset( $_POST['masteriyo_course_bundle_id'] )) {
			$bundle_course_ids =  absint($_POST['masteriyo_course_bundle_id']);
			if ( isset( $_POST['masteriyo_course_bundle_id'] ) ) {

			$course_bundle    = masteriyo_get_bundle_product( $bundle_course_ids );

			if ( $course_bundle ) {
					update_post_meta( $bundle_course_ids, '_wc_product_id', $product_id );
					update_post_meta( $product_id, '_masteriyo_course_bundle_id', $bundle_course_ids );
					delete_post_meta($product_id,'_masteriyo_course_id');

			}
		}
		}
		//phpcs:enable
	}

	/**
	 * Add icon to masteriyo tab.
	 *
	 * @since 2.2.0
	 */
	public function add_masteriyo_tab_icon() {
		$handle = 'add_masteriyo_tab_icon';

		if ( ! wp_style_is( $handle, 'registered' ) ) {
			wp_register_style( $handle, false );
		}

		wp_enqueue_style( $handle );

		$inline_css = '#woocommerce-product-data ul.wc-tabs li.masteriyo_options.masteriyo_tab a:before{content:"\1F4D6";}';

		wp_add_inline_style( $handle, $inline_css );
	}

	/**
	 * Add masteriyo tab to product tabs.
	 *
	 * @since 2.2.0
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_masteriyo_tab( $tabs ) {
		$tabs[ WcCourseProductType::COURSE ] = array(
			'label'    => __( 'Course', 'learning-management-system' ),
			'target'   => 'mto_course_options',
			'class'    => array( 'show_if_mto_course', 'show_if_mto_course_recurring' ),
			'priority' => 1,
		);

		// Show general in course.
		$tabs['general']['class'][] = 'show_if_simple';
		$tabs['general']['class'][] = 'show_if_external';
		$tabs['general']['class'][] = 'show_if_giftcard';
		$tabs['general']['class'][] = 'show_if_gift-card';
		$tabs['general']['class'][] = 'show_if_mto_course show_if_mto_course_recurring';

		$tabs['inventory']['class'][] = 'show_if_mto_course show_if_mto_course_recurring';

		// Hide shipping attributes.
		$tabs['shipping']['class'][]  = 'hide_if_mto_course hide_if_mto_course_recurring';
		$tabs['attribute']['class'][] = 'hide_if_mto_course hide_if_mto_course_recurring';

		if ( masteriyo_bundles_enabled() ) {
			$tabs[ WcCourseProductType::COURSE_BUNDLE ] = array(
				'label'    => __( 'Course Bundles', 'learning-management-system' ),
				'target'   => 'mto_course_bundle_options',
				'class'    => array( 'show_if_mto_course_bundle', 'show_if_mto_course_bundle_recurring' ),
				'priority' => 1,
			);

			// Show general in course.
			$tabs['general']['class'][]   = 'show_if_mto_course_bundle show_if_mto_course_bundle_recurring';
			$tabs['inventory']['class'][] = 'show_if_mto_course_bundle show_if_mto_course_bundle_recurring';

			// Hide shipping attributes.
			$tabs['shipping']['class'][]  = 'hide_if_mto_course_bundle hide_if_mto_course_bundle_recurring';
			$tabs['attribute']['class'][] = 'hide_if_mto_course_bundle hide_if_mto_course_bundle_recurring';
		}

		return $tabs;
	}

	/**
	 * Display masteriyo tab content.
	 *
	 * @since 2.2.0
	 */
	public function display_masteriyo_tab_content() {
		if ( ! function_exists( 'woocommerce_wp_select' ) ) {
			return;
		}

		$options   = array(
			'' => esc_html__( 'Please select a course', 'learning-management-system' ),
		);
		$course_id = get_post_meta( get_the_ID(), '_masteriyo_course_id', true );

			$course = masteriyo_get_course( $course_id );
		if ( $course ) {
			$options[ $course_id ] = $course->get_name();
		}

				echo '<div id="mto_course_options" class="panel woocommerce_options_panel hidden">';

				\woocommerce_wp_select(
					array(
						'id'                => 'masteriyo_course_id',
						'value'             => $course_id,
						'wrapper_class'     => 'show_if_mto_course show_if_mto_course_recurring',
						'label'             => esc_html__( 'Course', 'learning-management-system' ),
						'desc_tip'          => true,
						'description'       => esc_html__( 'Select a course to connect with the product.', 'learning-management-system' ),
						'options'           => $options,
						'custom_attributes' => array(
							'data-course-access-mode' => $course ? $course->get_access_mode() : '',
						),
					)
				);

				echo '</div>';

		if ( masteriyo_bundles_enabled() ) {

			$bundle_course_id = get_post_meta( get_the_ID(), '_masteriyo_course_bundle_id', true );

			$bundle_course = masteriyo_get_bundle_product( $bundle_course_id );

			if ( $bundle_course ) {
				$options[ $bundle_course_id ] = $bundle_course->get_name();
			}

				echo '<div id="mto_course_bundle_options" class="panel woocommerce_options_panel hidden">';

				\woocommerce_wp_select(
					array(
						'id'                => 'masteriyo_course_bundle_id',
						'value'             => $bundle_course_id,
						'wrapper_class'     => 'show_if_mto_course_bundle show_if_mto_course_bundle_recurring',
						'label'             => esc_html__( 'Course Bundle', 'learning-management-system' ),
						'desc_tip'          => true,
						'description'       => esc_html__( 'Select a course bundle to connect with the product.', 'learning-management-system' ),
						'options'           => $options,
						'custom_attributes' => array(
							'data-course-access-mode' => $bundle_course ? $bundle_course->get_access_mode() : '',
						),
					)
				);

				echo '</div>';

		}

	}

	/**
	 * Enqueue necessary scripts.
	 *
	 * @since 2.2.0
	 *
	 * @param array $scripts
	 * @return array
	 */
	public function enqueue_scripts( $scripts ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$scripts['wc-integration'] = array(
			'src'      => plugin_dir_url( MASTERIYO_WC_INTEGRATION_ADDON_FILE ) . '/assets/js/wc-integration' . $suffix . '.js',
			'context'  => 'admin',
			'deps'     => array( 'selectWoo' ),
			'callback' => function() {
				return $this->is_wc_product_add_page() || $this->is_wc_product_edit_page();
			},
		);

		$scripts['wc-integration-add-to-cart'] = array(
			'src'      => plugin_dir_url( MASTERIYO_WC_INTEGRATION_ADDON_FILE ) . '/assets/js/add-to-cart.js',
			'context'  => 'public',
			'deps'     => array( 'jquery' ),
			'callback' => function() {
				$is_bundle_page = masteriyo_is_bundle_page()
					|| masteriyo_is_bundles_archive_page();
				return masteriyo_is_single_course_page() || masteriyo_is_courses_page() || is_tax( 'course_cat' ) || $is_bundle_page;
			},
		);

		return $scripts;
	}

	/**
	 * Localize admin scripts.
	 *
	 * @since 2.2.0
	 *
	 * @param array $scripts
	 * @return array
	 */
	public function localize_admin_scripts( $scripts ) {
		$scripts['wc-integration'] = array(
			'name' => '_MASTERIYO_WC_INTEGRATION_',
			'data' => array(
				'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
				'adminUrl'               => admin_url(),
				'nonces'                 => array(
					'listCourses' => wp_create_nonce( 'masteriyo_wc_integration_list_courses' ),
				),
				'isWCSubscriptionActive' => Helper::is_wc_subscriptions_active(),
			),
		);

		// Pass WC orders admin URL so the orders page can show a notice when add-to-cart is active and WC orders exist.
		if ( isset( $scripts['backend']['data'] ) ) {
			$scripts['backend']['data']['wcOrdersAdminUrl'] = Helper::is_add_to_cart_enable() && Helper::has_wc_orders_for_masteriyo_courses()
				? $this->get_wc_orders_admin_url()
				: '';
		}

		return $scripts;
	}

	/**
	 * Localize WC integration data for the Masteriyo admin backend bundle.
	 *
	 * The masteriyo-backend script is enqueued on all Masteriyo admin pages
	 * (including the course builder) but NOT on WooCommerce product pages.
	 * The wc-integration script handles the WC pages; this covers the builder.
	 */
	public function localize_backend_wc_data() {
		wp_localize_script(
			'masteriyo-backend',
			'_MASTERIYO_WC_INTEGRATION_',
			array(
				'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
				'adminUrl'               => admin_url(),
				'nonces'                 => array(
					'listCourses' => wp_create_nonce( 'masteriyo_wc_integration_list_courses' ),
				),
				'isWCSubscriptionActive' => Helper::is_wc_subscriptions_active(),
				'wcOrdersAdminUrl'       => Helper::is_add_to_cart_enable() && Helper::has_wc_orders_for_masteriyo_courses()
					? $this->get_wc_orders_admin_url()
					: '',
			)
		);
	}

	/**
	 * Return the WC orders admin URL, using the HPOS page when available and
	 * falling back to the legacy CPT list when HPOS is disabled.
	 *
	 * @return string
	 */
	private function get_wc_orders_admin_url() {
		return $this->is_hpos_enabled()
			? admin_url( 'admin.php?page=wc-orders' )
			: admin_url( 'edit.php?post_type=shop_order' );
	}

	/**
	 * Localize public scripts.
	 *
	 * @since 1.11.3 [Free]
	 *
	 * @param array $scripts
	 * @return array
	 */
	public function localize_public_scripts( $scripts ) {
		$scripts['wc-integration-add-to-cart'] = array(
			'name' => '_MASTERIYO_WC_INTEGRATION_ADD_TO_CART_DATA_',
			'data' => array(
				'ajaxURL'          => admin_url( 'admin-ajax.php' ),
				'cartURL'          => \wc_get_cart_url(),
				'addToCartText'    => Helper::get_enroll_btn_label_before(),
				'goToCartText'     => Helper::get_enroll_btn_label_after(),
				'addingToCartText' => __( 'Adding to Cart', 'learning-management-system' ),
				'nonces'           => array(
					'addToCart' => wp_create_nonce( 'masteriyo_wc_integration_add_to_cart' ),
				),
			),
		);

		return $scripts;
	}


	/**
	 * Return true if the page is WC product add page.
	 *
	 * @since 2.2.0
	 *
	 * @return boolean
	 */
	public function is_wc_product_add_page() {
		global $pagenow, $typenow;

		if ( 'post-new.php' === $pagenow && 'product' === $typenow ) {
			return true;
		}

		return false;
	}

	/**
	 * Return true if the page is WC product edit page.
	 *
	 * @since 2.2.0
	 *
	 * @return boolean
	 */
	public function is_wc_product_edit_page() {
		global $pagenow, $typenow;

		if ( 'post.php' === $pagenow && 'product' === $typenow ) {
			return true;
		}

		return false;
	}

	/**
	 * Create Masteriyo order when WooCommerce order is created.
	 *
	 * @since 2.2.0
	 * @since 2.5.18 Removed $wc_order parameter due to checkout and deletion issue with WC Subscription addon.
	 *
	 * @param int $wc_order_id
	 */
	public function create_user_course( $wc_order_id ) {
		// Bail early if WC is not active.
		if ( ! ( function_exists( 'wc_get_product' ) && function_exists( 'wc_get_order' ) ) ) {
			return;
		}

		$wc_order = wc_get_order( $wc_order_id );

		if ( ! $wc_order || ! absint( $wc_order->get_customer_id() ) ) {
			return;
		}

		// Return only WC_Order_Item_Product.
		$order_items = array_filter(
			$wc_order->get_items(),
			function( $order_item ) {
				return is_a( $order_item, 'WC_Order_Item_Product' );
			}
		);

		foreach ( $order_items as $order_item ) {
			$product = wc_get_product( $order_item->get_product_id() );
			// Bail early if product doesn't exist.
			if ( ! $product ) {
				continue;
			}

			$product_type = $product->get_type();

			if ( in_array( $product_type, array( WcCourseProductType::COURSE_BUNDLE, WcCourseProductType::COURSE_BUNDLE_RECURRING ), true ) ) {
				$course_bundle = masteriyo_get_bundle_product( $product->get_meta( '_masteriyo_course_bundle_id', true ) );
				if ( ! $course_bundle ) {
					continue;
				}
				$courses = $course_bundle->get_courses();
				$order_item->update_meta_data( '_masteriyo_course_bundle_id', $course_bundle->get_id() );
				$order_item->save_meta_data();
				Helper::clear_wc_orders_cache();
				foreach ( $courses as $course ) {
					$course = masteriyo_get_course( $course );

					// Bail early if course doesn't exist.
					if ( ! $course ) {
						continue;
					}

					// Get user courses.
					$query = new UserCourseQuery(
						array(
							'course_id' => $course->get_id(),
							'user_id'   => $wc_order->get_customer_id(),
						)
					);

					$user_courses = $query->get_user_courses();
					$user_course  = empty( $user_courses ) ? masteriyo( 'user-course' ) : current( $user_courses );

					if ( empty( $user_courses ) ) {
						$user_course->set_status( UserCourseStatus::INACTIVE );
					}

					$user_course->set_course_id( $course->get_id() );
					$user_course->set_user_id( $wc_order->get_customer_id() );
					$user_course->set_price( $product->get_price() );

					if ( OrderStatus::COMPLETED === $wc_order->get_status() ) {
						$this->maybe_set_enrollment_start_date( $user_course );
						$user_course->set_status( UserCourseStatus::ACTIVE );
					} elseif ( in_array( $wc_order->get_status(), $this->setting->get( 'unenrollment_status' ), true ) ) {
						$user_course->set_status( UserCourseStatus::INACTIVE );
						$user_course->set_date_start( null );
						$user_course->set_date_modified( null );
						$user_course->set_date_end( null );
					} elseif ( in_array( $wc_order->get_status(), array_merge( $this->setting->get( 'unenrollment_status' ), array( OrderStatus::PROCESSING, 'checkout-draft' ) ), true ) ) {
						$user_course->set_status( UserCourseStatus::INACTIVE );
						$user_course->set_date_start( null );
						$user_course->set_date_modified( null );
						$user_course->set_date_end( null );
					}

					$user_course->save();

					if ( $user_course->get_id() ) {
						$user_course->update_meta_data( '_wc_order_id', $wc_order_id );
						$user_course->save_meta_data();
					}

					if ( OrderStatus::COMPLETED === $wc_order->get_status() ) {
						$this->send_wc_enrollment_email( $user_course, $wc_order );
					}
				}
			} else {
				$course = masteriyo_get_course( $product->get_meta( '_masteriyo_course_id', true ) );

				// Bail early if course doesn't exist.
				if ( ! $course ) {
					continue;
				}

				// Save course id in the order item as meta.
				$order_item->update_meta_data( '_masteriyo_course_id', $course->get_id() );
				$order_item->save_meta_data();
				Helper::clear_wc_orders_cache();

				// Get user courses.
				$query = new UserCourseQuery(
					array(
						'course_id' => $course->get_id(),
						'user_id'   => $wc_order->get_customer_id(),
					)
				);

				$user_courses = $query->get_user_courses();
				$user_course  = empty( $user_courses ) ? masteriyo( 'user-course' ) : current( $user_courses );

				if ( empty( $user_courses ) ) {
					$user_course->set_status( UserCourseStatus::INACTIVE );
				}

				$user_course->set_course_id( $course->get_id() );
				$user_course->set_user_id( $wc_order->get_customer_id() );
				$user_course->set_price( $product->get_price() );

				if ( OrderStatus::COMPLETED === $wc_order->get_status() ) {
					$this->maybe_set_enrollment_start_date( $user_course );
					$user_course->set_status( UserCourseStatus::ACTIVE );
				} elseif ( in_array( $wc_order->get_status(), $this->setting->get( 'unenrollment_status' ), true ) ) {
					$user_course->set_status( UserCourseStatus::INACTIVE );
					$user_course->set_date_start( null );
					$user_course->set_date_modified( null );
					$user_course->set_date_end( null );
				}

				$user_course->save();

				if ( $user_course->get_id() ) {
					$user_course->update_meta_data( '_wc_order_id', $wc_order_id );
					$user_course->save_meta_data();
				}

				if ( OrderStatus::COMPLETED === $wc_order->get_status() ) {
					$this->send_wc_enrollment_email( $user_course, $wc_order );
				}
			}
		}
	}

	/**
	 * Start the enrollment clock only when access actually (re)starts.
	 *
	 * A plain re-save of an active enrollment keeps its original start date; a
	 * new or lapsed enrollment starts it. This stops a completed-order edit from
	 * silently extending time-limited access. Read before the status is changed.
	 *
	 * @since 2.6.0
	 *
	 * @param \Masteriyo\Models\UserCourse $user_course User course.
	 */
	protected function maybe_set_enrollment_start_date( $user_course ) {
		$access_started = UserCourseStatus::ACTIVE === $user_course->get_status( 'edit' )
			&& $user_course->get_date_start( 'edit' );

		if ( ! $access_started ) {
			$user_course->set_date_start( current_time( 'mysql', true ) );
		}
	}

	/**
	 * Update masteriyo_can_start_course() for course connected with WC product.
	 *
	 * @since 2.2.0
	 *
	 * @param bool $can_start_course Whether user can start the course.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param \Masteriyo\Models\User $user User object.
	 * @return boolean
	 */
	public function update_can_start_course( $can_start_course, $course, $user ) {
		// Bail early if WC is not active.
		if ( ! function_exists( 'wc_get_product' ) || ! $course ) {
			return $can_start_course;
		}

		$product = wc_get_product( $course->get_meta( '_wc_product_id' ) );

		if ( ! $product ) {
			return $can_start_course;
		}

		// Bail early if the course is open
		if ( CourseAccessMode::OPEN === $course->get_access_mode() ) {
			return $can_start_course;
		}

		// Bail early iif the user is not logged in
		if ( ! is_user_logged_in() ) {
			return $can_start_course;
		}

		$query = new UserCourseQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => $user->get_id(),
				'per_page'  => 1,
			)
		);

		$user_course = current( $query->get_user_courses() );

		if ( empty( $user_course ) ) {
			return $can_start_course;
		}

		$wc_order_id = $user_course->get_meta( '_wc_order_id' );
		$wc_order    = wc_get_order( $wc_order_id );

		if ( ! $wc_order ) {
			return $can_start_course;
		}

		if ( CourseAccessMode::RECURRING === $course->get_access_mode() ) {
			$subscription = function_exists( 'wcs_get_subscriptions_for_order' ) ? current( wcs_get_subscriptions_for_order( $wc_order_id ) ) : false;
			if ( ! $subscription ) {
				return $can_start_course;
			}
			// A WC_Subscription, so the status is WooCommerce's vocabulary, not Masteriyo's.
			$can_start_course = $subscription->has_status( 'active' );
		} else {
			$can_start_course = OrderStatus::COMPLETED === $wc_order->get_status();
		}

		return $can_start_course;
	}

	/**
	 * Add student role to WC customer.
	 *
	 * @since 2.4.6
	 *
	 * @param int $user_id User ID.
	 */
	public function add_student_role_to_wc_customer( $user_id ) {
		remove_action( 'profile_update', array( $this, 'add_student_role_to_wc_customer' ) );
		remove_action( 'user_register', array( $this, 'add_student_role_to_wc_customer' ) );

		try {
			$user  = masteriyo( 'user' );
			$store = masteriyo( 'user.store' );

			$user->set_id( $user_id );
			$store->read( $user );

			if ( $user->has_role( 'customer' ) && ! $user->has_role( 'masteriyo_student' ) ) {
				$user->add_role( 'masteriyo_student' );
				$user->save();
			}
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}

		add_action( 'profile_update', array( $this, 'add_student_role_to_wc_customer' ) );
		add_action( 'user_register', array( $this, 'add_student_role_to_wc_customer' ) );
	}

	/**
	 * Registers the REST API routes for the WC Integration addon.
	 *
	 * @since 1.11.0 [free]
	 *
	 * @param string $namespace The API namespace.
	 * @param string $rest_base The REST base.
	 * @param \Masteriyo\RestApi\Controllers\Version1\CoursesController $controller The Courses controller instance.
	 */
	public function register_rest_api_course_routes( $namespace, $rest_base, $controller ) {
		register_rest_route(
			$namespace,
			'/' . $rest_base . '/create-wc-product',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_wc_product' ),
					'permission_callback' => array( $controller, 'create_item_permissions_check' ),
					'args'                => array(
						'course_id'      => array(
							'default'     => 0,
							'description' => __( 'Course ID for WC product will be created.', 'learning-management-system' ),
							'required'    => true,
							'type'        => 'integer',
						),
						'product_create' => array(
							'default'     => true,
							'description' => __( 'Weather product will be created or not.', 'learning-management-system' ),
							'required'    => true,
							'type'        => 'boolean',
						),
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $rest_base . '/(?P<id>[\d]+)/link-wc-product',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'link_wc_product' ),
					'permission_callback' => array( $controller, 'update_item_permissions_check' ),
					'args'                => array(
						'id'         => array(
							'description' => __( 'Course ID.', 'learning-management-system' ),
							'required'    => true,
							'type'        => 'integer',
						),
						'product_id' => array(
							'description' => __( 'WooCommerce product ID to link.', 'learning-management-system' ),
							'required'    => true,
							'type'        => 'integer',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'unlink_wc_product' ),
					'permission_callback' => array( $controller, 'update_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Course ID.', 'learning-management-system' ),
							'required'    => true,
							'type'        => 'integer',
						),
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/' . $rest_base . '/list-wc-products',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'list_wc_products_rest' ),
					'permission_callback' => function() {
						return current_user_can( 'edit_masteriyo_courses' ) || current_user_can( 'manage_options' );
					},
					'args'                => array(
						'search' => array(
							'default'           => '',
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * List WooCommerce products via REST API for the course link-product selector.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function list_wc_products_rest( $request ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return rest_ensure_response(
				array(
					'data' => array(),
					'meta' => array( 'total' => 0 ),
				)
			);
		}

		$search = $request->get_param( 'search' );

		$args = array(
			'limit'      => 20,
			'status'     => 'publish',
			'orderby'    => 'title',
			'order'      => 'ASC',
			'type'       => array( 'simple', 'variable', 'subscription', 'variable-subscription', WcCourseProductType::COURSE, WcCourseProductType::COURSE_RECURRING ),
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_masteriyo_course_id',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$products            = wc_get_products( $args );
		$product_type_labels = wc_get_product_types();

		$data = array_map(
			function( $product ) use ( $product_type_labels ) {
				$type = $product->get_type();
				return array(
					'id'         => $product->get_id(),
					'name'       => $product->get_name(),
					'price'      => $product->get_regular_price(),
					'type'       => $type,
					'type_label' => isset( $product_type_labels[ $type ] ) ? $product_type_labels[ $type ] : $type,
				);
			},
			$products
		);

		return rest_ensure_response(
			array(
				'data' => $data,
				'meta' => array(
					'total' => count( $data ),
				),
			)
		);
	}

	/**
	 * Creates a WooCommerce product for a given Masteriyo course.
	 *
	 * @since 1.11.0 [free]
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 *
	 * @return \WP_Error|\WP_REST_Response A WP_Error object on failure, or a WP_REST_Response on success.
	 */
	public function create_wc_product( $request ) {
		$course_id      = absint( $request->get_param( 'course_id' ) ?? 0 );
		$product_create = masteriyo_string_to_bool( $request->get_param( 'product_create' ) ?? true );

		if ( $request->get_param( 'is_course_bundle' ) ) {
			$course = masteriyo_get_bundle_product( $course_id );
		} else {
			$course = masteriyo_get_course( $course_id );
		}

		if ( ! $course ) {
			return new \WP_Error( 'masteriyo_course_not_found', __( 'Course not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		if ( ! $product_create ) {
			return new \WP_Error( 'masteriyo_product_not_created', __( 'Product not created.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		if ( ! $course->get_price() ) {
			return new \WP_Error( 'masteriyo_course_price_not_set', __( 'Set a paid course price before creating a WooCommerce product.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		$product_id = absint( get_post_meta( $course_id, '_wc_product_id', true ) );

		if ( $product_id && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );

			$existing_course_types = WcCourseProductType::all();
			if ( $product && in_array( $product->get_type(), $existing_course_types, true ) ) {
				return rest_ensure_response(
					array(
						'success'    => true,
						'product_id' => $product_id,
						'message'    => __( 'Product already created.', 'learning-management-system' ),
					)
				);
			}
		}

		$is_recurring = CourseAccessMode::RECURRING === $course->get_access_mode() && Helper::is_wc_subscriptions_active();

		if ( $request->get_param( 'is_course_bundle' ) ) {
			/**
			 * Filters the WooCommerce product object to create for a course bundle.
			 *
			 * The bundle product classes belong to the course-bundle addon, which is
			 * pro; this addon ships to both products and so cannot name them. Pro
			 * answers from `CourseBundleAddon`. With no answer there is no bundle to
			 * sell, which is the correct outcome for the free product.
			 *
			 * @param object|null $product      The product object, or null.
			 * @param bool        $is_recurring Whether a recurring product was asked for.
			 * @param object      $course       The bundle model.
			 */
			$product = apply_filters( 'masteriyo_wc_new_bundle_product', null, $is_recurring, $course );

			if ( ! $product ) {
				return new \WP_Error( 'masteriyo_course_not_found', __( 'Course not found.', 'learning-management-system' ), array( 'status' => 404 ) );
			}

			$product->set_sale_price( $course->get_regular_price() );
		} else {
			$product = $is_recurring ? new CourseRecurringProduct() : new CourseProduct();
			$product->set_category_ids( $course->get_category_ids() );
			$product->set_tag_ids( $course->get_tag_ids() );
			$product->set_reviews_allowed( $course->get_reviews_allowed() );
			$product->set_catalog_visibility( $course->get_catalog_visibility() );
			$product->set_short_description( $course->get_short_description() );
		}

		$product->set_name( $course->get_title() );
		$product->set_description( $course->get_description() );
		$product->set_featured( $course->get_featured() );
		$product->set_price( $course->get_price() );
		$product->set_regular_price( $course->get_regular_price() );
		$product->set_sale_price( $course->get_sale_price() );
		$product->set_image_id( $course->get_image_id() );

		if ( $is_recurring ) {
			$billing_period      = $course->get_billing_period();
			$billing_interval    = max( 1, absint( $course->get_billing_interval() ) );
			$expire_after_months = absint( $course->get_billing_expire_after() );

			// billing_expire_after is in months; _subscription_length must be in period units
			// and a multiple of billing_interval (WCS only accepts interval-aligned values).
			$months_per_period = array(
				'day'   => 1.0 / 30,
				'week'  => 7.0 / 30,
				'month' => 1.0,
				'year'  => 12.0,
			);
			// WCS hard limits per period unit.
			$max_length_per_period = array(
				'day'   => 90,
				'week'  => 52,
				'month' => 24,
				'year'  => 5,
			);

			if ( 0 === $expire_after_months || ! isset( $months_per_period[ $billing_period ] ) ) {
				$subscription_length = 0;
			} else {
				$length_in_periods = (int) ceil( $expire_after_months / $months_per_period[ $billing_period ] );
				$length_in_periods = (int) ( ceil( $length_in_periods / $billing_interval ) * $billing_interval );

				if ( isset( $max_length_per_period[ $billing_period ] ) && $length_in_periods > $max_length_per_period[ $billing_period ] ) {
					$max_valid         = (int) ( floor( $max_length_per_period[ $billing_period ] / $billing_interval ) * $billing_interval );
					$length_in_periods = $max_valid > 0 ? $max_valid : 0;
				}

				$subscription_length = $length_in_periods;
			}

			$product->update_meta_data( '_subscription_price', $course->get_price() );
			$product->update_meta_data( '_subscription_period', $billing_period );
			$product->update_meta_data( '_subscription_period_interval', $billing_interval );
			$product->update_meta_data( '_subscription_length', $subscription_length );
		}

		$product_id = $product->save();

		if ( $product_id ) {
			update_post_meta( $course_id, '_wc_product_id', $product_id );
			if ( $request->get_param( 'is_course_bundle' ) ) {
				update_post_meta( $product_id, '_masteriyo_course_bundle_id', $course_id );
			} else {
				update_post_meta( $product_id, '_masteriyo_course_id', $course_id );
			}
		}

		return rest_ensure_response(
			array(
				'success'    => true,
				'product_id' => $product_id,
				'message'    => __( 'Product created successfully.', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Append WC integration data in course data response.
	 *
	 * @since 1.11.0 [free]
	 *
	 * @param array $data Course data.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @param \Masteriyo\RestApi\Controllers\Version1\CoursesController $controller REST courses controller object.
	 *
	 * @return array
	 */
	public function append_wd_integration_data_in_response( $data, $course, $context, $controller ) {

		// Check if $course is an instance of Course
		if ( ! ( $course instanceof \Masteriyo\Models\Course ) && ! masteriyo_is_bundle_product( $course ) ) {
			return $data;
		}

		$product_id = absint( get_post_meta( $course->get_id(), '_wc_product_id', true ) );

		$product_exists       = false;
		$linked_product_id    = 0;
		$linked_product_name  = '';
		$linked_product_price = '';

		if ( $product_id && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				if ( in_array( $product->get_type(), WcCourseProductType::all(), true ) ) {
					$product_exists       = true;
					$linked_product_id    = $product_id;
					$linked_product_name  = $product->get_name();
					$linked_product_price = $product->get_regular_price();
				} else {
					// Standard WC product linked via link-wc-product endpoint.
					$linked_product_id    = $product_id;
					$linked_product_name  = $product->get_name();
					$linked_product_price = $product->get_regular_price();
				}
			}
		}

		$data['wc_integration'] = array(
			'course_id'               => $course->get_id(),
			'product_create'          => $product_exists,
			'linked_product_id'       => $linked_product_id,
			'linked_product_name'     => $linked_product_name,
			'linked_product_price'    => $linked_product_price,
			'linked_product_edit_url' => $linked_product_id ? admin_url( 'post.php?post=' . $linked_product_id . '&action=edit' ) : '',
		);

		if ( masteriyo_is_bundle_product( $course ) ) {
			$data['wc_integration'] = array(
				'course_id'               => $course->get_id(),
				'product_create'          => $product_exists,
				'is_course_bundle'        => true,
				'linked_product_id'       => $linked_product_id,
				'linked_product_name'     => $linked_product_name,
				'linked_product_price'    => $linked_product_price,
				'linked_product_edit_url' => $linked_product_id ? admin_url( 'post.php?post=' . $linked_product_id . '&action=edit' ) : '',
			);
		}

		return $data;
	}

	/**
	 * Tell WooCommerce that Masteriyo course products don't need manual processing.
	 * When all items in an order return false, WooCommerce natively auto-completes
	 * the order via maybe_complete_order() — no direct status manipulation needed.
	 *
	 * @param bool        $needs_processing Whether the item needs processing.
	 * @param \WC_Product $product          Product object.
	 * @param int         $order_id         WooCommerce order ID.
	 * @return bool
	 */
	public function course_order_item_needs_processing( $needs_processing, $product, $order_id ) {
		if ( ! $this->setting->get( 'auto_redirect.enable', false ) ) {
			return $needs_processing;
		}

		if ( in_array( $product->get_type(), WcCourseProductType::all(), true ) ) {
			return false;
		}

		return $needs_processing;
	}

	/**
	 * Redirect the student to their enrolled courses page after WooCommerce checkout.
	 *
	 * Fires on woocommerce_thankyou (inside the order-received template). WordPress
	 * output buffering keeps headers available here, so wp_safe_redirect() works.
	 * Only redirects when order is completed (enrollment is active).
	 *
	 * @param int $order_id WooCommerce order ID.
	 */
	public function maybe_redirect_to_enrolled_course( $order_id ) {
		if ( ! $this->setting->get( 'auto_redirect.enable', false ) || ! is_user_logged_in() ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->has_status( OrderStatus::COMPLETED ) ) {
			return;
		}

		$course_ids = $this->get_course_ids_from_order( $order );
		if ( empty( $course_ids ) ) {
			return;
		}

		wp_safe_redirect( masteriyo_get_account_url() . '#/courses' );
		exit;
	}

	/**
	 * Remove the WooCommerce "order-confirmation" entry from WordPress's page template
	 * hierarchy when the current request is for a Masteriyo order-received page.
	 *
	 * WC Blocks' OrderConfirmationTemplate hooks into `page_template_hierarchy` at
	 * priority 1 and prepends `order-confirmation` whenever `is_wc_endpoint_url('order-received')`
	 * is true — which is true for ALL pages that carry the `order-received` rewrite endpoint,
	 * including Masteriyo's checkout page. This causes WordPress to load WC's block-based
	 * Order Confirmation template instead of the normal page template, preventing
	 * [masteriyo_checkout] from running and showing WC's thankyou instead of Masteriyo's.
	 *
	 * @param string[] $templates Page template hierarchy.
	 * @return string[]
	 */
	public function remove_wc_order_confirmation_template( $templates ) {
		global $wp;

		if ( ! isset( $wp->query_vars['order-received'] ) ) {
			return $templates;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		if ( 0 !== strpos( $key, 'masteriyo_order_' ) ) {
			return $templates;
		}

		return array_values(
			array_filter(
				$templates,
				function( $t ) {
					return 'order-confirmation' !== $t;
				}
			)
		);
	}

	/**
	 * Add "Create Masteriyo Course" or "Edit Course" row action to the WC product list.
	 *
	 * @param array    $actions Row actions.
	 * @param \WP_Post $post    Current post object.
	 * @return array
	 */
	public function add_product_row_action( $actions, $post ) {
		if ( 'product' !== $post->post_type ) {
			return $actions;
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return $actions;
		}

		$product   = wc_get_product( $post->ID );
		$course_id = $product ? absint( $product->get_meta( '_masteriyo_course_id', true ) ) : 0;

		if ( $course_id ) {
			$actions['masteriyo_edit_course'] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( admin_url( 'admin.php?page=masteriyo#/courses/' . $course_id . '/edit' ) ),
				esc_html__( 'Edit Course', 'learning-management-system' )
			);
		} elseif ( current_user_can( 'edit_masteriyo_courses' ) || current_user_can( 'manage_options' ) ) {
			$skip_types = array(
				WcCourseProductType::COURSE,
				WcCourseProductType::COURSE_RECURRING,
				WcCourseProductType::COURSE_BUNDLE,
				WcCourseProductType::COURSE_BUNDLE_RECURRING,
			);
			if ( $product && in_array( $product->get_type(), $skip_types, true ) ) {
				return $actions;
			}

			// Also skip products already linked to an existing course bundle.
			$bundle_id = $product ? absint( $product->get_meta( '_masteriyo_course_bundle_id', true ) ) : 0;
			if ( $bundle_id && masteriyo_get_bundle_product( $bundle_id ) ) {
				return $actions;
			}

			$wc_types   = function_exists( 'wc_get_product_types' ) ? wc_get_product_types() : array();
			$raw_type   = $product ? $product->get_type() : '';
			$type_label = isset( $wc_types[ $raw_type ] ) ? $wc_types[ $raw_type ] : ucwords( str_replace( '-', ' ', $raw_type ) );

			$actions['masteriyo_create_course'] = sprintf(
				'<a href="#" class="masteriyo-create-course-action" data-product-id="%d" data-product-name="%s" data-product-type="%s" data-product-type-label="%s">%s</a>',
				esc_attr( $post->ID ),
				esc_attr( $product ? $product->get_name() : '' ),
				esc_attr( $raw_type ),
				esc_attr( $type_label ),
				esc_html__( 'Create Course', 'learning-management-system' )
			);
		}

		return $actions;
	}

	/**
	 * Enqueue the product list row-action admin script.
	 */
	public function enqueue_product_row_action_script() {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'product' !== $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		wp_enqueue_style(
			'masteriyo-wc-product-row-action',
			plugin_dir_url( MASTERIYO_PLUGIN_FILE ) . 'assets/css/wc-integration/assets/scss/wc-product-row-action.css',
			array( 'dashicons' ),
			MASTERIYO_VERSION
		);

		wp_enqueue_script(
			'masteriyo-wc-product-row-action',
			plugin_dir_url( MASTERIYO_WC_INTEGRATION_ADDON_FILE ) . '/assets/js/wc-product-row-action.js',
			array( 'wp-api-fetch' ),
			MASTERIYO_VERSION,
			true
		);

		wp_localize_script(
			'masteriyo-wc-product-row-action',
			'_MASTERIYO_WC_ROW_ACTION_',
			array(
				'createCourseText' => __( 'Create Course', 'learning-management-system' ),
				'editCourseText'   => __( 'Edit Course', 'learning-management-system' ),
				'creatingText'     => __( 'Creating...', 'learning-management-system' ),
				'errorText'        => __( 'Failed to create course. Please try again.', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Output the "Convert to Masteriyo Course" confirmation modal on the products list screen.
	 *
	 * Only renders on edit.php?post_type=product. HTML is server-rendered so JS only
	 * handles show/hide and dynamic data — no DOM construction in JavaScript.
	 */
	public function render_product_row_action_modal() {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'product' !== $_GET['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		?>
		<div class="masteriyo-convert-course-modal masteriyo-hidden" id="masteriyo-convert-modal-overlay">
			<div class="masteriyo-overlay">
				<div class="masteriyo--modal masteriyo-convert-modal-content">
					<h4 class="masteriyo--title masteriyo-convert-modal-title-row">
						<span class="dashicons dashicons-info masteriyo-convert-modal-icon"></span>
						<?php
						echo esc_html(
							sprintf(
							/* translators: %s: the product's name */
								__( 'Convert to %s Course?', 'learning-management-system' ),
								masteriyo_get_plugin_name()
							)
						);
						?>
					</h4>
					<div class="masteriyo--content">
						<p class="masteriyo-convert-modal-desc">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: the product's name */
								__( 'To create a course from this product, its type will be changed to %s Course.', 'learning-management-system' ),
								masteriyo_get_plugin_name()
							)
						);
						?>
						</p>
						<div class="masteriyo-convert-modal-highlight">
							<div class="masteriyo-product-name" id="masteriyo-modal-product-name"></div>
							<div class="masteriyo-type-change">
								<span id="masteriyo-modal-type-from"></span>
								<span class="masteriyo-type-arrow">→</span>
								<span>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: the product's name */
										__( '%s Course', 'learning-management-system' ),
										masteriyo_get_plugin_name()
									)
								);
								?>
								</span>
							</div>
						</div>
						<label class="masteriyo-convert-modal-check-label" for="masteriyo-convert-confirm-check">
							<input type="checkbox" id="masteriyo-convert-confirm-check">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: the product's name */
									__( 'I understand the product type will change to %s Course.', 'learning-management-system' ),
									masteriyo_get_plugin_name()
								)
							);
							?>
						</label>
					</div>
					<div class="masteriyo-actions">
						<button class="masteriyo-btn masteriyo-btn-outline" id="masteriyo-modal-cancel"><?php esc_html_e( 'Cancel', 'learning-management-system' ); ?></button>
						<button class="masteriyo-btn masteriyo-btn-primary" id="masteriyo-modal-confirm" disabled><?php esc_html_e( 'Convert & Create Course', 'learning-management-system' ); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Register all WC integration REST routes.
	 */
	public function register_wc_rest_routes() {
		register_rest_route(
			'masteriyo/v1',
			'/orders/unified',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_unified_orders' ),
				'permission_callback' => function() {
					return is_user_logged_in();
				},
				'args'                => array(
					'page'     => array(
						'default' => 1,
						'type'    => 'integer',
						'minimum' => 1,
					),
					'per_page' => array(
						'default' => 10,
						'type'    => 'integer',
						'minimum' => 1,
						'maximum' => 100,
					),
					'status'   => array(
						'default' => '',
						'type'    => 'string',
					),
					'after'    => array(
						'default' => '',
						'type'    => 'string',
					),
					'before'   => array(
						'default' => '',
						'type'    => 'string',
					),
				),
			)
		);

		register_rest_route(
			'masteriyo/v1',
			'/orders/wc/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_wc_order_detail' ),
				'permission_callback' => array( $this, 'get_wc_order_detail_permissions_check' ),
				'args'                => array(
					'id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			'masteriyo/v1',
			'/courses/create-from-wc-product',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_course_from_wc_product' ),
				'permission_callback' => function() {
					return current_user_can( 'edit_masteriyo_courses' ) || current_user_can( 'manage_options' );
				},
				'args'                => array(
					'product_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);
	}

	/**
	 * REST callback: create a draft Masteriyo course from a WC product.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_course_from_wc_product( $request ) {
		$product_id = absint( $request->get_param( 'product_id' ) );
		/** @var \WC_Product $product */
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return new \WP_Error( 'masteriyo_product_not_found', __( 'Product not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$existing_course_id = absint( get_post_meta( $product_id, '_masteriyo_course_id', true ) );
		if ( $existing_course_id ) {
			return rest_ensure_response(
				array(
					'course_id' => $existing_course_id,
					'edit_url'  => admin_url( 'admin.php?page=masteriyo#/courses/' . $existing_course_id . '/edit' ),
					'message'   => __( 'Course already exists.', 'learning-management-system' ),
				)
			);
		}

		// Determine access mode from product type.
		$product_type = $product->get_type();
		$access_mode  = in_array( $product_type, array( 'subscription', 'variable-subscription' ), true )
			? CourseAccessMode::RECURRING
			: CourseAccessMode::ONE_TIME;

		$course = masteriyo( 'course' );
		$course->set_name( $product->get_name() );
		$course->set_slug( $product->get_slug() );
		$course->set_description( $product->get_description() );
		$course->set_short_description( $product->get_short_description() );
		if ( ! empty( $product->get_regular_price() ) ) {
			$course->set_regular_price( $product->get_regular_price() );
		}

		if ( ! empty( $product->get_sale_price() ) ) {
			$course->set_sale_price( $product->get_sale_price() );
		}

		$course->set_price( $product->get_price() );

		if ( ! $course->get_regular_price() ) {
			$course->set_regular_price( $product->get_price() );
		}

		$course->set_access_mode( $access_mode );
		$course->set_author_id( get_current_user_id() );
		$course->set_status( PostStatus::DRAFT );
		$course->set_reviews_allowed( $product->get_reviews_allowed() );

		if ( $product->get_sale_price() && $product->get_date_on_sale_from() ) {
			$course->set_date_on_sale_from( $product->get_date_on_sale_from() );
		}

		if ( $product->get_sale_price() && $product->get_date_on_sale_to() ) {
			$course->set_date_on_sale_to( $product->get_date_on_sale_to() );
		}

		if ( $product->get_image_id() ) {
			$course->set_featured_image( $product->get_image_id() );
		}

		if ( $product->get_purchase_note() ) {
			$course->set_purchase_note( $product->get_purchase_note() );
		}

		$course_id = $course->save();

		if ( ! $course_id ) {
			return new \WP_Error( 'masteriyo_course_create_failed', __( 'Failed to create course.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		update_post_meta( $course_id, '_wc_product_id', $product_id );
		update_post_meta( $product_id, '_masteriyo_course_id', $course_id );

		// Convert product type: subscription products become mto_course_recurring,
		// all others become mto_course.
		$target_product_type = CourseAccessMode::RECURRING === $access_mode
			? WcCourseProductType::COURSE_RECURRING
			: WcCourseProductType::COURSE;
		wp_set_object_terms( $product_id, $target_product_type, 'product_type' );

		return rest_ensure_response(
			array(
				'course_id' => $course_id,
				'edit_url'  => admin_url( 'admin.php?page=masteriyo#/courses/' . $course_id . '/edit' ),
				'message'   => __( 'Course created successfully.', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Return merged Masteriyo + WooCommerce orders for the current user.
	 *
	 * Phase 1 — collect lightweight [id, type, timestamp] pairs from each source
	 *            using its own native API (handles WC HPOS automatically).
	 * Phase 2 — merge + sort the pairs, apply pagination → get total + page slice.
	 * Phase 3 — load full order details only for the paginated items (~per_page).
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function get_unified_orders( $request ) {
		$user_id   = get_current_user_id();
		$page      = max( 1, absint( $request->get_param( 'page' ) ) );
		$per_page  = min( 100, max( 1, absint( $request->get_param( 'per_page' ) ) ) );
		$status    = sanitize_text_field( $request->get_param( 'status' ) );
		$after_ts  = $request->get_param( 'after' ) ? strtotime( sanitize_text_field( $request->get_param( 'after' ) ) ) : false;
		$before_ts = $request->get_param( 'before' ) ? strtotime( sanitize_text_field( $request->get_param( 'before' ) ) ) : false;

		list( $total, $rows ) = $this->query_orders( $user_id, $status, $after_ts, $before_ts, $page, $per_page );

		$data = array();
		foreach ( $rows as $row ) {
			$formatted = 'mto-order' === $row->post_type
				? $this->format_mto_order( (int) $row->ID )
				: $this->format_wc_order( (int) $row->ID );
			if ( $formatted ) {
				$data[] = $formatted;
			}
		}

		return rest_ensure_response(
			array(
				'data' => $data,
				'meta' => array(
					'total'        => $total,
					'pages'        => (int) ceil( $total / $per_page ),
					'per_page'     => $per_page,
					'current_page' => $page,
				),
			)
		);
	}

	/**
	 * Query unified orders (MTO + WC) with DB-level sorting and pagination.
	 *
	 * Builds two sub-queries — one for MTO orders (always in wp_posts) and one
	 * for WC orders (wp_posts on classic storage, wc_orders on HPOS) — then
	 * combines them with UNION ALL so ORDER BY + LIMIT/OFFSET execute in the DB,
	 * not in PHP. All filters (status, date range) are pushed down into each
	 * sub-query before the UNION, keeping the result set small.
	 *
	 * @param int        $user_id
	 * @param string     $status    Order status slug (empty string = no filter).
	 * @param int|false  $after_ts  Unix timestamp lower bound, or false.
	 * @param int|false  $before_ts Unix timestamp upper bound, or false.
	 * @param int        $page
	 * @param int        $per_page
	 * @return array{ 0: int, 1: array }
	 */
	private function query_orders( $user_id, $status, $after_ts, $before_ts, $page, $per_page ) {
		global $wpdb;

		// ── MTO sub-query (always in wp_posts) ────────────────────────────────
		$mto_where   = array( "p.post_type = 'mto-order'" );
		$mto_where[] = $wpdb->prepare(
			"EXISTS ( SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = '_customer_id' AND pm.meta_value = %s )",
			$user_id
		);
		if ( $status ) {
			$mto_where[] = $wpdb->prepare( 'p.post_status = %s', $status );
		} else {
			$mto_where[] = "p.post_status != 'trash'";
		}
		if ( false !== $after_ts ) {
			$mto_where[] = $wpdb->prepare( 'p.post_date >= %s', gmdate( 'Y-m-d H:i:s', $after_ts ) );
		}
		if ( false !== $before_ts ) {
			$mto_where[] = $wpdb->prepare( 'p.post_date <= %s', gmdate( 'Y-m-d H:i:s', $before_ts ) );
		}
		$mto_sub = "SELECT p.ID, 'mto-order' AS post_type, UNIX_TIMESTAMP(p.post_date) AS ts
		            FROM {$wpdb->posts} p
		            WHERE " . implode( ' AND ', $mto_where ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// ── WC sub-query (wc_orders for HPOS, wp_posts for classic storage) ──
		if ( $this->is_hpos_enabled() ) {
			$wc_table = $wpdb->prefix . 'wc_orders';
			$wc_where = array(
				$wpdb->prepare( 'o.customer_id = %d', $user_id ),
				"o.type = 'shop_order'",
			);
			if ( $status ) {
				$wc_where[] = $wpdb->prepare( 'o.status = %s', 'wc-' . ltrim( $status, 'wc-' ) );
			} else {
				$wc_where[] = "o.status != 'trash'";
			}
			if ( false !== $after_ts ) {
				$wc_where[] = $wpdb->prepare( 'o.date_created_gmt >= %s', gmdate( 'Y-m-d H:i:s', $after_ts ) );
			}
			if ( false !== $before_ts ) {
				$wc_where[] = $wpdb->prepare( 'o.date_created_gmt <= %s', gmdate( 'Y-m-d H:i:s', $before_ts ) );
			}
			$wc_sub = "SELECT o.id AS ID, 'shop_order' AS post_type, UNIX_TIMESTAMP(o.date_created_gmt) AS ts
			           FROM {$wc_table} o
			           WHERE " . implode( ' AND ', $wc_where ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			$wc_where   = array( "p.post_type = 'shop_order'" );
			$wc_where[] = $wpdb->prepare(
				"EXISTS ( SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.ID AND pm.meta_key = '_customer_user' AND pm.meta_value = %s )",
				$user_id
			);
			if ( $status ) {
				$wc_where[] = $wpdb->prepare( 'p.post_status = %s', 'wc-' . ltrim( $status, 'wc-' ) );
			} else {
				$wc_where[] = "p.post_status != 'trash'";
			}
			if ( false !== $after_ts ) {
				$wc_where[] = $wpdb->prepare( 'p.post_date >= %s', gmdate( 'Y-m-d H:i:s', $after_ts ) );
			}
			if ( false !== $before_ts ) {
				$wc_where[] = $wpdb->prepare( 'p.post_date <= %s', gmdate( 'Y-m-d H:i:s', $before_ts ) );
			}
			$wc_sub = "SELECT p.ID, 'shop_order' AS post_type, UNIX_TIMESTAMP(p.post_date) AS ts
			           FROM {$wpdb->posts} p
			           WHERE " . implode( ' AND ', $wc_where ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// ── UNION ALL — single result set, sorted and paginated in DB ─────────
		$union_sql = "( {$mto_sub} ) UNION ALL ( {$wc_sub} )"; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM ( {$union_sql} ) AS combined" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$limit  = (int) $per_page;
		$offset = (int) ( ( $page - 1 ) * $per_page );
		$rows   = $wpdb->get_results( "SELECT ID, post_type, ts FROM ( {$union_sql} ) AS combined ORDER BY ts DESC LIMIT {$limit} OFFSET {$offset}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array( $total, $rows );
	}

	/**
	 * Format a single Masteriyo order for the unified orders response.
	 *
	 * @param int $order_id
	 * @return array|null
	 */
	private function format_mto_order( $order_id ) {
		$order = masteriyo_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		$course_name = '';
		foreach ( $order->get_items() as $item ) {
			if ( 'course' === $item->get_type() ) {
				$course_name = get_post_field( 'post_title', (int) $item->get_course_id() );
				break;
			}
		}

		return array(
			'id'              => $order->get_id(),
			'post_type'       => 'mto-order',
			'status'          => $order->get_status(),
			'total'           => $order->get_total(),
			'total_formatted' => $order->get_rest_formatted_total(),
			'date'            => $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0,
			'course_name'     => $course_name,
		);
	}

	/**
	 * Format a single WooCommerce order for the unified orders response.
	 *
	 * @param int $order_id
	 * @return array|null
	 */
	private function format_wc_order( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		$course_name = '';
		foreach ( $order->get_items() as $item ) {
			$course_id = $item->get_meta( '_masteriyo_course_id' );
			if ( $course_id ) {
				$course_name = get_post_field( 'post_title', (int) $course_id );
				break;
			}
		}
		if ( ! $course_name ) {
			$items = $order->get_items();
			$first = reset( $items );
			if ( $first ) {
				$course_name = html_entity_decode( $first->get_name(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			}
		}

		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' )
			? html_entity_decode( get_woocommerce_currency_symbol( $order->get_currency() ) )
			: $order->get_currency();

		return array(
			'id'              => $order->get_id(),
			'post_type'       => 'shop_order',
			'status'          => $order->get_status(),
			'total'           => $order->get_total(),
			'total_formatted' => $currency_symbol . number_format_i18n( (float) $order->get_total(), 2 ),
			'date'            => $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0,
			'course_name'     => $course_name,
		);
	}

	/**
	 * Send the WC enrollment email to the student if enabled.
	 *
	 * @param \Masteriyo\Models\UserCourse $user_course
	 * @param \WC_Order                    $wc_order
	 */
	private function send_wc_enrollment_email( $user_course, $wc_order ) {
		$email = new WcEnrollmentEmailToStudent();

		if ( ! $email->is_enabled() ) {
			return;
		}

		$email->trigger( $user_course, $wc_order );
	}

	/**
	 * Whether WooCommerce HPOS (High-Performance Order Storage) is active.
	 *
	 * @return bool
	 */
	private function is_hpos_enabled() {
		return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Check if the current user can read a specific WC order.
	 *
	 * Used as the permission_callback for /orders/wc/{id}. Ownership is verified
	 * here so the main callback never needs to repeat it.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return true|\WP_Error
	 */
	public function get_wc_order_detail_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( ! function_exists( 'wc_get_order' ) ) {
			return false;
		}

		$order = wc_get_order( absint( $request->get_param( 'id' ) ) );

		if ( ! $order ) {
			return new \WP_Error( 'masteriyo_rest_wc_order_invalid_id', __( 'Invalid ID.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		return (int) $order->get_customer_id() === get_current_user_id();
	}

	/**
	 * Return a single WC order in the same shape as the Masteriyo OrderSchema.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_wc_order_detail( $request ) {
		$order = wc_get_order( absint( $request->get_param( 'id' ) ) );

		if ( ! $order ) {
			return new \WP_Error( 'masteriyo_rest_wc_order_invalid_id', __( 'Invalid ID.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$course_lines = array();
		foreach ( $order->get_items() as $item ) {
			/** @var \WC_Order_Item_Product $item */
			$course_lines[] = array(
				'id'       => $item->get_id(),
				'name'     => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'subtotal' => $item->get_subtotal(),
			);
		}

		$coupon_lines = array();
		foreach ( $order->get_coupon_codes() as $code ) {
			$coupon_lines[] = array(
				'id'   => 0,
				'code' => $code,
			);
		}

		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' )
			? get_woocommerce_currency_symbol( $order->get_currency() )
			: $order->get_currency();

		return rest_ensure_response(
			array(
				'id'              => $order->get_id(),
				'status'          => $order->get_status(),
				'date_created'    => $order->get_date_created() ? $order->get_date_created()->format( 'Y-m-d H:i:s' ) : '',
				'total'           => (float) $order->get_total(),
				'currency_symbol' => $currency_symbol,
				'payment_method'  => $order->get_payment_method_title(),
				'transaction_id'  => $order->get_transaction_id(),
				'customer_note'   => $order->get_customer_note(),
				'tax_total'       => (float) $order->get_total_tax(),
				'discount_total'  => (float) $order->get_discount_total(),
				'billing'         => array(
					'first_name' => $order->get_billing_first_name(),
					'last_name'  => $order->get_billing_last_name(),
					'email'      => $order->get_billing_email(),
					'phone'      => $order->get_billing_phone(),
					'company'    => $order->get_billing_company(),
					'address_1'  => $order->get_billing_address_1(),
					'address_2'  => $order->get_billing_address_2(),
					'city'       => $order->get_billing_city(),
					'postcode'   => $order->get_billing_postcode(),
					'state'      => $order->get_billing_state(),
					'country'    => $order->get_billing_country(),
				),
				'course_lines'    => $course_lines,
				'coupon_lines'    => $coupon_lines,
			)
		);
	}

	/**
	 * Append the unified orders endpoint URL to the account page localized scripts.
	 *
	 * @param array $scripts Localized scripts array.
	 * @return array
	 */
	public function append_unified_orders_endpoint( $scripts ) {
		if ( isset( $scripts['account']['data'] ) && Helper::has_wc_orders_for_masteriyo_courses() ) {
			$scripts['account']['data']['unifiedOrdersEndpoint'] = true;
		}

		return $scripts;
	}

	/**
	 * Show an admin notice when Masteriyo and WooCommerce guest checkout settings are mismatched,
	 * or when account creation during checkout is disabled while guest checkout is active.
	 */
	public function guest_checkout_misconfiguration_notice() {
		$masteriyo_guest_enabled = masteriyo_string_to_bool( masteriyo_get_setting( 'general.registration.enable_guest_checkout' ) );

		// Only relevant when Masteriyo guest checkout is enabled — WC side must match.
		if ( ! $masteriyo_guest_enabled ) {
			return;
		}

		$wc_guest_enabled          = 'yes' === get_option( 'woocommerce_enable_guest_checkout' );
		$wc_signup_enabled         = 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout' );
		$wc_delayed_signup_enabled = 'yes' === get_option( 'woocommerce_enable_delayed_account_creation' );
		$wc_account_settings_url   = admin_url( 'admin.php?page=wc-settings&tab=account' );
		$wc_guest_url              = $wc_account_settings_url . '#woocommerce_enable_guest_checkout';

		if ( ! $wc_guest_enabled ) {
			printf(
				'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
				esc_html( masteriyo_get_plugin_name() . ':' ),
				wp_kses(
					sprintf(
						/* translators: %s: Anchor tag linking to WooCommerce account settings */
						__( 'WooCommerce guest checkout is off. Guests cannot purchase WooCommerce-linked courses. %s.', 'learning-management-system' ),
						'<a href="' . esc_url( $wc_guest_url ) . '">' . esc_html__( 'Enable WooCommerce guest checkout', 'learning-management-system' ) . '</a>'
					),
					array( 'a' => array( 'href' => array() ) )
				)
			);
		}

		// woocommerce_created_customer fires only when WC creates an account — either "During checkout"
		// or "After checkout (recommended)". If neither is on, guests complete payment but are never enrolled.
		if ( $wc_guest_enabled && ! $wc_signup_enabled && ! $wc_delayed_signup_enabled ) {
			printf(
				'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
				esc_html( masteriyo_get_plugin_name() . ':' ),
				wp_kses(
					sprintf(
						/* translators: %s: Anchor tag linking to WooCommerce account settings */
						__( 'WooCommerce account creation at checkout is off. Guests who purchase a course cannot be enrolled. Enable "After checkout (recommended)" or "During checkout" under %s.', 'learning-management-system' ),
						'<a href="' . esc_url( $wc_account_settings_url ) . '">' . esc_html__( 'WooCommerce → Settings → Account creation', 'learning-management-system' ) . '</a>'
					),
					array( 'a' => array( 'href' => array() ) )
				)
			);
		}
	}

	/**
	 * Extract all Masteriyo course IDs from a WooCommerce order.
	 * Handles single courses, recurring courses, and course bundles.
	 *
	 * @param \WC_Order $order WooCommerce order object.
	 * @return int[]
	 */
	private function get_course_ids_from_order( $order ) {
		$course_ids = array();

		foreach ( $order->get_items() as $item ) {
			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}

			$product = wc_get_product( $item->get_product_id() );
			if ( ! $product ) {
				continue;
			}

			if ( in_array( $product->get_type(), array( WcCourseProductType::COURSE_BUNDLE, WcCourseProductType::COURSE_BUNDLE_RECURRING ), true ) ) {
				$bundle = masteriyo_get_bundle_product( $product->get_meta( '_masteriyo_course_bundle_id', true ) );
				if ( $bundle ) {
					foreach ( $bundle->get_courses() as $course_id ) {
						$course_ids[] = absint( $course_id );
					}
				}
			} elseif ( in_array( $product->get_type(), array( WcCourseProductType::COURSE, WcCourseProductType::COURSE_RECURRING ), true ) ) {
				$course_id = absint( $product->get_meta( '_masteriyo_course_id', true ) );
				if ( $course_id ) {
					$course_ids[] = $course_id;
				}
			}
		}

		return array_unique( array_filter( $course_ids ) );
	}

	/**
	 * Link an existing WooCommerce product to a Masteriyo course.
	 *
	 * Saves `_wc_product_id` on the course and `_masteriyo_course_id` on the product.
	 * Syncs WC product price → course regular price (product is source of truth on link).
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function link_wc_product( $request ) {
		if ( ! current_user_can( 'edit_masteriyo_courses' ) && ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'masteriyo_forbidden', __( 'You do not have permission to link this product.', 'learning-management-system' ), array( 'status' => 403 ) );
		}

		$course_id  = absint( $request->get_param( 'id' ) );
		$product_id = absint( $request->get_param( 'product_id' ) );

		$course = masteriyo_get_course( $course_id );
		if ( ! $course ) {
			return new \WP_Error( 'masteriyo_course_not_found', __( 'Course not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return new \WP_Error( 'masteriyo_wc_not_active', __( 'WooCommerce is not active.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new \WP_Error( 'masteriyo_wc_product_not_found', __( 'WooCommerce product not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		// Block if the product is already linked to a different course.
		$existing_course_id = absint( $product->get_meta( '_masteriyo_course_id', true ) );
		if ( $existing_course_id && $existing_course_id !== $course_id ) {
			return new \WP_Error(
				'masteriyo_product_already_linked',
				__( 'This product is already linked to another course.', 'learning-management-system' ),
				array( 'status' => 409 )
			);
		}

		// Save cross-reference meta.
		update_post_meta( $course_id, '_wc_product_id', $product_id );
		$product->update_meta_data( '_masteriyo_course_id', $course_id );
		$product->save_meta_data();

		// Sync WC product regular price → course.
		$wc_price = $product->get_regular_price();
		if ( '' !== $wc_price ) {
			$course->set_regular_price( $wc_price );
			$course->set_price( $wc_price );
			$course->save();
		}

		// Convert product type via WC's data store so update_version_and_type() fires correctly.
		// Instantiating CourseProduct with the existing ID reads all current data from DB,
		// then save() sets the product_type taxonomy to mto_course through WC's own mechanism.
		$current_type = $product->get_type();
		try {
			if ( Helper::is_wc_subscriptions_active() && in_array( $current_type, array( 'subscription', 'variable-subscription' ), true ) ) {
				$typed_product = new CourseRecurringProduct( $product_id );
			} else {
				$typed_product = new CourseProduct( $product_id );
			}
			$typed_product->save();
			$product_type = $typed_product->get_type();
		} catch ( \Exception $e ) {
			masteriyo_get_logger()->error(
				sprintf( 'Failed to convert product %d type: %s', $product_id, $e->getMessage() ),
				array( 'source' => 'wc-integration' )
			);
			$product_type = $current_type;
		}

		return rest_ensure_response(
			array(
				'success'      => true,
				'course_id'    => $course_id,
				'product_id'   => $product_id,
				'product_type' => $product_type,
				'message'      => __( 'Product linked successfully.', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Unlink a WooCommerce product from a Masteriyo course.
	 *
	 * Removes `_wc_product_id` from the course and `_masteriyo_course_id` from the product.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function unlink_wc_product( $request ) {
		$course_id = absint( $request->get_param( 'id' ) );

		$course = masteriyo_get_course( $course_id );
		if ( ! $course ) {
			return new \WP_Error( 'masteriyo_course_not_found', __( 'Course not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$product_id = absint( get_post_meta( $course_id, '_wc_product_id', true ) );

		delete_post_meta( $course_id, '_wc_product_id' );

		if ( $product_id && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$product->delete_meta_data( '_masteriyo_course_id' );
				$product->save_meta_data();
			}
		}

		return rest_ensure_response(
			array(
				'success'   => true,
				'course_id' => $course_id,
				'message'   => __( 'Product unlinked successfully.', 'learning-management-system' ),
			)
		);
	}
}
