<?php
/**
 * Zoom meeting class.
 *
 * @since 2.5.19
 *
 * @package Masteriyo\packages\Zoom
 */


namespace ThemeGrill\Zoom;

defined( 'ABSPATH' ) || exit;


/**
 * Meeting
 *
 * @since 2.5.19
 */
class Meeting {

	private $request = null;

	protected $base_url = 'https://api.zoom.us/v2/';

	public $data = array(
		'topic'      => '',
		'type'       => 2,
		'start_time' => '',
		'duration'   => '',
		'password'   => '',
	);


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
	 * Create meeting
	 *
	 * @param $path
	 * @param $method
	 * @param $data
	 * @param $headers
	 *
	 * @return object
	 */
	public function create( $path = '', $method = 'POST', $data = array(), $headers = array() ) {
		return $this->request->fetch( $path, $method, $data, $headers );
	}


	/**
	 * Update meeting
	 *
	 * @param string $path
	 * @param string $method
	 * @param array $data
	 * @param array $headers
	 *
	 * @return object
	 */
	public function update( $path = '', $method = 'PATCH', $data = array(), $headers = array(), $id ) {
		$this->request->fetch( $path, $method, $data, $headers, $id );
	}


	/**
	 * Read a meeting
	 *
	 * @param int $id
	 *
	 */
	public function read( $id ) {
		$this->request->fetch( $id );
	}

	/**
	 * Delete meeting
	 *
	 * @param string $path
	 * @param string $method
	 * @param array $data
	 * @param string $id
	 *
	 * @return object
	 */
	public function delete( $path = '', $method = 'DELETE', $headers = array(), $id ) {
		$this->request->fetch( $path, $method, '', $headers, $id );
	}

	/**
	 * List meetings
	 *
	 * @param array $args
	 * @return array
	 */
	public function list( $args ) {
		$this->request->fetch( $args );
	}
}
