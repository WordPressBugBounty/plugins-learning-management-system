<?php
/**
 * Masteriyo setting class.
 *
 * @package Masteriyo\Google Meet
 *
 * @since 1.11.0
 */

namespace Masteriyo\Addons\GoogleMeet\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Masteriyo Setting class.
 *
 * @class Masteriyo\Setting
 */

class GoogleMeetSetting {
	/**
	 * Setting option name.
	 *
	 * @var string
	 */
	private $name = 'masteriyo_google_meet_settings';

	/**
	 * Setting User Id.
	 *
	 * @var integer
	 */
	public $user_id = 0;

	/**
	 * Setting data.
	 *
	 * @since 1.11.0
	 *
	 * @var array
	 */
	private $data = array(
		'client_id'                   => '',
		'project_id'                  => '',
		'auth_uri'                    => '',
		'token_uri'                   => '',
		'auth_provider_x509_cert_url' => '',
		'client_secret'               => '',
		'redirect_uris'               => array(),
		'access_token'                => '',
	);

	/**
	 * Constructor.
	 *
	 * @since 1.11.0
	 */
	public function __construct() {
		$this->read();
	}

	/**
	 * Return data.
	 *
	 * @since 1.11.0
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Store client settings.
	 *
	 * @since 1.11.0
	 *
	 * @param Model         $setting  Object object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating If is creating a new object.
	 */
	public function save() {
		update_user_meta( get_current_user_id(), $this->name, $this->get_data() );
	}

	/**
	 * Store client settings.
	 *
	 * @since 1.11.0
	 *
	 * @param Model         $setting  Object object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating If is creating a new object.
	 */
	public function delete() {
		delete_user_meta( get_current_user_id(), $this->name );
	}


	/**
	 * Read the settings from database.
	 *
	 * @since 1.11.0
	 *
	 */
	public function read() {
		$data       = get_user_meta( get_current_user_id(), $this->name, true );
		$this->data = wp_parse_args( $data, $this->data );
	}

	/**
	 * Return setting value.
	 *
	 * @since 1.11.0
	 * @param string $key Setting key.
	 * @param string $default Setting default value.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return masteriyo_array_get( $this->get_data(), $key, $default );
	}

	/**
	 * Save setting value.
	 *
	 * @since 1.11.0
	 *
	 * @param string $key Setting key.
	 * @param mixed $value Setting default.
	 */
	public function set( $key, $value ) {
		masteriyo_array_set( $this->data, $key, $value );
	}
}
