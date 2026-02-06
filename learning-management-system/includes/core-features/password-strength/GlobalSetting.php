<?php
/**
 * Store global password strength  options.
 *
 * @since 2.1.0
 * @package  Masteriyo\CoreFeatures\PasswordStrength
 */

namespace Masteriyo\CoreFeatures\PasswordStrength;

defined( 'ABSPATH' ) || exit;

use Masteriyo\CoreFeatures\PasswordStrength\Enums\PasswordStrength;

class GlobalSetting {

	/**
	 * Global option name.
	 *
	 * @since 2.1.0
	 */
	const OPTION_NAME = 'masteriyo_password_strength_settings';

	/**
	 * Data.
	 *
	 * @since 2.1.0
	 *
	 * @var array
	 */
	public $data = array(
		'enable'        => false,
		'min_length'    => 4,
		'max_length'    => 24,
		'strength'      => PasswordStrength::VERY_LOW,
		'show_strength' => true,
	);

	/**
	 * Initialize global setting.
	 *
	 * @since 2.1.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.1.0
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_rest_response_setting_data', array( $this, 'append_setting_in_response' ), 10, 4 );
		add_action( 'masteriyo_new_setting', array( $this, 'save_password_strength_settings' ), 10, 1 );
	}

	/**
	 * Append setting to response.
	 *
	 * @since 2.1.0
	 *
	 * @param array $data Setting data.
	 * @param Masteriyo\Models\Setting $setting Setting object.
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @param Masteriyo\RestApi\Controllers\Version1\SettingsController $controller REST settings controller object.
	 *
	 * @return array
	 */
	public function append_setting_in_response( $data, $setting, $context, $controller ) {
		$data['advance']['password_strength'] = $this->get();
		return $data;
	}

	/**
	 * Save global password strength  settings.
	 *
	 * @since 2.1.0
	 *
	 * @param \Masteriyo\Models\Setting $setting Setting object.
	 */
	public function save_password_strength_settings( $setting ) {
		$request = masteriyo_current_http_request();

		if ( ! masteriyo_is_rest_api_request() ) {
			return;
		}

		if ( ! isset( $request['advance']['password_strength'] ) ) {
			return;
		}

		$settings = masteriyo_array_only( $request['advance']['password_strength'], array_keys( $this->data ) );
		$settings = wp_parse_args( $settings, $this->get() );

		// Sanitization
		$settings['enable']        = masteriyo_string_to_bool( $settings['enable'] );
		$settings['min_length']    = absint( $settings['min_length'] );
		$settings['max_length']    = absint( $settings['max_length'] );
		$settings['strength']      = sanitize_text_field( $settings['strength'] );
		$settings['show_strength'] = masteriyo_string_to_bool( $settings['show_strength'] );

		// Validate
		$settings['min_length'] = $settings['min_length'] < 4 ? 4 : $settings['min_length'];
		$settings['max_length'] = $settings['max_length'] > 24 ? 24 : $settings['max_length'];
		$settings['strength']   = in_array( $settings['strength'], PasswordStrength::all(), true ) ? $settings['strength'] : PasswordStrength::VERY_LOW;

		update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Return global white field value.
	 *
	 * @since 2.1.0
	 *
	 * @param string $key
	 * @return string|array
	 */
	public function get( $key = null ) {
		$settings   = get_option( self::OPTION_NAME, $this->data );
		$this->data = wp_parse_args( $settings, $this->data );

		if ( null === $key ) {
			return $this->data;
		}

		if ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}

		return null;
	}

	/**
	 * Set global password strength  field.
	 *
	 * @since 2.1.0
	 *
	 * @param string $key Setting key.
	 * @param mixed $value Setting value.
	 */
	public function set( $key, $value ) {
		if ( isset( $this->data[ $key ] ) ) {
			$this->data[ $key ] = $value;
		}
	}
}
