<?php
/**
 * H5P compatibility for the Masteriyo learn page.
 *
 * Provides a standalone render endpoint (?masteriyo_h5p=<id>) that boots H5P in a
 * minimal page for iframe embedding, and overrides the [h5p] shortcode during REST
 * so the learn page receives an iframe while editors keep the raw shortcode.
 *
 * @since x.x.x
 */

namespace Masteriyo\Addons\H5P\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * H5PRenderer class.
 */
class H5PRenderer {

	/**
	 * Query var for the render endpoint.
	 *
	 * @var string
	 */
	const QUERY_VAR = 'masteriyo_h5p';

	/**
	 * Request header the interactive (learn page) app sends on every REST call.
	 *
	 * @var string
	 */
	const APP_HEADER = 'X-Masteriyo-App';

	/**
	 * Original [h5p] shortcode callback, captured before we override it.
	 *
	 * @since x.x.x
	 *
	 * @var callable|null
	 */
	protected $original_h5p_shortcode = null;

	/**
	 * Current REST request, captured via rest_pre_dispatch.
	 *
	 * @since x.x.x
	 *
	 * @var \WP_REST_Request|null
	 */
	protected $current_rest_request = null;

	/**
	 * Initialize hooks.
	 *
	 * @since x.x.x
	 */
	public function init() {
		add_filter( 'query_vars', array( $this, 'add_query_var' ) );
		// Priority -1: disable the admin bar before core's _wp_admin_bar_init (priority 0) so its 32px bump CSS is never enqueued.
		add_action( 'template_redirect', array( $this, 'maybe_disable_admin_bar' ), -1 );
		add_action( 'template_redirect', array( $this, 'maybe_render_h5p' ) );
		add_action( 'rest_api_init', array( $this, 'hook_rest_content_filters' ) );
	}

	/**
	 * Register the custom query var.
	 *
	 * @since x.x.x
	 *
	 * @param string[] $vars Existing query vars.
	 * @return string[]
	 */
	public function add_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Override the [h5p] shortcode during REST requests.
	 *
	 * @since x.x.x
	 */
	public function hook_rest_content_filters() {
		add_filter( 'rest_pre_dispatch', array( $this, 'capture_rest_request' ), 10, 3 );

		if ( $this->is_h5p_shortcode_available() ) {
			global $shortcode_tags;
			$this->original_h5p_shortcode = $shortcode_tags['h5p'] ?? null;

			remove_shortcode( 'h5p' );
			add_shortcode( 'h5p', array( $this, 'maybe_render_h5p_shortcode_as_iframe' ) );
		}
	}

	/**
	 * Capture the current REST request for the shortcode handler.
	 *
	 * @since x.x.x
	 *
	 * @param mixed            $result   Pre-emptive response.
	 * @param \WP_REST_Server  $_server  REST server instance.
	 * @param \WP_REST_Request $request  Current REST request.
	 * @return mixed Unchanged $result.
	 */
	public function capture_rest_request( $result, $_server, $request ) {
		$this->current_rest_request = $request;
		return $result;
	}

	/**
	 * Resolve [h5p]: iframe for the learn page, raw shortcode for editors.
	 *
	 * @since x.x.x
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function maybe_render_h5p_shortcode_as_iframe( $atts ) {
		// Outside Masteriyo REST, defer to H5P so other plugins are unaffected.
		if ( ! $this->is_masteriyo_rest_request() ) {
			return $this->original_h5p_shortcode ? call_user_func( $this->original_h5p_shortcode, $atts ) : '';
		}

		if ( $this->is_learn_page_request() ) {
			return $this->render_h5p_shortcode_as_iframe( $atts );
		}

		$atts   = shortcode_atts( array( 'id' => 0 ), $atts, 'h5p' );
		$h5p_id = absint( $atts['id'] );

		return $h5p_id ? sprintf( '[h5p id="%d"]', $h5p_id ) : '';
	}

	/**
	 * Whether the current request targets a Masteriyo REST route.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	protected function is_masteriyo_rest_request() {
		return $this->current_rest_request && false !== strpos( $this->current_rest_request->get_route(), '/masteriyo/' );
	}

	/**
	 * Whether the request originates from the interactive learn page.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	protected function is_learn_page_request() {
		return $this->current_rest_request && 'interactive' === $this->current_rest_request->get_header( self::APP_HEADER );
	}

	/**
	 * Render [h5p id="N"] as the learn-page iframe.
	 *
	 * @since x.x.x
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_h5p_shortcode_as_iframe( $atts ) {
		$atts   = shortcode_atts( array( 'id' => 0 ), $atts, 'h5p' );
		$h5p_id = absint( $atts['id'] );

		return $h5p_id ? $this->build_h5p_iframe( $h5p_id ) : '';
	}

	/**
	 * Build the learn-page iframe markup (height is resized by the parent).
	 *
	 * @since x.x.x
	 *
	 * @param int $h5p_id H5P content ID.
	 * @return string
	 */
	protected function build_h5p_iframe( $h5p_id ) {
		$h5p_id = absint( $h5p_id );

		if ( ! $h5p_id ) {
			return '';
		}

		$src = add_query_arg( self::QUERY_VAR, $h5p_id, home_url( '/' ) );

		return sprintf(
			'<div class="masteriyo-h5p-iframe-wrap" data-h5p-id="%1$d" style="position:relative;width:100%%;overflow:hidden;">'
			. '<iframe src="%2$s" '
			. 'allow="fullscreen *; autoplay *" '
			. 'style="width:100%%;border:none;display:block;" '
			. 'height="400" '
			. 'title="H5P Content">'
			. '</iframe>'
			. '</div>',
			$h5p_id,
			esc_url( $src )
		);
	}

	/**
	 * Disable the admin bar on the render endpoint so its 32px offset never clips the iframe.
	 *
	 * @since x.x.x
	 */
	public function maybe_disable_admin_bar() {
		$h5p_id = get_query_var( self::QUERY_VAR );

		if ( $h5p_id && is_numeric( $h5p_id ) ) {
			show_admin_bar( false );
		}
	}

	/**
	 * Render a minimal HTML page with the H5P content when the query var is set.
	 *
	 * @since x.x.x
	 */
	public function maybe_render_h5p() {
		$h5p_id = get_query_var( self::QUERY_VAR );

		if ( ! $h5p_id || ! is_numeric( $h5p_id ) ) {
			return;
		}

		$h5p_id = absint( $h5p_id );

		add_filter( 'qm/process', '__return_false', 9999 );

		if ( ! $this->is_h5p_shortcode_available() ) {
			wp_die( esc_html__( 'H5P plugin is not active.', 'learning-management-system' ), '', array( 'response' => 404 ) );
		}

		// Priority 20: run the shortcode after H5P registers 'h5p-core' (priority 10).
		$shortcode_output = '';
		add_action(
			'wp_enqueue_scripts',
			function() use ( $h5p_id, &$shortcode_output ) {
				$shortcode_output = do_shortcode( '[h5p id="' . $h5p_id . '"]' );
			},
			20
		);

		// Priority 25: enqueue the height/xAPI bridge after H5P's scripts are registered.
		add_action(
			'wp_enqueue_scripts',
			function() use ( $h5p_id ) {
				$deps = wp_script_is( 'h5p-core', 'registered' ) ? array( 'h5p-core' ) : array();

				wp_enqueue_script(
					'masteriyo-h5p-renderer',
					plugins_url( 'assets/js/h5p-renderer.js', MASTERIYO_H5P_ADDON_FILE ),
					$deps,
					MASTERIYO_VERSION,
					true
				);
				wp_localize_script(
					'masteriyo-h5p-renderer',
					'masteriyoH5PRenderer',
					array(
						'h5pId' => $h5p_id,
					)
				);
			},
			25
		);

		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );

		ob_start();
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<style>
				* { box-sizing: border-box; margin: 0; padding: 0; }
				body { background: transparent; overflow: hidden; }
				.masteriyo-h5p-wrap { width: 100%; }
				.masteriyo-h5p-wrap .h5p-container { width: 100% !important; }
				</style>
			<?php wp_head(); ?>
		</head>
		<body>
			<?php
			if ( empty( trim( $shortcode_output ) ) ) {
				while ( ob_get_level() > 0 ) {
					ob_end_clean();
				}
				wp_die( esc_html__( 'H5P content not found.', 'learning-management-system' ), '', array( 'response' => 404 ) );
			}
			?>
			<div class="masteriyo-h5p-wrap">
				<?php echo $shortcode_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}
		exit;
	}

	/**
	 * Whether the [h5p] shortcode is registered (by H5P, H5P.com or a compatible plugin).
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	protected function is_h5p_shortcode_available() {
		return shortcode_exists( 'h5p' );
	}
}
