<?php
/**
 * REST API Utilities Controller
 *
 * Handles requests to the utilities endpoint, specifically for managing redundant enrollments.
 *
 * @category API
 * @package Masteriyo\RestApi
 * @since 1.9.0 [Free]
 */

namespace Masteriyo\RestApi\Controllers\Version1;

use Masteriyo\Helper\Permission;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;
use WP_Filesystem_Base;

defined( 'ABSPATH' ) || exit;

/**
 * REST API Utilities Controller Class.
 *
 * @package Masteriyo\RestApi
 */
class UtilitiesController extends WP_REST_Controller {
	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'utilities';

	/**
	 * Permission class instance.
	 *
	 * @since 1.9.0 [Free]
	 * @var Permission
	 */
	protected $permission;

	/**
	 * Constructor.
	 *
	 * Sets up the utilities controller.
	 *
	 * @since 1.9.0 [Free]
	 * @param Permission|null $permission The permission handler instance.
	 */
	public function __construct( ?Permission $permission = null ) {
		$this->permission = $permission;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.9.0 [Free]
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/refresh-course-access',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'refresh_course_access' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
			)
		);

		/**
		 * Register the route for forcing a plugin update.
		 *
		 * @since 2.14.0
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/plugin-update',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'force_plugin_update' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			)
		);

		/**
		 * Register the route for checking a plugin update.
		 *
		 * @since 2.14.0
		 */
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/plugin-check-update',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'check_plugin_update' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			)
		);
	}

	/**
	 * Checks if the current user has permissions to perform deletion.
	 *
	 * @since 1.9.0 [Free]
	 * @param WP_REST_Request $request The request.
	 * @return true|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new WP_Error( 'masteriyo_null_permission', __( 'Sorry, the permission object for this resource is null.', 'learning-management-system' ) );
		}

		if ( ! masteriyo_is_current_user_admin() ) {
			return new \WP_Error(
				'masteriyo_permission_denied',
				__( 'Sorry, you are not allowed to delete this.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Checks if the current user has permissions to perform update.
	 *
	 * @since 2.14.0
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return true|WP_Error True if the request has access, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new WP_Error( 'masteriyo_null_permission', __( 'Sorry, the permission object for this resource is null.', 'learning-management-system' ) );
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return new \WP_Error(
				'masteriyo_permission_denied',
				__( 'Sorry, you are not allowed to update plugins for this site.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * Refreshes course access by deleting redundant enrollments and updating the status of invalid users.
	 *
	 * @since 1.9.3 [Free]
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_Error|WP_REST_Response WP_Error on failure, WP_REST_Response on success.
	 */
	public function refresh_course_access( $request ) {
		$this->delete_redundant_enrollments();

		$this->update_invalid_users_status();

		$this->delete_redundant_lesson_activities();

		return rest_ensure_response(
			array(
				'message' => __( 'Course access refreshed successfully.', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Deletes duplicate enrollments while keeping an active status enrollment if possible.
	 *
	 * @since 1.9.0 [Free]
	 *
	 * @return int|WP_Error Number of deleted enrollments on success, WP_Error on failure.
	 */
	private function delete_redundant_enrollments() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'masteriyo_user_items';

			$sub_query = "SELECT
      user_id,
      item_id,
      COALESCE(MAX(CASE WHEN status = 'active' THEN id END), MAX(id)) as id_to_keep
      FROM
      {$table_name}
      GROUP BY
      user_id, item_id
      HAVING
      COUNT(*) > 1";

		$sub_query = $wpdb->prepare( $sub_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$delete_query = "DELETE t1
                    FROM {$table_name} t1
                    INNER JOIN ({$sub_query}) t2
                    ON t1.user_id = t2.user_id AND t1.item_id = t2.item_id
                    WHERE t1.id <> t2.id_to_keep";

		$result = $wpdb->query( $delete_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			masteriyo_get_logger()->error( 'Failed to delete redundant enrollments.', array( 'source' => 'tools-utility' ) );

			return new WP_Error( 'masteriyo_deletion_failed', __( 'Failed to delete redundant enrollments.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		masteriyo_get_logger()->info( 'Deleted redundant enrollments.', array( 'source' => 'tools-utility' ) );

		return $result;
	}

	/**
	 * Updates the status to 'inactive' for users who do not exist.
	 *
	 * @since 1.9.3 [Free]
	 *
	 * @return int|WP_Error Number of users whose status was updated on success, WP_Error on failure.
	 */
	private function update_invalid_users_status() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'masteriyo_user_items';

		$update_invalid_users_query = "UPDATE {$table_name}
		SET status = 'inactive'
		WHERE user_id NOT IN (SELECT ID FROM {$wpdb->users})";

		$result = $wpdb->query( $update_invalid_users_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			masteriyo_get_logger()->error( 'Failed to update status for invalid users.', array( 'source' => 'tools-utility' ) );

			return new WP_Error( 'masteriyo_update_invalid_users_failed', __( 'Failed to update status for invalid users.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		masteriyo_get_logger()->info( 'Updated status for invalid users.', array( 'source' => 'tools-utility' ) );

		return $result;
	}

	/**
	 * Deletes redundant user activities for a lesson.
	 *
	 * @since 1.14.3 [Free]
	 *
	 * @return int|WP_Error Number of rows affected on success, WP_Error on failure.
	 */
	private function delete_redundant_lesson_activities() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'masteriyo_user_activities';

		$query = "
					DELETE FROM {$table_name}
					WHERE id IN (
							SELECT id FROM (
									SELECT id,
												ROW_NUMBER() OVER (
														PARTITION BY user_id, item_id, parent_id, activity_type
														ORDER BY
																CASE WHEN activity_status = 'completed' THEN 0 ELSE 1 END,
																id DESC
												) as row_num
									FROM {$table_name}
									WHERE activity_type = 'lesson'
							) as ranked
							WHERE row_num > 1
					)
				";

		$result = $wpdb->query( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( false === $result ) {
			masteriyo_get_logger()->error( 'Failed to delete redundant user activities for lesson.', array( 'source' => 'tools-utility' ) );

			return new WP_Error( 'masteriyo_delete_duplicate_user_lesson_activity', __( 'Failed to delete redundant user activities for lesson.', 'learning-management-system' ) );
		}

		masteriyo_get_logger()->info( 'Deleted redundant user activities for lesson.', array( 'source' => 'tools-utility' ) );

		return $result;
	}

	/**
	 * Force a plugin update.
	 *
	 * @since 2.14.0
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_Error|WP_REST_Response WP_Error on failure, WP_REST_Response on success.
	 */
	public function force_plugin_update( $request ) {
		$plugin_slug = plugin_basename( MASTERIYO_PLUGIN_FILE );
		$cache_key   = str_replace( '-', '_', $plugin_slug ) . '_updater';

		delete_transient( $cache_key );

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';

		if ( ! class_exists( 'Plugin_Upgrader' ) || ! class_exists( 'WP_Ajax_Upgrader_Skin' ) ) {
			return new WP_Error( 'masteriyo_update_failed', __( 'Required classes for plugin update not found.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->bulk_upgrade( array( $plugin_slug ) );

		if ( is_wp_error( $skin->result ) ) {
			return new WP_Error(
				'masteriyo_update_failed',
				$skin->result->get_error_message(),
				array( 'status' => 500 )
			);
		} elseif ( $skin->get_errors()->has_errors() ) {
			return new WP_Error(
				'masteriyo_update_failed',
				$skin->get_error_messages(),
				array( 'status' => 500 )
			);
		} elseif ( is_array( $result ) && isset( $result[ $plugin_slug ] ) ) {
			$error_obj = $result[ $plugin_slug ];

			if ( is_wp_error( $error_obj ) ) {
				return new WP_Error(
					'masteriyo_update_failed',
					$error_obj->get_error_message(),
					array( 'status' => 500 )
				);
			}

			if ( true === $result[ $plugin_slug ] ) {
					return new WP_Error(
						'masteriyo_update_failed',
						$upgrader->strings['up_to_date'],
						array( 'status' => 500 )
					);
			}

			if ( false === $result[ $plugin_slug ] ) {
				return new WP_Error(
					'masteriyo_update_failed',
					$upgrader->strings['process_failed'],
					array( 'status' => 500 )
				);
			}

			$plugin_data = get_plugins( '/' . $result[ $plugin_slug ]['destination_name'] );
			$plugin_data = reset( $plugin_data );

			return rest_ensure_response(
				array(
					/* translators: %s: Plugin version. */
					'message'     => sprintf( __( 'Plugin updated. New version: %s', 'learning-management-system' ), $plugin_data['Version'] ),
					'new_version' => $plugin_data['Version'],
				)
			);
		} elseif ( false === $result ) {
			global $wp_filesystem;

			$error_message = __( 'Unable to connect to the filesystem. Please confirm your credentials.', 'learning-management-system' );

			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->has_errors() ) {
					$error_message = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			return new WP_Error(
				'masteriyo_update_failed',
				$error_message,
				array( 'status' => 500 )
			);
		}

		return new WP_Error( 'masteriyo_update_failed', __( 'Failed to update plugin.', 'learning-management-system' ), array( 'status' => 500 ) );
	}

	/**
	 * Check if a plugin update is available.
	 *
	 * @since 2.14.0
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_Error|WP_REST_Response WP_Error on failure, WP_REST_Response on success.
	 */
	public function check_plugin_update( $request ) {
		/**
		 * Filters the result of the plugin update check.
		 *
		 * The updater is pro's — it queries pro's own release API, which the free
		 * product has no business calling and no class to call it with. Free updates
		 * through wordpress.org, which WordPress checks by itself, so core asks here
		 * rather than checking. Pro answers from `pro/bootstrap.php`.
		 *
		 * Unanswered, the endpoint reports the installed version with no update
		 * available. That is the correct answer for a product core cannot check, and
		 * it is what the button on Masteriyo → Tools shows a free admin.
		 *
		 * @param array|\WP_Error|null $result  The check result, or null for none.
		 * @param \WP_REST_Request     $request The request.
		 */
		$result = apply_filters( 'masteriyo_check_plugin_update', null, $request );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( is_array( $result ) ) {
			return rest_ensure_response( $result );
		}

		return rest_ensure_response(
			array(
				'current_version' => MASTERIYO_VERSION,
				'message'         => __( 'No updates available. You are using the latest version.', 'learning-management-system' ),
			)
		);
	}
}
