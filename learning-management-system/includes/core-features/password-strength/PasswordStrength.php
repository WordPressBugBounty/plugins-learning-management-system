<?php
namespace Masteriyo\CoreFeatures\PasswordStrength;

use Masteriyo\Constants;

defined( 'ABSPATH' ) || exit;

class PasswordStrength {
	/**
	 * password strength addon global setting instance.
	 *
	 * @since 2.1.0
	 *
	 * @var \Masteriyo\CoreFeatures\PasswordStrength\GlobalSetting
	 */
	public $global_setting = null;
	/**
	 * Initialize module.
	 *
	 * @since 2.1.0
	 */
	public function init() {
		$this->global_setting = new GlobalSetting();
		$this->global_setting->init();
		if ( ! masteriyo_get_setting( 'advance.password_strength.enable' ) ) {
			return;
		}
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.1.0
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_scripts' ) );
		add_action( 'masteriyo_enqueue_styles', array( $this, 'enqueue_styles' ), 20 );
	}

	/**
	 * Enqueue styles.
	 *
	 * @param array $styles
	 *
	 * @since 2.1.0
	 */
	public function enqueue_styles( $styles ) {
		return masteriyo_parse_args(
			$styles,
			array(
				'password-strength' => array(
					'src'      => plugin_dir_url( Constants::get( 'MASTERIYO_PASSWORD_STRENGTH_FILE' ) ) . 'css/password-strength.css',
					'has_rtl'  => false,
					'context'  => 'public',
					'callback' => array( $this, 'load_js' ),
				),
			)
		);
	}

	/**
	 * Load password strength js and libs only on student and instructor registration forms.
	 *
	 * @since 2.1.0
	 *
	 * @return boolean
	 */
	public function load_js() {
		global $post;

		// Handle instructor registration shortcode.
		if ( $post && has_shortcode( $post->post_content, 'masteriyo_instructor_registration' ) ) {
			return true;
		}

		// Handle instructor registration shortcode.
		if ( $post && has_shortcode( $post->post_content, 'masteriyo_account' ) && ! is_user_logged_in() ) {
			return true;
		}

		return masteriyo_is_instructor_registration_page() || masteriyo_is_signup_page();
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 2.1.0
	 *
	 * @param array $scripts Array of scripts.
	 * @return array
	 */
	public function enqueue_scripts( $scripts ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		return wp_parse_args(
			$scripts,
			array(
				'password-strength' => array(
					'src'      => plugin_dir_url( Constants::get( 'MASTERIYO_PASSWORD_STRENGTH_FILE' ) ) . '/js/password-strength' . $suffix . '.js',
					'context'  => 'public',
					'callback' => array( $this, 'load_js' ),
					'deps'     => array( 'wp-i18n', 'masteriyo-zxcvbn' ),
				),
				'zxcvbn'            => array(
					'src'      => plugin_dir_url( Constants::get( 'MASTERIYO_PASSWORD_STRENGTH_FILE' ) ) . '/js/zxcvbn' . $suffix . '.js',
					'context'  => 'public',
					'callback' => array( $this, 'load_js' ),
				),
			)
		);
	}

	/**
	 * Localize scripts.
	 *
	 * @since 2.1.0
	 *
	 * @param array $scripts Array of scripts.
	 * @return array
	 */
	public function localize_scripts( $scripts ) {
		$settings = masteriyo_array_snake_to_camel( $this->global_setting->get() );

		$settings['i18n'] = array(
			/* translators: %d: number of characters (minimum). */
			'minCharacters'       => __( 'Minimum of %d characters', 'learning-management-system' ),
			/* translators: %d: number of characters (maximum). */
			'maxCharacters'       => __( 'Maximum of %d characters', 'learning-management-system' ),
			'atLeastOneUppercase' => __( 'At least one uppercase letter', 'learning-management-system' ),
			'atLeastOneNumber'    => __( 'At least one number', 'learning-management-system' ),
			'atLeastOneSpecial'   => __( 'At least one special character', 'learning-management-system' ),
			'veryWeak'            => __( 'Very Weak', 'learning-management-system' ),
			'weak'                => __( 'Weak', 'learning-management-system' ),
			'good'                => __( 'Good', 'learning-management-system' ),
			'strong'              => __( 'Strong', 'learning-management-system' ),
			'veryStrong'          => __( 'Very Strong', 'learning-management-system' ),
		);

		return masteriyo_parse_args(
			$scripts,
			array(
				'password-strength' => array(
					'name' => '_MASTERIYO_PASSWORD_STRENGTH_',
					'data' => $settings,
				),
			)
		);
	}
}
