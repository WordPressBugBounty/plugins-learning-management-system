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

use Masteriyo\Addons\Stripe\Client\StripeClient;
use Masteriyo\Addons\Stripe\Setting as StripeSetting;
use Masteriyo\AddonsFramework\Addons;
use Masteriyo\Constants;

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
			sprintf(
				/* translators: %s: the product's name */
				__( '%s Onboard', 'learning-management-system' ),
				masteriyo_get_plugin_name()
			),
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

		$this->stripe_connect();

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
			// plugins_url(), not plugin_dir_url() . '/…': the latter doubles the slash, and
			// WordPress derives the translation file name from this path, so the handle
			// would look for a JSON that can never exist.
			masteriyo_is_production() ? plugins_url( 'assets/js/build/masteriyo-gettingStarted.js', MASTERIYO_PLUGIN_FILE ) : 'http://localhost:3000/dist/gettingStarted.js',
			$onboard_dependencies['dependencies'],
			$onboard_dependencies['version'],
			true
		);

		if ( masteriyo_is_production() ) {
			wp_register_script(
				'masteriyo-dependencies',
				plugins_url( 'assets/js/build/masteriyo-dependencies.js', MASTERIYO_PLUGIN_FILE ),
				$onboard_dependencies['dependencies'],
				$onboard_dependencies['version'],
				true
			);
		}

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'masteriyo-onboarding', 'learning-management-system', Constants::get( 'MASTERIYO_LANGUAGES' ) );
		}

		// Add localization vars.
		wp_localize_script(
			'masteriyo-onboarding',
			'_MASTERIYO_',
			/**
			 * Filters the onboarding wizard's localized data.
			 *
			 * Pro's white-label addon adds the configured brand title and
			 * logo here, so the wizard header can drop the canonical
			 * Masteriyo branding.
			 *
			 * @param array $data The wizard's localized data.
			 */
			apply_filters(
				'masteriyo_onboarding_localized_data',
				array(
					'rootApiUrl'              => esc_url_raw( untrailingslashit( rest_url() ) ),
					'nonce'                   => wp_create_nonce( 'wp_rest' ),
					'stripe_nonce'            => wp_create_nonce( 'masteriyo_stripe_nonce' ),
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
					'courseListURL'           => esc_url( admin_url( '/admin.php?page=masteriyo#/courses' ) ),
					'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
					'ajaxNonce'               => wp_create_nonce( 'masteriyo_allow_usage_notice_nonce' ),
					'is_stripe_addon_active'  => masteriyo_bool_to_string( ( new Addons() )->is_active( 'stripe' ) ),
					'allowUsage'              => masteriyo_bool_to_string( masteriyo_get_setting( 'advance.tracking.allow_usage' ) ),
					'show_allow_usage_notice' => masteriyo_bool_to_string( masteriyo_show_usage_tracking_notice() ),
				)
			)
		);
		wp_enqueue_media();
		wp_enqueue_script( 'masteriyo-onboarding' );
		wp_enqueue_script( 'masteriyo-dependencies' );

		ob_start();

		$this->setup_wizard_header();
		$this->setup_wizard_body();
		$this->setup_wizard_footer();

		exit;
	}

	/**
	 * Stripe connect.
	 *
	 * Handle stripe connect process.
	 *
	 * @return void
	 */
	private function stripe_connect() {
		if (
		! isset( $_GET['page'] ) ||
		$this->page_name !== $_GET['page'] ||
		! isset( $_GET['nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'masteriyo_stripe_nonce' ) ||
		! current_user_can( 'manage_options' )
		) {
			return;
		}

		$error = false;

		if ( isset( $_GET['return_url'] ) ) { // Account link request.
			$return_url = sanitize_url( wp_unslash( $_GET['return_url'] ) );

			if ( ! $error ) {
				$response = StripeClient::create()->get_account_link(
					array(
						'mode'       => StripeSetting::is_sandbox_enable() ? 'test' : 'live',
						'return_url' => $return_url,
						'nonce'      => sanitize_text_field( wp_unslash( $_GET['nonce'] ) ),
					)
				);

				if ( is_wp_error( $response ) || empty( $response['data'] ) ) {
					$error = true;
				} else {
					wp_redirect( $response['data'] );
					exit;
				}
			}
		} elseif ( isset( $_GET['accountId'], $_GET['mode'] ) ) { // Account save.
			$account_id = sanitize_text_field( wp_unslash( $_GET['accountId'] ) );
			$mode       = sanitize_text_field( wp_unslash( $_GET['mode'] ) );

			if ( empty( $account_id ) ||
			! preg_match( '/^acct_[a-zA-Z0-9]+$/', $account_id ) ||
			! in_array( $mode, array( 'test', 'live' ), true ) ) { // Validate account id and mode.
				$error = true;
			}

			if ( ! $error ) {
				// Setting holds its data statically and save() writes the whole array back, so
				// read first or every key this block does not set is reset to its default.
				StripeSetting::read();

				$stripe_setting = new StripeSetting();
				$stripe_setting->set_props(
					array(
						'stripe_user_id' => $account_id,
						'sandbox'        => 'test' === $mode,
						'enable'         => true,
						'use_platform'   => true,
					)
				);

				$stripe_setting->save();
			}
		} else { // Reset.
			// See above: read first, so a reset clears the connect credentials without also
			// discarding the webhook secret and the gateway's own copy.
			StripeSetting::read();

			$stripe_setting = new StripeSetting();
			$stripe_setting->set_props(
				array(
					'stripe_user_id'       => '',
					'sandbox'              => true,
					'test_secret_key'      => '',
					'test_publishable_key' => '',
					'live_secret_key'      => '',
					'live_publishable_key' => '',
					'enable'               => true,
				)
			);

			$stripe_setting->save();
		}

		$base_url = remove_query_arg( array( 'action', 'nonce', 'accountId', 'mode', 'return_url', 'step' ) );
		$hash     = $error ? '#/?step=setup&builder=gutenberg&stripe_error=true' : '#/?step=setup&builder=gutenberg';
		wp_safe_redirect( $base_url . $hash );
		exit;
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
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: the product's name */
								__( '%s LMS - Onboarding', 'learning-management-system' ),
								masteriyo_get_plugin_name()
							)
						);
						?>
					</title>
					<?php wp_print_head_scripts(); ?>
					<?php
					/*
					 * The wizard prints its own document, so nothing enqueues styles for
					 * it — the app is CSS-in-JS. The wp.media modal is not, and without
					 * these it renders raw. `.screen-reader-text` lives in wp-admin's
					 * common.css, which this page must not pull in wholesale, so the one
					 * rule the modal needs is restated here.
					 */
					wp_print_styles( array( 'media-views' ) );
					?>
					<style>
						.masteriyo-user-onboarding-wizard .screen-reader-text {
							border: 0;
							clip-path: inset( 50% );
							height: 1px;
							margin: -1px;
							overflow: hidden;
							padding: 0;
							position: absolute;
							width: 1px;
							word-wrap: normal !important;
						}
					</style>
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
