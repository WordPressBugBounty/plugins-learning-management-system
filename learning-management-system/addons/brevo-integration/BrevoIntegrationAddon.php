<?php
/**
 * Masteriyo social share addon setup.
 *
 * @package Masteriyo\Addons\BrevoIntegration
 *
 * @since 1.13.3
 */
namespace Masteriyo\Addons\BrevoIntegration;

use Masteriyo\Addons\BrevoIntegration\Controllers\BrevoIntegrationController;
use Masteriyo\Addons\BrevoIntegration\API\API;
use Masteriyo\Constants;
use Masteriyo\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo BrevoIntegration class.
 *
 * @class Masteriyo\Addons\BrevoIntegration
 *
 * @since 1.13.3
 */
class BrevoIntegrationAddon {

	use Singleton;

	/**
	 * Initialize.
	 *
	 * @since 1.13.3
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.13.3
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_checkout_fields', array( $this, 'add_brevo_consent_checkbox_field_for_checkout' ) );
		add_filter( 'masteriyo_registration_form_fields', array( $this, 'add_brevo_consent_checkbox' ) );
		add_action( 'masteriyo_registration_form_before_submit_button', array( $this, 'render_container_for_brevo' ) );
		add_action( 'masteriyo_checkout_form_content', array( $this, 'render_container_for_brevo' ), 125 );
		add_filter( 'masteriyo_get_template', array( $this, 'change_template_for_brevo' ), 10, 2 );
		add_action( 'masteriyo_created_customer', array( $this, 'handle_customer_creation' ), 10, 4 );
		add_filter( 'masteriyo_rest_response_setting_data', array( $this, 'append_setting_in_response' ) );
		add_action( 'masteriyo_new_setting', array( $this, 'save_settings' ) );
		add_filter( 'masteriyo_rest_api_get_rest_namespaces', array( $this, 'register_rest_namespaces' ) );
	}

	/**
	 * Adds "masteriyo_brevo_consent_checkbox" to the checkout form fields.
	 *
	 * @since 1.13.3
	 *
	 * @param array $fields The checkout form fields.
	 * @return array The updated checkout form fields.
	 */
	public function add_brevo_consent_checkbox_field_for_checkout( $fields ) {
		$api_key = BrevoIntegrationSettings::get_api_key();

		if ( empty( $api_key ) || ! masteriyo_string_to_bool( BrevoIntegrationSettings::get( 'is_connected' ) ) ) {
			return $fields;
		}

		$forced_email_subscription = masteriyo_string_to_bool( BrevoIntegrationSettings::get( 'enable_forced_email_subscription' ) );

		if ( $forced_email_subscription ) {
			return $fields;
		}

		$fields['masteriyo_brevo_consent_checkbox'] = array(
			'label'        => __( 'Brevo Consent Checkbox', 'learning-management-system' ),
			'enable'       => true,
			'required'     => false,
			'type'         => 'checkbox',
			'class'        => array( 'form-row-wide' ),
			'autocomplete' => 'no',
			'priority'     => 125,
		);

		return $fields;
	}

	/**
	 * Adds "masteriyo_brevo_consent_checkbox" to the registration form fields.
	 *
	 * @since 1.13.3
	 *
	 * @param array $fields The registration form fields.
	 * @return array The updated registration form fields.
	 */
	public function add_brevo_consent_checkbox( $fields ) {
		$fields[] = 'masteriyo_brevo_consent_checkbox';

		return $fields;
	}

	/**
	 * Renders a container for Brevo email subscription.
	 *
	 * @since 1.13.3
	 */
	public function render_container_for_brevo() {
		$forced_email_subscription = masteriyo_string_to_bool( BrevoIntegrationSettings::get( 'enable_forced_email_subscription' ) );

		if ( $forced_email_subscription ) {
			return;
		}

		$consent_message = BrevoIntegrationSettings::get( 'subscriber_consent_message' );
		$consent_message = empty( $consent_message ) ? 'I would like to receive the newsletters.' : $consent_message;

		/* translators: %s is the consent message */
		$consent_message = sprintf( esc_html__( '%s', 'masteriyo' ), $consent_message ); // phpcs:ignore

		masteriyo_get_template( 'brevo-integration/brevo-integration-container.php', array( 'consent_message' => $consent_message ) );
	}

	/**
	 * Changes the template for Brevo integration specific templates.
	 *
	 * This function changes the template for Brevo integration specific templates.
	 * It changes the template only if it matches the template name in the template map.
	 *
	 * @since 1.13.3
	 *
	 * @param string $template The template path.
	 * @param string $template_name The template name.
	 *
	 * @return string The updated template path or the original template path.
	 */
	public function change_template_for_brevo( $template, $template_name ) {

		$template_map = array(
			'brevo-integration/brevo-integration-container.php' => 'brevo-integration-container.php',
		);

		if ( isset( $template_map[ $template_name ] ) ) {
			$new_template = trailingslashit( Constants::get( 'MASTERIYO_BREVO_INTEGRATION_TEMPLATES' ) ) . $template_map[ $template_name ];

			return file_exists( $new_template ) ? $new_template : $template;
		}

		return $template;
	}

	/**
	 * Handles customer creation in Brevo integration.
	 *
	 * @since 1.13.3
	 *
	 * @param \Masteriyo\Models\User    $user The user object.
	 * @param string                    $password_generated The generated password.
	 * @param string                    $key The key.
	 * @param array                     $args The form arguments.
	 */
	public function handle_customer_creation( $user, $password_generated, $key, $args ) {
		if ( ! $user instanceof \Masteriyo\Models\User ) {
			return;
		}

		$forced_email_subscription = masteriyo_string_to_bool( BrevoIntegrationSettings::get( 'enable_forced_email_subscription' ) );
		$user_consent              = sanitize_text_field( masteriyo_array_get( $args, 'masteriyo_brevo_consent_checkbox' ) );
		$user_consent              = 'on' === $user_consent || '1' === $user_consent ? true : false;

		if ( ! $user_consent && ! $forced_email_subscription ) {
			return;
		}

		$email = $user->get_email();
		$list  = BrevoIntegrationSettings::get( 'list' );

		$list_ids = array();

		if ( ! empty( $list ) ) {
			$list_ids = array( (int) $list );
		}

		$api = new API( BrevoIntegrationSettings::get_api_key() );

		$data = array(
			'email'         => $email,
			'attributes'    => array(
				'FIRSTNAME' => $user->get_first_name(),
				'LASTNAME'  => $user->get_last_name(),
			),
			'listIds'       => $list_ids,
			'updateEnabled' => false,
		);

		$response = $api->create_contact( $data );

		if ( is_wp_error( $response ) ) {
			masteriyo_get_logger()->error( $response->get_error_message() . " For email: {$email}", array( 'source' => 'brevo-integration' ) );
			return;
		}

		masteriyo_get_logger()->info( "Brevo contact created for email: {$email}", array( 'source' => 'brevo-integration' ) );
	}

	/**
	 * Appends Brevo Integration settings to the response data.
	 *
	 * @since 1.13.3
	 *
	 * @param array $data Response data.
	 * @return array Modified response data.
	 */
	public function append_setting_in_response( $data ) {
		$data['integrations']['brevo_integration'] = BrevoIntegrationSettings::all( array( 'api_key' ) );

		return $data;
	}

	/**
	 * Saves Brevo Integration settings.
	 *
	 * @since 1.13.3
	 *
	 * @return void
	 */
	public function save_settings() {
		if ( ! masteriyo_is_rest_api_request() ) {
			return;
		}

		$request = masteriyo_current_http_request();

		if ( ! isset( $request['integrations']['brevo_integration'] ) ) {
			return;
		}

		$settings = masteriyo_array_only( $request['integrations']['brevo_integration'], array_keys( BrevoIntegrationSettings::all() ) );
		$settings = masteriyo_parse_args( $settings, BrevoIntegrationSettings::all() );

		$settings['enable_forced_email_subscription'] = sanitize_text_field( $settings['enable_forced_email_subscription'] ?? false );
		$settings['list']                             = sanitize_text_field( $settings['list'] ?? '' );
		$settings['subscriber_consent_message']       = sanitize_text_field( $settings['subscriber_consent_message'] ?? '' );

		unset( $settings['api_key'] );

		BrevoIntegrationSettings::set_props( $settings );
		BrevoIntegrationSettings::save();
	}


	/**
	 * Registers the Brevo Integration namespace to the REST API.
	 *
	 * @since 1.13.3
	 *
	 * @param array $namespaces List of namespaces and their controllers.
	 *
	 * @return array Modified list of namespaces and their controllers.
	 */
	public function register_rest_namespaces( $namespaces ) {
		$namespaces['masteriyo/v1']['brevo-integration'] = BrevoIntegrationController::class;

		return $namespaces;
	}
}
