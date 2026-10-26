<?php
namespace Masteriyo\CoreFeatures\PasswordStrength;

use Masteriyo\Constants;

defined( 'ABSPATH' ) || exit;

class PasswordStrength {
	/**
	 * password strength addon global setting instance.
	 *
	 * @since 3.1.0
	 *
	 * @var \Masteriyo\CoreFeatures\PasswordStrength\GlobalSetting
	 */
	public $global_setting = null;
	/**
	 * Initialize module.
	 *
	 * @since 3.1.0
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
	 * @since 3.1.0
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_scripts' ) );
		add_action( 'masteriyo_enqueue_styles', array( $this, 'enqueue_styles' ), 20 );

		// The policy was enforced in the browser alone, so it was both bypassable and invisible (#1398).
		add_filter( 'masteriyo_validate_registration_form_data', array( $this, 'validate_password_policy' ), 10, 2 );
		add_filter( 'masteriyo_validate_password_reset_form_data', array( $this, 'validate_password_policy' ), 10, 2 );
	}

	/**
	 * Refuse a password the policy forbids, naming the rules it breaks.
	 *
	 * @param \WP_Error $error Error object which should contain validation errors if there is any.
	 * @param array     $data Submitted form data.
	 *
	 * @return \WP_Error
	 */
	public function validate_password_policy( $error, $data ) {
		$password = isset( $data['password'] ) ? (string) $data['password'] : '';

		// An empty field is either the form's own required check or a password generated for the user.
		if ( '' === $password ) {
			return $error;
		}

		$policy = new Policy( $this->global_setting->get() );
		$failed = $policy->failures( $password );

		if ( empty( $failed ) ) {
			return $error;
		}

		$messages = Policy::messages();

		$error->add(
			'password_policy_not_met',
			sprintf( $messages['policyNotMet'], implode( ', ', $failed ) ),
			array( 'failed_rules' => array_keys( $failed ) )
		);

		return $error;
	}

	/**
	 * Enqueue styles.
	 *
	 * @param array $styles
	 *
	 * @since 3.1.0
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
	 * @since 3.1.0
	 *
	 * @return boolean
	 */
	public function load_js() {
		global $post;

		// Handle instructor registration shortcode.
		if ( $post && has_shortcode( $post->post_content, 'masteriyo_instructor_registration' ) ) {
			return true;
		}

		// Handle the account shortcode.
		if ( $post && has_shortcode( $post->post_content, 'masteriyo_account' ) && ! is_user_logged_in() ) {
			return true;
		}

		// The student form also renders from its own shortcode, on any page.
		return masteriyo_is_instructor_registration_page() || masteriyo_is_student_registration_page();
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 3.1.0
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
	 * @since 3.1.0
	 *
	 * @param array $scripts Array of scripts.
	 * @return array
	 */
	public function localize_scripts( $scripts ) {
		$settings = masteriyo_array_snake_to_camel( $this->global_setting->get() );

		// The rule wording comes from the policy, so the script and the server say the same thing.
		$settings['i18n'] = array_merge(
			Policy::messages(),
			array(
				'veryWeak'   => __( 'Very Weak', 'learning-management-system' ),
				'weak'       => __( 'Weak', 'learning-management-system' ),
				'good'       => __( 'Good', 'learning-management-system' ),
				'strong'     => __( 'Strong', 'learning-management-system' ),
				'veryStrong' => __( 'Very Strong', 'learning-management-system' ),
			)
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
