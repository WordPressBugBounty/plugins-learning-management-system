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
				// 	'display_name' => esc_html__( 'Masteriyo Manager', 'learning-management-system' ),
				// 	'capabilities' => Capabilities::get_manager_capabilities(),
				// ),
				'masteriyo_instructor' => array(
					'display_name' => esc_html__( 'Masteriyo Instructor', 'learning-management-system' ),
					'capabilities' => Capabilities::get_instructor_capabilities(),
				),
				'masteriyo_student'    => array(
					'display_name' => esc_html__( 'Masteriyo Student', 'learning-management-system' ),
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
	 * @since x.x.x
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
