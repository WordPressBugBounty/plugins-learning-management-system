<?php

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MultipleCurrency\Enums\PriceZoneStatus;
use Masteriyo\Addons\MultipleCurrency\Models\Setting;
use Masteriyo\Geolocation;
use Masteriyo\PostType\PostType;

if ( ! function_exists( 'masteriyo_create_pricing_zone_object' ) ) {
	/**
	 * Create instance of pricing zone model.
	 *
	 * @since 1.11.0
	 *
	 * @return \Masteriyo\Addons\MultipleCurrency\Models\PriceZone
	 */
	function masteriyo_create_pricing_zone_object() {
		return masteriyo( 'mto-pricing-zone' );
	}
}

if ( ! function_exists( 'masteriyo_create_pricing_zone_store' ) ) {
	/**
	 * Create instance of pricing zone repository.
	 *
	 * @since 1.11.0
	 *
	 * @return \Masteriyo\Addons\MultipleCurrency\Repository\PriceZoneRepository
	 */
	function masteriyo_create_pricing_zone_store() {
		return masteriyo( 'pricing-zones.store' );
	}
}

if ( ! function_exists( 'masteriyo_get_price_zone' ) ) {
	/**
	 * Get price zone.
	 *
	 * @since 1.11.0
	 *
	 * @param int|\Masteriyo\Addons\MultipleCurrency\Models\PriceZone|\WP_Post $price_zone Price zone id or Price zone Model or Post.
	 *
	 * @return \Masteriyo\Addons\MultipleCurrency\Models\PriceZone|null
	 */
	function masteriyo_get_price_zone( $price_zone ) {
		$price_zone_obj   = masteriyo_create_pricing_zone_object();
		$price_zone_store = masteriyo_create_pricing_zone_store();

		if ( is_a( $price_zone, 'Masteriyo\Addons\MultipleCurrency\Models\PriceZone' ) ) {
			$id = $price_zone->get_id();
		} elseif ( is_a( $price_zone, 'WP_Post' ) ) {
			$id = $price_zone->ID;
		} else {
			$id = absint( $price_zone );
		}

		try {
			$id = absint( $id );
			$price_zone_obj->set_id( $id );
			$price_zone_store->read( $price_zone_obj );
		} catch ( \Exception $e ) {
			return null;
		}

		/**
		 * Filters price zone object.
		 *
		 * @since 1.11.0
		 *
		 * @param \Masteriyo\Addons\MultipleCurrency\Models\PriceZone $price_zone_obj Price Zone object.
		 * @param int|\Masteriyo\Addons\MultipleCurrency\Models\PriceZone|WP_Post $price_zone Price Zone id or PriceZone Model or Post.
		 */
		return apply_filters( 'masteriyo_get_price_zone', $price_zone_obj, $price_zone );
	}
}

if ( ! function_exists( 'masteriyo_get_country_based_price' ) ) {
	/**
	 * Get master price zone.
	 *
	 * @since 1.11.0
	 *
	 * @param int|\Masteriyo\Models\Course|\WP_Post $course Course id or Course Model or Post.
	 * @param int|\Masteriyo\Addons\MultipleCurrency\Models\PriceZone|\WP_Post $pricing_zone Price zone id or Price zone Model or Post. Default is null.
	 *
	 * @return float|null The price for specified zone or country for the course or null if not found.
	 */
	function masteriyo_get_country_based_price( $course, $pricing_zone = null ) {

		$course = masteriyo_get_course( $course );

		if ( ! $course ) {
			return null;
		}

		if ( is_null( $pricing_zone ) ) {
			$pricing_zone = masteriyo_get_price_zone_by_country( masteriyo_get_user_current_country() );
		}

		$pricing_zone = masteriyo_get_price_zone( $pricing_zone );

		if ( ! $pricing_zone instanceof Masteriyo\Addons\MultipleCurrency\Models\PriceZone ) {
			return null;
		}

		if ( masteriyo_get_currency() === strtoupper( $pricing_zone->get_currency() ) ) { // Base currency and pricing zone currency should not be same.
			return null;
		}

		$is_enabled = masteriyo_string_to_bool( get_post_meta( $course->get_id(), "_multiple_currency__{$pricing_zone->get_id()}_enabled", true ) );

		if ( ! $is_enabled ) {
			return null;
		}

		$price          = floatval( $course->get_regular_price() );
		$price_key      = "_multiple_currency_{$pricing_zone->get_id()}_regular_price";
		$pricing_method = get_post_meta( $course->get_id(), "_multiple_currency_{$pricing_zone->get_id()}_pricing_method", true );

		if ( 'manual' === $pricing_method ) {
			$price = get_post_meta( $course->get_id(), $price_key, true );
		} else {
			$exchange_rate = floatval( $pricing_zone->get_exchange_rate() );
			$price         = $exchange_rate * $price;
		}

		return masteriyo_format_decimal( $price );
	}
}

if ( ! function_exists( 'masteriyo_get_country_based_sale_price' ) ) {
	/**
	 * Get master sale price zone.
	 *
	 * @since 1.11.0
	 *
	 * @param int|\Masteriyo\Models\Course|\WP_Post $course Course id or Course Model or Post.
	 * @param int|\Masteriyo\Addons\MultipleCurrency\Models\PriceZone|\WP_Post $pricing_zone Price zone id or Price zone Model or Post. Default is null.
	 *
	 * @return float|null The price for specified zone or country for the course or null if not found.
	 */
	function masteriyo_get_country_based_sale_price( $course, $pricing_zone = null ) {
		$course = masteriyo_get_course( $course );

		if ( ! $course ) {
			return null;
		}

		if ( is_null( $pricing_zone ) ) {
			$pricing_zone = masteriyo_get_price_zone_by_country( masteriyo_get_user_current_country() );
		}

		$pricing_zone = masteriyo_get_price_zone( $pricing_zone );

		if ( ! $pricing_zone instanceof Masteriyo\Addons\MultipleCurrency\Models\PriceZone ) {
			return null;
		}

		if ( masteriyo_get_currency() === strtoupper( $pricing_zone->get_currency() ) ) { // Base currency and pricing zone currency should not be same.
			return null;
		}

		$is_enabled = masteriyo_string_to_bool( get_post_meta( $course->get_id(), "_multiple_currency__{$pricing_zone->get_id()}_enabled", true ) );

		if ( ! $is_enabled ) {
			return null;
		}

		$price          = $course->get_sale_price();
		$price_key      = "_multiple_currency_{$pricing_zone->get_id()}_sale_price";
		$pricing_method = get_post_meta( $course->get_id(), "_multiple_currency_{$pricing_zone->get_id()}_pricing_method", true );

		if ( 'manual' === $pricing_method ) {
			$price = get_post_meta( $course->get_id(), $price_key, true );
		} else {
			if ( '' === $price ) {
				return null;
			}

			$price = floatval( $price );

			$exchange_rate = floatval( $pricing_zone->get_exchange_rate() );
			$price         = $exchange_rate * $price;
		}

		if ( '' === $price ) {
			return null;
		}

		return masteriyo_format_decimal( $price );
	}
}

if ( ! function_exists( 'masteriyo_get_price_zone_by_country' ) ) {
	/**
	 * Get price zone by country.
	 *
	 * @since 1.11.0
	 *
	 * @param string $country Country code.
	 *
	 * @return \Masteriyo\Addons\MultipleCurrency\Models\PriceZone|null Price zone object, or null if not found.
	 */
	function masteriyo_get_price_zone_by_country( $country ) {
		if ( ! $country ) {
			return null;
		}

		$query = new WP_Query(
			array(
				'post_type'      => PostType::PRICE_ZONE,
				'post_status'    => PriceZoneStatus::ACTIVE,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_countries',
						'value'   => maybe_serialize( strtoupper( $country ) ),
						'compare' => 'LIKE',
					),
				),
			)
		);

		$pricing_zone_id = 0;

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post_id ) {
				$pricing_zone_id = $post_id;
				break;
			}
		}

		return masteriyo_get_price_zone( $pricing_zone_id );
	}
}

if ( ! function_exists( 'masteriyo_get_active_pricing_zone_data' ) ) {

	/**
	 * Retrieves the active pricing zone data.
	 *
	 * @since 1.11.0
	 *
	 * @return array An array of active pricing zone data, including the pricing zone ID, title, currency code, and currency symbol.
	 */
	function masteriyo_get_active_pricing_zone_data() {
		$pricing_zones = masteriyo_get_pricing_zones( PriceZoneStatus::ACTIVE );

		$pricing_zones = array_filter(
			array_map(
				function( $pricing_zone ) {
					if ( ! $pricing_zone ) {
						return null;
					}

					$currency = $pricing_zone->get_currency();

					return array(
						'id'              => $pricing_zone->get_id(),
						'title'           => $pricing_zone->get_title(),
						'currency_code'   => $currency,
						'currency_symbol' => masteriyo_get_currency_symbol( $currency ),
					);
				},
				$pricing_zones
			)
		);

		return $pricing_zones;
	}
}

if ( ! function_exists( 'masteriyo_get_pricing_zones' ) ) {
	/**
	 * Retrieves a list of active pricing zones.
	 *
	 * @param string $status The status of the pricing zones to retrieve. Defaults to 'active'.
	 * @param array $excludes The array of IDs of pricing zones to be excluded.
	 *
	 * @return \Masteriyo\Addons\MultipleCurrency\Models\PriceZone[] An array of pricing zone objects.
	 */
	function masteriyo_get_pricing_zones( $status = PriceZoneStatus::ACTIVE, $excludes = array() ) {
		$query = new WP_Query(
			array(
				'post_type'      => PostType::PRICE_ZONE,
				'posts_per_page' => 2, // For the free version, it's limited to 2 pricing zones.
				'fields'         => 'ids',
				'post_status'    => $status,
				'post__not_in'   => $excludes,
			)
		);

		$post_ids = $query->posts ? $query->posts : array();

		$pricing_zones = array_filter(
			array_map(
				function( $post_id ) {
					$pricing_zone = masteriyo_get_price_zone( $post_id );

					if ( $pricing_zone ) {
						return $pricing_zone;
					}

					return null;
				},
				$post_ids
			)
		);

		return $pricing_zones;
	}
}

if ( ! function_exists( 'masteriyo_get_user_current_country' ) ) {
	/**
	 * Get the current user's country based on their IP address.
	 *
	 * @since 1.11.0
	 *
	 * If the site is in test mode, the country is determined by the test mode settings.
	 * Otherwise, the country is determined by the user's IP address.
	 *
	 * @return string The current user's country code.
	 */
	function masteriyo_get_user_current_country() {
		$current_country = '';
		$is_test_mode    = masteriyo_string_to_bool( Setting::get( 'test_mode.enabled' ) );

		if ( $is_test_mode ) {
			$current_country = strtoupper( Setting::get( 'test_mode.country' ) );
		} else {
			$current_country = masteriyo_get_user_current_country_using_maxmind();
		}

		return $current_country;
	}
}

if ( ! function_exists( 'masteriyo_get_used_currency_list_for_pricing_zone' ) ) {
	/**
	 * Retrieves a list of countries used in the pricing zones.
	 *
	 * @since 1.11.0
	 *
	 * @param array $excludes The of array of ids of pricing zone to be excluded.
	 *
	 * @return array An array of countries used in the pricing zones.
	 */
	function masteriyo_get_used_currency_list_for_pricing_zone( $excludes = array() ) {
		$pricing_zones = masteriyo_get_pricing_zones( PriceZoneStatus::ANY, $excludes );

		$currencies = array_filter(
			array_map(
				function( $pricing_zone ) {
					if ( ! $pricing_zone ) {
						return null;
					}

					return $pricing_zone->get_currency();
				},
				$pricing_zones
			)
		);

		return $currencies;
	}
}

if ( ! function_exists( 'masteriyo_get_used_country_list_for_pricing_zone' ) ) {
	/**
	 * Retrieves a list of countries used in the pricing zones.
	 *
	 * @since 1.11.0
	 *
	 * @param array $excludes The of array of ids of pricing zone to be excluded.
	 *
	 * @return array An array of countries used in the pricing zones.
	 */
	function masteriyo_get_used_country_list_for_pricing_zone( $excludes = array() ) {
		$pricing_zones = masteriyo_get_pricing_zones( PriceZoneStatus::ANY, $excludes );

		$countries = array_filter(
			array_map(
				function( $pricing_zone ) {
					if ( ! $pricing_zone ) {
						return null;
					}

					return $pricing_zone->get_countries();
				},
				$pricing_zones
			)
		);

		return call_user_func_array( 'array_merge_recursive', $countries );
	}
}

if ( ! function_exists( 'masteriyo_get_unused_country_list_for_pricing_zone' ) ) {
	/**
	 * Retrieves a list of countries used in the pricing zones.
	 *
	 * @since 1.11.0
	 *
	 * @param int $pricing_zone_id The ID of the pricing zone to exclude from the list.
	 *
	 * @return array An array of countries used in the pricing zones.
	 */
	function masteriyo_get_unused_country_list_for_pricing_zone( $pricing_zone_id = 0 ) {
		$all_countries = masteriyo( 'countries' )->get_countries();
		$all_countries = array_keys( $all_countries );

		$used_countries = masteriyo_get_used_country_list_for_pricing_zone();

		$unused_countries = array_diff( $all_countries, $used_countries );

		$current_pricing_zone_countries = array();

		$pricing_zone = masteriyo_get_price_zone( $pricing_zone_id );

		if ( $pricing_zone ) {
			$current_pricing_zone_countries = $pricing_zone->get_countries();
		}

		return wp_parse_args( $current_pricing_zone_countries, $unused_countries );
	}
}

if ( ! function_exists( 'masteriyo_get_user_current_country_using_maxmind' ) ) {
	/**
	 * Get the current country based on the user's IP address.
	 *
	 * @since 1.11.0
	 *
	 * @return string The current country.
	 */
	function masteriyo_get_user_current_country_using_maxmind() {
		$is_enabled      = Setting::get( 'maxmind.enabled' );
		$current_country = '';

		if ( ! $is_enabled ) {
			return $current_country;
		}

		$user_agent = masteriyo_get_user_agent();

		if ( ! stristr( $user_agent, 'bot' ) && ! stristr( $user_agent, 'spider' ) && ! stristr( $user_agent, 'crawl' ) ) {
			$geolocation = Geolocation::geolocate_ip( '', true, false );

			if ( isset( $geolocation['country'] ) && ! empty( $geolocation['country'] ) ) {
				$current_country = $geolocation['country'];
			}
		}

		if ( ! array_key_exists( strtoupper( $current_country ), masteriyo( 'countries' )->get_countries() ) ) {
			$current_country = '';
		}

		return $current_country;
	}
}

if ( ! function_exists( 'masteriyo_get_price_zone_by_currency' ) ) {
	/**
	 * Get price zone by currency.
	 *
	 * @since 1.17.1
	 *
	 * @param string $currency Country code.
	 *
	 * @return \Masteriyo\Addons\MultipleCurrency\Models\PriceZone|null Price zone object, or null if not found.
	 */
	function masteriyo_get_price_zone_by_currency( $currency ) {
		if ( ! $currency ) {
			return null;
		}

		$query = new WP_Query(
			array(
				'post_type'      => PostType::PRICE_ZONE,
				'post_status'    => PriceZoneStatus::ACTIVE,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_currency',
						'value'   => strtoupper( $currency ),
						'compare' => '=',
					),
				),
			)
		);

		$pricing_zone_id = 0;

		if ( ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post_id ) {
				$pricing_zone_id = $post_id;
				break;
			}
		}

		return masteriyo_get_price_zone( $pricing_zone_id );
	}
}

if ( ! function_exists( 'masteriyo_get_country_based_group_course_price' ) ) {
	/**
	 * Calculate the country-based group course price.
	 *
	 * This function calculates the group course price based on the pricing zone and exchange rate.
	 * It first checks if the pricing zone is specified, and if not, retrieves the pricing zone based
	 * on the user's current country. It then verifies if the pricing zone is valid and enabled for
	 * the course. Depending on the pricing method, it either retrieves a manually set group price or
	 * calculates it using the exchange rate. The final price is formatted and returned.
	 *
	 * @since 1.17.1
	 *
	 * @param int $course_id The ID of the course.
	 * @param float $group_price The base group price of the course.
	 * @param mixed $pricing_zone The pricing zone object or ID.
	 *
	 * @return float|null The calculated group course price or null if not applicable.
	 */
	function masteriyo_get_country_based_group_course_price( $course_id, $group_price, $pricing_zone ) {
		if ( is_null( $pricing_zone ) ) {
			$pricing_zone = masteriyo_get_price_zone_by_country( masteriyo_get_user_current_country() );
		}

		$pricing_zone = masteriyo_get_price_zone( $pricing_zone );

		if ( ! $pricing_zone instanceof Masteriyo\Addons\MultipleCurrency\Models\PriceZone ) {
			return null;
		}

		if ( masteriyo_get_currency() === strtoupper( $pricing_zone->get_currency() ) ) { // Base currency and pricing zone currency should not be same.
			return null;
		}

		$is_enabled = masteriyo_string_to_bool( get_post_meta( $course_id, "_multiple_currency__{$pricing_zone->get_id()}_enabled", true ) );

		if ( ! $is_enabled ) {
			return null;
		}

		$price_key      = "_multiple_currency_{$pricing_zone->get_id()}_group_price";
		$pricing_method = get_post_meta( $course_id, "_multiple_currency_{$pricing_zone->get_id()}_pricing_method", true );

		$price = $group_price;
		if ( 'manual' === $pricing_method ) {
			$price = get_post_meta( $course_id, $price_key, true );
		} else {
			$exchange_rate = floatval( $pricing_zone->get_exchange_rate() );
			$price         = $exchange_rate * $group_price;
		}

		return masteriyo_format_decimal( $price );
	}
}

if ( ! function_exists( 'masteriyo_get_currency_based_on_course' ) ) {
	/**
	 * Retrieves the currency and pricing zone based on the course.
	 *
	 * This function is used to retrieve the currency and pricing zone for a given course.
	 * It takes the course ID as the argument and returns an array with the currency and pricing zone.
	 * If the multiple currency feature is not enabled for the course or the selected currency is same as the base currency,
	 * it returns an empty array.
	 *
	 * @since 1.17.1
	 *
	 * @param int $course_id The course ID.
	 *
	 * @return array An array with the currency and pricing zone.
	 */
	function masteriyo_get_currency_and_pricing_zone_based_on_course( $course_id ) {
		$data = array( '', null );

		if ( ! masteriyo_string_to_bool( get_post_meta( $course_id, '_multiple_currency_enabled', true ) ) ) {
			return $data;
		}

		$pricing_zone = masteriyo_get_price_zone_by_country( masteriyo_get_user_current_country() );

		if ( ! $pricing_zone || ! masteriyo_string_to_bool( get_post_meta( $course_id, "_multiple_currency__{$pricing_zone->get_id()}_enabled", true ) ) ) {
			return $data;
		}

		$currency = $pricing_zone->get_currency();

		if ( empty( $currency ) || masteriyo_get_currency() === $currency ) {
			return $data;
		}

		return array( $currency, $pricing_zone );
	}
}
