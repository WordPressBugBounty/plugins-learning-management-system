<?php
/**
 * REST Auth Permission Type enums.
 *
 * @since 1.16.0
 * @package Masteriyo\Enums
 */

namespace Masteriyo\Enums;

defined( 'ABSPATH' ) || exit;

/**
 * REST Auth Permission Type class.
 *
 * @since 1.16.0
 */
class RestAuthPermissionType {
	/**
	 * Read Permissions
	 *
	 * @since 1.16.0
	 *
	 * @var string
	 */
	const READ = 'read';

	/**
	 * Write Permissions
	 *
	 * @since 1.16.0
	 *
	 * @var string
	 */
	const WRITE = 'write';

	/**
	 * Read Write Permissions
	 *
	 * @since 1.16.0
	 *
	 * @var string
	 */
	const READ_WRITE = 'read_write';

	/**
	 * Get all permission types.
	 *
	 * @since 1.16.0
	 * @static
	 *
	 * @return array
	 */
	public static function all() {
		/**
		 * Filter permission types.
		 *
		 * @since 1.16.0
		 * @param string[] $permission_types Permission types.
		 */
		$permission_types = apply_filters(
			'masteriyo_rest_auth_permission_types',
			array(
				self::READ,
				self::WRITE,
				self::READ_WRITE,
			)
		);

		return array_unique( $permission_types );
	}
}
