<?php
/**
 * Abstract Cache plugin compatibility.
 *
 * Every class which indents to support compatible with Cache Plugin should extends from it.
 *
 * @since 1.5.36
 * @package Masteriyo\Abstracts
 */

namespace Masteriyo\Abstracts;

defined( 'ABSPATH' ) || exit;


/**
 * Abstract cache plugin compatibility.
 */
abstract class CachePluginCompatibility {

	/**
	 * Cache plugin slug.
	 *
	 * @since 1.5.36
	 *
	 * @var string
	 */
	protected $plugin = '';

	/**
	 * Initialize.
	 *
	 * @since 1.5.36
	 * @return void
	 */
	public function init() {
		add_action( 'wp', array( $this, 'do_not_cache_page' ) );

		add_action( 'wp_optimize_admin_page_wpo_minify_status', array( $this, 'masteriyo_display_wpo_minify_notice' ) );

		add_action( 'w3tc_saved_options', array( $this, 'masteriyo_catch_w3tc_minify_settings' ) );
		add_action( 'masteriyo_admin_notices', array( $this, 'masteriyo_display_w3tc_minify_notice' ) );

		add_action( 'masteriyo_admin_notices', array( $this, 'masteriyo_display_ls_minify_notice' ) );

		add_action( 'masteriyo_admin_notices', array( $this, 'masteriyo_display_minify_notice' ) );
	}

	/**
	 * Callback for do not cache page.
	 *
	 * @since 1.5.36
	 */
	public function do_not_cache_page() {
		// Bail early if the associated cache plugin is not active.
		if ( ! $this->is_plugin_active() ) {
			return;
		}

		if ( masteriyo_is_checkout_page() ) {
			$this->do_not_cache();
		}

		if ( masteriyo_is_lost_password_page() ) {
			$this->do_not_cache();
		}

		if ( masteriyo_is_signin_page() ) {
			$this->do_not_cache();
		}

		if ( masteriyo_is_signup_page() ) {
			$this->do_not_cache();
		}

		if ( masteriyo_is_instructor_registration_page() ) {
			$this->do_not_cache();
		}
	}

	/**
	 * Show notice in litespeed cache.
	 *
	 * @since 1.15.0
	 *
	 * @return array
	 */
	public function masteriyo_display_ls_minify_notice() {

		$now          = get_current_screen();
		$current_page = $now->base;

		if ( 'litespeed-cache/litespeed-cache.php' === $this->plugin && strpos( $current_page, 'litespeed-cache_page_litespeed-page_optm' ) !== false ) {
			$minify_settings = array();
			$minify_css      = get_option( 'litespeed.conf.optm-css_min' );
			$minify_js       = get_option( 'litespeed.conf.optm-js_min	' );

			if ( $minify_js ) {
				$minify_settings[] = 'JavaScript';
			}

			if ( $minify_css ) {
				$minify_settings[] = 'CSS';
			}

			if ( ! empty( $minify_settings ) ) {
				$settings_text = implode( ' and ', $minify_settings );
				printf(
					'<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p><span class="screen-reader-text">%s</span></div>',
					esc_html( 'Masteriyo:' ),
					wp_kses_post( sprintf( 'For best results, please disable %s minification. Masteriyo already includes optimized files, and further minification may cause issues.', $settings_text ) ),
					esc_html__( 'Dismiss this notice.', 'learning-management-system' )
				);
			} elseif ( 'litespeed-cache/litespeed-cache.php' === $this->plugin && strpos( $current_page, 'litespeed-cache_page_litespeed-cache' ) !== false ) {
				$cache_rest = get_option( 'litespeed.conf.cache-rest' );
				if ( $cache_rest ) {
					printf(
						'<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p><span class="screen-reader-text">%s</span></div>',
						esc_html( 'Masteriyo:' ),
						wp_kses_post( sprintf( 'For best results, please disable Caching REST API. Masteriyo already includes optimized resources, and further caching may cause issues.' ) ),
						esc_html__( 'Dismiss this notice.', 'learning-management-system' )
					);
				}
			}
		}
	}

	/**
	 * Show notice in w3 total cache minify status page.
	 *
	 * @since 1.15.0
	 *
	 * @return array
	 */
	public function masteriyo_display_w3tc_minify_notice() {
		$data = get_transient( 'masteriyo_w3tc_data' );

		if ( ! $data ) {
			return;
		}

		$now          = get_current_screen();
		$current_page = $now->base;

		if ( 'w3-total-cache/w3-total-cache.php' === $this->plugin && strpos( $current_page, 'w3tc_minify' ) !== false ) {
			$minify_settings = array();

			if ( is_object( $data ) ) {
				if ( $data->get( 'minify.js.enable' ) ) {
					$minify_settings[] = 'JavaScript';
				}
				if ( $data->get( 'minify.css.enable' ) ) {
					$minify_settings[] = 'CSS';
				}
			}

			if ( ! empty( $minify_settings ) ) {
				$settings_text = implode( ' and ', $minify_settings );
				printf(
					'<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p><span class="screen-reader-text">%s</span></div>',
					esc_html( 'Masteriyo:' ),
					wp_kses_post( sprintf( 'For best results, please disable %s minification. Masteriyo already includes optimized files, and further minification may cause issues.', $settings_text ) ),
					esc_html__( 'Dismiss this notice.', 'learning-management-system' )
				);
			}
		}
	}

	/**
	 * Show notice in wp-optimize minify status page.
	 *
	 * @since 1.15.0
	 *
	 * @return array
	 */
	public function masteriyo_display_wpo_minify_notice() {
		$now = get_option( 'wpo_minify_config' );

		$enable_js  = ! empty( $now['enable_js'] );
		$enable_css = ! empty( $now['enable_css'] );

		$message_parts = array();
		if ( $enable_js ) {
			$message_parts[] = 'JavaScript';
		}
		if ( $enable_css ) {
			$message_parts[] = 'CSS';
		}

		if ( 'wp-optimize/wp-optimize.php' === $this->plugin ) {
			if ( ! empty( $message_parts ) ) {
				$message      = implode( ' and ', $message_parts );
				$full_message = sprintf(
					'For best results, please disable %s minification. Masteriyo already includes optimized files, and further minification may cause issues.',
					$message
				);

				printf(
					'<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p><span class="screen-reader-text">%s</span></div>',
					esc_html( 'Masteriyo:' ),
					wp_kses_post( $full_message ),
					esc_html__( 'Dismiss this notice.', 'learning-management-system' )
				);
			}
		}
	}

	/**
	 * Store w3tc settings data.
	 *
	 * @since 1.15.0
	 *
	 * @param object $data data.
	 * @return array
	 */
	public function masteriyo_catch_w3tc_minify_settings( $data ) {
		set_transient( 'masteriyo_w3tc_data', $data, HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Display minify-related notices for various plugins.
	 *
	 * @since 1.15.0
	 *
	 * @return void
	 */
	public function masteriyo_display_minify_notice() {
		$plugins = array(
			'wp-rocket'        => array(
				'plugin'      => 'wp-rocket/wp-rocket.php',
				'page_check'  => 'settings_page_wprocket',
				'option_key'  => 'wp_rocket_settings',
				'minify_keys' => array(
					'Javascript' => 'minify_js',
					'CSS'        => 'minify_css',
				),
			),
			'hummingbird'      => array(
				'plugin'      => 'hummingbird-performance/wp-hummingbird.php',
				'page_check'  => 'hummingbird_page_wphb-minification',
				'option_key'  => 'wphb_settings',
				'minify_keys' => array(
					'Javascript' => 'minify.do_assets.scripts',
					'CSS'        => 'minify.do_assets.styles',
				),
			),
			'wp-fastest-cache' => array(
				'plugin'      => 'wp-fastest-cache/wpFastestCache.php',
				'page_check'  => 'toplevel_page_wpfastestcacheoptions',
				'option_key'  => 'WpFastestCache',
				'is_json'     => true,
				'minify_keys' => array(
					'Javascript' => 'wpFastestCacheMinifyJs',
					'CSS'        => 'wpFastestCacheMinifyCss',
				),
			),
		);

		$screen = get_current_screen();
		if ( ! $screen || ! isset( $screen->base ) ) {
			return;
		}

		foreach ( $plugins as $plugin ) {
			if ( $plugin['plugin'] === $this->plugin && strpos( $screen->base, $plugin['page_check'] ) !== false ) {
					$minify_settings = array();
					$data            = $plugin['option_key'] ? get_option( $plugin['option_key'] ) : null;

				if ( ! empty( $plugin['is_json'] ) && $data ) {
						$data = json_decode( $data, true );
				}

				if ( $data ) {
					foreach ( $plugin['minify_keys'] as $type => $key ) {
							$value = $this->get_nested_option( $data, $key );
						if ( $value ) {
							$minify_settings[] = ucfirst( $type );
						}
					}
				}

				if ( ! empty( $minify_settings ) ) {
						$settings_text = implode( ' and ', $minify_settings );
						printf(
							'<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p><span class="screen-reader-text">%s</span></div>',
							esc_html( 'Masteriyo:' ),
							wp_kses_post( sprintf( 'For best results, please disable %s minification. Masteriyo already includes optimized files, and further minification may cause issues.', $settings_text ) ),
							esc_html__( 'Dismiss this notice.', 'learning-management-system' )
						);
				}

					break;
			}
		}
	}

	/**
	 * Helper function to get nested options using dot notation.
	 *
	 * @since 1.15.0
	 *
	 * @param array  $data Array of data.
	 * @param string $key  Key in dot notation.
	 * @return mixed
	 */
	private function get_nested_option( $data, $key ) {
		$keys = explode( '.', $key );
		foreach ( $keys as $k ) {
			if ( ! isset( $data[ $k ] ) ) {
					return null;
			}
			$data = $data[ $k ];
		}
		return $data;
	}

	/**
	 * Return true if the plugin is active.
	 *
	 * @since 1.5.36
	 *
	 * @return boolean
	 */
	protected function is_plugin_active() {
		return function_exists( 'is_plugin_active' ) && is_plugin_active( $this->plugin );
	}

	/**
	 * Do not page.
	 *
	 * @since 1.5.36
	 */
	abstract public function do_not_cache();
}
