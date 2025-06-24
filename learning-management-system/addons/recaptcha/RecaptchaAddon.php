<?php
/**
 * Masteriyo Google recaptcha setup.
 *
 * @package Masteriyo\Addons\Recaptcha
 *
 * @since 1.18.2
 */
namespace Masteriyo\Addons\Recaptcha;

use Masteriyo\Addons\Recaptcha\Enums\RecaptchaPage;
use Masteriyo\Constants;
use Masteriyo\Addons\Recaptcha\Request;
use Masteriyo\Addons\Recaptcha\GlobalSetting;
use Masteriyo\Addons\Recaptcha\Enums\RecaptchaVersion;

defined( 'ABSPATH' ) || exit;
/**
 * Main Masteriyo Recaptcha class.
 *
 * @class Masteriyo\Addons\Recaptcha
 */
class RecaptchaAddon {
	/**
	 * Google recaptcha global setting instance.
	 *
	 * @since 1.18.2
	 *
	 * @var \Masteriyo\Addons\Recaptcha\GlobalSetting
	 */
	public $global_setting = null;

	/**
	 * Google recaptcha request instance.
	 *
	 * @since 1.18.2
	 *
	 * @var \Masteriyo\Addons\Recaptcha\Request
	 */
	public $request = null;

	/**
	 * Constructor.
	 *
	 * @since 1.18.2
	 *
	 * @param \Masteriyo\Addons\Recaptcha\GlobalSetting $global_setting
	 * @param \Masteriyo\Addons\Recaptcha\Request $request
	 */
	public function __construct( GlobalSetting $global_setting, Request $request ) {
		$this->global_setting = $global_setting;
		$this->request        = $request;
	}
	/**
	 * Initialize module.
	 *
	 * @since 1.18.2
	 */
	public function init() {
		$this->global_setting->init();

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.18.2
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localized_public_scripts' ) );
		add_action( 'masteriyo_registration_form_before_submit_button', array( $this, 'render_container_for_recaptcha' ) );
		add_action( 'masteriyo_instructor_registration_form_before_submit_button', array( $this, 'render_container_for_recaptcha' ) );
		add_action( 'masteriyo_login_form_before_submit_button', array( $this, 'render_container_for_recaptcha' ) );
		add_filter( 'script_loader_tag', array( $this, 'add_async_attribute_to_recaptcha_script' ), 10, 2 );
		add_filter( 'masteriyo_validate_registration_form_data', array( $this, 'validate_form' ), 10, 2 );
		add_filter( 'masteriyo_validate_login_form_data', array( $this, 'validate_form' ), 10, 2 );
	}

	/**
	 * Validate forms.
	 *
	 * @since 1.18.2
	 * @param \WP_Error $error Error object which should contain validation errors if there is any.
	 * @param array $data Submitted form data.
	 *
	 * @return WP_Error
	 */
	public function validate_form( $error, $data ) {
		$post_id = url_to_postid( wp_get_referer() );

		if ( masteriyo_is_instructor_registration_page() && $this->global_setting->get( 'enable_instructor_register_form' ) ) {
			return $this->validate_recaptcha( $error, $data );
		}

		if ( masteriyo_is_signup_page() ) {
			if ( $this->global_setting->get( 'enable_student_register_form' ) ) {
				return $this->validate_recaptcha( $error, $data );

			}
			return $error;
		}

		if ( masteriyo_is_signin_page( $post_id ) && $this->global_setting->get( 'enable_login_form' ) ) {
			return $this->validate_recaptcha( $error, $data );
		}

		return $error;
	}

	/**
	 * Validate recaptcha response.
	 *
	 * @since 1.18.2
	 * @param \WP_Error $error Error object which should contain validation errors if there is any.
	 * @param array $data Submitted form data.
	 *
	 * @return WP_Error
	 */
	public function validate_recaptcha( $error, $data ) {
		$error_message = $this->global_setting->get( 'error_message' );
		$secret_key    = $this->global_setting->get( 'secret_key' );
		$score         = $this->global_setting->get( 'score' );
		$ip_address    = masteriyo_get_current_ip_address();

		$token = isset( $_POST['g-recaptcha-response'] ) ? $_POST['g-recaptcha-response'] : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $token ) ) {
			$error->add( 'recaptcha_error', $error_message );
		}

		/**
		 * Filters inclusion of user ip address while validation reCAPTCHA token.
		 *
		 * @since 1.18.2
		 *
		 * @param boolean
		 */
		if ( apply_filters( 'masteriyo_pro_recaptcha_include_user_ip_address', true ) ) {
			$validate = $this->request->validate( $secret_key, $token, $score, $ip_address, $error_message );
		} else {
			$validate = $this->request->validate( $secret_key, $token, $score, '', $error_message );
		}

		if ( is_wp_error( $validate ) ) {
			return $validate;
		}

		return $error;
	}

	/**
	 * Add async attribute to recaptcha script.
	 *
	 * @since 1.18.2
	 *
	 * @param string $tag
	 * @param string $handle
	 * @return string
	 */
	public function add_async_attribute_to_recaptcha_script( $tag, $handle ) {
		if ( 'masteriyo-recaptcha-official' === $handle ) {
			return str_replace( ' src', ' async src', $tag );
		}

		return $tag;
	}

	/**
	 * Render container for google recaptcha.
	 *
	 * @since 1.18.2
	 */
	public function render_container_for_recaptcha() {
		echo wp_kses_post( '<div id="masteriyo-recaptcha"></div>' );
	}

	/**
	 * Localized public scripts.
	 *
	 * @since 1.18.2
	 *
	 * @param array $localized_scripts
	 * @return array
	 */
	public function localized_public_scripts( $localized_scripts ) {
		return masteriyo_parse_args(
			$localized_scripts,
			array(
				'recaptcha' => array(
					'name' => '_MASTERIYO_RECAPTCHA_',
					'data' => masteriyo_array_except( masteriyo_array_snake_to_camel( $this->global_setting->get() ), 'secretKey' ),
				),
			)
		);
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.18.2
	 *
	 * @param array $scripts
	 * @return array
	 */
	public function enqueue_scripts( $scripts ) {
		/**
		 * Filters load of recaptcha official library.
		 *
		 * @since 1.18.2
		 * @param bool $load_recaptcha_official_library
		 */
		$load_library = apply_filters( 'masteriyo_pro_load_recaptcha_official_library', true );

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( $load_library ) {
			$scripts['recaptcha-official'] = array(
				'src'      => $this->get_recaptcha_url(),
				'context'  => 'public',
				'deps'     => array(),
				'version'  => '',
				'callback' => function() {
					if ( RecaptchaPage::ALL === $this->global_setting->get( 'page' ) ) {
						return true;
					}

					if ( masteriyo_is_instructor_registration_page() && $this->global_setting->get( 'enable_instructor_register_form' ) ) {
						return true;
					}

					if ( masteriyo_is_signup_page() ) {
						if ( $this->global_setting->get( 'enable_student_register_form' ) ) {
							return true;
						}
						return false;
					}

					if ( masteriyo_is_signin_page() && $this->global_setting->get( 'enable_login_form' ) ) {
						return true;
					}

					return false;
				},
			);
		}

		$scripts['recaptcha'] = array(
			'src'      => plugin_dir_url( Constants::get( 'MASTERIYO_RECAPTCHA_ADDON_FILE' ) ) . 'js/recaptcha' . $suffix . '.js',
			'context'  => 'public',
			'deps'     => $load_library ? array( 'jquery', 'masteriyo-recaptcha-official' ) : array( 'jquery' ),
			'callback' => function() {
				if ( masteriyo_is_instructor_registration_page() && $this->global_setting->get( 'enable_instructor_register_form' ) ) {
					return true;
				}

				if ( masteriyo_is_signup_page() ) {
					if ( $this->global_setting->get( 'enable_student_register_form' ) ) {
						return true;
					}
					return false;
				}

				if ( masteriyo_is_signin_page() && $this->global_setting->get( 'enable_login_form' ) ) {
					return true;
				}

				return false;
			},
		);

		return $scripts;
	}

	/**
	 * Return google reCAPTCHA url based on the domain of global setting.
	 *
	 * @since 1.18.2
	 *
	 * @return string
	 */
	public function get_recaptcha_url() {
		$domain   = $this->global_setting->get( 'domain' );
		$version  = $this->global_setting->get( 'version' );
		$language = $this->global_setting->get( 'language' );
		$site_key = $this->global_setting->get( 'site_key' );

		$url = "https://www.{$domain}/recaptcha/api.js";

		if ( RecaptchaVersion::V3 === $version ) {
			$url = add_query_arg(
				array(
					'render' => $site_key,
				),
				$url
			);
		}

		if ( ! empty( $language ) ) {
			$url = add_query_arg(
				array(
					'hl' => $language,
				),
				$url
			);
		}

		return $url;
	}
}
