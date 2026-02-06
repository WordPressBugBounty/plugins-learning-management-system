<?php
/**
 * Core features loader (FREE only).
 *
 * @package Masteriyo
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;

class CoreFeatures {

	/**
	 * Base directory.
	 *
	 * @var string
	 */
	protected $base_dir;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->base_dir = trailingslashit( MASTERIYO_CORE_FEATURES_DIR );
	}

	/**
	 * Get core-features base directory.
	 *
	 * @return string
	 */
	public function get_dir() {
		return $this->base_dir;
	}

	/**
	 * Discover all core-features.
	 *
	 * @return array
	 */
	public function get_all() {
		$dir = $this->get_dir();

		if ( ! is_dir( $dir ) || ! is_readable( $dir ) ) {
			return array();
		}

		$features = array();

		foreach ( scandir( $dir ) as $slug ) {
			if ( '.' === $slug || '..' === $slug ) {
				continue;
			}

			$main = trailingslashit( $dir . $slug ) . 'main.php';

			if ( is_dir( $dir . $slug ) && file_exists( $main ) ) {
				$features[ $slug ] = $main;
			}
		}

		/**
		 * Filter discovered core-features (FREE).
		 *
		 * @param array $features
		 */
		return apply_filters( 'masteriyo_core_features', $features );
	}

	/**
	 * Load all discovered core-features.
	 */
	public function load_all() {
		foreach ( $this->get_all() as $file ) {
			require_once $file;
		}
	}
}
