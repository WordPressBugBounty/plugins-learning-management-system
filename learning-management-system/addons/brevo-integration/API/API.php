<?php
/**
 * Brevo API class.
 *
 * @package Masteriyo\Addons\BrevoIntegration\API
 *
 * @since 1.13.3
 */
namespace Masteriyo\Addons\BrevoIntegration\API;

use Masteriyo\EmailMarketingAndCRM\APIClient;

/**
 * Brevo API class.
 *
 * @since 1.13.3
 */
class API extends APIClient {

	/**
	 * API key.
	 *
	 * @since 1.13.3
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * API endpoint.
	 *
	 * @since 1.13.3
	 *
	 * @var string
	 */
	private $endpoint = MASTERIYO_BREVO_INTEGRATION_BASE_URL;

	/**
	 * Constructor for API.
	 *
	 * @since 1.13.3
	 *
	 * @param string $api_key The API key.
	 */
	public function __construct( string $api_key ) {
		parent::__construct( $this->endpoint );

		$this->api_key = $api_key;

		$this->set_header( 'api-key', $this->api_key );
	}

	/**
	 * Validate the API key.
	 *
	 * @since 1.13.3
	 *
	 * @param string $api_key The API key.
	 *
	 * @return boolean True if the API key is valid, false otherwise.
	 */
	public function validate_api_key( $api_key ) {
		$this->set_header( 'api-key', $api_key );

		$account = $this->get_account();

		if ( is_wp_error( $account ) ) {
			return false;
		}

		return isset( $account['email'] );
	}

	/**
	 * Get account details from the API.
	 *
	 * @since 1.13.3
	 *
	 * @return array|\WP_Error The API response or a WP_Error instance.
	 */
	public function get_account() {
		return $this->get( 'account/' );
	}

	/**
	 * Get lists from the API.
	 *
	 * @since 1.13.3
	 *
	 * @return array|\WP_Error The API response or a WP_Error instance.
	 */
	public function get_lists() {
		return $this->get( 'contacts/lists' );
	}

	/**
	 * Create a new contact.
	 *
	 * @since 1.13.3
	 *
	 * @param array $data The contact data.
	 *
	 * @return array|\WP_Error The API response or a WP_Error instance.
	 */
	public function create_contact( array $data ) {
		return $this->post( 'contacts', $data );
	}

	/**
	 * Update a contact.
	 *
	 * @since 1.13.3
	 *
	 * @param array $data The contact data.
	 *                   Required keys: `email`.
	 *
	 * @return array|\WP_Error The API response or a WP_Error instance.
	 */
	public function update_contact( array $data ) {
		return $this->put( 'contacts/' . $data['email'], $data );
	}

	/**
	 * Get contact by email.
	 *
	 * @since 1.13.3
	 *
	 * @param string $email The contact email.
	 *
	 * @return array|\WP_Error The API response or a WP_Error instance.
	 */
	public function get_contact( string $email ) {
		return $this->get( 'contacts/' . $email );
	}

	/**
	 * Check if a contact exists by email.
	 *
	 * @since 1.13.3
	 *
	 * @param string $email The contact email.
	 *
	 * @return bool True if contact exists, false otherwise.
	 */
	public function is_contact_exists( string $email ) {
		$response = $this->get_contact( $email );

		if ( isset( $response['code'] ) && 'document_not_found' === $response['code'] ) {
			return false;
		}

		return true;
	}
}
