<?php
/**
 * Transient Cache class implementation.
 *
 * @package Masteriyo\Cache
 *
 * @since 1.11.0
 */

namespace Masteriyo\Cache;

use Masteriyo\Contracts\TransientCacheInterface;

/**
 * Transient Cache class
 *
 * @since 1.11.0
 */
class TransientCache implements TransientCacheInterface {

	/**
	 * The prefix used for all transient cache keys.
	 *
	 * This prefix is used to ensure that the cache keys are unique and do not conflict with other cache keys in the system.
	 *
	 * @since 1.11.0
	 *
	 * @access private
	 *
	 * @var string $prefix The prefix for all transient cache keys.
	 */
	private $prefix = 'masteriyo_transient_cache_';

	/**
	 * Set data in cache.
	 *
	 * @param string $key     The cache key.
	 * @param mixed  $data    The data to be cached.
	 * @param int    $expires Optional. Cache expiration time in seconds. Defaults to 0 (no expiration).
	 * @param string $group   Optional. The cache group.
	 *
	 * @since 1.11.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public function set_cache( $key, $data, $expires = 0, $group = '' ) {
		try {
			$key = $this->add_prefix( $key, $group );

			/**
			 * Fires an action after the cache data is set.
			 *
			 * This action is fired after the cache data is set using `set_transient()`.
			 * The action name is dynamically generated based on the cache key.
			 *
			 * @since 1.11.0
			 *
			 * @param mixed $cache_data The cache data that was set.
			 */
			do_action( 'masteriyo_transient_cache_before_' . $key, $data );

			$result = set_transient( $key, $data, $expires );

			/**
			 * Fires an action hook after the cache data has been updated.
			 *
			 * This action hook can be used to perform additional actions or tasks after the cache data has been updated.
			 *
			 * @since 1.11.0
			 *
			 * @param array $cache_data The cache data that was updated.
			 */
			do_action( 'masteriyo_transient_cache_after_' . $key, $data );

			return $result;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Get data from cache.
	 *
	 * @param string $key The cache key.
	 *
	 * @since 1.11.0
	 *
	 * @return mixed|null Cached data if available, null otherwise.
	 */
	public function get_cache( $key, $group = '' ) {
		try {
			$key  = $this->add_prefix( $key, $group );
			$data = get_transient( $key );

			return false === $data ? null : $data;
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Check if cache exists.
	 *
	 * @param string $key The cache key.
	 * @param string $group The cache group.
	 *
	 * @since 1.11.0
	 *
	 * @return bool True if cache exists, false otherwise.
	 */
	public function has_cache( $key, $group = '' ) {
		try {
			$key = $this->add_prefix( $key, $group );

			return $this->get_cache( $key ) ? true : false;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Delete cache
	 *
	 * @param string $key The cache key.
	 * @param string $group The cache group.
	 *
	 * @since 1.11.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_cache( $key, $group = '' ) {
		try {
			$key = $this->add_prefix( $key, $group );

			return delete_transient( $key );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Add prefix to the cache key.
	 *
	 * @param string $key The cache key.
	 * @param string $group The cache group.
	 *
	 * @since 1.11.0
	 *
	 * @return string Modified cache key with prefix.
	 */
	public function add_prefix( $key, $group ) {
		return $this->prefix . $group . $key;
	}

	/**
	 * Clear all caches with a specific prefix.
	 *
	 * @since 1.11.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_caches( $group = '' ) {
		global $wpdb;

		$option_timeout_prefix = esc_sql( '_transient_timeout_' . $this->prefix . $group . '%' );
		$option_prefix         = esc_sql( '_transient_' . $this->prefix . $group . '%' );

		$result_transients = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $option_prefix ) );

		$result_timeouts = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $option_timeout_prefix ) );

		if ( false !== $result_transients && false !== $result_timeouts ) {
			/**
			 * Fires when caches are cleared.
			 *
			 * @since 1.13.0
			 *
			 * @param string $group The cache group.
			 */
			do_action( 'masteriyo_caches_cleared', $group );
		} else {
			return false;
		}
	}
}
