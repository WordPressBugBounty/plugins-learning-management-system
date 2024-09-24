<?php
/**
 * Multiple Currency Addon for Masteriyo.
 *
 * @since 1.11.0
 */

namespace Masteriyo\Addons\MultipleCurrency;

use Masteriyo\Addons\MultipleCurrency\Controllers\MultipleCurrencySettingsController;
use Masteriyo\Addons\MultipleCurrency\Controllers\PriceZonesController;
use Masteriyo\Addons\MultipleCurrency\MaxMind\DatabaseService;
use Masteriyo\Addons\MultipleCurrency\PostType\PriceZone;
use Masteriyo\PostType\PostType;

/**
 * Multiple Currency Addon main class for Masteriyo.
 *
 * @since 1.11.0
 */
class MultipleCurrencyAddon {

	/**
	 * Initialize.
	 *
	 * @since 1.11.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initializes the hooks for the Multiple Currency addon.
	 *
	 * This method sets up various filters and actions to handle multiple currency functionality,
	 * such as adding schema to the course REST API, saving multiple currency data, modifying
	 * currency and prices based on the user's country, registering a multiple currency submenu,
	 * registering a price zone post type, and registering REST API namespaces.
	 *
	 * @since 1.11.0
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_admin_submenus', array( $this, 'register_multiple_currency_submenu' ) );
		add_filter( 'masteriyo_register_post_types', array( $this, 'register_price_zone_post_type' ) );
		add_filter( 'masteriyo_rest_api_get_rest_namespaces', array( $this, 'register_rest_namespaces' ) );

		add_filter( 'masteriyo_rest_course_schema', array( $this, 'add_multiple_currency_schema_to_course' ) );
		add_action( 'masteriyo_new_course', array( $this, 'save_multiple_currency_data' ), 10, 2 );
		add_action( 'masteriyo_update_course', array( $this, 'save_multiple_currency_data' ), 10, 2 );
		add_filter( 'masteriyo_rest_response_course_data', array( $this, 'append_multiple_currency_data_in_response' ), 10, 4 );

		add_filter( 'masteriyo_setup_course_data', array( $this, 'modify_price_on_frontend_page' ) ); // For single course page.
		add_filter( 'masteriyo_course_archive_course', array( $this, 'modify_price_on_frontend_page' ) ); // For course archive page.
		add_filter( 'masteriyo_checkout_modify_course_details', array( $this, 'modify_price_on_frontend_page' ) ); // For order summary page.

		add_filter( 'masteriyo_cart_contents_changed', array( $this, 'add_multiple_currency_course_content_to_cart_contents' ), 10, 1 );

		add_filter( 'masteriyo_rest_prepare_countries_list', array( $this, 'modify_countries_list' ), 10, 2 );

		add_filter( 'masteriyo_get_geolocation', array( $this, 'get_geolocation' ), 10, 2 );

		add_action( 'masteriyo_new_earning', array( $this, 'update_earning' ), 10, 2 );
	}

	/**
	 * Updates the earning after creation, converting amounts from local currency to base currency if necessary.
	 *
	 * This function is fired after creating an earning and checks if the earning is in local currency.
	 * If the earning is in local currency, it converts the amounts to the base currency using the provided exchange rate.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Addons\RevenueSharing\Models\Earning $earning The earning object.
	 * @param integer $id The earning ID.
	 */
	public function update_earning( $earning, $id ) {
		if ( ! $earning instanceof \Masteriyo\Addons\RevenueSharing\Models\Earning ) {
			return;
		}

		$order = masteriyo_get_order( $earning->get_order_id() );

		if ( ! $order ) {
			return;
		}

		$exchange_rate = $order->get_exchange_rate();

		if ( masteriyo_get_currency() === $order->get_currency() ) {
			return;
		}

		if ( $order->get_base_currency() && $exchange_rate ) {
			$conversion_factor = 1 / $exchange_rate;

			$earning->set_grand_total_amount( masteriyo_format_decimal( $earning->get_grand_total_amount() * $conversion_factor ) );
			$earning->set_total_amount( masteriyo_format_decimal( $earning->get_total_amount() * $conversion_factor ) );
			$earning->set_admin_amount( masteriyo_format_decimal( $earning->get_admin_amount() * $conversion_factor ) );
			$earning->set_instructor_amount( masteriyo_format_decimal( $earning->get_instructor_amount() * $conversion_factor ) );
			$earning->set_deductible_fee_amount( $earning->get_deductible_fee_amount() * $conversion_factor );

			$earning->save();
		}
	}

	/**
	 * Performs a geolocation lookup against the MaxMind database for the given IP address.
	 *
	 * @since 1.11.0
	 *
	 * @param array  $data       Geolocation data.
	 * @param string $ip_address The IP address to geolocate.
	 *
	 * @return array Geolocation including country code, state, city and postcode based on an IP address.
	 */
	public function get_geolocation( $data, $ip_address ) {
		if ( ! empty( $data['country'] ) ) {
			return $data;
		}

		if ( empty( $ip_address ) ) {
			return $data;
		}

		$database_service = new DatabaseService();

		$country_code = $database_service->get_iso_country_code_for_ip( $ip_address );

		return array(
			'country'  => $country_code,
			'state'    => '',
			'city'     => '',
			'postcode' => '',
		);
	}

	/**
	 * Modifies the list of countries based on the multiple currency settings.
	 *
	 * If the request is from the multiple currency context, this function will return the list of countries that are not yet assigned to any pricing zone.
	 *
	 * @since 1.11.0
	 *
	 * @param array           $countries      The list of countries.
	 * @param \WP_REST_Request $request The current REST request.
	 *
	 * @return array The modified list of countries.
	 */
	public function modify_countries_list( $countries, $request ) {
		$is_from_multiple_currency = masteriyo_string_to_bool( $request->get_param( 'is_from_multiple_currency' ) ?? false );
		$price_zone_id             = absint( $request->get_param( 'price_zone_id' ) ?? 0 );

		if ( $is_from_multiple_currency ) {
			$countries = masteriyo_get_unused_country_list_for_pricing_zone( $price_zone_id );

			$countries = array_map(
				function ( $code ) {
					return array( $code => masteriyo( 'countries' )->get_country_from_code( $code ) );
				},
				$countries
			);

			$countries = call_user_func_array( 'array_merge_recursive', $countries );
		}

		return $countries;
	}

	/**
	 * Adjusts the price of multiple currency courses in the cart.
	 *
	 * @since 1.11.0
	 *
	 * @param array $cart_contents The current contents of the cart.
	 *
	 * @return array Modified cart contents with updated pricing for multiple currency courses.
	 */
	public function add_multiple_currency_course_content_to_cart_contents( $cart_contents ) {
		if ( ! is_array( $cart_contents ) || empty( $cart_contents ) ) {
			return $cart_contents;
		}

		$cart_contents = array_map(
			function ( $cart_item ) {

				$course = $cart_item['data'];

				if ( ! $course instanceof \Masteriyo\Models\Course ) {
					return $cart_item;
				}

				if ( ! masteriyo_string_to_bool( get_post_meta( $course->get_id(), '_multiple_currency_enabled', true ) ) ) {
					return $cart_item;
				}

				$pricing_zone = masteriyo_get_price_zone_by_country( masteriyo_get_user_current_country() );

				if ( ! $pricing_zone || ! masteriyo_string_to_bool( get_post_meta( $course->get_id(), "_multiple_currency__{$pricing_zone->get_id()}_enabled", true ) ) ) {
					return $cart_item;
				}

				$currency = $pricing_zone->get_currency();

				if ( empty( $currency ) || masteriyo_get_currency() === $currency ) {
					return $cart_item;
				}

				$regular_price = masteriyo_get_country_based_price( $course, $pricing_zone );
				$sale_price    = masteriyo_get_country_based_sale_price( $course, $pricing_zone );

				if ( ! is_null( $regular_price ) ) {
					$regular_price = $regular_price ? $regular_price : 0;
					$course->set_regular_price( $regular_price );
					$course->set_sale_price( $sale_price );
				}

				if ( ! is_null( $sale_price ) ) {
					$course->set_price( $sale_price );
				} else {
					$course->set_price( $regular_price );
				}

				if ( ! is_null( $regular_price ) ) {
					if ( ! empty( $currency ) && ! is_null( $currency ) ) {
						$course->set_currency( $currency );
						$course->set_exchange_rate( $pricing_zone->get_exchange_rate() );
						$course->set_pricing_method( get_post_meta( $course->get_id(), "_multiple_currency_{$pricing_zone->get_id()}_pricing_method", true ) );
					}
				}

				$cart_item['data'] = $course;

				return $cart_item;
			},
			$cart_contents
		);

		return $cart_contents;
	}

	/**
	 * Modifies the price and sale price of a course based on the user's current country.
	 *
	 * This function checks if the course object is valid, then retrieves the country-based regular price and sale price for the course. It sets the course's price and regular price to the country-based regular price, and sets the course's sale price to the country-based sale price.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 *
	 * @return \Masteriyo\Models\Course The modified course object with updated prices.
	 */
	public function modify_price_on_frontend_page( $course ) {
		if ( ! $course instanceof \Masteriyo\Models\Course ) {
			return $course;
		}

		if ( ! masteriyo_string_to_bool( get_post_meta( $course->get_id(), '_multiple_currency_enabled', true ) ) ) {
			return $course;
		}

		if ( masteriyo_is_single_course_page() || masteriyo_is_courses_page( true ) || masteriyo_is_checkout_page() ) {
			$pricing_zone = masteriyo_get_price_zone_by_country( masteriyo_get_user_current_country() );

			if ( ! $pricing_zone || ! masteriyo_string_to_bool( get_post_meta( $course->get_id(), "_multiple_currency__{$pricing_zone->get_id()}_enabled", true ) ) ) {
				return $course;
			}

			$currency = $pricing_zone->get_currency();

			if ( empty( $currency ) || masteriyo_get_currency() === $currency ) {
				return $course;
			}

			$regular_price = masteriyo_get_country_based_price( $course, $pricing_zone );
			$sale_price    = masteriyo_get_country_based_sale_price( $course, $pricing_zone );

			if ( ! is_null( $regular_price ) ) {
				$regular_price = $regular_price ? $regular_price : 0;
				$course->set_regular_price( $regular_price );
				$course->set_sale_price( $sale_price );
			}

			if ( ! is_null( $sale_price ) ) {
				$course->set_price( $sale_price );
			} else {
				$course->set_price( $regular_price );
			}

			if ( ! is_null( $regular_price ) ) {
				if ( ! empty( $currency ) && ! is_null( $currency ) ) {
					$course->set_currency( $currency );
				}
			}
		}

		return $course;
	}

	/**
	 * Append multiple currency to course response.
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
	public function append_multiple_currency_data_in_response( $data, $course, $context, $controller ) {

		if ( $course instanceof \Masteriyo\Models\Course ) {

			$active_zones = masteriyo_get_active_pricing_zone_data();

			$active_zones = array_map(
				function( $active_zone ) use ( $course ) {
					$pricing_method = get_post_meta( $course->get_id(), "_multiple_currency_{$active_zone['id']}_pricing_method", true );
					$regular_price  = masteriyo_format_decimal( get_post_meta( $course->get_id(), "_multiple_currency_{$active_zone['id']}_regular_price", true ) );
					$sale_price     = masteriyo_format_decimal( get_post_meta( $course->get_id(), "_multiple_currency_{$active_zone['id']}_sale_price", true ) );

					$enabled_key = "_multiple_currency__{$active_zone['id']}_enabled";
					$enabled     = metadata_exists( 'post', $course->get_id(), $enabled_key ) ? get_post_meta( $course->get_id(), $enabled_key, true ) : true;

					$active_zone['enabled']        = masteriyo_string_to_bool( $enabled );
					$active_zone['pricing_method'] = $pricing_method ? $pricing_method : 'exchange_rate';
					$active_zone['regular_price']  = $regular_price;
					$active_zone['sale_price']     = $sale_price;

					return $active_zone;
				},
				$active_zones
			);

			$enabled = masteriyo_string_to_bool( get_post_meta( $course->get_id(), '_multiple_currency_enabled', true ) );

			$data['multiple_currency'] = array(
				'enabled'       => $enabled,
				'pricing_zones' => $active_zones,
			);
		}

		return $data;
	}

	/**
	 * Save multiple currency data.
	 *
	 * @since 1.11.0
	 *
	 * @param integer $id The course ID.
	 * @param \Masteriyo\Models\Course $object The course object.
	 */
	public function save_multiple_currency_data( $id, $course ) {
		$request = masteriyo_current_http_request();

		if ( null === $request ) {
			return;
		}
		if ( ! isset( $request['multiple_currency'] ) ) {
			return;
		}

		$active_zones = masteriyo_get_active_pricing_zone_data();

		if ( ! empty( $active_zones ) ) {
			foreach ( $active_zones as $active_zone ) {

				if ( isset( $request['multiple_currency']['enabled'] ) ) {
					update_post_meta( $id, '_multiple_currency_enabled', masteriyo_string_to_bool( $request['multiple_currency']['enabled'] ) );

					if ( isset( $request['multiple_currency'][ $active_zone['id'] . '_key' ] ) ) {

						$regular_price = '';
						$sale_price    = '';

						if ( isset( $request['multiple_currency'][ $active_zone['id'] . '_key' ]['regular_price'] ) ) {
							$regular_price = masteriyo_format_decimal( $request['multiple_currency'][ $active_zone['id'] . '_key' ]['regular_price'] );
						}

						if ( isset( $request['multiple_currency'][ $active_zone['id'] . '_key' ]['sale_price'] ) ) {
							$sale_price = masteriyo_format_decimal( $request['multiple_currency'][ $active_zone['id'] . '_key' ]['sale_price'] );
						}

						if ( isset( $request['multiple_currency'][ $active_zone['id'] . '_key' ]['enabled'] ) ) {
							update_post_meta( $id, "_multiple_currency__{$active_zone['id']}_enabled", masteriyo_string_to_bool( $request['multiple_currency'][ $active_zone['id'] . '_key' ]['enabled'] ) );
						}

						if ( isset( $request['multiple_currency'][ $active_zone['id'] . '_key' ]['pricing_method'] ) ) {
							update_post_meta( $id, "_multiple_currency_{$active_zone['id']}_pricing_method", sanitize_text_field( $request['multiple_currency'][ $active_zone['id'] . '_key' ]['pricing_method'] ) );
						}

						update_post_meta( $id, "_multiple_currency_{$active_zone['id']}_regular_price", $regular_price );

						$sale_price = '' !== $sale_price && (float) $regular_price > (float) $sale_price ? $sale_price : '';

						update_post_meta( $id, "_multiple_currency_{$active_zone['id']}_sale_price", $sale_price );
					}
				}
			}
		}
	}

	/**
	 * Add multiple currency fields to course schema.
	 *
	 * @since 1.11.0
	 *
	 * @param array $schema
	 * @return array
	 */
	public function add_multiple_currency_schema_to_course( $schema ) {
		$schema = wp_parse_args(
			$schema,
			array(
				'multiple_currency' => array(
					'description' => __( 'Multiple currency setting', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'enabled'        => array(
								'description' => __( 'Enable multiple currency.', 'learning-management-system' ),
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'pricing_method' => array(
								'description' => __( 'Maximum Group Size', 'learning-management-system' ),
								'type'        => 'string',
								'default'     => 'exchange_rate',
								'context'     => array( 'view', 'edit' ),
							),
							'regular_price'  => array(
								'description' => __( 'Course regular price.', 'learning-management-system' ),
								'type'        => 'string',
								'default'     => '',
								'context'     => array( 'view', 'edit' ),
							),
							'sale_price'     => array(
								'description' => __( 'Course sale price.', 'learning-management-system' ),
								'type'        => 'string',
								'default'     => '',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			)
		);

		return $schema;
	}

	/**
	 * Register REST API namespaces for the Group Courses.
	 *
	 * @since 1.9.0
	 *
	 * @param array $namespaces Rest namespaces.
	 *
	 * @return array Modified REST namespaces including Group Courses endpoints.
	 */
	public function register_rest_namespaces( $namespaces ) {
		$namespaces['masteriyo/v1']['multiple-currency-pricing-zones'] = PriceZonesController::class;
		$namespaces['masteriyo/v1']['multiple-currency-settings']      = MultipleCurrencySettingsController::class;

		return $namespaces;
	}

	/**
	 * Register price zone post types.
	 *
	 * @since 1.9.0
	 *
	 * @param string[] $post_types
	 *
	 * @return string[]
	 */
	public function register_price_zone_post_type( $post_types ) {
		$post_types[] = PriceZone::class;

		return $post_types;
	}

	/**
	 * Register multiple currency submenu.
	 *
	 * @since 1.9.0
	 *
	 * @param array $submenus Admin submenus.
	 *
	 * @return array
	 */
	public function register_multiple_currency_submenu( $submenus ) {
		$submenus['multiple-currency/pricing-zones'] = array(
			'page_title' => __( 'Multiple Currency', 'learning-management-system' ),
			'menu_title' => __( 'Multiple Currency', 'learning-management-system' ),
			'position'   => 63,
		);

		return $submenus;
	}
}
