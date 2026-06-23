<?php
/**
 * Masteriyo Certificate Setup.
 *
 * @since 1.13.0
 *
 * @package Masteriyo\Addons
 * @subpackage Masteriyo\Addons\Certificate
 */

namespace Masteriyo\Addons\Certificate;

use Masteriyo\Addons\Certificate\PDF\CertificatePDF;
use Masteriyo\Addons\Certificate\PostType\Certificate;
use Masteriyo\Addons\Certificate\RestApi\Controllers\Version1\CertificatesController;
use Masteriyo\Constants;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\CourseProgressQuery;
use Masteriyo\ScriptStyle;

defined( 'ABSPATH' ) || exit;

class CertificateAddon {
	/**
	 * The single instance of the class.
	 *
	 * @since 1.13.0
	 *
	 * @var \Masteriyo\Addons\Certificate\CertificateAddon
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.13.0
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @since 1.13.0
	 *
	 * @return \Masteriyo\Addons\Certificate\CertificateAddon Instance.
	 */
	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 1.13.0
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @since 1.13.0
	 */
	public function __wakeup() {}

	/**
	 * Blocks class instance.
	 *
	 * @since 1.13.0
	 *
	 * @var Masteriyo\Addons\Certificate\Blocks
	 */
	public $blocks;

	/**
	 * Initialize the application.
	 *
	 * @since 1.13.0
	 */
	public function init() {
		$this->blocks = new Blocks();

		$this->blocks->init();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.13.0
	 */
	public function init_hooks() {
		add_action( 'init', 'masteriyo_download_certificate_fonts' );
		add_action( 'template_redirect', array( $this, 'handle_certificate_preview' ) );
		add_action( 'init', array( $this, 'handle_certificate_download' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_learn_page_scripts' ) );
		add_filter( 'default_title', array( $this, 'change_default_certificate_editor_title' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_block_editor_scripts_styles' ), 11 );
		add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'add_localization_to_admin_scripts' ) );
		add_action( 'masteriyo_new_course', array( $this, 'save_certificate_data' ), 10, 2 );
		add_action( 'masteriyo_update_course', array( $this, 'save_certificate_data' ), 10, 2 );
		add_filter( 'masteriyo_rest_response_course_data', array( $this, 'append_certificate_data' ), 10, 3 );
		add_filter( 'masteriyo_rest_course_schema', array( $this, 'add_course_certificate_schema' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_filter( 'masteriyo_register_post_types', array( $this, 'register_post_types' ) );
		add_filter( 'masteriyo_admin_submenus', array( $this, 'add_submenus' ) );
		add_action( 'masteriyo_activation', array( $this, 'clear_gutenberg_certs_cache' ) );
		add_action( 'save_post_mto-certificate', array( $this, 'clear_gutenberg_certs_cache' ) );

		add_action( 'masteriyo_single_course_sidebar_content_after_progress', array( $this, 'render_certificate_share_for_single_course_page' ) );
		add_action( 'masteriyo_template_course_inside_progress', array( $this, 'render_certificate_share_for_single_course_page' ), 1, 1 );
		add_action( 'masteriyo_single_course_minimal_sidebar_content_after_progress', array( $this, 'render_certificate_share_for_single_course_page' ) );
		// add_action( 'masteriyo_after_single_course_highlights', array( $this, 'render_certificate_share_for_single_course_page' ) );

		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'add_certificate_share_popup_modal' ) );

		add_filter( 'query_vars', array( $this, 'add_certificate_share_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_certificate_share_preview' ), 10 );
		add_action( 'masteriyo_pdfdraft_cert_email_fallback', array( $this, 'handle_pdfdraft_cert_email_fallback' ), 10, 2 );
	}

	/**
	 * Add certificate share query vars.
	 *
	 * @param array $query_vars The existing query vars.
	 *
	 * @return array The modified query vars.
	 *
	 * @since 1.13.3
	 */
	public function add_certificate_share_query_vars( $query_vars ) {

		$query_vars[] = 'username';
		$query_vars[] = 'certificate_id';
		$query_vars[] = 'course_id';

		return $query_vars;
	}

	/**
	 * Handles the preview of a certificate.
	 *
	 * @since 1.13.3
	 */
	public function handle_certificate_share_preview() {
		if ( ! isset( $_GET['username'], $_GET['certificate_id'], $_GET['course_id'] ) ) { // @phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$username             = sanitize_text_field( get_query_var( 'username' ) );
		$certificate_id       = absint( get_query_var( 'certificate_id' ) );
		$course_id            = absint( get_query_var( 'course_id' ) );
		$user                 = get_user_by( 'login', $username );
		$user_id              = $user ? $user->ID : 0;
		$is_valid_certificate = masteriyo_get_course_certificate_id( $course_id ) === $certificate_id;
		$current_user_id      = get_current_user_id();
		$is_admin             = masteriyo_is_current_user_admin();

		// phpcs:ignore Generic.CodeAnalysis.RequireExplicitBooleanOperatorPrecedence.MissingParentheses
		if ( ! ( $user_id && $user_id === $current_user_id ) && ! $is_admin || ! $is_valid_certificate ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this content.', 'learning-management-system' ) );
		}

		if ( $certificate_id && $course_id ) {
			$certificate = masteriyo_get_certificate( $certificate_id );

			if ( ! $certificate ) {
				return;
			}

			$certificate_html_content = $certificate->get_html_content();

			if ( is_wp_error( $certificate_html_content ) ) {
				return;
			}

			if ( 'pdfdraft' === $certificate->get_content_format() ) {
				$this->serve_pdfdraft_share_preview( $certificate, $course_id, $user_id );
				return;
			}

			$certificate_pdf = new CertificatePDF( $course_id, $user_id, $certificate_html_content );

			if ( ! $certificate_pdf || is_wp_error( $certificate_pdf ) ) {
				return;
			}

			$certificate_pdf->serve_preview();
		}
	}

	/**
	 * Serve a PDFDraft certificate share preview as an HTML page.
	 *
	 * @since x.x.x
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate
	 * @param int $course_id
	 * @param int $user_id
	 */
	protected function serve_pdfdraft_share_preview( $certificate, $course_id, $user_id ) {
		$rendered_html = $certificate->get_rendered_html( 'edit' );

		if ( empty( $rendered_html ) ) {
			wp_die( esc_html__( 'This certificate has not been published yet. Please contact the site administrator.', 'learning-management-system' ) );
		}

		$certificate_pdf = new CertificatePDF( $course_id, $user_id, '', $certificate->get_id() );
		$html            = preg_replace( '/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $certificate_pdf->process_html_for_download( $rendered_html ) );

		$json   = json_decode( $certificate->get_html_content(), true );
		$layout = isset( $json['settings']['layout'] ) ? $json['settings']['layout'] : array();

		$width_in  = (float) ( $layout['width'] ?? 11 );
		$height_in = (float) ( $layout['height'] ?? 8.5 );
		$unit      = $layout['unit'] ?? 'in';
		$dpi       = 96;
		$to_px     = array(
			'in' => $dpi,
			'cm' => $dpi / 2.54,
			'mm' => $dpi / 25.4,
			'px' => 1,
		);
		$mult      = $to_px[ $unit ] ?? $dpi;
		$canvas_w  = round( $width_in * $mult );
		$canvas_h  = round( $height_in * $mult );

		$preview_font_links = '';
		if ( isset( $json['pages'] ) ) {
			$generic_fonts = array( 'serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'system-ui', 'inherit', 'initial', 'unset', 'inter' );
			$families      = array();
			array_walk_recursive(
				$json['pages'],
				function( $value, $key ) use ( &$families, $generic_fonts ) {
					if ( 'fontFamily' === $key && is_string( $value ) && ! empty( $value ) ) {
						$clean = trim( $value, " \"'\t" );
						if ( ! in_array( strtolower( $clean ), $generic_fonts, true ) ) {
							$families[ $clean ] = true;
						}
					}
				}
			);
			foreach ( array_keys( $families ) as $family ) {
				$encoded             = rawurlencode( $family );
				$preview_font_links .= '<link href="https://fonts.googleapis.com/css2?family=' . esc_attr( $encoded ) . ':wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">' . "\n\t\t\t"; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
			}
		}

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		header( 'Content-Type: text/html; charset=utf-8' );
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<!DOCTYPE html>
		<html lang="<?php echo esc_attr( get_locale() ); ?>">
		<head>
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e( 'Certificate Preview', 'learning-management-system' ); ?></title>
			<link rel="preconnect" href="https://fonts.googleapis.com">
			<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"> <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- standalone HTML page, wp_enqueue_style() not applicable. ?>
			<?php echo $preview_font_links; ?>
			<style>
				<?php echo $certificate_pdf->prepare_pdfdraft_css(); ?>
				* { box-sizing: border-box; }
				html, body {
					margin: 0; padding: 0;
					width: 100%; height: 100%;
					overflow: auto;
					background: #525659;
					display: flex;
					align-items: flex-start;
					justify-content: center;
					padding: 16px;
				}
				.masteriyo-cert-wrap {
					width: <?php echo (int) $canvas_w; ?>px;
					height: <?php echo (int) $canvas_h; ?>px;
					flex-shrink: 0;
					box-shadow: 0 4px 24px rgba(0,0,0,0.5);
				}
				.masteriyo-cert-wrap > * {
					width: 100% !important;
					height: 100% !important;
				}
			</style>
		</head>
		<body>
			<div class="masteriyo-cert-wrap"><?php echo $html; ?></div>
		</body>
		</html>
		<?php
		// phpcs:enable
		die();
	}

	/**
	 * Render certificate share button in single course page.
	 *
	 * @since 1.13.3
	 *
	 * @param \Masteriyo\Models\Course $course
	 */
	public function render_certificate_share_for_single_course_page( $course ) {

		if ( ! $course ) {
			return;
		}

		$certificate_id = get_post_meta( $course->get_id(), '_certificate_enabled', true );

		if ( ! $certificate_id ) {
			return;
		}

		$single_course_enabled = masteriyo_is_certificate_enabled_for_single_course( $course->get_id() );

		if ( ! $single_course_enabled ) {
			return;
		}

		$certificate_id = get_post_meta( $course->get_id(), '_certificate_id', true );

		if ( ! $certificate_id ) {
			return;
		}

		$certificate = masteriyo_get_certificate( $certificate_id );

		if ( ! $certificate ) {
			return;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
				'status'    => array( CourseProgressStatus::COMPLETED ),
			)
		);

		$activity = current( $query->get_course_progress() );

		if ( ! $activity ) {
			return;
		}

		require MASTERIYO_CERTIFICATE_TEMPLATES . '/certificate-share.php';
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.13.3
	 *
	 * @param array $scripts Array of scripts.
	 * @return array
	 */
	public function enqueue_scripts( $scripts ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		return masteriyo_parse_args(
			$scripts,
			array(
				'masteriyo-certificate-share-single-course' => array(
					'src'      => plugin_dir_url( Constants::get( 'MASTERIYO_CERTIFICATE_BUILDER_ADDON_FILE' ) ) . 'assets/js/frontend/single-course' . $suffix . '.js',
					'context'  => 'public',
					'callback' => function() {
						return masteriyo_is_single_course_page();
					},
					'deps'     => array( 'jquery' ),
				),
			)
		);
	}

	/**
	 * Renders a popup modal with certificate preview on single course pages.
	 *
	 * @since 1.13.3
	 *
	 * @return void
	 */
	public function add_certificate_share_popup_modal() {
		if ( ! masteriyo_is_single_course_page() ) {
			return;
		}

		$course = $GLOBALS['course'];
		$course = masteriyo_get_course( $course );

		if ( ! $course ) {
			return;
		}

		$certificate_id = get_post_meta( $course->get_id(), '_certificate_id', true );

		if ( ! $certificate_id ) {
			return;
		}

		$certificate = masteriyo_get_certificate( $certificate_id );

		if ( ! $certificate ) {
			return;
		}

		$user_id = get_current_user_id();

		$user = masteriyo_get_user( $user_id );

		if ( is_wp_error( $user ) ) {
			return;
		}

		$certificate_url = array(
			'id'       => $certificate->get_id(),
			'view_url' => masteriyo_get_certificate_addon_view_url( $course, $user_id, $certificate->get_id() ),
		);

		if ( empty( $certificate_url ) ) {
			return;
		}

		require MASTERIYO_CERTIFICATE_TEMPLATES . '/certificate-share-modal.php';
	}

	/**
	 * Handle preview of a certificate.
	 *
	 * @since 1.13.0
	 */
	public function handle_certificate_preview() {
		if ( is_singular( 'mto-certificate' ) && ! masteriyo_is_current_user_admin() && ! masteriyo_is_current_user_instructor() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this content.', 'learning-management-system' ) );
		}

		$preview_id = isset( $_GET['preview_id'] ) ? absint( $_GET['preview_id'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$preview_id = is_null( $preview_id ) && isset( $_GET['p'] ) ? absint( $_GET['p'] ) : $preview_id; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( isset( $_GET['preview'] ) && $preview_id ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$certificate = masteriyo_get_certificate( $preview_id ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( is_null( $certificate ) ) {
				return;
			}

			if (
				masteriyo_is_current_user_admin() ||
				( masteriyo_is_current_user_instructor() && $certificate->get_author_id() === get_current_user_id() )
			) {
				$certificate_pdf = new CertificatePDF( 0, get_current_user_id(), $certificate->get_html_content() );
				$certificate_pdf->serve_preview();
			}
		} elseif ( is_singular( 'mto-certificate' ) ) {
			$certificate = masteriyo_get_certificate( get_queried_object_id() );

			if ( $certificate ) {
				wp_safe_redirect( $certificate->get_post_preview_link(), 302, 'learning-management-system' );
			}
		}
	}

	/**
	 * Handle certificate download.
	 *
	 * @since 1.13.0
	 */
	public function handle_certificate_download() {
		if ( isset( $_GET['masteriyo_download_certificate'] ) ) {
			if ( ! is_user_logged_in() ) {
				wp_die( esc_html__( 'You must be logged in to download certificates.', 'learning-management-system' ) );
			}
			if ( ! isset( $_GET['nonce'] ) ) {
				wp_die( esc_html__( 'Nonce is required.', 'learning-management-system' ) );
			}
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['nonce'] ) ), 'masteriyo_download_certificate' ) ) {
				wp_die( esc_html__( 'Invalid nonce. Maybe the nonce has expired.', 'learning-management-system' ) );
			}

			if ( empty( $_GET['course_id'] ) ) {
				wp_die( esc_html__( 'Invalid course ID.', 'learning-management-system' ) );
			}

			$course = masteriyo_get_course( absint( $_GET['course_id'] ) );

			if ( is_null( $course ) ) {
				wp_die( esc_html__( 'Invalid course ID.', 'learning-management-system' ) );
			}

			$certificate_id = masteriyo_get_course_certificate_id( $course->get_id() );
			$certificate    = masteriyo_get_certificate( $certificate_id );

			if ( is_null( $certificate ) ) {
				wp_die( esc_html__( 'Invalid certificate ID. The certificate may not exist.', 'learning-management-system' ) );
			}

			if ( ! masteriyo_user_has_completed_course( $course, get_current_user_id() ) ) {
				wp_die( esc_html__( 'Please complete the course to download the certificate.', 'learning-management-system' ) );
			}

			// PDFDraft certificates: generate PDF in the browser using PDFExporter.
			if ( 'pdfdraft' === $certificate->get_content_format() ) {
				$this->serve_pdfdraft_client_download( $certificate, $course );
				return;
			}

			$certificate_pdf = new CertificatePDF( $course->get_id(), get_current_user_id(), $certificate->get_html_content() );
			$certificate_pdf->serve_download();
		}
	}

	/**
	 * Serve a pdfdraft certificate download page that generates the PDF client-side.
	 *
	 * PHP resolves all merge tags in the certificate JSON. The bundled
	 * masteriyo-pdfdraftCertDownload.js passes the resolved JSON to PDFExporter
	 * to produce the PDF in the student's browser.
	 *
	 * @since x.x.x
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate
	 * @param \Masteriyo\Models\Course $course
	 */
	protected function serve_pdfdraft_client_download( $certificate, $course ) {
		$json = json_decode( $certificate->get_html_content(), true );

		if ( empty( $json ) || ! isset( $json['pages'] ) ) {
			wp_die( esc_html__( 'This certificate has not been published yet. Please contact the site administrator.', 'learning-management-system' ) );
		}

		$certificate_pdf = new CertificatePDF( $course->get_id(), get_current_user_id(), '', $certificate->get_id() );
		$resolved_json   = $certificate_pdf->resolve_pdfdraft_json_for_download( $json );

		$student  = masteriyo_get_user( get_current_user_id() );
		$filename = sanitize_file_name(
			sprintf(
				'%s-%s-certificate.pdf',
				$student && ! is_wp_error( $student ) ? $student->get_display_name() : 'student',
				$course->get_name()
			)
		);

		$script_url = masteriyo_is_production()
			? plugins_url( 'assets/js/build/masteriyo-pdfdraftCertDownload.js', MASTERIYO_PLUGIN_FILE )
			: 'http://localhost:3000/dist/masteriyo-pdfdraftCertDownload.js';

		$cert_data = array(
			'filename' => $filename,
			'json'     => $resolved_json,
		);

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		header( 'Content-Type: text/html; charset=utf-8' );
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<!DOCTYPE html>
		<html lang="<?php echo esc_attr( get_locale() ); ?>">
		<head>
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php echo esc_html( $course->get_name() ); ?> — <?php esc_html_e( 'Certificate', 'learning-management-system' ); ?></title>
			<style>
				body { margin: 0; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: Arial, sans-serif; }
				#masteriyo-cert-status { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,.1); padding: 32px 48px; text-align: center; color: #333; font-size: 16px; max-width: 480px; }
			</style>
		</head>
		<body>
			<div id="masteriyo-cert-status"><?php esc_html_e( 'Generating your certificate, please wait…', 'learning-management-system' ); ?></div>
			<script>window.masteriyo_cert_download = <?php echo wp_json_encode( $cert_data ); ?>;</script>
			<script src="<?php echo esc_url( $script_url ); ?>"></script> <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- standalone HTML page, wp_enqueue_script() not applicable. ?>
		</body>
		</html>
		<?php
		// phpcs:enable
		die();
	}

	/**
	 * Return true if the action schedule is enabled for Email.
	 *
	 * @since 1.13.0
	 *
	 * @return boolean
	 */
	public static function is_email_schedule_enabled() {
		return masteriyo_is_email_schedule_enabled();
	}

	/**
	 * Localize learn page scripts.
	 *
	 * @since 1.13.0
	 *
	 * @param array $scripts Array of scripts.
	 *
	 * @return array
	 */
	public function localize_learn_page_scripts( $scripts ) {
		global $wp;

		if ( ! masteriyo_is_learn_page() || ! $wp || ! isset( $wp->query_vars['course_name'] ) ) {
			return $scripts;
		}

		// To support different permalink structures.
		$course_name = isset( $wp->query_vars['course_name'] ) ? (string) $wp->query_vars['course_name'] : '';

		if ( preg_match( '/^\d+$/', $course_name ) ) {
			$args = array(
				'p'              => absint( $course_name ),
				'posts_per_page' => 1,
				'post_type'      => PostType::COURSE,
				'post_status'    => PostStatus::PUBLISH,
			);
		} else {
			$args = array(
				'name'           => sanitize_text_field( $course_name ),
				'posts_per_page' => 1,
				'post_type'      => PostType::COURSE,
				'post_status'    => PostStatus::PUBLISH,
			);
		}

		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			return $scripts;
		}

		$enabled = masteriyo_is_certificate_enabled_for_course( $posts[0]->ID );
		$scripts['learn']['data']['isCertificateEnabled'] = masteriyo_bool_to_string( $enabled );

		return $scripts;
	}

	/**
	 * Get the course ID by the course slug.
	 *
	 * @since 1.13.0
	 *
	 * @param string $course_slug The course slug.
	 *
	 * @return int The course ID, or 0 if not found.
	 */
	private function get_course_id_by_name( $course_slug ) {
		$courses = get_posts(
			array(
				'post_type'   => 'mto-course',
				'name'        => $course_slug,
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);

		return is_array( $courses ) ? array_shift( $courses ) : 0;
	}

	/**
	 * Change default title for certificate editor.
	 *
	 * @since 1.13.0
	 *
	 * @param string   $post_content
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	public function change_default_certificate_editor_title( $post_content, $post ) {
		if ( 'mto-certificate' === $post->post_type ) {
			return __( 'Sample Certificate', 'learning-management-system' );
		}
		return $post_content;
	}

	/**
	 * Load required scripts and styles for block editor.
	 *
	 * @since 1.13.0
	 *
	 * @return void
	 */
	public function load_block_editor_scripts_styles() {
		if ( 'toplevel_page_masteriyo' !== get_current_screen()->id ) {
			return;
		}

		global $post;

		wp_enqueue_script( 'masteriyo-certificate-blocks', plugins_url( '/assets/js/build/certificate-blocks.js', Constants::get( 'MASTERIYO_PLUGIN_FILE' ) ), ScriptStyle::get_asset_deps( 'certificate-blocks' ), MASTERIYO_VERSION, true );
		wp_enqueue_style( 'wp-edit-post' );
		wp_enqueue_style( 'wp-format-library' );
		wp_enqueue_style( 'masteriyo-blocks' );
		wp_add_inline_style( 'wp-edit-post', $this->get_certificate_fonts_css() );
		wp_add_inline_style( 'wp-edit-post', 'html.wp-toolbar { background-color: #F7FAFC; }' );
		$categories = function_exists( 'get_block_categories' ) ? get_default_block_categories() : array();

		if ( ! empty( $categories ) ) {
			array_unshift(
				$categories,
				array(
					'slug'  => 'masteriyo',
					'title' => esc_html__( 'Masteriyo LMS', 'learning-management-system' ),
				)
			);
		}

		wp_add_inline_script(
			'wp-blocks',
			sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( $categories ) ),
			'after'
		);
	}

	/**
	 * Get certificate fonts css.
	 *
	 * @since 1.13.0
	 * @return string
	 */
	private function get_certificate_fonts_css() {
		$font_urls = masteriyo_get_certificate_font_urls();
		$font_css  = '';
		$css       = '';

		foreach ( $font_urls as $font_name => $font_url ) {
			$font_css .= "@font-face { font-family: $font_name; src: url('$font_url') format('truetype') }\n";
			$css      .= '.has-' . masteriyo_camel_to_kebab( $font_name ) . "-font-family { font-family: $font_name; }\n";
		}
		return $font_css . $css;
	}

	/**
	 * Add localization data to admin scripts.
	 *
	 * @since 1.13.0
	 * @param array $localized_scripts Localized admin scripts.
	 *
	 * @return array
	 */
	public function add_localization_to_admin_scripts( $localized_scripts ) {
		$editor_settings = function_exists( 'get_block_editor_settings' ) && masteriyo_is_admin_page() ? get_block_editor_settings( array(), new \WP_Block_Editor_Context() ) : array();
		masteriyo_array_set( $editor_settings, '__experimentalFeatures.typography.fontFamilies.theme', $this->get_certificate_editor_typography_config() );
		return masteriyo_parse_args(
			$localized_scripts,
			array(
				'backend' => array(
					'data' => array(
						'allowedBlockTypes'        => array(
							'core/paragraph',
							'core/image',
							'core/heading',
							'core/separator',
							'core/spacer',
							'core/columns',
							'core/column',
							'core/quote',
							'core/code',
							'core/shortcode',
							'core/group',
							'core/list',
							'core/list-item',
							'core/html',
							'core/audio',
							'core/freeform',
							'core/buttons',
							'core/button',
							'masteriyo/certificate',
							'masteriyo/course-title',
							'masteriyo/student-name',
							'masteriyo/course-completion-date',
						),
						'editorStyles'             => function_exists( 'get_block_editor_theme_styles' ) ? get_block_editor_theme_styles() : (object) array(),
						'editorSettings'           => $editor_settings,
						'certificate_samples'      => masteriyo_get_certificate_templates(),
						'pdfdraft_assets_base_url' => plugins_url( 'addons/certificate/assets/', Constants::get( 'MASTERIYO_PLUGIN_FILE' ) ),
						'hasGutenbergCerts'        => $this->has_gutenberg_certificates(),
					),
				),
			)
		);
	}

	/**
	 * Editor typography config.
	 * @since 1.13.0
	 * @return array
	 */
	private function get_certificate_editor_typography_config() {
		$font_urls      = masteriyo_get_certificate_font_urls();
		$config         = array();
		$font_names_map = array(
			'Cinzel'              => 'Cinzel',
			'DejaVuSansCondensed' => 'DejaVu Sans Condensed',
			'DMSans'              => 'DM Sans',
			'GreatVibes'          => 'Great Vibes',
			'GrenzeGotisch'       => 'Grenze Gotisch',
			'Lora'                => 'Lora',
			'Poppins'             => 'Poppins',
			'Roboto'              => 'Roboto',
			'AbhayaLibre'         => 'Abhaya Libre',
			'AdineKirnberg'       => 'Adine Kirnberg',
			'AlexBrush'           => 'Alex Brush',
			'Allura'              => 'Allura',
		);

		foreach ( $font_urls as $font_name => $font_url ) {
			$config[] = array(
				'fontFamily' => $font_name,
				'name'       => $font_names_map[ $font_name ] ?? $font_name,
				'slug'       => $font_name,
			);
		}
		return $config;
	}

	/**
	 * Save certificate ID data.
	 *
	 * @since 1.13.0
	 *
	 * @param integer $id
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	public function save_certificate_data( $id, $course ) {
		$request = masteriyo_current_http_request();

		if ( ! isset( $request['certificate_enabled'] ) ) {
			return;
		}

		update_post_meta( $id, '_certificate_enabled', masteriyo_string_to_bool( $request['certificate_enabled'] ) );

		if ( isset( $request['certificate_id'], $request['certificate_id']['value'] ) ) {
			update_post_meta( $id, '_certificate_id', absint( $request['certificate_id']['value'] ) );
		}

		if ( isset( $request['certificate_single_course_enabled'] ) ) {
			update_post_meta( $id, '_certificate_single_course_enabled', masteriyo_array_get( $request, 'certificate_single_course_enabled', false ) );
		}

	}

	/**
	 * Append certificate data to course response.
	 *
	 * @since 1.13.0
	 *
	 *  @param array                                                    $data Course data.
	 * @param Masteriyo\Models\Course                                  $course Course object.
	 * @param string                                                   $context What the value is for. Valid values are view and edit.
	 *  @param Masteriyo\RestApi\Controllers\Version1\CoursesController $controller REST courses controller object.t.
	 *
	 * @return \WP_REST_Response
	 */
	public function append_certificate_data( $data, $course, $request ) {
		$certificate_id   = masteriyo_get_course_certificate_id( $course->get_id() );
		$certificate_name = $certificate_id > 0 ? '#' . $certificate_id : '';
		$certificate      = masteriyo_get_certificate( $certificate_id );

		if ( $certificate ) {
			$certificate_name = $certificate->get_name();
		}

		$data['certificate'] = array(
			'id'                    => $certificate_id,
			'name'                  => $certificate_name,
			'enabled'               => masteriyo_is_certificate_enabled_for_course( $course->get_id() ),
			'single_course_enabled' => masteriyo_is_certificate_enabled_for_single_course( $course->get_id() ),
		);

		return $data;
	}

	/**
	 * Add course certificate fields to course schema.
	 *
	 * @since 1.13.0
	 *
	 * @param array $schema
	 * @return array
	 */
	public function add_course_certificate_schema( $schema ) {
		$schema = wp_parse_args(
			$schema,
			array(
				'certificate' => array(
					'description' => __( 'Course certificate setting', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'                    => array(
								'description' => __( 'Course certificate ID', 'learning-management-system' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'enable'                => array(
								'description' => __( 'Enable course certificate', 'learning-management-system' ),
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view', 'edit' ),
							),
							'email_enabled'         => array(
								'description' => __( 'Attach certificate to email after course completion.', 'learning-management-system' ),
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view', 'edit' ),
							),
							'single_course_enabled' => array(
								'description' => __( 'Display certificate in single course page after course completion.', 'learning-management-system' ),
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			)
		);
			return $schema;
	}

	/**
	 * Register rest routes.
	 *
	 * @since 1.13.0
	 */
	public function register_rest_routes() {
		$controller = masteriyo( CertificatesController::class );

		if ( $controller ) {
			$controller->register_routes();
		}

		register_rest_route(
			'masteriyo/pro/v1',
			'/certificate-pdf-data',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_certificate_pdf_data' ),
				'permission_callback' => function() {
					return is_user_logged_in();
				},
				'args'                => array(
					'course_id' => array(
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'masteriyo/pro/v1',
			'/certificate-pdf-email',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_upload_certificate_pdf_email' ),
				'permission_callback' => function() {
					return is_user_logged_in();
				},
				'args'                => array(
					'course_id'  => array(
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
					'pdf_base64' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * REST handler: return the resolved PDFDraft certificate JSON for client-side PDF generation.
	 *
	 * @since x.x.x
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_get_certificate_pdf_data( \WP_REST_Request $request ) {
		$course_id = (int) $request->get_param( 'course_id' );
		$user_id   = get_current_user_id();

		$certificate_id = masteriyo_get_course_certificate_id( $course_id );
		if ( ! $certificate_id ) {
			return new \WP_Error( 'no_certificate', __( 'No certificate for this course.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$certificate = masteriyo_get_certificate( $certificate_id );
		if ( ! $certificate || is_wp_error( $certificate ) || 'pdfdraft' !== $certificate->get_content_format() ) {
			return new \WP_Error( 'not_pdfdraft', __( 'Certificate is not a PDFDraft certificate.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		$course = masteriyo_get_course( $course_id );
		if ( ! $course ) {
			return new \WP_Error( 'no_course', __( 'Course not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		if ( ! masteriyo_is_current_user_admin() && ! masteriyo_is_current_user_instructor() ) {
			if ( ! masteriyo_user_has_completed_course( $course, $user_id ) ) {
				return new \WP_Error( 'not_completed', __( 'You must complete the course to download its certificate.', 'learning-management-system' ), array( 'status' => 403 ) );
			}
		}

		$raw_json = json_decode( $certificate->get_html_content(), true );
		if ( empty( $raw_json ) || ! isset( $raw_json['pages'] ) ) {
			return new \WP_Error( 'invalid_json', __( 'Certificate JSON could not be resolved.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		$pdf  = new CertificatePDF( $course_id, $user_id, '', $certificate_id );
		$json = $pdf->resolve_pdfdraft_json_for_download( $raw_json );

		$student   = masteriyo_get_user( $user_id );
		$full_name = $student ? trim( $student->get_first_name() . ' ' . $student->get_last_name() ) : '';
		if ( ! $full_name && $student ) {
			$full_name = $student->get_display_name();
		}
		$filename = sanitize_file_name(
			sprintf( '%s - %s.pdf', $course->get_name(), $full_name ? $full_name : __( 'Certificate', 'learning-management-system' ) )
		);

		return rest_ensure_response(
			array(
				'filename' => $filename,
				'json'     => $json,
			)
		);
	}

	/**
	 * REST handler: receive base64-encoded PDF from browser, store it, and send the completion email with attachment.
	 *
	 * @since x.x.x
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_upload_certificate_pdf_email( \WP_REST_Request $request ) {
		$course_id = (int) $request->get_param( 'course_id' );
		$user_id   = get_current_user_id();

		$transient_key = 'masteriyo_pdfdraft_cert_email_' . $course_id . '_' . $user_id;
		$pending       = get_transient( $transient_key );

		if ( ! is_array( $pending ) ) {
			return new \WP_Error( 'no_pending_email', __( 'No pending certificate email for this course.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$pdf_data = base64_decode( $request->get_param( 'pdf_base64' ), true );
		if ( ! $pdf_data ) {
			return new \WP_Error( 'invalid_pdf', __( 'Invalid PDF data.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		if ( 0 !== strncmp( $pdf_data, '%PDF-', 5 ) ) {
			return new \WP_Error( 'invalid_pdf', __( 'Uploaded file is not a valid PDF.', 'learning-management-system' ), array( 'status' => 400 ) );
		}
		if ( strlen( $pdf_data ) > 20 * MB_IN_BYTES ) {
			return new \WP_Error( 'pdf_too_large', __( 'The certificate PDF exceeds the maximum allowed size.', 'learning-management-system' ), array( 'status' => 400 ) );
		}

		$upload_dir = wp_upload_dir();
		$cert_dir   = trailingslashit( $upload_dir['basedir'] ) . 'masteriyo/temp-certs/';
		wp_mkdir_p( $cert_dir );

		$pdf_path = $cert_dir . 'cert-' . $course_id . '-' . $user_id . '-' . time() . '-' . wp_generate_password( 8, false ) . '.pdf';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $pdf_path, $pdf_data ) ) {
			return new \WP_Error( 'save_failed', __( 'Could not save PDF file.', 'learning-management-system' ), array( 'status' => 500 ) );
		}

		// Update transient with the PDF path so trigger() can find it.
		set_transient( $transient_key, array_merge( $pending, array( 'pdf_path' => $pdf_path ) ), 10 * MINUTE_IN_SECONDS );

		// Cancel the fallback cron — the PDF is ready.
		$scheduled = wp_next_scheduled( 'masteriyo_pdfdraft_cert_email_fallback', array( $course_id, $user_id ) );
		if ( $scheduled ) {
			wp_unschedule_event( $scheduled, 'masteriyo_pdfdraft_cert_email_fallback', array( $course_id, $user_id ) );
		}

		// Re-trigger the completion email — trigger() will find the PDF path in the transient.
		$course_progress = masteriyo_get_course_progress_by_user_and_course( $user_id, $course_id );
		if ( ! $course_progress ) {
			delete_transient( $transient_key );
			@unlink( $pdf_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return new \WP_Error( 'no_progress', __( 'Course progress not found.', 'learning-management-system' ), array( 'status' => 404 ) );
		}

		$email = new \Masteriyo\Emails\Student\CourseCompletionEmailToStudent();
		if ( $email->is_enabled() ) {
			$email->trigger( $course_progress );
		}

		// Safety: clean up temp file if trigger() did not delete it.
		if ( file_exists( $pdf_path ) ) {
			@unlink( $pdf_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Fallback cron: send the completion email without a PDF attachment if the browser
	 * never uploaded the PDF within the deferred window.
	 *
	 * @since x.x.x
	 *
	 * @param int $course_id Course ID.
	 * @param int $user_id   Student user ID.
	 */
	public function handle_pdfdraft_cert_email_fallback( $course_id, $user_id ) {
		$transient_key = 'masteriyo_pdfdraft_cert_email_' . $course_id . '_' . $user_id;

		if ( ! get_transient( $transient_key ) ) {
			return; // Email already sent by the browser-upload path.
		}

		delete_transient( $transient_key );

		$course_progress = masteriyo_get_course_progress_by_user_and_course( $user_id, $course_id );
		if ( ! $course_progress ) {
			return;
		}

		$email = new \Masteriyo\Emails\Student\CourseCompletionEmailToStudent();
		if ( ! $email->is_enabled() ) {
			return;
		}

		// Send without deferring again — bypasses the defer logic.
		add_filter( 'masteriyo_defer_pdfdraft_cert_email', '__return_false' );
		$email->trigger( $course_progress );
		remove_filter( 'masteriyo_defer_pdfdraft_cert_email', '__return_false' );
	}

	/**
	 * Register post types.
	 *
	 * @since 1.13.0
	 *
	 * @param string[] $post_types
	 *
	 * @return string[]
	 */
	public function register_post_types( $post_types ) {
		$post_types['certificate'] = Certificate::class;

		return $post_types;
	}

	/**
	 * Add admin submenus.
	 *
	 * @since 1.13.0
	 *
	 * @param array $submenus
	 *
	 * @return array
	 */
	public function add_submenus( $submenus ) {
		if ( $this->has_gutenberg_certificates() ) {
			return masteriyo_parse_args(
				$submenus,
				array(
					'certificates'    => array(
						'page_title' => esc_html__( 'Certificates', 'learning-management-system' ),
						'menu_title' => esc_html__( 'Certificates', 'learning-management-system' ),
						'position'   => 40,
						'capability' => 'edit_certificates',
					),
					'certificates-v2' => array(
						'page_title' => esc_html__( 'Certificate V2', 'learning-management-system' ),
						'menu_title' => '↳ ' . esc_html__( 'Certificate V2', 'learning-management-system' ),
						'position'   => 41,
						'capability' => 'edit_certificates',
						'hide'       => true,
					),
				)
			);
		}

		return masteriyo_parse_args(
			$submenus,
			array(
				'certificates-v2' => array(
					'page_title' => esc_html__( 'Certificates', 'learning-management-system' ),
					'menu_title' => esc_html__( 'Certificates', 'learning-management-system' ),
					'position'   => 40,
					'capability' => 'edit_certificates',
				),
			)
		);
	}

	/**
	 * Invalidate the cached Gutenberg-certificate flag.
	 *
	 * @since x.x.x
	 */
	public function clear_gutenberg_certs_cache() {
		delete_transient( 'masteriyo_has_gutenberg_certs' );
	}

	/**
	 * Check whether any certificate uses the old Gutenberg format.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	private function has_gutenberg_certificates() {
		$cached = get_transient( 'masteriyo_has_gutenberg_certs' );

		if ( false !== $cached ) {
			return (bool) $cached;
		}

		$posts = get_posts(
			array(
				'post_type'      => 'mto-certificate',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => '_masteriyo_content_format',
						'value'   => 'gutenberg',
						'compare' => '=',
					),
					array(
						'key'     => '_masteriyo_content_format',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		$result = ! empty( $posts );
		set_transient( 'masteriyo_has_gutenberg_certs', $result ? 1 : 0, HOUR_IN_SECONDS );

		return $result;
	}
}
