<?php
/**
 * Masteriyo WooCommerce integration setting class.
 *
 * @since 2.2.0
 * @package Masteriyo\Addons\WcIntegration
 */

namespace Masteriyo\Addons\WcIntegration;

use Masteriyo\Addons\WcIntegration\Enums\AutoRedirectType;
use Masteriyo\Enums\OrderStatus;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo WooCommerce integration setting class.
 *
 * @class Masteriyo\Addons\WcIntegration\Setting
 *
 * @phpstan-type AutoRedirect array{display_type: AutoRedirectType::*, page_id: int<0, max>, custom_url: string}
 */

class Setting {
	/**
	 * Setting option name.
	 *
	 * @var string
	 */
	private $name = 'masteriyo_wc_integration_setting';

	/**
	 * Setting data.
	 *
	 * @since 2.2.0
	 *
	 * @var array
	 */
	private $data = array(
		'unenrollment_status' => array( OrderStatus::CANCELLED, OrderStatus::FAILED, OrderStatus::REFUNDED ),
		'add_to_cart'         => array(
			'enable'                  => false,
			'enroll_btn_label_before' => 'Add to Cart',
			'enroll_btn_label_after'  => 'View Cart',
		),
		'auto_redirect'       => array(
			'display_type' => AutoRedirectType::NONE,
			'page_id'      => 0,
			'custom_url'   => '',
		),
	);

	/**
	 * Initialize.
	 *
	 * @since 2.2.0
	 */
	public function init() {
		add_filter( 'masteriyo_rest_pre_insert_setting_object', array( $this, 'save' ), 10, 3 );
		add_filter( 'masteriyo_rest_prepare_setting_object', array( $this, 'append_setting_in_response' ), 10, 3 );
	}

	/**
	 * Append WooCommerce integration setting to the global settings.
	 *
	 * @since 2.2.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param Model            $object   Object data.
	 * @param WP_REST_Request  $request  Request object.
	 * @return WP_REST_Response
	 */
	public function append_setting_in_response( $response, $object, $request ) {
		$data                                = $response->get_data();
		$data['integrations']['woocommerce'] = $this->get();

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Store woocommerce integration settings.
	 *
	 * @since 2.2.0
	 *
	 * @param Model         $setting  Object object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating If is creating a new object.
	 *
	 * @return Masteriyo\Models\Setting
	 */
	public function save( $setting, $request, $creating ) {
		$setting_in_db = get_option( $this->name, $this->data );
		$post_data     = masteriyo_array_get( $request, 'integrations.woocommerce', array() );
		$setting_arr   = wp_parse_args( $post_data, $setting_in_db );

		$setting_arr['auto_redirect'] = $this->normalize_auto_redirect( (array) $setting_arr['auto_redirect'] );

		update_option( $this->name, $setting_arr );

		return $setting;
	}

	/**
	 * Return setting value.
	 *
	 * @since 2.2.0
	 * @param string $key Setting key.
	 * @param mixed $default Setting default.
	 * @return mixed
	 */
	public function get( $key = null, $default = null ) {
		$setting = get_option( $this->name, array() );
		$setting = wp_parse_args( $setting, $this->data );

		$setting['auto_redirect'] = $this->normalize_auto_redirect( (array) $setting['auto_redirect'] );

		$value = $key ? masteriyo_array_get( $setting, $key, $default ) : $setting;

		return $value;
	}

	/**
	 * Sanitize the redirect destination, deriving it from the old on/off toggle on
	 * sites that saved only that: on meant the Courses tab, off meant no redirect.
	 *
	 * @param array{enable?: bool|string, display_type?: string, page_id?: int|string, custom_url?: string} $auto_redirect Stored or posted value.
	 *
	 * @return AutoRedirect
	 */
	private function normalize_auto_redirect( array $auto_redirect ): array {
		$type = $auto_redirect['display_type'] ?? (
			masteriyo_string_to_bool( $auto_redirect['enable'] ?? false ) ? AutoRedirectType::COURSES : AutoRedirectType::NONE
		);

		return array(
			'display_type' => in_array( $type, AutoRedirectType::all(), true ) ? $type : AutoRedirectType::NONE,
			'page_id'      => absint( $auto_redirect['page_id'] ?? 0 ),
			'custom_url'   => esc_url_raw( (string) ( $auto_redirect['custom_url'] ?? '' ) ),
		);
	}

	/**
	 * Save setting value.
	 *
	 * @since 2.2.0
	 *
	 * @param string $key Setting key.
	 * @param mixed $default Setting default.
	 */
	public function set( $key, $value ) {
		masteriyo_array_set( $this->data, $key, $value );
		update_option( $this->name, $this->data );
	}
}
