<?php
/**
 * Tracking information for the Masteriyo.
 *
 * @package Masteriyo\Tracking
 *
 * @since 1.6.0
 */

namespace Masteriyo\Tracking;

use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\Roles;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * MasteriyoTrackingInfo class.
 */
class MasteriyoTrackingInfo {
	/**
	 * Get product license key.
	 *
	 * @since 1.6.0
	 *
	 * @return string|null
	 */
	public static function get_license_key() {
		return get_option( 'masteriyo_pro_license_key', null );
	}

	/**
	 * Get the base product plugin slug.
	 *
	 * @since 1.6.0
	 *
	 * @return string The base product plugin slug.
	 */
	public static function get_slug() {
		if ( self::is_premium() ) {
			return 'learning-management-system-pro/lms.php';
		} else {
			return 'learning-management-system/lms.php';
		}
	}

	/**
	 * Return base product file.
	 *
	 * @since 1.6.0
	 *
	 * @return string
	 */
	public static function get_file_path() {
		return WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . self::get_slug();
	}

	/**
	 * Check if user is using premium version.
	 *
	 * @since 1.6.0
	 *
	 * @return boolean True if the user is using the premium version, false otherwise.
	 */
	public static function is_premium() {
		if ( is_plugin_active( 'learning-management-system-pro/lms.php' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks if usage is allowed.
	 *
	 * @since 1.6.0
	 *
	 * @return boolean
	 */
	public static function is_usage_allowed() {
		return masteriyo_get_setting( 'advance.tracking.allow_usage' );
	}

	/**
	 * Return publish courses by AI count.
	 *
	 * @since 1.6.16
	 *
	 * @return integer
	 */
	public static function get_publish_course_by_ai_count() {

		$meta_query = array(
			'key'     => '_is_ai_created',
			'value'   => '1',
			'compare' => '=',
		);

		$args = array(
			'post_type'      => PostType::COURSE,
			'post_status'    => PostStatus::PUBLISH,
			'posts_per_page' => -1,
			'meta_query'     => array( $meta_query ),
		);

		$query = new \WP_Query( $args );

		return $query->found_posts;
	}

	/**
	 * Return publish courses count.
	 *
	 * @since 1.6.0
	 *
	 * @return integer
	 */
	public static function get_publish_course_count() {
		return masteriyo_array_get( (array) wp_count_posts( PostType::COURSE ), PostStatus::PUBLISH, 0 );
	}

	/**
	 * Return completed orders count.
	 *
	 * @since 1.6.0
	 *
	 * @return integer
	 */
	public static function get_completed_orders_count() {
		return masteriyo_array_get( (array) wp_count_posts( PostType::ORDER ), OrderStatus::COMPLETED, 0 );
	}

	/**
	 * Get total number of enrolled users on the site (excluding admins, instructors, and managers).
	 *
	 * @since 1.20.0
	 *
	 * @return int Total enrolled users count.
	 */
	public static function masteriyo_count_total_enrolled_users() {
		global $wpdb;

		$count = 0;

		if ( $wpdb ) {
			$sql = $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items WHERE ( status = %s OR status = %s )",
				UserCourseStatus::ACTIVE,
				UserCourseStatus::ENROLLED
			);

			$exclude_users = array();
			if ( did_action( 'plugins_loaded' ) ) {
				$exclude_users = array_map(
					'absint',
					(array) get_users(
						array(
							'role__in' => array( Roles::ADMIN, Roles::INSTRUCTOR, Roles::MANAGER ),
							'fields'   => 'ID',
						)
					)
				);
			}
			if ( ! empty( $exclude_users ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $exclude_users ), '%d' ) );
				$sql         .= $wpdb->prepare( " AND user_id NOT IN ($placeholders)", ...$exclude_users );
			}

			$count = $wpdb->get_var( $sql );
		}

		/**
		 * Filters total enrolled users count.
		 *
		 * @since 1.20.0
		 *
		 * @param int $count Total enrolled users count.
		 */
		return absint( $count );
	}

	/**
	 * Calculates the number of days since the plugin was installed.
	 *
	 * Retrieves the installation date from the 'masteriyo_install_date' option.
	 * If the value is not numeric, it attempts to convert it to a timestamp.
	 * Returns the number of full days elapsed since installation.
	 *
	 *  @since 1.20.0
	 * @return int Number of days since the plugin was installed.
	 */
	public static function get_install_days() {
		$install_time = get_option( 'masteriyo_install_date', time() );
		if ( ! is_numeric( $install_time ) ) {
			$install_time = strtotime( $install_time );
		}
		$current_time       = time();
		$days_since_install = floor( ( $current_time - $install_time ) / DAY_IN_SECONDS );
		return $days_since_install;
	}


	/**
	 * Get total published certificates.
	 *
	 * @since 2.1.0
	 *
	 * @return int Published certificate count.
	 */
	public static function total_published_certificates() {
		$query = new WP_Query(
			array(
				'post_type'      => 'mto-certificate',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => -1,
			)
		);

		return $query->found_posts;
	}

	/**
	 * Get total published groups.
	 *
	 * @since 2.1.0
	 *
	 * @return int Group count.
	 */
	public static function total_publish_groups() {
		return masteriyo_array_get(
			(array) wp_count_posts( PostType::GROUP ),
			PostStatus::PUBLISH,
			0
		);
	}

	/**
	 * Get total published announcements.
	 *
	 * @since 2.1.0
	 *
	 * @return int Announcement count.
	 */
	public static function total_publish_announcement() {
		return masteriyo_array_get(
			(array) wp_count_posts( PostType::COURSEANNOUNCEMENT ),
			PostStatus::PUBLISH,
			0
		);
	}

	/**
	 * Get addon data such as activated addons and counts.
	 *
	 * @since 2.1.0
	 *
	 * @return array Addon data list.
	 */
	public static function get_addons_data() {
		$addons = get_option( 'masteriyo_active_addons', array() );

		if ( empty( $addons ) || ! is_array( $addons ) ) {
			return array(
				'total_activated_addons' => 0,
				'activated_addons'       => array(),
			);
		}

		$activated_addons = array();

		foreach ( $addons as $addon_key => $addon ) {
			$stats                          = self::get_addon_stats( $addon_key );
			$activated_addons[ $addon_key ] = ! empty( $stats ) ? $stats : '';
		}

		return array(
			'total_activated_addons' => count( $activated_addons ),
			'activated_addons'       => $activated_addons,
		);
	}




	/**
	 * Get addon specific statistics.
	 *
	 * @since 2.1.0
	 *
	 * @param string $addon_key Addon key.
	 * @return array Addon statistics.
	 */
	protected static function get_addon_stats( $addon_key ) {

		switch ( $addon_key ) {

			case 'certificate':
				return array(
					'total_publish_certificates' => self::total_published_certificates(),
				);

			case 'group-courses':
				return array(
					'total_publish_groups' => self::total_publish_groups(),
				);

			case 'course-announcement':
				return array(
					'total_publish_announcement' => self::total_publish_announcement(),
				);

			default:
				return array();
		}
	}

	/**
	 * Return base product name.
	 *
	 * @since 1.6.0
	 *
	 * @return string
	 */
	public static function get_name() {
		return self::is_premium() ? 'Masteriyo Pro' : 'Masteriyo';
	}

	/**
	 * Return meta information.
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */
	public static function get_meta_data() {
		return array(
			'license_key'        => self::get_license_key(),
			'course_count'       => self::get_publish_course_count(),
			'course_by_ai_count' => self::get_publish_course_by_ai_count(),
			'order_count'        => self::get_completed_orders_count(),
		);
	}

	/**
	 * Return masteriyo plugin data information.
	 *
	 * @since 1.6.0
	 *
	 * @return array
	 */
	public static function all() {
		return array(
			'product_name'    => self::get_name(),
			'product_version' => masteriyo_get_version(),
			'product_meta'    => self::get_meta_data(),
			'product_type'    => 'plugin',
			'product_slug'    => self::get_slug(),
			'is_premium'      => self::is_premium(),
		);
	}
}
