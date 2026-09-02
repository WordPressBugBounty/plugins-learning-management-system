<?php
/**
 * Core features loader.
 *
 * Loads either FREE or PRO core-features based on type.
 *
 * @package Masteriyo
 *
 * @since 3.1.0
 */

namespace Masteriyo;

defined( 'ABSPATH' ) || exit;

class CoreFeatures {

	/**
	 * Core-feature type.
	 *
	 * @since 3.1.0
	 *
	 * @var string free|pro
	 */
	protected $type;

	/**
	 * Base directory.
	 *
	 * @since 3.1.0
	 *
	 * @var string
	 */
	protected $base_dir;

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param string $type Core feature type: free|pro.
	 */
	public function __construct( $type = 'free' ) {
		$this->type = ( 'pro' === $type ) ? 'pro' : 'free';

		// The pro constant is defined by `pro/bootstrap.php`, which the free product
		// does not ship. Only pro ever asks for the pro type, so the second test
		// changes nothing at runtime and states for the analysis what that relies on.
		$this->base_dir = ( 'pro' === $this->type && defined( 'MASTERIYO_PRO_CORE_FEATURES_DIR' ) )
			? trailingslashit( MASTERIYO_PRO_CORE_FEATURES_DIR )
			: trailingslashit( MASTERIYO_CORE_FEATURES_DIR );
	}

	/**
	 * Get core-features base directory.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public function get_dir() {
		return $this->base_dir;
	}

	/**
	 * Discover all core-features.
	 *
	 * @since 3.1.0
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

		// Both hook names are spelled out as literals rather than selected inside the
		// apply_filters() call: a computed hook name is invisible to every static reader,
		// including the surface differ and WordPress's own hook-documentation parser.
		if ( 'pro' === $this->type ) {
			/**
			 * Filter discovered pro core-features.
			 *
			 * @since 3.1.0
			 *
			 * @param array  $features
			 * @param string $type free|pro
			 */
			return apply_filters( 'masteriyo_pro_core_features', $features, $this->type );
		}

		/**
		 * Filter discovered core-features.
		 *
		 * @since 3.1.0
		 *
		 * @param array  $features
		 * @param string $type free|pro
		 */
		return apply_filters( 'masteriyo_core_features', $features, $this->type );
	}

	/**
	 * Load all discovered core-features.
	 *
	 * @since 3.1.0
	 */
	public function load_all() {
		foreach ( $this->get_all() as $file ) {
			require_once $file;
		}
	}
}
