<?php

/**
 * LMSMigrationController Class.
 *
 * Handles the migration of data from other WordPress LMS plugins to Masteriyo.
 *
 * @since 1.8.0
 * @package Masteriyo\Addons\MigrationTool\Controllers
 */

namespace Masteriyo\Addons\MigrationTool\Controllers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\Helper;
use Masteriyo\Addons\MigrationTool\TutorLMS;
use Masteriyo\Helper\Permission;
use Masteriyo\PostType\PostType;
use Masteriyo\RestApi\Controllers\Version1\RestController;
use WP_Error;
/**
 * LMSMigrationController class.
 *
 * This class provides REST endpoints for migrating data from other LMS plugins to Masteriyo.
 */
class LMSMigrationController extends RestController {

	/**
	 * Endpoint namespace.
	 *
	 * @since 1.8.0
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.8.0
	 *
	 * @var string
	 */
	protected $rest_base = 'migrations';

	/**
	 * Permission class.
	 *
	 * @since 1.8.0
	 *
	 * @var \Masteriyo\Helper\Permission;
	 */
	protected $permission = null;

	/**
	 * Constructor.
	 *
	 * @since 1.8.0
	 *
	 * @param Permission $permission
	 */
	public function __construct( Permission $permission ) {
		$this->permission = $permission;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'migrate' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::CREATABLE ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/lms',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_other_LMSs' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Checks if the user has permission to import items.
	 *
	 * @since 1.8.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$instructor = masteriyo_get_current_instructor();
		if ( $instructor && ! $instructor->is_active() ) {
			return new \WP_Error(
				'masteriyo_rest_user_not_approved',
				__( 'Sorry, you are not approved by the manager.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->permission->rest_check_post_permissions( PostType::COURSE, 'create' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to import courses.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Handles the migration of LMS data based on the request.
	 *
	 * @since 1.8.0
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_Error|WP_REST_Response The response or WP_Error on failure.
	 */
	public function migrate( $request ) {
		$lms_name      = sanitize_text_field( $request->get_param( 'lms_name' ) );
		$type          = sanitize_text_field( $request->get_param( 'type' ) );
		$remaining_ids = $request->get_param( 'ids' );

		if ( is_array( $remaining_ids ) ) {
			$remaining_ids = array_map( 'intval', $remaining_ids );
		} else {
			$remaining_ids = array();
		}

		if ( empty( $lms_name ) || ! array_key_exists( $lms_name, $this->get_other_LMS_list() ) ) {
			return new WP_Error( 'migration_invalid_parameters', 'Please select a valid LMS.', array( 'status' => 400 ) );
		}

		wp_raise_memory_limit( 'admin' );

		if ( empty( $type ) ) {
			$type = 'courses';
			update_option( 'masteriyo_remaining_migrated_items', 'not_started' );
		}
		switch ( $lms_name ) {
			case 'learnpress':
				return $this->migrate_lms_data( $type, 'LearnPress', $remaining_ids );
			case 'sfwd-lms':
				return $this->migrate_lms_data( $type, 'LearnDash', $remaining_ids );
			case 'lifterlms':
				return $this->migrate_lms_data( $type, 'LifterLMS', $remaining_ids );
			case 'masterstudy':
				return $this->migrate_lms_data( $type, 'MasterStudy', $remaining_ids );
			case 'tutor':
				return $this->migrate_tutor_data( $type, $remaining_ids );
			default:
				return new WP_Error( 'migration_not_supported', 'Migration for the selected LMS is not supported.', array( 'status' => 400 ) );
		}
	}

	/**
	 * Retrieves a list of other LMS plugins available for migration.
	 *
	 * @since 1.8.0
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response object.
	 */
	public function get_other_LMSs( $request ) {
		$data = array();

		foreach ( $this->get_other_LMS_list() as  $key => $plugin ) {
			if ( in_array( $plugin['name'], get_option( 'active_plugins', array() ), true ) ) {
				$data[] = array(
					'name'  => $key,
					'label' => $plugin['label'],
				);
			}
		}

		return rest_ensure_response( array( 'data' => $data ) );
	}

	/**
	 * Retrieves other LMS plugins data.
	 *
	 * @since 1.8.0
	 *
	 * @return array An array of other LMS plugins data.
	 */
	private function get_other_LMS_list() {
		$data = array(
			'learnpress'  => array(
				'label' => 'LearnPress',
				'name'  => 'learnpress/learnpress.php',
			),
			'sfwd-lms'    =>
			array(
				'label' => 'LearnDash',
				'name'  => 'sfwd-lms/sfwd_lms.php',
			),
			'tutor'       =>
			array(
				'label' => 'Tutor',
				'name'  => 'tutor/tutor.php',
			),
			'lifterlms'   => array(
				'label' => 'LifterLMS',
				'name'  => 'lifterlms/lifterlms.php',
			),
			'masterstudy' => array(
				'label' => 'MasterStudy',
				'name'  => 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php',
			),
		);

		return $data;
	}


	/**
	 * Handles the migration of other LMS's courses, orders, reviews, etc.
	 *
	 * @since 1.16.0
	 *
	 * @param string $type The type of migration to be performed. Accepted values are 'courses', 'orders', etc.
	 * @param string $lms The name of the LMS plugin to be migrated. Accepted values are 'LearnPress', 'LearnDash', 'LifterLMS', etc.
	 * @param array $remaining_ids Array of remaining IDs to be migrated. Default is an empty array.
	 *
	 * @return WP_REST_Response Returns WP_REST_Response.
	 */
	private function migrate_lms_data( $type, $lms, $remaining_ids = array() ) {
		$migration_map = array(
			'LearnPress'  => array(
				'courses' => 'Masteriyo\Addons\MigrationTool\LearnPress::migrate_lp_courses',
				'orders'  => 'Masteriyo\Addons\MigrationTool\LearnPress::migrate_lp_orders',
				'reviews' => 'Masteriyo\Addons\MigrationTool\LearnPress::migrate_lp_reviews',
			),
			'LearnDash'   => array(
				'courses' => 'Masteriyo\Addons\MigrationTool\LearnDash::migrate_ld_courses',
				'orders'  => 'Masteriyo\Addons\MigrationTool\LearnDash::migrate_ld_orders',
			),
			'LifterLMS'   => array(
				'courses' => 'Masteriyo\Addons\MigrationTool\LifterLMS::migrate_lf_courses',
				'orders'  => 'Masteriyo\Addons\MigrationTool\LifterLMS::migrate_lf_orders',
			),
			'MasterStudy' => array(
				'courses' => 'Masteriyo\Addons\MigrationTool\MasterStudy::migrate_ms_courses',
				'orders'  => 'Masteriyo\Addons\MigrationTool\MasterStudy::migrate_ms_orders',
				'reviews' => 'Masteriyo\Addons\MigrationTool\MasterStudy::migrate_ms_reviews',
			),
		);

		if ( ! isset( $migration_map[ $lms ][ $type ] ) ) {
			return rest_ensure_response(
				array(
					'message' => __( 'Invalid migration type specified.', 'learning-management-system' ),
					'status'  => 400,
				)
			);
		}

		$migration_function = $migration_map[ $lms ][ $type ];
		$result             = is_callable( $migration_function ) ? call_user_func( $migration_function, $remaining_ids ) : false;

		if ( $result ) {
			return rest_ensure_response( $result );
		}

		foreach ( $migration_map[ $lms ] as $fallback_type => $fallback_function ) {
			if ( $fallback_type !== $type ) {
				$result = is_callable( $fallback_function ) ? call_user_func( $fallback_function, $remaining_ids ) : false;
				if ( $result ) {
					return rest_ensure_response( $result );
				}
			}
		}

		Helper::delete_remaining_migrated_items();

		/* translators: %s is the LMS name */
		return rest_ensure_response( array( 'message' => sprintf( __( 'All the %s data migrated successfully.', 'learning-management-system' ), $lms ) ) );
	}

	/**
	 * Migrates TutorLMS data.
	 *
	 * @since 1.13.0
	 *
	 * @param string $type The type of data to migrate.
	 * @param array $remaining_ids The IDs of items to migrate.
	 * @return WP_REST_Response The response object.
	 */
	private function migrate_tutor_data( $type, $remaining_ids ) {
		$migration_functions = array(
			'courses'             => 'migrate_tutor_courses',
			'orders'              => 'migrate_tutor_order',
			'reviews'             => 'migrate_tutor_reviews',
			'announcement'        => 'get_and_update_tutor_announcements',
			'questions_n_answers' => 'update_questions_and_answers_from_tutor',
		// Uncomment if needed in the future:
		// 'quiz_attempts'        => 'update_quiz_attempts_from_tutor_to_masteriyo'
		);

		$next_type = null;

		if ( isset( $migration_functions[ $type ] ) ) {
			$migration_function = $migration_functions[ $type ];
			$result             = TutorLMS::$migration_function( $remaining_ids );

			if ( $result ) {
				return $result;
			} else {
				$next_type = $this->get_next_migration_type( $type );
			}
		}

		if ( $next_type ) {
			return $this->migrate_tutor_data( $next_type, $remaining_ids );
		}

		return rest_ensure_response( array( 'message' => __( 'All the Tutor LMS data migrated successfully.', 'learning-management-system' ) ) );
	}

	private function get_next_migration_type( $current_type ) {
		$migration_order = array(
			'courses',
			'orders',
			'reviews',
			'announcement',
			'questions_n_answers',
		// Uncomment if needed in the future:
		// 'quiz_attempts'
		);

		$current_index = array_search( $current_type, $migration_order );

		if ( false !== $current_index && isset( $migration_order[ $current_index + 1 ] ) ) {
			return $migration_order[ $current_index + 1 ];
		}

		return null;
	}
}
