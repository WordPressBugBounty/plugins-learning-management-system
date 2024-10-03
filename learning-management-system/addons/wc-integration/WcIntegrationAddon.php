<?php
/**
 * Masteriyo WooCommerce Integration setup.
 *
 * @package Masteriyo\WcIntegration
 *
 * @since 1.8.1
 */

namespace Masteriyo\Addons\WcIntegration;

use Masteriyo\Enums\OrderStatus;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Pro\Enums\SubscriptionStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo WcIntegration class.
 *
 * @class Masteriyo\Addons\WcIntegration
 * @since 1.8.1
 */

class WcIntegrationAddon {

	/**
	 * Instance of Setting class.
	 *
	 * @since 1.8.1
	 *
	 * @var \Masteriyo\Addons\WcIntegration\Setting
	 */
	public $setting = null;

	/**
	 * The single instance of the class.
	 *
	 * @since 1.8.1
	 *
	 * @var \Masteriyo\Addons\WcIntegration\WcIntegrationAddon|null
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.8.1
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @since 1.8.1
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
	 * @since 1.8.1
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @since 1.8.1
	 */
	public function __wakeup() {}

	/**
	 * Initialize module.
	 *
	 * @since 1.8.1
	 */
	public function init() {
		$this->setting = new Setting();
		$this->setting->init();

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.8.1
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'localize_admin_scripts' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_public_scripts' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_masteriyo_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'display_masteriyo_tab_content' ) );
		add_action( 'admin_head', array( $this, 'add_masteriyo_tab_icon' ) );
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

		if ( Helper::is_wc_subscriptions_active() ) {
			add_action( 'woocommerce_mto_course_recurring_add_to_cart', array( $this, 'use_simple_add_to_cart_template' ) );
			add_filter( 'woocommerce_is_subscription', array( $this, 'modify_is_subscription' ), 10, 3 );
			add_filter( 'wcs_admin_is_subscription_product_save_request', array( $this, 'modify_is_subscription_product_save_request' ), 10, 3 );
		}

		add_action( 'masteriyo_rest_api_register_course_routes', array( $this, 'register_rest_api_course_routes' ), 10, 3 );
		add_action( 'masteriyo_update_course', array( $this, 'update_wc_product_price' ), 10, 2 );
		add_action( 'masteriyo_before_delete_course', array( $this, 'delete_wc_product' ), 10, 2 );
		add_action( 'masteriyo_after_trash_course', array( $this, 'update_wc_product_price' ), 10, 2 );
		add_action( 'masteriyo_course_restore', array( $this, 'update_wc_product_price' ), 10, 2 );
		add_action( 'masteriyo_rest_restore_course_item', array( $this, 'update_wc_product_price' ), 10, 2 );
		add_filter( 'masteriyo_rest_response_course_data', array( $this, 'append_wd_integration_data_in_response' ), 10, 4 );
		add_filter( 'masteriyo_enroll_button_class', array( $this, 'add_add_to_cart_btn_class' ), 10, 3 );

		add_filter( 'masteriyo_single_course_add_to_cart_text', array( $this, 'add_tot_cart_btn_text' ), 99, 2 );
		add_filter( 'masteriyo_course_add_to_cart_text', array( $this, 'add_tot_cart_btn_text' ), 99, 2 );
	}

	/**
	 * delete wc product.
	 *
	 * @since 1.13.3
	 *
	 * @param int $item_id
	 * @param  $item
	 * @return void
	 */
	public function delete_wc_product( $item_id, $item ) {
		global $wpdb;
		$product_id = get_post_meta( $item_id, '_wc_product_id', true );

		if ( PostType::COURSE === get_post_type( $product_id ) ) {
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
	 * @since 1.13.3
	 *
	 * @param int $item_id
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

		$product->save();
	}



	/**
	 * Modifies the text of the "Add to Cart" button for a course that is connected to a WooCommerce product.
	 *
	 * If the course is connected to a WooCommerce product, the button text will be "Add to Cart" if the product is not in the cart, or "Go to Cart" if the product is already in the cart.
	 *
	 * @since 1.11.3
	 *
	 * @param string $text The default button text.
	 * @param \Masteriyo\Models\Course $course The course object.
	 *
	 * @return string The modified button text.
	 */
	public function add_tot_cart_btn_text( $text, $course ) {
		if ( ! $course || ! Helper::is_add_to_cart_enable() ) {
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
	 * @since 1.11.3
	 *
	 * @param string[] $class An array of class names.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param \Masteriyo\Models\CourseProgress $progress Course progress object.
	 *
	 * @return string[] The modified class array.
	 */
	public function add_add_to_cart_btn_class( $class, $course, $progress ) {

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
	 * Modify is subscription.
	 *
	 * @since 1.8.1
	 * @param boolean $is_subscription Is subscription.
	 * @param int $id Product id.
	 * @param \WC_Product $product WC Product object
	 * @return boolean
	 */
	public function modify_is_subscription( $is_subscription, $id, $product ) {
		if ( $product->is_type( 'mto_course_recurring' ) ) {
			$is_subscription = true;
		}
		return $is_subscription;
	}

	/**
	 * Mark request if for subscription.
	 *
	 * @since 1.8.1
	 * @param bool $is_subscription_product_save_request Is subscription product save request.
	 * @param int $post_id Post ID.
	 * @param array $product_types Product types.
	 */
	public function modify_is_subscription_product_save_request( $is_subscription_product_save_request, $post_id, $product_types ) {
		if ( isset( $_POST['product-type'] ) && 'mto_course_recurring' === sanitize_key( $_POST['product-type'] ) ) { // phpcs:ignore
			$is_subscription_product_save_request = true;
		}
		return $is_subscription_product_save_request;
	}

	/**
	 * Print inline scripts.
	 *
	 * @since 1.8.1
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
			if ( $( \'#product-type\' ).val() === \'mto_course_recurring\' ) {
				$(\'option[value="mto_course_recurring"]\').show();
				$(\'option[value="mto_course"]\').hide();
			} else {
				$(\'option[value="mto_course_recurring"]\').hide();
				$(\'option[value="mto_course"]\').show();
			}
		})(jQuery);
		';

		wp_print_inline_script_tag( $scripts );
	}

	/**
	 * Use simple add to cart template for Masteriyo course product type.
	 *
	 * @since 1.8.1
	 */
	public function use_simple_add_to_cart_template() {
		wc_get_template( 'single-product/add-to-cart/simple.php' );
	}

	/**
	 * Register custom course product class.
	 *
	 * @since 1.8.1
	 *
	 * @param string $class_name Class name.
	 * @param string $product_type Product type
	 * @return array
	 */
	public function register_course_product_class( $class_name, $product_type ) {
		if ( 'mto_course' === $product_type ) {
			$class_name = CourseProduct::class;
		}

		if ( 'mto_course_recurring' === $product_type && Helper::is_wc_subscriptions_active() ) {
			$class_name = \Masteriyo\Addons\WcIntegration\CourseRecurringProduct::class;
		}

		return $class_name;
	}

	/**
	 * Add course product type in the product type selector.
	 *
	 * @since 1.8.1
	 *
	 * @param array $types WooCommerce product types.
	 * @return array
	 */
	public function add_course_product_type( $types ) {
		$types['mto_course'] = __( 'Masteriyo Course', 'learning-management-system' );

		if ( Helper::is_wc_subscriptions_active() ) {
			$types['mto_course_recurring'] = __( 'Masteriyo Course', 'learning-management-system' );
		}

		return $types;
	}

	/**
	 * Convert WC status to Masteriyo status.
	 *
	 * @since 1.8.1
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
	 * @since 1.8.1
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

		// Return only WC_Order_Item_Product.
		$order_items = array_filter(
			$wc_order->get_items(),
			function( $order_item ) {
				return is_a( $order_item, 'WC_Order_Item_Product' );
			}
		);

		foreach ( $order_items as $order_item ) {
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
				$user_course->set_date_start( null );
				$user_course->set_date_modified( null );
				$user_course->set_date_end( null );
			}

			$user_course->save();
		}
	}

	/**
	 * Change to WooCommerce Add to Cart URL.
	 *
	 * @since 1.8.1
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

		$product_id = get_post_meta( $course->get_id(), '_wc_product_id', true );
		$product    = wc_get_product( $product_id );

		if ( ! $product || ( $product && PostStatus::PUBLISH !== $product->get_status() ) ) {
			return $url;
		}

		$is_added_to_cart = Helper::is_course_added_to_cart( $course->get_id() );

		if ( is_null( $is_added_to_cart ) ) {
			return $url;
		}

		if ( ! $is_added_to_cart ) {
			$url = $product->add_to_cart_url();
		}

		if ( $is_added_to_cart ) {
			$url = \wc_get_cart_url();
		}

		if ( '' === get_option( 'permalink_structure' ) && 'no' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			$url = $product->get_permalink();
		} elseif ( '' !== get_option( 'permalink_structure' ) ) {
			$url = wp_http_validate_url( $url ) ? $url : $product->get_permalink() . $url;
		}

		return $url;
	}

	/**
	 * Register ajax handlers.
	 *
	 * @since 1.8.1
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
	 * @since 1.8.1
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
			}
		}
		//phpcs:enable
	}

	/**
	 * Add icon to masteriyo tab.
	 *
	 * @since 1.8.1
	 */
	public function add_masteriyo_tab_icon() {
		echo '<style>
			#woocommerce-product-data ul.wc-tabs li.masteriyo_options.masteriyo_tab a:before {
				content: "\1F4D6";
			}
		</style>';
	}

	/**
	 * Add masteriyo tab to product tabs.
	 *
	 * @since 1.8.1
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_masteriyo_tab( $tabs ) {
		$tabs['mto_course'] = array(
			'label'    => __( 'Course', 'learning-management-system' ),
			'target'   => 'mto_course_options',
			'class'    => array( 'show_if_mto_course', 'show_if_mto_course_recurring' ),
			'priority' => 1,
		);

		// Show general in course.
		$tabs['general']['class'][] = 'show_if_simple';
		$tabs['general']['class'][] = 'show_if_external';
		$tabs['general']['class'][] = 'show_if_mto_course show_if_mto_course_recurring';

		$tabs['inventory']['class'][] = 'show_if_mto_course show_if_mto_course_recurring';

		// Hide shipping attributes.
		$tabs['shipping']['class'][]  = 'hide_if_mto_course hide_if_mto_course_recurring';
		$tabs['attribute']['class'][] = 'hide_if_mto_course hide_if_mto_course_recurring';
		$tabs['advanced']['class'][]  = 'hide_if_mto_course hide_if_mto_course_recurring';

		return $tabs;
	}

	/**
	 * Display masteriyo tab content.
	 *
	 * @since 1.8.1
	 */
	public function display_masteriyo_tab_content() {
		if ( ! function_exists( 'woocommerce_wp_select' ) ) {
			return;
		}

		$options   = array(
			'' => esc_html__( 'Please select a course', 'learning-management-system' ),
		);
		$course_id = get_post_meta( get_the_ID(), '_masteriyo_course_id', true );
		$course    = masteriyo_get_course( $course_id );

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
	}

	/**
	 * Enqueue necessary scripts.
	 *
	 * @since 1.8.1
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
				return masteriyo_is_single_course_page() || masteriyo_is_courses_page();
			},
		);

		return $scripts;
	}

	/**
	 * Localize admin scripts.
	 *
	 * @since 1.8.1
	 *
	 * @param array $scripts
	 * @return array
	 */
	public function localize_admin_scripts( $scripts ) {
		$scripts['wc-integration'] = array(
			'name' => '_MASTERIYO_WC_INTEGRATION_',
			'data' => array(
				'ajaxUrl'                => admin_url( 'admin-ajax.php' ),
				'nonces'                 => array(
					'listCourses' => wp_create_nonce( 'masteriyo_wc_integration_list_courses' ),
				),
				'isWCSubscriptionActive' => Helper::is_wc_subscriptions_active(),
			),
		);

		return $scripts;
	}

	/**
	 * Localize public scripts.
	 *
	 * @since 1.11.3
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
	 * @since 1.8.1
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
	 * @since 1.8.1
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
	 * @since 1.8.1
	 *
	 * @param int $wc_order_id
	 */
	public function create_user_course( $wc_order_id ) {
		// Bail early if WC is not active.
		if ( ! ( function_exists( 'wc_get_product' ) && function_exists( 'wc_get_order' ) ) ) {
			return;
		}

		$wc_order = wc_get_order( $wc_order_id );

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

			$course = masteriyo_get_course( $product->get_meta( '_masteriyo_course_id', true ) );

			// Bail early if course doesn't exist.
			if ( ! $course ) {
				continue;
			}

			// Save course id in the order item as meta.
			$order_item->update_meta_data( '_masteriyo_course_id', $course->get_id() );
			$order_item->save_meta_data();

			// Get user courses.
			$query = new UserCourseQuery(
				array(
					'course_id' => $course->get_id(),
					'user_id'   => $wc_order->get_customer_id(),
				)
			);

			$user_courses = $query->get_user_courses();
			$user_course  = empty( $user_courses ) ? masteriyo( 'user-course' ) : current( $user_courses );

			$user_course->set_course_id( $course->get_id() );
			$user_course->set_user_id( $wc_order->get_customer_id() );
			$user_course->set_price( $product->get_price() );

			if ( OrderStatus::COMPLETED === $wc_order->get_status() ) {
				$user_course->set_status( UserCourseStatus::ACTIVE );
				$user_course->set_date_start( current_time( 'mysql', true ) );
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
		}
	}

	/**
	 * Update masteriyo_can_start_course() for course connected with WC product.
	 *
	 * @since 1.8.1
	 *
	 * @param bool $can_start_course Whether user can start the course.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param \Masteriyo\Models\User $user User object.
	 * @return boolean
	 */
	public function update_can_start_course( $can_start_course, $course, $user ) {
		// Bail early if WC is not active.
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		if ( ! $course ) {
			return;
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
			$can_start_course = SubscriptionStatus::ACTIVE === $subscription->get_status();
		} else {
			$can_start_course = OrderStatus::COMPLETED === $wc_order->get_status();
		}

		return $can_start_course;
	}

	/**
	 * Add student role to WC customer.
	 *
	 * @since 1.8.1
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
	 * @since 1.11.0
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
	}

	/**
	 * Creates a WooCommerce product for a given Masteriyo course.
	 *
	 * @since 1.11.0
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 *
	 * @return \WP_Error|\WP_REST_Response A WP_Error object on failure, or a WP_REST_Response on success.
	 */
	public function create_wc_product( $request ) {
		$course_id      = absint( $request->get_param( 'course_id' ) ?? 0 );
		$product_create = masteriyo_string_to_bool( $request->get_param( 'product_create' ) ?? true );

		$course = masteriyo_get_course( $course_id );

		if ( ! $course ) {
			return new \WP_Error( 'masteriyo_course_not_found', __( 'Course not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		if ( ! $product_create ) {
			return new \WP_Error( 'masteriyo_product_not_created', __( 'Product not created.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		if ( ! $course->get_price() ) {
			return new \WP_Error( 'masteriyo_course_price_not_set', __( 'Course price not set.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$product_id = absint( get_post_meta( $course_id, '_wc_product_id', true ) );

		if ( $product_id && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );

			if ( $product && 'mto_course' === $product->get_type() ) {
				return rest_ensure_response(
					array(
						'success'    => true,
						'product_id' => $product_id,
						'message'    => __( 'Product already created.', 'learning-management-system' ),
					)
				);
			}
		}

		$product = new CourseProduct();

		$product->set_name( $course->get_title() );
		$product->set_description( $course->get_description() );
		$product->set_short_description( $course->get_short_description() );
		$product->set_featured( $course->get_featured() );
		$product->set_price( $course->get_price() );
		$product->set_regular_price( $course->get_regular_price() );
		$product->set_sale_price( $course->get_sale_price() );
		$product->set_image_id( $course->get_image_id() );
		$product->get_category_ids( $course->get_category_ids() );
		$product->get_tag_ids( $course->get_tag_ids() );
		$product->set_reviews_allowed( $course->get_reviews_allowed() );
		$product->set_catalog_visibility( $course->get_catalog_visibility() );
		$product->set_post_password( $course->get_post_password() );

		$product_id = $product->save();

		if ( $product_id ) {
			update_post_meta( $course_id, '_wc_product_id', $product_id );
			update_post_meta( $product_id, '_masteriyo_course_id', $course_id );
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
	 * @since 1.11.0
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
		if ( ! ( $course instanceof \Masteriyo\Models\Course ) ) {
			return $data;
		}

		$product_id = absint( get_post_meta( $course->get_id(), '_wc_product_id', true ) );

		$product_exists = false;
		if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
			$product_exists = true;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product || 'mto_course' !== $product->get_type() ) {
			$product_exists = true;
		}

		$data['wc_integration'] = array(
			'course_id'      => $course->get_id(),
			'product_create' => ! $product_exists,
		);

		return $data;
	}
}
