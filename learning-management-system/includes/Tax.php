<?php
/**
 * Tax functionality handler.
 *
 * @package Masteriyo\Classes
 * @version 2.21.0
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;

class Tax {
	/**
	 * Whether taxes are enabled.
	 *
	 * @var bool
	 *
	 * @since 2.21.0
	 */
	protected $enabled = false;

	/**
	 * Tax calculation method (e.g., 'checkout', 'inclusive').
	 *
	 * @var string
	 *
	 * @since 2.21.0
	 */
	protected $calculation_method = 'checkout';

	/**
	 * Whether to display prices inclusive of tax.
	 *
	 * @var bool
	 *
	 * @since 2.21.0
	 */
	protected $display_inclusive = false;

	/**
	 * Tax regions.
	 *
	 * @var array
	 *
	 * @since 2.21.0
	 */
	protected $regions = array();

	/**
	 * Default tax rate when no specific rate is found.
	 *
	 * @var float
	 *
	 * @since 2.21.0
	 */
	protected $default_tax_rate = 0.0;

	/**
	 * Initialize tax functionality.
	 *
	 * @since 2.21.0
	 *
	 * @return void
	 */
	public function init() {
		$this->init_settings();
		$this->init_hooks();
	}

	/**
	 * Initialize tax settings.
	 *
	 * @since 2.21.0
	 *
	 * @return void
	 */
	protected function init_settings() {
		$settings = masteriyo_get_setting( 'payments.taxes' );

		if ( ! is_array( $settings ) || empty( $settings ) ) {
			$settings = array();
		}

		$this->calculation_method = $settings['calculation_method'] ?? 'checkout';
		$this->display_inclusive  = $settings['display_inclusive'] ?? false;
		$this->regions            = maybe_unserialize( $settings['regions'] ?? '' );
		$this->default_tax_rate   = floatval( $settings['default_tax_rate'] ?? 0.0 );

		// Check if taxes are enabled.
		$this->enabled = 'checkout' === $this->calculation_method;
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.21.0
	 *
	 * @return void
	 */
	protected function init_hooks() {
		if ( $this->enabled ) {
			// Tax calculation on checkout page.
			add_filter( 'masteriyo_calculated_total', array( $this, 'calculate_taxes' ), 11, 2 );
		}

		if ( $this->display_inclusive ) {
			add_filter( 'masteriyo_setup_course_data', array( $this, 'modify_price_on_frontend_page' ), 9 ); // For single course page.
			add_filter( 'masteriyo_course_archive_course', array( $this, 'modify_price_on_frontend_page' ), 9 ); // For course archive page.

			add_filter( 'masteriyo_bundled_course', array( $this, 'modify_price_on_frontend_page' ), 10 ); // For single course within a specific course bundle page.
			add_filter( 'masteriyo_single_course_bundle', array( $this, 'modify_price_on_frontend_page_for_course_bundle' ), 9 ); // For single/archive course bundle page.

			add_filter( 'masteriyo_group_buy_btn_price', array( $this, 'modify_group_course_price' ), 9 ); // For modification of course price for the group in single course page.

			add_filter( 'masteriyo_tax_inclusive', array( $this, 'tax_inclusive' ) ); // Display tax inclusive text.
		}
	}

	/**
	 * Calculate taxes for cart.
	 *
	 * @since 2.21.0
	 *
	 * @param float $total Cart total.
	 * @param \Masteriyo\Cart\Cart $cart Cart object.
	 *
	 * @return float $total Cart total with taxes.
	 */
	public function calculate_taxes( $total, $cart ) {
		if ( ! $this->enabled ) {
			$cart->set_tax_total( 0 );
			return;
		}

		$tax_total = $this->get_tax_total( $total );

		$cart->set_tax_total( $tax_total );

		return $total + $tax_total;
	}

	/**
	 * Modify price on frontend page to include tax.
	 *
	 * @since 2.21.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 * @return \Masteriyo\Models\Course Modified course object.
	 */
	public function modify_price_on_frontend_page( $course ) {
		if ( ! $this->enabled || ! $this->display_inclusive || ! $course instanceof \Masteriyo\Models\Course ) {
			return $course;
		}

		// Not supported for archive course bundle page.
		if ( masteriyo_is_bundles_archive_page() ) {
			return $course;
		}

		$this->add_tax_to_display_prices( $course );

		return $course;
	}

	/**
	 * Modify price on frontend page to include tax for course bundle.
	 *
	 * @since 2.21.0
	 *
	 * @param \Masteriyo\Models\Course $course_bundle The course bundle object.
	 * @return \Masteriyo\Models\Course Modified course bundle object.
	 */
	public function modify_price_on_frontend_page_for_course_bundle( $course_bundle ) {
		if ( ! $this->enabled || ! $this->display_inclusive || ! masteriyo_is_bundle_product( $course_bundle ) ) {
			return $course_bundle;
		}

		// Not supported for archive course bundle page.
		if ( masteriyo_is_bundles_archive_page() ) {
			return $course_bundle;
		}

		$this->add_tax_to_display_prices( $course_bundle );

		return $course_bundle;
	}

	/**
	 * Add tax to a course's display prices, inclusive of tax.
	 *
	 * The regular price (struck through when on sale) and the active price
	 * (the current amount) are taxed independently, because on a sale course
	 * get_price() returns the sale price while get_regular_price() does not.
	 *
	 * @param \Masteriyo\Models\Course $course The course or course bundle object.
	 * @return void
	 */
	private function add_tax_to_display_prices( $course ) {
		$sale_price    = $course->get_sale_price();
		$regular_price = $course->get_regular_price();
		$active_price  = $course->get_price();

		if ( $sale_price ) {
			$sale_price += $this->get_tax_total( $sale_price );
			$course->set_sale_price( $sale_price );
		}

		if ( $regular_price ) {
			$regular_price += $this->get_tax_total( $regular_price );
			$course->set_regular_price( $regular_price );
		}

		if ( $active_price ) {
			$active_price += $this->get_tax_total( $active_price );
			$course->set_price( $active_price );
		}
	}

	/**
	 * Modify price of a course for group to include tax.
	 *
	 * @since 2.21.0
	 *
	 * @param float $price The price of the course.
	 * @return float Modified price of the course.
	 */
	public function modify_group_course_price( $price ) {
		if ( ! $this->enabled || ! $this->display_inclusive ) {
			return $price;
		}

		$price += $this->get_tax_total( $price );

		return $price;
	}

	/**
	 * Get whether the store is set to display prices inclusive of tax.
	 *
	 * @since 2.21.0
	 *
	 * @param boolean $tax_inclusive Whether the store is set to display prices inclusive of tax.
	 * @return boolean Whether the store is set to display prices inclusive of tax.
	 */
	public function tax_inclusive( $tax_inclusive ) {
		if ( ! $this->display_inclusive ) {
			return false;
		}

		if ( 'checkout' === $this->calculation_method ) {
			$country = masteriyo_get_user_billing_country();
			$state   = masteriyo_get_user_billing_state();

			if ( empty( $country ) ) {
				return false;
			}

			$rate = $this->get_tax_rate_for_location( $country, $state );

			if ( $rate <= 0 ) {
				return false;
			}
		}

		if ( masteriyo_is_single_course_page() || masteriyo_is_courses_page( true ) || masteriyo_is_bundle_page() ) {
			return true;
		}

		return $tax_inclusive;
	}

	/**
	 * Get tax rate for specific location.
	 *
	 * @since 2.21.0
	 *
	 * @param string $country
	 * @param string $state
	 * @return int|float
	 */
	public function get_tax_rate_for_location( $country, $state = '' ) {
		$region = $this->regions[ $country ] ?? array();

		if ( empty( $region ) ) {
			return $this->default_tax_rate;
		}

		if ( ! empty( $state ) && isset( $region['states'][ $state ] ) ) {
			return $region['states'][ $state ] ?? $this->default_tax_rate;
		}

		return $region['rate'] ?? $this->default_tax_rate;
	}

	/**
	 * Check if taxes are enabled.
	 *
	 * @since 2.21.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Get the total tax for a given subtotal.
	 *
	 * @since 2.21.0
	 *
	 * @param float $subtotal Subtotal to calculate tax for.
	 *
	 * @return float Total tax.
	 */
	private function get_tax_total( $subtotal ) {
		$tax_total = 0;
		$country   = masteriyo_get_user_billing_country();
		$state     = masteriyo_get_user_billing_state();

		if ( $country ) {
			// If state is required field and is empty, return 0.

			$rate = $this->get_tax_rate_for_location( $country, $state );

			if ( $rate > 0 ) {
				$tax_total = $subtotal * ( $rate / 100 );
			}
		}

		return $tax_total;
	}
}
