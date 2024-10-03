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

		add_action( 'masteriyo_after_single_course_highlights', array( $this, 'render_certificate_share_for_single_course_page' ) );
		// add_action( 'masteriyo_after_single_course_highlights', array( $this, 'render_certificate_share_for_single_course_page' ) );

		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'add_certificate_share_popup_modal' ) );

		add_filter( 'query_vars', array( $this, 'add_certificate_share_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_certificate_share_preview' ), 10 );
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
		$username       = sanitize_text_field( get_query_var( 'username' ) );
		$certificate_id = absint( get_query_var( 'certificate_id' ) );
		$course_id      = absint( get_query_var( 'course_id' ) );

		$user = masteriyo_get_user_by_username_certificate( $username );

		if ( is_wp_error( $user ) ) {
			return;
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

			$certificate_pdf = new CertificatePDF( $course_id, $user->get_id(), $certificate_html_content );

			if ( ! $certificate_pdf || is_wp_error( $certificate_pdf ) ) {
				return;
			}

			$certificate_pdf->serve_preview();
		}
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
			if ( ! wp_verify_nonce( $_GET['nonce'], 'masteriyo_download_certificate' ) ) {
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

			$certificate_pdf = new CertificatePDF( $course->get_id(), get_current_user_id(), $certificate->get_html_content() );
			$certificate_pdf->serve_download();
		}
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

		$course_slug = sanitize_text_field( $wp->query_vars['course_name'] );

		$course_id = $this->get_course_id_by_name( $course_slug );

		if ( ! $course_id ) {
			return $scripts;
		}

		$enabled = masteriyo_is_certificate_enabled_for_course( $course_id );
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
		wp_add_inline_script(
			'wp-blocks',
			sprintf( 'wp.blocks.setCategories( %s );', wp_json_encode( get_block_categories( $post ) ) ),
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
						'allowedBlockTypes'   => array(
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
						'editorStyles'        => function_exists( 'get_block_editor_theme_styles' ) ? get_block_editor_theme_styles() : (object) array(),
						'editorSettings'      => $editor_settings,
						'certificate_samples' => masteriyo_get_certificate_templates(),
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
		return masteriyo_parse_args(
			$submenus,
			array(
				'certificates' => array(
					'page_title' => esc_html__( 'Certificates', 'learning-management-system' ),
					'menu_title' => esc_html__( 'Certificates', 'learning-management-system' ),
					'position'   => 75,
					'capability' => 'edit_certificates',
				),
			)
		);
	}
}
