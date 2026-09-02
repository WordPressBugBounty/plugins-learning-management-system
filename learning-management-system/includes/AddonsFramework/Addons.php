<?php
/**
 * Masteriyo addons class.
 *
 * @since 2.0.5
 * @since 1.6.11
 * @package Masteriyo\AddonsFramework
 */

namespace Masteriyo\AddonsFramework;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Constants;

/**
 * Masteriyo addons class.
 */
class Addons {
	/**
	 * Active addons list.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 *
	 * @var string
	 */
	public $active_addons_option = 'masteriyo_active_addons';

	/**
	 * Constructor
	 *
	 * @since 2.5.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @since 2.5.14
	 * @since 1.6.11
	 */
	public function init_hooks() {
		add_action( 'masteriyo_activation', array( $this, 'on_activate' ) );
		add_action( 'masteriyo_deactivate', array( $this, 'on_deactivate' ) );
	}

	/**
	 * Run all the setup files of addon.
	 *
	 * @since 2.5.14
	 * @since 1.6.11
	 */
	public function on_activate() {
		foreach ( $this->get_active_addons() as $slug => $addon ) {
			$active_addon_file = $this->get_addon_file( $slug, 'setup.php' );
			if ( null !== $active_addon_file ) {
				require_once $active_addon_file;
			}
		}
	}

	/**
	 * Run all the destroy files of addon.
	 *
	 * @since 2.5.14
	 * @since 1.6.11
	 */
	public function on_deactivate() {
		foreach ( $this->get_active_addons() as $slug => $addon ) {
			// Note the spelling: this has always looked for `destory.php`, while
			// set_inactive() looks for `destroy.php`. Both are load-bearing for
			// whichever addons happen to ship each name. Do not "fix" it here.
			$active_addon_file = $this->get_addon_file( $slug, 'destory.php' );
			if ( null !== $active_addon_file ) {
				require_once $active_addon_file;
			}
		}
	}

	/**
	 * Return masteriyo pro addons dir.
	 *
	 * The core addons root only. Use get_addon_roots() to reach every root, or
	 * get_addon_dir() to find the one a given addon lives in.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 * @return string
	 */
	public function get_addons_dir() {
		return MASTERIYO_PRO_ADDONS_DIR;
	}

	/**
	 * Return masteriyo pro addons url.
	 *
	 * The core addons root only. See get_addons_dir().
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 * @return string
	 */
	public function get_addons_url() {
		return plugin_dir_url( MASTERIYO_PLUGIN_FILE ) . 'addons';
	}

	/**
	 * Return every root an addon may be discovered under.
	 *
	 * Core contributes exactly one — the shared `addons/` directory. Pro appends
	 * its own through the filter below, from its own bootstrap, because core may
	 * not name a pro path. The free product therefore sees one root and finds no
	 * pro addon, with no inventory of pro addons anywhere in shared code.
	 *
	 * Roots are searched in order and the first match wins, so a shared addon can
	 * never be shadowed by a pro directory of the same slug.
	 *
	 * @return array[] List of arrays with `dir` and `url` keys.
	 */
	public function get_addon_roots() {
		$roots = array(
			array(
				'dir' => Constants::get( 'MASTERIYO_PRO_ADDONS_DIR' ),
				'url' => $this->get_addons_url(),
			),
		);

		/**
		 * Filters the roots addons are discovered under.
		 *
		 * @param array[] $roots List of arrays with `dir` and `url` keys.
		 * @param \Masteriyo\AddonsFramework\Addons $addons Addons object.
		 */
		return apply_filters( 'masteriyo_addon_roots', $roots, $this );
	}

	/**
	 * Return the root directory the given addon lives in.
	 *
	 * @param string $slug Addon slug.
	 *
	 * @return string|null The root's directory, or null when no root has the addon.
	 */
	public function get_addon_dir( $slug ) {
		foreach ( $this->get_addon_roots() as $root ) {
			if ( file_exists( $root['dir'] . "/{$slug}/main.php" ) ) {
				return $root['dir'];
			}
		}

		return null;
	}

	/**
	 * Return a file inside an addon, resolved against whichever root has the addon.
	 *
	 * @param string $slug Addon slug.
	 * @param string $file File name relative to the addon directory.
	 *
	 * @return string|null Absolute path, or null when the addon or the file is absent.
	 */
	public function get_addon_file( $slug, $file ) {
		$dir = $this->get_addon_dir( $slug );

		if ( null === $dir ) {
			return null;
		}

		$path = $dir . "/{$slug}/{$file}";

		return file_exists( $path ) ? $path : null;
	}

	/**
	 * Return default addon header.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 * @return string
	 */
	private function get_default_addon_headers() {
		return apply_filters(
			'masteriyo_pro_default_addon_headers',
			array(
				'Addon Name'  => 'Addon Name',
				'Addon URI'   => 'Addon URI',
				'Addon Type'  => 'Addon Type',
				'Description' => 'Description',
				'Author'      => 'Author',
				'Author URI'  => 'Author URI',
				'Requires'    => 'Requires',
				'Plan'        => 'Plan',
				'Category'    => 'Category',
				'Hidden'      => 'Hidden',
			)
		);
	}

	/**
	 * Return main addon file form addon slug.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 *
	 * @param string $slug Addon slug.
	 *
	 * @return string|null
	 */
	public function get_main_file( $slug ) {
		return $this->get_addon_file( $slug, 'main.php' );
	}

	/**
	 * Return addon thumbnail url.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 *
	 * @param string $slug Addon slug.
	 *
	 * @return string
	 */
	public function get_thumbnail_url( $slug ) {
		foreach ( $this->get_addon_roots() as $root ) {
			if ( file_exists( $root['dir'] . "/{$slug}/thumbnail.png" ) ) {
				return $root['url'] . "/{$slug}/thumbnail.png";
			}
		}

		return '';
	}

	/**
	 * Get addon data from the main file.
	 *
	 * @since 2.0.5
	 * @since 2.5.0
	 * @since 1.6.11
	 *
	 * @param string $addon Main addon file or slug.
	 * @param bool $rest Whether to format the data for rest or not.
	 * @param string $context Edit/View context.
	 *
	 * @return array
	 */
	public function get_data( $addon, $rest = false, $context = 'view' ) {
		if ( ! ( file_exists( $addon ) && is_readable( $addon ) ) ) {
			$file = $this->get_main_file( $addon );
		} else {
			$file = $addon;
		}

		$main_data = get_file_data( $file, $this->get_default_addon_headers() );
		$data      = array_merge( $main_data, $this->get_additional_data( $addon ) );

		if ( $rest ) {
			$keys = array_map(
				function( $addon_key ) {
					return sanitize_key( str_replace( ' ', '_', $addon_key ) );
				},
				array_keys( $data )
			);

			$data = array_combine( $keys, array_values( $data ) );
		}

		if ( 'view' === $context ) {
			/**
			 * Filter addon data.
			 *
			 * @since 2.2.0
			 * @since 1.6.11
			 *
			 * @param array $data Addon data.
			 * @param string $slug Addon slug.
			 * @param bool $rest Whether to format the data for rest or not.
			 */
			$data = apply_filters( 'masteriyo_pro_addon_data', $data, $addon, $rest );
		}

		return $data;
	}

	/**
	 * Return all addons in the following format.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 *
	 * @return array
	 */
	public function get_all_addons() {
		global $wp_filesystem;

		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		if ( ! $wp_filesystem ) {
			return;
		}

		// List all addons, from every root. First root wins on a slug collision,
		// which is why the pro root is appended after the core one.
		$addon_main_files = array();
		foreach ( $this->get_addon_roots() as $root ) {
			foreach ( (array) $wp_filesystem->dirlist( $root['dir'] ) as $addon => $data ) {
				if ( isset( $addon_main_files[ $addon ] ) ) {
					continue;
				}

				$addon_main_files[ $addon ] = $this->get_main_file( $addon );
			}
		}

		// Remove addons whose main files doesn't exist.
		$addon_main_files = array_filter( $addon_main_files );

		// Allow third party to include addons.
		$addon_main_files = apply_filters( 'masteriyo_pro_addons', $addon_main_files );
		return $addon_main_files;
	}

	/**
	 * Return all addons with addon data.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 *
	 * @return array
	 */
	public function get_addons_data() {
		$addons      = $this->get_all_addons();
		$addons_data = array_map(
			function( $addon ) {
				return $this->get_data( $addon );
			},
			$addons
		);

		return $addons_data;
	}

	/**
	 * Load all the addons.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 */
	public function load_all() {
		// Load addons.
		foreach ( $this->get_all_addons() as $slug => $main_file ) {
			require_once $main_file;
		}
	}

	/**
	 * Return true if the addon is exists.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 *
	 * @param string $slug
	 * @return boolean
	 */
	public function is_addon( $slug ) {
		return null !== $this->get_main_file( $slug );
	}

	/**
	 * Get active addons.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 *
	 * @return array
	 */
	public function get_active_addons() {
		return get_option( $this->active_addons_option, array() );
	}

	/**
	 * Get inactive addons.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 *
	 * @return array
	 */
	public function get_inactive_addons() {
		$all_addons = $this->get_all_addons();

		$inactive_addons = array_filter(
			$all_addons,
			function( $slug ) {
				return ! $this->is_active( $slug );
			},
			ARRAY_FILTER_USE_KEY
		);

		$inactive_addons = array_map(
			function( $inactive_addon ) {
				return $this->get_data( $inactive_addon );
			},
			$inactive_addons
		);

		return $inactive_addons;
	}

	/**
	 * Return true if the addon is active.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 *
	 * @param string $slug Addon slug.
	 * @return boolean
	 */
	public function is_active( $slug ) {
		$active_addons = $this->get_active_addons();

		return isset( $active_addons[ $slug ] );
	}

	/**
	 * Set an addon inactive.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 *
	 * @param string $slug Addon slug.
	 *
	 * @return boolean|array
	 */
	public function set_inactive( $slug ) {
		$main_file = $this->get_main_file( $slug );

		if ( null === $main_file ) {
			return false;
		}

		$addon_data = $this->get_data( $main_file );

		if ( ! $this->is_active( $slug ) ) {
			return $addon_data;
		}

		$active_addons = $this->get_active_addons();
		unset( $active_addons[ $slug ] );

		update_option( $this->active_addons_option, $active_addons );

		$active_addon_file = $this->get_addon_file( $slug, 'destroy.php' );
		if ( null !== $active_addon_file ) {
			require_once $active_addon_file;
		}

		/**
		 * Fires after addon is deactivated.
		 *
		 * @since 2.3.8
		 * @since 1.6.11
		 * @param \Masteriyo\AddonsFramework\Addons $addons Addons class.
		 */
		do_action( "masteriyo_pro_addon_{$slug}_deactivate", $this );

		return $addon_data;
	}

	/**
	 * Set an addon active.
	 *
	 * @since 2.0.5
	 * @since 1.6.11
	 *
	 * @param string $slug Addon slug.
	 *
	 * @return bool|array
	 */
	public function set_active( $slug ) {
		$main_file = $this->get_main_file( $slug );

		if ( null === $main_file ) {
			return false;
		}

		$addon_data = $this->get_data( $main_file );

		if ( $this->is_active( $slug ) ) {
			return $addon_data;
		}

		$active_addons          = $this->get_active_addons();
		$active_addons[ $slug ] = $addon_data;

		update_option( $this->active_addons_option, $active_addons );

		$active_addon_file = $this->get_addon_file( $slug, 'setup.php' );
		if ( null !== $active_addon_file ) {
			require_once $active_addon_file;
		}

		$active_addon_file = $this->get_addon_file( $slug, 'main.php' );
		if ( null !== $active_addon_file ) {
			require_once $active_addon_file;
		}

		/**
		 * Fires after addon is activate.
		 *
		 * @since 2.3.8
		 * @since 1.6.11
		 * @param \Masteriyo\AddonsFramework\Addons $addons Addons class.
		 */
		do_action( "masteriyo_pro_addon_{$slug}_activate", $this );

		return $addon_data;
	}

	/**
	 * Get complete addon data along with thumbnail and active status.
	 *
	 * @since 2.2.0
	 * @since 1.6.11
	 *
	 * @param string $slug Addon slug.
	 * @return array
	 */
	public function get_additional_data( $slug ) {
		return array(
			'slug'                  => $slug,
			'active'                => $this->is_active( $slug ),
			'thumbnail'             => $this->get_thumbnail_url( $slug ),
			'requirement_fulfilled' => 'yes',
		);
	}

	/**
	 * Return addon plan.
	 *
	 * @since 2.5.0
	 * @since 1.6.11
	 *
	 * @param string $slug Addon slug.
	 * @return string
	 */
	public function get_addon_plan( $slug ) {
		return masteriyo_array_get( $this->get_data( $slug ), 'Plan', '' );
	}

	/**
	 * Return true if the addon is hidden from the addons listing UI.
	 *
	 * A hidden addon (`Hidden: yes` header) loads, activates and localizes
	 * like any other — only the listing stops presenting it.
	 *
	 * @param string $slug Addon slug.
	 * @return boolean
	 */
	public function is_hidden( $slug ) {
		if ( ! $this->is_addon( $slug ) ) {
			return false;
		}

		return masteriyo_string_to_bool( masteriyo_array_get( $this->get_data( $slug ), 'Hidden', false ) );
	}

	/**
	 * Return if the addon is allowed for the current user plan.
	 *
	 * Core knows nothing about plans, so every addon is allowed here. Pro gates
	 * the answer against the licence plan through the filter below — the seam
	 * that keeps this class free of any licence concept.
	 *
	 * @since 2.5.0
	 *
	 * @param string $slug Addon slug.
	 * @return boolean
	 */
	public function is_allowed( $slug ) {
		/**
		 * Filters whether addon is allowed or not.
		 *
		 * @since 1.6.11
		 *
		 * @param string $slug Addon slug.
		 * @param \Masteriyo\AddonsFramework\Addons $addons Addons object.
		 */
		return apply_filters( 'masteriyo_pro_is_addon_allowed', true, $slug, $this );
	}
}
