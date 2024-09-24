<?php
/**
 * Masteriyo SureCart Integration setup.
 *
 * @package Masteriyo\SureCartIntegration
 *
 * @since 1.12.0
 */

namespace Masteriyo\Addons\SureCartIntegration;

use Masteriyo\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo SureCartIntegration addon class.
 *
 * @class Masteriyo\Addons\SureCartIntegration
 *
 * @since 1.12.0
 */

class SureCartIntegrationAddon {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.12.0
	 *
	 * @var \Masteriyo\Addons\SureCartIntegration\SureCartIntegrationAddon|null
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.12.0
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @since 1.12.0
	 *
	 * @return \Masteriyo\Addons\SureCartIntegration\SureCartIntegrationAddon Instance.
	 */
	final public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 1.12.0
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @since 1.12.0
	 */
	public function __wakeup() {}

	/**
	 * Initialize module.
	 *
	 * @since 1.12.0
	 */
	public function init() {
		$this->init_hooks();
	}
	/**
	 * Initialize hooks.
	 *
	 * @since 1.12.0
	 */
	public function init_hooks() {
		add_action( 'init', array( $this, 'create_integration' ) );
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'profile_update', array( $this, 'add_student_role_to_surecart_customer' ) );
		add_action( 'user_register', array( $this, 'add_student_role_to_surecart_customer' ) );

	}

	/**
	 * Initialize the SureCart integration class.
	 *
	 * @since 1.12.0
	 *
	 * @return string Plugin path.
	 */
	public function create_integration() {
			( new SureCartService() )->bootstrap();
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.12.0
	 *
	 * @param array $scripts Array of scripts.
	 * @return array
	 */
	public function enqueue_scripts( $scripts ) {

		return masteriyo_parse_args(
			$scripts,
			array(
				'masteriyo-surecart-courses-course-archive' => array(
					'src'      => plugin_dir_url( Constants::get( 'MASTERIYO_SURECART_INTEGRATION_ADDON_FILE' ) ) . '/frontend/single-course.js',
					'context'  => 'public',
					'callback' => function() {
						return true;
					},
					'deps'     => array( 'jquery' ),
				),
			)
		);
	}

	/**
	 * Add student role to SureCart customer.
	 *
	 * @since 1.13.0
	 *
	 * @param int $user_id User ID.
	 */
	public function add_student_role_to_surecart_customer( $user_id ) {
		remove_action( 'profile_update', array( $this, 'add_student_role_to_surecart_customer' ) );
		remove_action( 'user_register', array( $this, 'add_student_role_to_surecart_customer' ) );

		try {
			$user  = masteriyo( 'user' );
			$store = masteriyo( 'user.store' );

			$user->set_id( $user_id );
			$store->read( $user );

			if ( $user->has_role( 'sc_customer' ) && ! $user->has_role( 'masteriyo_student' ) ) {
				$user->add_role( 'masteriyo_student' );
				$user->save();
			}
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		}

		add_action( 'profile_update', array( $this, 'add_student_role_to_surecart_customer' ) );
		add_action( 'user_register', array( $this, 'add_student_role_to_surecart_customer' ) );
	}

}
