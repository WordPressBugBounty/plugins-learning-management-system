<?php
/**
 * Certificate PDF builder class.
 *
 * @since 1.13.0
 */

namespace Masteriyo\Addons\Certificate\PDF;

use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;
use Mpdf\Output\Destination;
use Masteriyo\Addons\Certificate\Models\Setting;

class CertificatePDF {
	/**
	 * The Mpdf instance.
	 *
	 * @since 1.13.0
	 *
	 * @var \Mpdf\Mpdf
	 */
	public $mpdf;

	/**
	 * Course ID.
	 *
	 * @since 1.13.0
	 *
	 * @var integer
	 */
	protected $course_id;

	/**
	 * Student ID.
	 *
	 * @since 1.13.0
	 *
	 * @var integer
	 */
	protected $student_id;

	/**
	 * Certificate template html.
	 *
	 * @since 1.13.0
	 *
	 * @var string
	 */
	protected $template;

	/**
	 * Contains all the css.
	 *
	 * @var array
	 */
	protected $styles = array();

	/**
	 * Contain the html blocks
	 *
	 * @var array
	 */
	protected $html = array();

	/**
	 * True if the certificate preview is being generated. Otherwise false.
	 *
	 * @since 1.13.0
	 *
	 * @var boolean
	 */
	protected $preview = false;

	/**
	 * Constructor.
	 *
	 * @since 1.13.0
	 *
	 * @param integer $course_id
	 * @param integer $student_id
	 * @param string $template
	 */
	public function __construct( $course_id, $student_id, $template ) {
		$this->set_course_id( $course_id );
		$this->set_student_id( $student_id );
		$this->set_template( $template );
	}

	/**
	 * Initialize mpdf.
	 *
	 * @since 1.13.0
	 */
	public function init_mpdf() {
		if ( $this->mpdf instanceof Mpdf ) {
			return;
		}

		$upload_dir = wp_upload_dir();

		$font_dirs   = ( new \Mpdf\Config\ConfigVariables() )->getDefaults()['fontDir'];
		$font_dirs[] = $upload_dir['basedir'] . '/masteriyo/certificate-fonts';

		$default_font_config = ( new \Mpdf\Config\FontVariables() )->getDefaults();
		$fontdata            = $default_font_config['fontdata'];

		$this->mpdf = new Mpdf(
			array(
				'tempDir'          => masteriyo_get_temp_dir() . '/mpdf',
				'fontDir'          => $font_dirs,
				'margin_left'      => 0,
				'margin_right'     => 0,
				'margin_top'       => 0,
				'margin_bottom'    => 0,
				'default_font'     => 'Arial, sans-serif',
				'autoScriptToLang' => true,
				'autoLangToFont'   => true,
				'fontdata'         => $fontdata + array(
					'cinzel'              => array(
						'R' => 'Cinzel-VariableFont_wght.ttf',
					),
					'dejavusanscondensed' => array(
						'R' => 'DejaVuSansCondensed.ttf',
						'B' => 'DejaVuSansCondensed-Bold.ttf',
					),
					'dmsans'              => array(
						'R' => 'DMSans-Regular.ttf',
						'B' => 'DMSans-Bold.ttf',
						'I' => 'DMSans-Italic.ttf',
					),
					'greatvibes'          => array(
						'R' => 'GreatVibes-Regular.ttf',
					),
					'grenzegotisch'       => array(
						'R' => 'GrenzeGotisch-VariableFont_wght.ttf',
					),
					'librebaskerville'    => array(
						'R' => 'LibreBaskerville-Regular.ttf',
						'B' => 'LibreBaskerville-Bold.ttf',
						'I' => 'LibreBaskerville-Italic.ttf',
					),
					'lora'                => array(
						'R' => 'Lora-VariableFont_wght.ttf',
						'I' => 'Lora-Italic-VariableFont_wght.ttf',
					),
					'poppins'             => array(
						'R' => 'Poppins-Regular.ttf',
						'B' => 'Poppins-Bold.ttf',
						'I' => 'Poppins-Italic.ttf',
					),
					'roboto'              => array(
						'R' => 'Roboto-Regular.ttf',
						'B' => 'Roboto-Bold.ttf',
						'I' => 'Roboto-Italic.ttf',
					),
					'abhayalibre'         => array(
						'R' => 'AbhayaLibre-Regular.ttf',
						'B' => 'AbhayaLibre-Bold.ttf',
					),
					'adinekirnberg'       => array(
						'R' => 'AdineKirnberg.ttf',
					),
					'alexbrush'           => array(
						'R' => 'AlexBrush-Regular.ttf',
					),
					'allura'              => array(
						'R' => 'Allura-Regular.ttf',
					),
				),
			)
		);
		$this->mpdf->setMBencoding( 'UTF-8' );

		/**
		 * Filters mpdf debug mode for making certificate PDF file.
		 *
		 * @since 1.13.0
		 *
		 * @param boolean $bool
		 * @param \Mpdf\Mpdf $mpdf
		 */
		$this->mpdf->debug = apply_filters( 'masteriyo_certificate_mpdf_debug_mode', false, $this->mpdf );

		/**
		 * Filters mpdf image debug mode for making certificate PDF file.
		 *
		 * @since 1.13.0
		 *
		 * @param boolean $bool
		 * @param \Mpdf\Mpdf $mpdf
		 */
		$this->mpdf->showImageErrors = apply_filters( 'masteriyo_certificate_mpdf_show_image_errors', false, $this->mpdf );

		/**
		 * Filters Mpdf class instance used for making certificate PDF file.
		 *
		 * @since 1.13.0
		 *
		 * @param boolean $bool
		 * @param \Mpdf\Mpdf $mpdf
		 */
		$this->mpdf = apply_filters( 'masteriyo_certificate_builder_mpdf', $this->mpdf );
	}

	/**
	 * Prepare PDF.
	 *
	 * @since 1.13.0
	 *
	 * @since 1.13.0 Added $is_preview argument.
	 *
	 * @param string $template The certificate template.
	 * @param boolean $is_preview
	 *
	 * @return true|\WP_Error
	 */
	public function prepare_pdf( $is_preview = false ) {
		$this->init_mpdf();
		$this->set_is_preview( $is_preview );

		/**
		 * Added setting to enable HTTPS instead of HTTP for certificate image URLs.
		 *
		 * @since 1.13.0
		 */
		$use_ssl_verified = masteriyo_bool_to_string( Setting::get( 'use_ssl_verified' ) );

		if ( 'yes' === $use_ssl_verified ) {
			$template = str_replace( 'http:', 'https:', $this->get_template() );
		} else {
			$template = str_replace( 'http:', 'https:', $this->get_template() );
		}

		$template = masteriyo_process_certificate_template_smart_tags( $template, $this->get_course_id(), $this->get_student_id(), $is_preview );
		$blocks   = parse_blocks( $template );

		if ( empty( $blocks ) || 'masteriyo/certificate' !== $blocks[0]['blockName'] ) {
			return new \WP_Error(
				'masteriyo_invalid_certificate_template',
				__( 'Invalid certificate template.', 'learning-management-system' )
			);
		}

		$block_builder = masteriyo_make_block_builder_instance( $blocks[0], $this );

		$this->add_html( $block_builder->build() );
		$this->mpdf->WriteHTML( $this->prepare_css(), HTMLParserMode::HEADER_CSS );
		$this->mpdf->WriteHTML( $this->prepare_fonts_css(), HTMLParserMode::HEADER_CSS );
		$this->mpdf->WriteHTML( $this->prepare_html() );
	}

	/**
	 * Serve certificate preview.
	 *
	 * @since 1.13.0
	 */
	public function serve_preview() {
		$result = $this->prepare_pdf( true );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		if ( masteriyo_is_certificate_html_inspection_mode() ) {
			printf( '<body><style>%s%s</style>%s</body>', $this->prepare_css(), $this->prepare_fonts_css(), $this->prepare_html() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			die;
		}

		$this->mpdf->Output( $this->make_filename( true ), Destination::INLINE );
		die;
	}

	/**
	 * Serve certificate download.
	 *
	 * @since 1.13.0
	 */
	public function serve_download() {
		$result = $this->prepare_pdf( false );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}
		$this->mpdf->Output( $this->make_filename(), Destination::DOWNLOAD );
		die;
	}

	/**
	 * Make certificate filename.
	 *
	 * @since 1.13.0
	 *
	 * @param boolean $is_preview
	 *
	 * @return string
	 */
	public function make_filename( $is_preview = false ) {
		$course   = masteriyo_get_course( $this->get_course_id() );
		$student  = masteriyo_get_user( $this->get_student_id() );
		$filename = 'certificate-' . get_bloginfo( 'name' );

		if ( ! is_null( $course ) && ! is_null( $student ) && ! is_wp_error( $student ) ) {
			$filename = sprintf( '%s - %s - %s', $student->get_username(), $course->get_name(), get_bloginfo( 'name' ) );
		}

		if ( $is_preview ) {
			$filename .= ' - preview';
		}

		$filename = sanitize_file_name( $filename . '.pdf' );

		/**
		 * Filters certificate PDF filename.
		 *
		 * @since 1.13.0
		 *
		 * @param string $filename
		 * @param \Masteriyo\Addons\Certificate\PDF\CertificatePDF $certificate_pdf_instance
		 * @param boolean $is_preview
		 */
		return apply_filters( 'masteriyo_certificate_pdf_filename', $filename, $this, $is_preview );
	}

	/**
	 * Add a CSS statement.
	 *
	 * @since 1.13.0
	 *
	 * @param string $selector CSS selector.
	 * @param string $css_property The CSS property name.
	 * @param string $value The CSS property value.
	 */
	public function add_style( $selector, $css_property = null, $value = null ) {
		if ( ! isset( $this->styles[ $selector ] ) ) {
			$this->styles[ $selector ] = array();
		}
		$this->styles[ $selector ][ $css_property ] = $value;
	}

	/**
	 * Prepare CSS.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function prepare_css() {
		$css = array();

		foreach ( $this->styles as $selector => $style ) {
			$css[] = $selector . ' {';

			foreach ( $style as $key => $val ) {
				$css [] = sprintf( '%s: %s;', $key, $val );
			}
			$css [] = '}';
		}

		$css [] = masteriyo_get_filesystem()->get_contents( MASTERIYO_CERTIFICATE_ASSETS . '/css/gutenberg-styles.css' );

		return implode( PHP_EOL, $css );
	}

	/**
	 * Prepare fonts css.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function prepare_fonts_css() {
		$css = '';
		foreach ( array_keys( masteriyo_get_certificate_font_urls() ) as $font_family ) {
			$css .= '.has-' . masteriyo_camel_to_kebab( $font_family ) . '-font-family {font-family: ' . strtolower( $font_family ) . ';}';
		}
		return $css;
	}

	/**
	 * Add HTML markup.
	 *
	 * @since 1.13.0
	 *
	 * @param string $html
	 */
	public function add_html( $html ) {
		$this->html[] = $html;
	}

	/**
	 * Output the content
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function prepare_html() {
		return implode( PHP_EOL, $this->html );
	}

	/**
	 * Set 'preview' property.
	 *
	 * True if a certificate preview is being generated. Otherwise false.
	 *
	 * @since 1.13.0
	 *
	 * @param boolean $is_preview
	 */
	public function set_is_preview( $is_preview ) {
		$this->preview = $is_preview;
	}

	/**
	 * Set course ID.
	 *
	 * @since 1.13.0
	 *
	 * @param integer $course_id
	 */
	public function set_course_id( $course_id ) {
		$this->course_id = $course_id;
	}

	/**
	 * Set student ID.
	 *
	 * @since 1.13.0
	 *
	 * @param integer $student_id
	 */
	public function set_student_id( $student_id ) {
		$this->student_id = $student_id;
	}

	/**
	 * Set template html.
	 *
	 * @since 1.13.0
	 *
	 * @param string $template
	 */
	public function set_template( $template ) {
		$this->template = $template;
	}

	/**
	 * Get preview property.
	 *
	 * True if a certificate preview is being generated. Otherwise false.
	 *
	 * @since 1.13.0
	 *
	 * @return boolean
	 */
	public function is_preview() {
		return $this->preview;
	}

	/**
	 * Get course ID.
	 *
	 * @since 1.13.0
	 *
	 * @return integer
	 */
	public function get_course_id() {
		return $this->course_id;
	}

	/**
	 * Get student ID.
	 *
	 * @since 1.13.0
	 *
	 * @return integer
	 */
	public function get_student_id() {
		return $this->student_id;
	}

	/**
	 * Get template html.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_template() {
		return $this->template;
	}
}
