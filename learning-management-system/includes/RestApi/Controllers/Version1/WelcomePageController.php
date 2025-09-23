<?php
/**
 * REST API WelcomePageController class.
 *
 * Handles the API requests for checking and creating the required pages for Masteriyo.
 *
 * @since 2.0.0 [Free]
 */
namespace Masteriyo\RestApi\Controllers\Version1;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Activation;
use Masteriyo\Addons\Stripe\Setting as StripeSetting;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Pro\Addons;
use WP_REST_Request;
use WP_Error;
use WP_REST_Response;

class WelcomePageController extends RestController {

	protected $rest_base = 'welcome-page';
	protected $namespace = 'masteriyo/v1';

	/**
	 * Register the routes for the create pages API.
	 *
	 * Registers the routes for retrieving the current status of pages and creating missing pages.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);
	}


	/**
	 * Return publish courses count.
	 *
	 * @since 2.0.0
	 *
	 * @return integer
	 */
	public function get_course_count() {
		return masteriyo_array_get( (array) wp_count_posts( PostType::COURSE ), PostStatus::PUBLISH, 0 );
	}


	/**
	 * Get the status of the required pages.
	 *
	 * Checks if the required pages (Learn, Account, Checkout) are present and published.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function get_item( $request ) {
		$page_check_result      = $this->check_required_pages();
		$stripe_setting         = new StripeSetting();
		$payment_data           = array(
			'offline_payment' => masteriyo_get_setting( 'payments.offline.enable' ) ?? false,
			'paypal'          => masteriyo_get_setting( 'payments.paypal.enable' ) ?? false,
			'paypal_email'    => masteriyo_get_setting( 'payments.paypal.email' ) ?? '',
			'stripe'          => $stripe_setting->get( 'enable' ) ?? false,
			'stripe_user_id'  => $stripe_setting->get( 'stripe_user_id' ) ?? false,
		);
		$show_staters_templates = get_option( 'masteriyo_show_starters_templates' );
		$skip_payment_setup     = get_option( 'masteriyo_skip_payment_setup' );
		$course_count           = $this->get_course_count();
		$course_created         = false;
		if ( $course_count > 0 ) {
			$course_created = true;
		}
		return new WP_REST_Response(
			array(
				'page_status'             => $page_check_result,
				'payment_data'            => $payment_data,
				'show_starters_templates' => $show_staters_templates,
				'course_created'          => $course_created,
				'skip_payment_setup'      => $skip_payment_setup,
			),
			200
		);
	}




	/**
	 * Create the missing required pages if needed.
	 *
	 * Creates any missing required pages (Learn, Account, Checkout) if they are not found.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function create_item( $request ) {
		$page_check_result = $this->check_required_pages();
		if ( ! empty( $page_check_result ) ) {
			Activation::create_pages();
		}

		$stripe_setting = new StripeSetting();
		$addons         = new Addons();

		$payment_data = $this->get_default_payment_data( $request, $addons, $stripe_setting );

		if ( isset( $request['payments'] ) && is_array( $request['payments'] ) ) {
			$this->process_payment_settings( $request['payments'], $addons, $stripe_setting );
		}

		if ( isset( $request['show_starters_templates'] ) ) {
			update_option(
				'masteriyo_show_starters_templates',
				masteriyo_string_to_bool( $request['show_starters_templates'] )
			);
		}

		return new WP_REST_Response(
			array(
				'missing_pages'           => $page_check_result,
				'payment_data'            => $payment_data,
				'show_starters_templates' => get_option( 'masteriyo_show_starters_templates' ),
				'course_created'          => $this->get_course_count() > 0,
			),
			200
		);
	}

	private function get_default_payment_data( $request, $addons, $stripe_setting ) {
		$offline_payment_enabled = masteriyo_get_setting( 'payments.offline.enable' ) ?? false;
		$paypal_enabled          = masteriyo_get_setting( 'payments.paypal.enable' ) ?? false;
		$stripe_enabled          = masteriyo_get_setting( 'payments.stripe.enable' ) ?? false;

		$payment_data = array(
			'offline_payment' => (bool) $offline_payment_enabled,
			'paypal'          => (bool) $paypal_enabled,
			'stripe'          => (bool) $stripe_enabled,
		);

		if ( isset( $request['payments'] ) ) {
			$payments                        = $request['payments'];
			$payment_data['offline_payment'] = masteriyo_string_to_bool( $payments['offline_payment'] ?? $payment_data['offline_payment'] );
			$payment_data['paypal']          = masteriyo_string_to_bool( $payments['paypal'] ?? $payment_data['paypal'] );
			$payment_data['stripe']          = masteriyo_string_to_bool( $payments['stripe'] ?? $payment_data['stripe'] );
		}

		return $payment_data;
	}

	private function process_payment_settings( $payments, $addons, $stripe_setting ) {
		$settings = array();

		if ( array_key_exists( 'offline_payment', $payments ) ) {
			$settings['payments.offline.enable'] = masteriyo_string_to_bool( $payments['offline_payment'] );
		}

		if ( array_key_exists( 'paypal', $payments ) ) {
			$settings['payments.paypal.enable'] = masteriyo_string_to_bool( $payments['paypal'] );
		}
		if ( isset( $payments['paypal_email'] ) ) {
			$settings['payments.paypal.email'] = sanitize_email( $payments['paypal_email'] );
		}

		if ( isset( $payments['stripe'] ) ) {
			$settings['payments.stripe.enable'] = masteriyo_string_to_bool( $payments['stripe'] );
		}

		foreach ( $settings as $key => $value ) {
			masteriyo_set_setting( $key, $value );
		}

		if ( isset( $settings['payments.stripe.enable'] ) && $settings['payments.stripe.enable'] && ! $addons->is_active( 'stripe' ) ) {
			$addons->set_active( 'stripe' );
		}
	}




	/**
	 * Check the status of required pages.
	 *
	 * Checks if the required pages (Learn, Account, Checkout) are set up correctly.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @return array Status of the pages (either 'success' or 'error' with a message).
	 */
	public function check_required_pages() {
		$required_pages = array(
			'learn'    => array(
				'setting_key' => 'general.pages.learn_page_id',
				'name'        => 'Learn',
			),
			'account'  => array(
				'setting_key' => 'general.pages.account_page_id',
				'name'        => 'Account',
			),
			'checkout' => array(
				'setting_key' => 'general.pages.checkout_page_id',
				'name'        => 'Checkout',
			),
		);

		$missing_pages = array();

		foreach ( $required_pages as $slug => $details ) {
			$page_id = absint( masteriyo_get_setting( $details['setting_key'] ) );

			if ( empty( $page_id ) || 'publish' !== get_post_status( $page_id ) ) {
				$missing_pages[ $slug ] = $details['name'];
			}
		}

		if ( ! empty( $missing_pages ) ) {
			return array_values( $missing_pages );
		}

		return array();
	}

	/**
	 * Check if a given request has access to read/delete item(s).
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function permissions_check( $request ) {
		if ( masteriyo_is_current_user_admin() ) {
			return true;
		}

		return current_user_can( 'manage_options' ) || current_user_can( 'manage_masteriyo_settings' );
	}

	/**
	 * Permissions check for getting page details.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function get_items_permissions_check( $request ) {
		return $this->permissions_check( $request );
	}

	/**
	 * Permissions check for creating pages.
	 *
	 * @since 2.0.0 [Free]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ) {
		return $this->permissions_check( $request );
	}
}
