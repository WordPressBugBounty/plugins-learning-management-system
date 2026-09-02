<?php
/**
 * Checkout field definitions.
 *
 * @package Masteriyo\Classes
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;

/**
 * The single source of truth for the checkout form's fields.
 *
 * One place answers all three questions about a checkout field — does it exist, is it
 * visible, is it required — so the form renderer and the server-side validator cannot
 * disagree about any of them. Both read the list this class produces; neither re-derives
 * visibility from the settings on its own.
 *
 * The list is assembled from the country address fields (with the country locale overlay
 * applied), plus the fields that are not part of an address, and is then handed to the
 * `masteriyo_checkout_fields` filter *last*, so a third party can add fields or override
 * what is resolved here.
 */
class CheckoutFields {

	/**
	 * Fields a purchase cannot complete without, whether or not they are configurable.
	 *
	 * The whole list: a buyer is identified by a name and reachable by an email, and where
	 * the site shows a GDPR consent it has to be given. Every other field is required only
	 * where the seller has chosen to collect it.
	 *
	 * @var string[]
	 */
	const ALWAYS_REQUIRED = array(
		'billing_first_name',
		'billing_last_name',
		'billing_email',
		'gdpr',
	);

	/**
	 * Fields that are shown when the seller enables them, and never demanded.
	 *
	 * A buyer who is not a business has no company name to give, and a second address line
	 * exists precisely for the addresses that need one. Every other address field is
	 * enforced once it is enabled — asking for an address and then accepting half of it
	 * would leave the seller with records they cannot use — softened per country by the
	 * locale table and by the rule that a state cannot be required where none is known.
	 *
	 * @var string[]
	 */
	const NEVER_REQUIRED = array(
		'billing_address_2',
		'billing_company',
	);

	/**
	 * Get the resolved checkout field definitions.
	 *
	 * @param string $country Country code the address fields are resolved for. Defaults to
	 *                        the store's base country.
	 *
	 * @return array Field key => definition, each carrying a boolean `enable` and `required`.
	 */
	public static function get_fields( $country = '' ) {
		/** @var \Masteriyo\Countries $countries */
		$countries = masteriyo( 'countries' );

		$fields = array_merge(
			$countries->get_address_fields( $country, 'billing_' ),
			self::get_non_address_fields()
		);

		$fields = self::resolve_visibility( $fields );
		$fields = self::resolve_requiredness( $fields );

		/**
		 * Filters checkout fields.
		 *
		 * @since 1.0.0
		 *
		 * @param array $fields Checkout fields.
		 */
		return apply_filters( 'masteriyo_checkout_fields', $fields );
	}

	/**
	 * Get the fields the checkout form shows and the checkout validates.
	 *
	 * @param string $country Country code the address fields are resolved for.
	 *
	 * @return array
	 */
	public static function get_visible_fields( $country = '' ) {
		return array_filter(
			self::get_fields( $country ),
			function ( $field ) {
				return ! empty( $field['enable'] );
			}
		);
	}

	/**
	 * The fields that are not part of a billing address.
	 *
	 * `enable` and `required` are left out on purpose: both are resolved below, from the
	 * one map each, so that no field carries its own private answer.
	 *
	 * @return array
	 */
	protected static function get_non_address_fields() {
		return array(
			'customer_note'     => array(
				'label'        => __( 'Customer Note', 'learning-management-system' ),
				'type'         => 'text',
				'class'        => array( 'form-row-wide' ),
				'autocomplete' => 'no',
				'priority'     => 110,
			),
			'attachment_upload' => array(
				'label'    => __( 'Upload Attachment', 'learning-management-system' ),
				'type'     => 'file',
				'class'    => array( 'form-row-wide' ),
				'priority' => 115,
			),
			'gdpr'              => array(
				'label'        => __( 'GDPR', 'learning-management-system' ),
				'type'         => 'checkbox',
				'class'        => array( 'form-row-wide' ),
				'autocomplete' => 'no',
				'priority'     => 130,
			),
		);
	}

	/**
	 * Which fields the checkout shows.
	 *
	 * @return array<string, bool>
	 */
	protected static function get_visibility_map() {
		$country_enabled = self::is_field_enabled( 'country' );

		return array(
			'billing_first_name' => true,
			'billing_last_name'  => true,
			'billing_email'      => true,
			'billing_company'    => self::is_field_enabled( 'company' ),
			'billing_country'    => $country_enabled,
			'billing_address_1'  => self::is_field_enabled( 'address_1' ),
			'billing_address_2'  => self::is_field_enabled( 'address_2' ),
			// City, state and postcode are parts of an address that mean nothing without
			// the country they belong to, so collecting the country is their precondition.
			'billing_state'      => $country_enabled && self::is_field_enabled( 'state' ),
			'billing_city'       => $country_enabled && self::is_field_enabled( 'city' ),
			'billing_postcode'   => $country_enabled && self::is_field_enabled( 'postcode' ),
			'billing_phone'      => self::is_field_enabled( 'phone' ),
			'customer_note'      => self::is_field_enabled( 'customer_note' ),
			'attachment_upload'  => self::is_field_enabled( 'attachment_upload' ),
			// Not the GDPR setting alone: the notice also needs a message and a privacy
			// policy page to link to, and this helper is what decides that.
			'gdpr'               => (bool) masteriyo_show_gdpr_msg(),
		);
	}

	/**
	 * Set `enable` on every field.
	 *
	 * A field the map does not know about was contributed by a third party through one of
	 * the address-field filters; it keeps whatever it says about itself, and is visible if
	 * it says nothing.
	 *
	 * @param array $fields Field definitions.
	 *
	 * @return array
	 */
	protected static function resolve_visibility( $fields ) {
		$map = self::get_visibility_map();

		foreach ( $fields as $key => $field ) {
			if ( isset( $map[ $key ] ) ) {
				$fields[ $key ]['enable'] = $map[ $key ];
			} else {
				$fields[ $key ]['enable'] = isset( $field['enable'] ) ? ! empty( $field['enable'] ) : true;
			}
		}

		return $fields;
	}

	/**
	 * Set `required` on every field.
	 *
	 * Every field carries a deliberate designation: the two lists above, and otherwise
	 * whatever the definition says — which for an address field is the country locale's
	 * answer. Nothing is conditional on a feature being enabled: the one candidate was tax
	 * calculation, which reads the buyer's country, and the country is enforced wherever it
	 * is collected. The store's own country is a different question, and every reader of it
	 * already copes with it being unset, so an incomplete store address blocks nothing.
	 *
	 * @param array $fields Field definitions.
	 *
	 * @return array
	 */
	protected static function resolve_requiredness( $fields ) {
		foreach ( $fields as $key => $field ) {
			if ( in_array( $key, self::ALWAYS_REQUIRED, true ) ) {
				$fields[ $key ]['required'] = true;
			} elseif ( in_array( $key, self::NEVER_REQUIRED, true ) ) {
				$fields[ $key ]['required'] = false;
			} else {
				$fields[ $key ]['required'] = ! empty( $field['required'] );
			}
		}

		return $fields;
	}

	/**
	 * Whether the seller has enabled a checkout field in the settings.
	 *
	 * @param string $name Setting name under `payments.checkout_fields`.
	 *
	 * @return bool
	 */
	protected static function is_field_enabled( $name ) {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'payments.checkout_fields.' . $name ) );
	}
}
