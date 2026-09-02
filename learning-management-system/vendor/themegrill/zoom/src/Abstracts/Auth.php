<?php
/**
 * Abstract auth class
 *
 * @since 2.5.19
 *
 * @package Masteriyo\packages\Zoom
 */

namespace ThemeGrill\Zoom\Abstracts;

defined( 'ABSPATH' ) || exit;


use ThemeGrill\Zoom\Request;
/**
 * Abstract Auth
 *
 * @since 2.5.19
 */
abstract class Auth {

	/**
	 * Setting base url
	 *
	 * @var string
	*/
	protected $base_url = 'https://zoom.us/oauth/token';

	/**
	 * Setting client id
	 *
	 * @var string
	*/
	protected $client_id = '';

	/**
	 * Setting client secret
	 *
	 * @var string
	*/
	protected $client_secret = '';

	/**
	 * Setting account id
	 *
	 * @var string
	*/
	protected $account_id = '';

	/**
	 * Setting request
	 *
	 * @var string
	*/
	protected $request = '';

	/**
	 * Constructor.
	 *
	 * @since 2.5.19
	 *
	 * @param \Masteriyo\packages\Zoom $request
	*/
	public function __construct( Request $request ) {
		$this->request = $request;
		$this->request->set_base_url( $this->base_url );
	}

	/**
	 * Returns the base url of zoom.
	 *
	 * @since  2.5.19
	 *
	 * @return string
	*/
	public function get_base_url() {
		return $this->base_url;
	}

	/**
	 * Returns the client id.
	 *
	 * @since  2.5.19
	 *
	 * @return string
	*/
	public function get_client_id() {
		return $this->client_id;
	}

	/**
	 * Returns the client secret.
	 *
	 * @since  2.5.19
	 *
	 * @return string
	*/
	public function get_client_secret() {
		return $this->client_secret;
	}

	/**
	 * Returns the account id.
	 *
	 * @since  2.5.19
	 *
	 * @return string
	*/
	public function get_account_id() {
		return $this->account_id;
	}

	/**
	 * Returns the encoded code obtained by encrypting client id and client secret.
	 *
	 * @since  2.5.19
	 *
	 * @return string
	*/
	public function get_credentials() {
		return base64_encode( $this->get_client_id() . ':' . $this->get_client_secret() );
	}

	/**
	 * Set client id.
	 *
	 * @since 2.5.19
	 *
	 * @param string $client_id
	*/
	public function set_client_id( $client_id ) {
		$this->client_id = $client_id;
	}

	/**
	 * Set client secret.
	 *
	 * @since 2.5.19
	 *
	 * @param string $client_secret
	*/
	public function set_client_secret( $client_secret ) {
		$this->client_secret = $client_secret;
	}

	/**
	 * Set account id.
	 *
	 * @since 2.5.19
	 *
	 * @param string $account_id
	*/
	public function set_account_id( $account_id ) {
		$this->account_id = $account_id;
	}
}
