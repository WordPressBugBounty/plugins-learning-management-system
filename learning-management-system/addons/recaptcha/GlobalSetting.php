<?php
/**
 * Store global recaptcha options.
 *
 * @since x.x.x
 * @package \Masteriyo\Addons\Recaptcha
 */

namespace Masteriyo\Addons\Recaptcha;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Addons\Recaptcha\Enums\RecaptchaDomain;
use Masteriyo\Addons\Recaptcha\Enums\RecaptchaPage;
use Masteriyo\Addons\Recaptcha\Enums\RecaptchaSize;
use Masteriyo\Addons\Recaptcha\Enums\RecaptchaTheme;
use Masteriyo\Addons\Recaptcha\Enums\RecaptchaVersion;

class GlobalSetting {

	/**
	 * Global option name.
	 *
	 * @since x.x.x
	 */
	const OPTION_NAME = 'masteriyo_recaptcha_settings';

	/**
	 * Data.
	 *
	 * @since x.x.x
	 *
	 * @var array
	 */
	public $data = array(
		'version'                         => RecaptchaVersion::V2_I_AM_NOT_A_ROBOT,
		'site_key'                        => '',
		'secret_key'                      => '',
		'error_message'                   => '',
		'theme'                           => RecaptchaTheme::LIGHT,
		'size'                            => RecaptchaSize::NORMAL,
		'domain'                          => RecaptchaDomain::GOOGLE_COM,
		'score'                           => 0.5,
		'pages'                           => RecaptchaPage::ALL,
		'language'                        => '', // Automatic according to client system.
		'enable_login_form'               => false,
		'enable_student_register_form'    => false,
		'enable_instructor_register_form' => false,
	);

	/**
	 * Initialize global setting.
	 *
	 * @since x.x.x
	 */
	public function init() {
		$this->set( 'error_message', __( 'Please solve the CAPTCHA to proceed', 'learning-management-system' ) );

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since x.x.x
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_rest_response_setting_data', array( $this, 'append_setting_in_response' ), 10, 4 );
		add_action( 'masteriyo_new_setting', array( $this, 'save_recaptcha_settings' ), 10, 1 );
	}

	/**
	 * Append setting to response.
	 *
	 * @since x.x.x
	 *
	 * @param array $data Setting data.
	 * @param Masteriyo\Models\Setting $setting Setting object.
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @param Masteriyo\RestApi\Controllers\Version1\SettingsController $controller REST settings controller object.
	 *
	 * @return array
	 */
	public function append_setting_in_response( $data, $setting, $context, $controller ) {
		$data['authentication']['recaptcha'] = $this->get();

		return $data;
	}

	/**
	 * Save global recaptcha settings.
	 *
	 * @since x.x.x
	 *
	 * @param \Masteriyo\Models\Setting $setting Setting object.
	 */
	public function save_recaptcha_settings( $setting ) {
		$request = masteriyo_current_http_request();

		if ( ! masteriyo_is_rest_api_request() ) {
			return;
		}

		if ( ! isset( $request['authentication']['recaptcha'] ) ) {
			return;
		}

		$settings = masteriyo_array_only( $request['authentication']['recaptcha'], array_keys( $this->data ) );
		$settings = wp_parse_args( $settings, $this->get() );
		$settings = $this->sanitize( $settings );

		update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Sanitized global recaptcha settings.
	 *
	 * @since x.x.x
	 *
	 * @return array
	 */
	protected function sanitize( $settings ) {
		// Sanitization
		$settings['version']                         = sanitize_text_field( $settings['version'] );
		$settings['site_key']                        = sanitize_text_field( $settings['site_key'] );
		$settings['secret_key']                      = sanitize_text_field( $settings['secret_key'] );
		$settings['error_message']                   = sanitize_text_field( $settings['error_message'] );
		$settings['theme']                           = sanitize_text_field( $settings['theme'] );
		$settings['size']                            = sanitize_text_field( $settings['size'] );
		$settings['domain']                          = sanitize_text_field( $settings['domain'] );
		$settings['pages']                           = sanitize_text_field( $settings['pages'] );
		$settings['language']                        = sanitize_text_field( $settings['language'] );
		$settings['score']                           = masteriyo_round( absint( $settings['score'] ), 1 );
		$settings['enable_login_form']               = masteriyo_string_to_bool( $settings['enable_login_form'] );
		$settings['enable_student_register_form']    = masteriyo_string_to_bool( $settings['enable_student_register_form'] );
		$settings['enable_instructor_register_form'] = masteriyo_string_to_bool( $settings['enable_instructor_register_form'] );

		return $settings;
	}

	/**
	 * Return global white field value.
	 *
	 * @since x.x.x
	 *
	 * @param string $key
	 * @return string|mixed
	 */
	public function get( $key = null ) {
		$settings   = get_option( self::OPTION_NAME, $this->data );
		$settings   = wp_parse_args( $settings, $this->data );
		$this->data = $this->sanitize( $settings );

		if ( null === $key ) {
			return $this->data;
		}

		if ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}

		return null;
	}

	/**
	 * Set global recaptcha field.
	 *
	 * @since x.x.x
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
