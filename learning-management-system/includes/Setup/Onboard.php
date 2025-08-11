<?php
/**
 * Masteriyo Onboard class.
 *
 * @since 1.0.0
 *
 * @package  Masteriyo\Setup
 */

namespace Masteriyo\Setup;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\Stripe\Setting as StripeSetting;

class Onboard {

	/**
	 * Page name.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string Current page name.
	 */
	private $page_name = 'masteriyo-onboard';

	/**
	 * Initializing onboarding class.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_onboarding_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'onboard_setup_wizard' ), 30 );
	}

	/**
	 * Add Menu for onboard process.
	 *
	 * @since 1.0.0
	 */
	public function add_onboarding_admin_menu() {
		add_menu_page(
			__( 'Masteriyo Onboard', 'learning-management-system' ),
			'masteriyo onboard',
			'manage_options',
			$this->page_name,
			''
		);
	}

	/**
	 * Onboarding process.
	 *
	 * @since 1.0.0
	 */
	public function onboard_setup_wizard() {

		$this->handle_stripe_disconnect();

		// if we are here, we assume we don't need to run the wizard again
		// and the user doesn't need to be redirected here
		update_option( 'masteriyo_first_time_activation_flag', true );

		// Proceeding only when we are on right page.
		if ( ! isset( $_GET['page'] ) || $this->page_name !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$onboard_dependencies = include_once MASTERIYO_PLUGIN_DIR . '/assets/js/build/masteriyo-gettingStarted.asset.php';

		wp_register_script(
			'masteriyo-onboarding',
			masteriyo_is_production() ? plugin_dir_url( MASTERIYO_PLUGIN_FILE ) . '/assets/js/build/masteriyo-gettingStarted.js' : 'http://localhost:3000/dist/gettingStarted.js',
			$onboard_dependencies['dependencies'],
			$onboard_dependencies['version'],
			true
		);

		if ( masteriyo_is_production() ) {
			wp_register_script(
				'masteriyo-dependencies',
				plugin_dir_url( MASTERIYO_PLUGIN_FILE ) . '/assets/js/build/masteriyo-dependencies.js',
				$onboard_dependencies['dependencies'],
				$onboard_dependencies['version'],
				true
			);
		}

		// Add localization vars.
		wp_localize_script(
			'masteriyo-onboarding',
			'_MASTERIYO_',
			array(
				'rootApiUrl'              => esc_url_raw( untrailingslashit( rest_url() ) ),
				'nonce'                   => wp_create_nonce( 'wp_rest' ),
				'stripe_nonce'            => wp_create_nonce( 'masteriyo_stripe_connect' ),
				'adminURL'                => esc_url( admin_url() ),
				'siteURL'                 => esc_url( home_url( '/' ) ),
				'pluginUrl'               => esc_url( plugin_dir_url( MASTERIYO_PLUGIN_FILE ) ),
				'permalinkStructure'      => get_option( 'permalink_structure' ),
				'permalinkOptionsPage'    => esc_url( admin_url( 'options-permalink.php' ) ),
				'pageBuilderURL'          => esc_url( admin_url( '/admin.php?page=masteriyo#/courses/:courseId/edit' ) ),
				'pagesID'                 => array(
					'courses'  => masteriyo_get_page_id_by_slug( 'courses' ),
					'account'  => masteriyo_get_page_id_by_slug( 'account' ),
					'checkout' => masteriyo_get_page_id_by_slug( 'masteriyo-checkout' ),
				),
				'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
				'ajaxNonce'               => wp_create_nonce( 'masteriyo_allow_usage_notice_nonce' ),
				'allowUsage'              => masteriyo_bool_to_string( masteriyo_get_setting( 'advance.tracking.allow_usage' ) ),
				'subscribeUpdates'        => masteriyo_bool_to_string( masteriyo_get_setting( 'advance.tracking.subscribe_updates' ) ),
				'subscriptionEmail'       => masteriyo_get_setting( 'advance.tracking.email' ),
				'show_allow_usage_notice' => masteriyo_bool_to_string( masteriyo_show_usage_tracking_notice() ),
			)
		);

		wp_enqueue_script( 'masteriyo-onboarding' );
		wp_enqueue_script( 'masteriyo-dependencies' );

		ob_start();

		$this->setup_wizard_header();
		$this->setup_wizard_body();
		$this->setup_wizard_footer();

		exit;
	}

	/**
	 * Handle stripe disconnect.
	 *
	 * @return void
	 */
	private function handle_stripe_disconnect() {
		if (
			! isset( $_GET['page'] ) || $this->page_name !== $_GET['page'] ||
			! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'masteriyo_stripe_connect' ) ||
			! isset( $_GET['action'] ) || 'stripe-disconnect' !== sanitize_text_field( wp_unslash( $_GET['action'] ) )
		) {
			return;
		}

		$stripe_setting = new StripeSetting();

		$stripe_setting->set_props(
			[
				'stripe_user_id'       => '',
				'sandbox'              => true,
				'test_secret_key'      => '',
				'test_publishable_key' => '',
				'live_secret_key'      => '',
				'live_publishable_key' => '',
				'enable'               => true,
			]
		);
		$stripe_setting->save();
		wp_safe_redirect( remove_query_arg( array( 'action', 'nonce' ) ) );
		exit;
	}

	/**
	 * Handle stripe data.
	 *
	 * @return void
	 */
	private function handle_stripe_data() {
		if (
			! isset( $_GET['page'] ) || $this->page_name !== $_GET['page'] ||
			! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'masteriyo_stripe_connect' ) ||
			empty( $_GET['state'] )
		) {
			return;
		}

		$decoded_state = base64_decode( sanitize_text_field( wp_unslash( $_GET['state'] ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( ! $decoded_state ) {
			return;
		}
		$state_data = json_decode( $decoded_state, true );
		if ( ! $state_data || ! is_array( $state_data ) ) {
			return;
		}
		if ( array_diff(
			array(
				'stripe_user_id',
				'stripe_publishable_key',
				'access_token',
				'refresh_token',
				'livemode',
			),
			array_keys( $state_data )
		) ) {
			return;
		}
		$is_live_mode = ! empty( $state_data['livemode'] );
		$mode         = $is_live_mode ? 'live' : 'test';
		$data         = array(
			'stripe_user_id'          => $state_data['stripe_user_id'],
			'sandbox'                 => ! $is_live_mode,
			"{$mode}_publishable_key" => $state_data['stripe_publishable_key'],
			"{$mode}_secret_key"      => $state_data['access_token'],
		);
		printf( '<script>var _MASTERIYO_ONBOARDING_STRIPE_DATA_ = %s;</script>', wp_json_encode( $data ) );
		printf( '<script>window.history.replaceState("", {}, "%s")</script>', esc_url_raw( admin_url( 'admin.php?page=masteriyo-onboard&step=payment' ) ) );
	}


	/**
	 * Setup wizard header content.
	 *
	 * @since 1.0.0
	 */
	public function setup_wizard_header() {
		?>
			<!DOCTYPE html>
			<html <?php language_attributes(); ?>>
				<head>
					<meta name="viewport" content="width=device-width"/>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
					<title>
						<?php esc_html_e( 'Masteriyo LMS - Onboarding', 'learning-management-system' ); ?>
					</title>
					<?php wp_print_head_scripts(); ?>
					<?php $this->handle_stripe_data(); ?>
				</head>
		<?php
	}

	/**
	 * Setup wizard body content.
	 *
	 * @since 1.0.0
	 */
	public function setup_wizard_body() {
		?>
			<body class="masteriyo-user-onboarding-wizard notranslate" translate="no">
				<div id="masteriyo-onboarding" class="masteriyo-main-wrap">
				</div>
			</body>
		<?php
	}

	/**
	 * Setup wizard footer content.
	 *
	 * @since 1.0.0
	 */
	public function setup_wizard_footer() {
		if ( function_exists( 'wp_print_media_templates' ) ) {
			wp_print_media_templates();
		}
		wp_print_footer_scripts();
		wp_print_scripts( 'masteriyo-onboarding' );
		?>
		</html>
		<?php
	}
}
