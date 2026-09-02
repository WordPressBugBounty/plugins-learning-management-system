<?php
/**
 * Request class
 *
 * @since 2.5.19
 *
 * @package Masteriyo\packages\Zoom
 */

namespace ThemeGrill\Zoom;

defined( 'ABSPATH' ) || exit;


use ThemeGrill\Zoom\Request;
use ThemeGrill\Zoom\Abstracts\Auth;
use ThemeGrill\Zoom\Contracts\Auth as AuthInterface;

/**
 * Server to server auth (auth)
 *
 * @since 2.5.19
 */
class ServerToServerAuth extends Auth implements AuthInterface {

	/**
	 * Within the method you can set user credential for zoom to abstract Auth class
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * @param string $account_id
	 *
	 * @since 2.5.19
	*/
	public function set_credentials( $client_id = '', $client_secret = '', $account_id = '' ) {

		Auth::set_client_id( $client_id );
		Auth::set_client_secret( $client_secret );
		Auth::set_account_id( $account_id );

	}

	/**
	 * Within the method you can get token from zoom server to access zoom features.
	 *
	 * @since 2.5.19
	 *
	 * @return object
	*/
	public function get_token() {

		$headers = array(
			'Authorization' => 'Basic ' . Auth::get_credentials(),
			'Content-Type'  => 'application/x-www-form-urlencoded',
			'Host'          => 'zoom.us',
		);

		$data = array(
			'grant_type' => 'account_credentials',
			'account_id' => Auth::get_account_id(),
		);

		$request = new Request();

		$request->set_base_url( Auth::get_base_url() );

		return $request->fetch( '', 'POST', $data, $headers );
	}

	public function authorize() {
	}
}
