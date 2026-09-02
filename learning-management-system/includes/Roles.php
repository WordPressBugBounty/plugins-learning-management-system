<?php
/**
 * Roles class.
 *
 * @since 1.0.0
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;


class Roles {

	/**
	 * Manager role slug.
	 *
	 * @since 1.5.37
	 *
	 * @var string
	 */
	const MANAGER = 'masteriyo_manager';

	/**
	 * Instructor role slug.
	 *
	 * @since 1.5.37
	 *
	 * @var string
	 */
	const INSTRUCTOR = 'masteriyo_instructor';

	/**
	 * Student role slug.
	 *
	 * @since 1.5.37
	 *
	 * @var string
	 */
	const STUDENT = 'masteriyo_student';

	/**
	 * Admin role slug.
	 *
	 * @since 1.5.37
	 *
	 * @var string
	 */
	const ADMIN = 'administrator';

	/**
	 * Editor role slug.
	 *
	 * @since 1.5.37
	 *
	 * @var string
	 */
	const EDITOR = 'editor';

	/**
	 * Author role slug.
	 *
	 * @since 1.5.37
	 *
	 * @var string
	 */
	const AUTHOR = 'author';

	/**
	 * Contributor role slug.
	 *
	 * @since 1.5.37
	 *
	 * @var string
	 */
	const CONTRIBUTOR = 'contributor';

	/**
	 * Subscriber role slug.
	 *
	 * @since 1.5.37
	 *
	 * @var string
	 */
	const SUBSCRIBER = 'subscriber';

	/**
	 * Return all roles.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public static function get_all() {
		/**
		 * Filters the user roles.
		 *
		 * @since 1.0.0
		 *
		 * @param array $roles List of roles.
		 */
		return apply_filters(
			'masteriyo_user_roles',
			array(
				// 'masteriyo_manager'    => array(
				//  'display_name' => esc_html__( 'Masteriyo Manager', 'learning-management-system' ),
				//  'capabilities' => Capabilities::get_manager_capabilities(),
				// ),
				'masteriyo_instructor' => array(
					'display_name' => esc_html(
						sprintf(
							/* translators: %s: the product's name */
							__( '%s Instructor', 'learning-management-system' ),
							masteriyo_get_plugin_name()
						)
					),
					'capabilities' => Capabilities::get_instructor_capabilities(),
				),
				'masteriyo_student'    => array(
					'display_name' => esc_html(
						sprintf(
							/* translators: %s: the product's name */
							__( '%s Student', 'learning-management-system' ),
							masteriyo_get_plugin_name()
						)
					),
					'capabilities' => Capabilities::get_student_capabilities(),
				),
			)
		);
	}

	/**
	 * Register the Masteriyo roles if they don't already exist.
	 *
	 * Idempotent - safe to call on every request. Used on activation and by the
	 * runtime self-heal in Install when roles go missing.
	 *
	 * @return void
	 */
	public static function create() {
		foreach ( self::get_all() as $role_slug => $role ) {
			if ( null === get_role( $role_slug ) ) {
				add_role( $role_slug, $role['display_name'], $role['capabilities'] );
			}
		}
	}

	/**
	 * Add any capabilities defined in get_all() that are missing from existing roles.
	 *
	 * Runs on every request but only writes to the database when a capability is
	 * actually absent from the stored role, so the cost is a set of in-memory array
	 * lookups on the happy path.
	 */
	public static function sync_caps() {
		foreach ( self::get_all() as $role_slug => $role ) {
			$wp_role = get_role( $role_slug );

			if ( ! $wp_role ) {
				continue;
			}

			foreach ( $role['capabilities'] as $cap => $grant ) {
				if ( ! isset( $wp_role->capabilities[ $cap ] ) ) {
					$wp_role->add_cap( $cap, $grant );
				}
			}
		}

		self::sync_admin_caps();
	}

	/**
	 * Add any Masteriyo capabilities missing from the 'administrator' role.
	 *
	 * Capabilities are normally seeded once, on plugin activation, by
	 * Activation::assign_core_capabilities_to_admin(). Sites that only ever
	 * auto-update (never deactivate/reactivate) never receive capabilities
	 * added in later releases, which silently breaks any endpoint gated on
	 * a bare custom capability (e.g. Gradebook's `read_grade_results`).
	 * Runs on every request but only writes when a capability is actually
	 * missing from the stored role.
	 */
	private static function sync_admin_caps() {
		$wp_role = get_role( self::ADMIN );

		if ( ! $wp_role ) {
			return;
		}

		foreach ( Capabilities::get_admin_capabilities() as $cap => $grant ) {
			if ( ! isset( $wp_role->capabilities[ $cap ] ) ) {
				$wp_role->add_cap( $cap, $grant );
			}
		}
	}

	/**
	 * Remove all roles.
	 *
	 * @since 1.5.37
	 */
	public static function remove_all() {
		// Remove the masteriyo manager role for now.
		remove_role( 'masteriyo_manager' );

		foreach ( self::get_all() as $role_slug => $role ) {
			remove_role( $role_slug );
		}
	}
}
