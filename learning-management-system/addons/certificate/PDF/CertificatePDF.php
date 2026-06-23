<?php
/**
 * Certificate PDF builder class.
 *
 * @since 2.3.7
 */

namespace Masteriyo\Addons\Certificate\PDF;

defined( 'ABSPATH' ) || exit;


use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;
use Mpdf\Output\Destination;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Masteriyo\Addons\Certificate\Models\Setting;
use Masteriyo\Query\CourseProgressQuery;

class CertificatePDF {
	/**
	 * The Mpdf instance.
	 *
	 * @since 2.3.7
	 *
	 * @var \Mpdf\Mpdf
	 */
	public $mpdf;

	/**
	 * Course ID.
	 *
	 * @since 2.3.7
	 *
	 * @var integer
	 */
	protected $course_id;

	/**
	 * Student ID.
	 *
	 * @since 2.3.7
	 *
	 * @var integer
	 */
	protected $student_id;

	/**
	 * Certificate template html.
	 *
	 * @since 2.3.7
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
	 * @since 2.4.4
	 *
	 * @var boolean
	 */
	protected $preview = false;

	/**
	 * Certificate ID (optional, used for pdfdraft format).
	 *
	 * @since x.x.x
	 *
	 * @var integer|null
	 */
	protected $certificate_id = null;

	/**
	 * Preview HTML override — set when generating a one-time preview from the designer.
	 * When set, prepare_pdf_pdfdraft() uses this instead of the DB-stored rendered_html.
	 *
	 * @since x.x.x
	 *
	 * @var string|null
	 */
	protected $preview_rendered_html = null;

	/**
	 * Constructor.
	 *
	 * @since 2.3.7
	 *
	 * @param integer $course_id
	 * @param integer $student_id
	 * @param string $template
	 * @param integer|null $certificate_id Optional. Required for pdfdraft format.
	 */
	public function __construct( $course_id, $student_id, $template, $certificate_id = null ) {
		$this->set_course_id( $course_id );
		$this->set_student_id( $student_id );
		$this->set_template( $template );
		$this->certificate_id = $certificate_id ? absint( $certificate_id ) : null;
	}

	/**
	 * Extract page dimensions from a pdfdraft certificate's JSON layout, returned
	 * as an array suitable for passing to init_mpdf().
	 *
	 * @since x.x.x
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate
	 * @return array{format: float[]}
	 */
	protected function extract_pdfdraft_layout( $certificate ): array {
		$json   = json_decode( $certificate->get_html_content(), true );
		$layout = $json['settings']['layout'] ?? array();

		$width  = (float) ( $layout['width'] ?? 11 );
		$height = (float) ( $layout['height'] ?? 8.5 );
		$unit   = $layout['unit'] ?? 'in';
		$orient = $layout['orientation'] ?? 'landscape';

		$to_mm = array(
			'in' => 25.4,
			'cm' => 10.0,
			'mm' => 1.0,
			'px' => 0.2646,
		);
		$mult  = $to_mm[ $unit ] ?? 25.4;

		// Return [width_mm, height_mm] as-is — do NOT set mPDF orientation
		// separately, as it would swap the dimensions and cause layout issues.
		return array(
			'format' => array( round( $width * $mult, 2 ), round( $height * $mult, 2 ) ),
		);
	}

	public function init_mpdf( array $page_format = array() ) {
		if ( $this->mpdf instanceof Mpdf ) {
			return;
		}

		$upload_dir = wp_upload_dir();

		$font_dirs = ( new \Mpdf\Config\ConfigVariables() )->getDefaults()['fontDir'];
		$font_dirs = array_merge( $font_dirs, array( $upload_dir['basedir'] . '/masteriyo/certificate-fonts' ) );

		$default_font_config = ( new \Mpdf\Config\FontVariables() )->getDefaults();
		$fontdata            = $default_font_config['fontdata'];
		$fontdata            = $fontdata + array(
			'cinzel'                               => array(
				'R' => 'Cinzel-VariableFont_wght.ttf',
			),
			'dejavusanscondensed'                  => array(
				'R' => 'DejaVuSansCondensed.ttf',
				'B' => 'DejaVuSansCondensed-Bold.ttf',
			),
			'dmsans'                               => array(
				'R' => 'DMSans-Regular.ttf',
				'B' => 'DMSans-Bold.ttf',
				'I' => 'DMSans-Italic.ttf',
			),
			'greatvibes'                           => array(
				'R' => 'GreatVibes-Regular.ttf',
			),
			'grenzegotisch'                        => array(
				'R' => 'GrenzeGotisch-VariableFont_wght.ttf',
			),
			'librebaskerville'                     => array(
				'R' => 'LibreBaskerville-Regular.ttf',
				'B' => 'LibreBaskerville-Bold.ttf',
				'I' => 'LibreBaskerville-Italic.ttf',
			),
			'lora'                                 => array(
				'R' => 'Lora-VariableFont_wght.ttf',
				'I' => 'Lora-Italic-VariableFont_wght.ttf',
			),
			'poppins'                              => array(
				'R' => 'Poppins-Regular.ttf',
				'B' => 'Poppins-Bold.ttf',
				'I' => 'Poppins-Italic.ttf',
			),
			'roboto'                               => array(
				'R' => 'Roboto-Regular.ttf',
				'B' => 'Roboto-Bold.ttf',
				'I' => 'Roboto-Italic.ttf',
			),
			'abhayalibre'                          => array(
				'R' => 'AbhayaLibre-Regular.ttf',
				'B' => 'AbhayaLibre-Bold.ttf',
			),
			'adinekirnberg'                        => array(
				'R' => 'AdineKirnberg.ttf',
			),
			'alexbrush'                            => array(
				'R' => 'AlexBrush-Regular.ttf',
			),
			'allura'                               => array(
				'R' => 'Allura-Regular.ttf',
			),
			'notosansdevanagariextracondensedthin' => array(
				'R' => 'NotoSansDevanagari_ExtraCondensed-Thin.ttf',
			),
		);
		if ( function_exists( 'set_exception_handler' ) ) {
			set_exception_handler(
				function ( $e ) {
					wp_die(
						/* translators: %s: Error Message */
						sprintf( esc_html__( 'Critical Error: %s', 'learning-management-system' ), esc_html( $e->getMessage() ) ),
						esc_html__( 'Critical Error', 'learning-management-system' ),
						array( 'response' => 400 )
					);
				}
			);
		}
		$mpdf_config = array(
			'tempDir'          => masteriyo_get_temp_dir() . '/mpdf',
			'fontDir'          => $font_dirs,
			'margin_left'      => 0,
			'margin_right'     => 0,
			'margin_top'       => 0,
			'margin_bottom'    => 0,
			'default_font'     => 'Arial, sans-serif',
			'autoScriptToLang' => false,
			'autoLangToFont'   => true,
			'fontdata'         => $fontdata + masteriyo_get_font_configurations(),
		);

		// Merge in page dimensions when provided (pdfdraft format).
		// Do NOT set 'orientation' alongside an explicit [w, h] format array —
		// mPDF swaps the dimensions when orientation='L', which would make a
		// landscape canvas overflow to page 2 (blank page 1 bug).
		// Passing the correct pixel dimensions as the format is sufficient.
		if ( ! empty( $page_format['format'] ) ) {
			$mpdf_config['format'] = $page_format['format'];
		}

		$this->mpdf = new Mpdf( apply_filters( 'masteriyo_mpdf_config', $mpdf_config ) );

		$this->mpdf->setMBencoding( 'UTF-8' );

		/**
		 * Filters mpdf debug mode for making certificate PDF file.
		 *
		 * @since 2.3.7
		 *
		 * @param boolean $bool
		 * @param \Mpdf\Mpdf $mpdf
		 */
		$this->mpdf->debug = apply_filters( 'masteriyo_certificate_mpdf_debug_mode', false, $this->mpdf );

		/**
		 * Filters mpdf image debug mode for making certificate PDF file.
		 *
		 * @since 2.3.7
		 *
		 * @param boolean $bool
		 * @param \Mpdf\Mpdf $mpdf
		 */
		$this->mpdf->showImageErrors = apply_filters( 'masteriyo_certificate_mpdf_show_image_errors', false, $this->mpdf );

		/**
		 * Filters Mpdf class instance used for making certificate PDF file.
		 *
		 * @since 2.3.7
		 *
		 * @param boolean $bool
		 * @param \Mpdf\Mpdf $mpdf
		 */
		$this->mpdf = apply_filters( 'masteriyo_certificate_builder_mpdf', $this->mpdf );
	}

	/**
	 * Prepare PDF.
	 *
	 * @since 2.3.7
	 *
	 * @since 2.4.4 Added $is_preview argument.
	 *
	 * @param string $template The certificate template.
	 * @param boolean $is_preview
	 *
	 * @return true|\WP_Error
	 */
	public function prepare_pdf( $is_preview = false ) {
		$this->set_is_preview( $is_preview );

		if ( null !== $this->preview_rendered_html ) {
			$layout = array();
			if ( $this->certificate_id ) {
				$preview_cert = masteriyo_get_certificate( $this->certificate_id );
				if ( $preview_cert ) {
					$layout = $this->extract_pdfdraft_layout( $preview_cert );
				}
			}
			$this->init_mpdf( $layout );
			return $this->prepare_pdf_pdfdraft( null, $is_preview );
		}

		if ( $this->certificate_id ) {
			$certificate = masteriyo_get_certificate( $this->certificate_id );
			if ( $certificate && 'pdfdraft' === $certificate->get_content_format() ) {
				$this->init_mpdf( $this->extract_pdfdraft_layout( $certificate ) );
				return $this->prepare_pdf_pdfdraft( $certificate, $is_preview );
			}
		}

		// Existing Gutenberg pipeline — init with defaults as before.
		$this->init_mpdf();

		// Existing Gutenberg pipeline — unchanged.
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
	 * Prepare PDF for pdfdraft format certificates.
	 *
	 * @since x.x.x
	 *
	 * @param \Masteriyo\Addons\Certificate\Models\Certificate $certificate
	 * @param boolean $is_preview
	 * @return true|\WP_Error
	 */
	protected function prepare_pdf_pdfdraft( $certificate, $is_preview ) {
		$html = $this->preview_rendered_html ?? ( $certificate ? $certificate->get_rendered_html( 'edit' ) : '' );

		if ( empty( $html ) ) {
			return new \WP_Error(
				'masteriyo_no_rendered_html',
				__( 'Certificate has not been saved yet. Please open and save the certificate in the editor before generating a PDF.', 'learning-management-system' )
			);
		}

		$html = $this->replace_pdfdraft_merge_tags( $html, $is_preview );

		$html = preg_replace( '/\s+on\w+="[^"]*"/i', '', $html );
		$html = preg_replace( "/\s+on\w+='[^']*'/i", '', $html );
		$html = preg_replace( '/\s+contenteditable="[^"]*"/i', '', $html );

		try {
			$this->add_html( $html );
			$this->mpdf->WriteHTML( $this->prepare_pdfdraft_css(), HTMLParserMode::HEADER_CSS );
			$this->mpdf->WriteHTML( $this->prepare_html() );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'masteriyo_mpdf_error',
				sprintf(
					/* translators: %s: error message */
					__( 'PDF generation failed: %s', 'learning-management-system' ),
					$e->getMessage()
				)
			);
		}

		return true;
	}

	/**
	 * Build the merge tag replacements array for PDFDraft certificates.
	 *
	 * @since x.x.x
	 *
	 * @param boolean $is_preview
	 * @return array Map of {{tag}} => resolved value.
	 */
	public function build_pdfdraft_replacements( $is_preview = false ) {
		$course  = masteriyo_get_course( $this->get_course_id() );
		$student = masteriyo_get_user( $this->get_student_id() );

		if ( $is_preview || ! $course || ! $student || is_wp_error( $student ) ) {
			$replacements = array(
				'{{masteriyo:student_name_full}}'  => __( 'Student Full Name', 'learning-management-system' ),
				'{{masteriyo:student_name_first}}' => __( 'First Name', 'learning-management-system' ),
				'{{masteriyo:student_name_last}}'  => __( 'Last Name', 'learning-management-system' ),
				'{{masteriyo:course_title}}'       => $course ? $course->get_name() : __( 'Course Title', 'learning-management-system' ),
				'{{masteriyo:completion_date}}'    => date_i18n( get_option( 'date_format' ) ),
				'{{masteriyo:start_date}}'         => date_i18n( get_option( 'date_format' ) ),
				'{{masteriyo:instructor_name}}'    => __( 'Instructor Name', 'learning-management-system' ),
				'{{masteriyo:co_instructors}}'     => __( 'Co-Instructor Name', 'learning-management-system' ),
				'{{masteriyo:course_duration}}'    => __( '10 Hours', 'learning-management-system' ),
				'{{masteriyo:grade}}'              => '100%',
				'{{masteriyo:verification_code}}'  => 'XXXX-XXXX-XXXX',
				'{{masteriyo:qr_code}}'            => '',
				'{{masteriyo:current_date}}'       => date_i18n( get_option( 'date_format' ) ),
				'{{masteriyo:current_time}}'       => date_i18n( get_option( 'time_format' ) ),
				'{{masteriyo:current_timestamp}}'  => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				'{{masteriyo:site_name}}'          => get_bloginfo( 'name' ),
				'{{user.display_name}}'            => __( 'Student Name', 'learning-management-system' ),
				'{{user.first_name}}'              => __( 'First Name', 'learning-management-system' ),
				'{{user.last_name}}'               => __( 'Last Name', 'learning-management-system' ),
				'{{user.email}}'                   => 'student@example.com',
				'{{user.login}}'                   => __( 'username', 'learning-management-system' ),
				'{{user.id}}'                      => '1',
				'{{user.url}}'                     => home_url(),
				'{{user.description}}'             => __( 'Student bio', 'learning-management-system' ),
				'{{user.registered}}'              => date_i18n( get_option( 'date_format' ) ),
				'{{user.avatar}}'                  => '',
				'{{user.roles}}'                   => __( 'Student', 'learning-management-system' ),
				'{{site.name}}'                    => get_bloginfo( 'name' ),
				'{{site.tagline}}'                 => get_bloginfo( 'description' ),
				'{{site.url}}'                     => get_site_url(),
				'{{site.home_url}}'                => home_url(),
				'{{site.admin_email}}'             => get_option( 'admin_email' ),
				'{{site.current_date}}'            => date_i18n( get_option( 'date_format' ) ),
				'{{site.current_time}}'            => date_i18n( get_option( 'time_format' ) ),
				'{{site.wp_version}}'              => get_bloginfo( 'version' ),
				'{{site.language}}'                => get_bloginfo( 'language' ),
				'{{post.title}}'                   => $course ? $course->get_name() : __( 'Course Title', 'learning-management-system' ),
				'{{post.author_name}}'             => __( 'Instructor Name', 'learning-management-system' ),
			);
		} else {
			$course_id  = $this->get_course_id();
			$student_id = $this->get_student_id();

			$completion_date = '';
			$start_date      = '';
			$progress_query  = new CourseProgressQuery(
				array(
					'course_id' => $course_id,
					'user_id'   => $student_id,
					'per_page'  => 1,
				)
			);
			$course_progress = current( $progress_query->get_course_progress() );
			if ( $course_progress ) {
				$completion_date = $course_progress->get_completed_at()
					? date_i18n( get_option( 'date_format' ), $course_progress->get_completed_at()->getOffsetTimestamp() )
					: '';
				$start_date      = $course_progress->get_started_at()
					? date_i18n( get_option( 'date_format' ), $course_progress->get_started_at()->getOffsetTimestamp() )
					: '';
			}

			$instructor      = masteriyo_get_user( $course->get_author_id() );
			$instructor_name = '';
			if ( $instructor && ! is_wp_error( $instructor ) ) {
				$instructor_full = trim( $instructor->get_first_name() . ' ' . $instructor->get_last_name() );
				$instructor_name = $instructor_full ?: $instructor->get_display_name();
			}

			$verification_code = $course_id . '-' . masteriyo_get_course_certificate_id( $course_id ) . '-' . $student_id;

			$wp_user = get_user_by( 'id', $this->get_student_id() );

			$student_full_name = trim( $student->get_first_name() . ' ' . $student->get_last_name() );
			if ( ! $student_full_name ) {
				$student_full_name = $student->get_display_name();
			}

			$replacements = array(
				'{{masteriyo:student_name_full}}'  => $student_full_name,
				'{{masteriyo:student_name_first}}' => $student->get_first_name() ? $student->get_first_name() : $student->get_display_name(),
				'{{masteriyo:student_name_last}}'  => $student->get_last_name(),
				'{{masteriyo:course_title}}'       => $course->get_name(),
				'{{masteriyo:completion_date}}'    => $completion_date,
				'{{masteriyo:start_date}}'         => $start_date,
				'{{masteriyo:instructor_name}}'    => $instructor_name,
				'{{masteriyo:co_instructors}}'     => '',
				'{{masteriyo:course_duration}}'    => masteriyo_minutes_to_time_length_string( $course->get_duration() ),
				'{{masteriyo:grade}}'              => '',
				'{{masteriyo:verification_code}}'  => $verification_code,
				'{{masteriyo:qr_code}}'            => $this->get_pdfdraft_qr_code_html( $verification_code ),
				'{{masteriyo:current_date}}'       => date_i18n( get_option( 'date_format' ) ),
				'{{masteriyo:current_time}}'       => date_i18n( get_option( 'time_format' ) ),
				'{{masteriyo:current_timestamp}}'  => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
				'{{masteriyo:site_name}}'          => get_bloginfo( 'name' ),
				'{{user.display_name}}'            => $student->get_display_name(),
				'{{user.first_name}}'              => $student->get_first_name() ? $student->get_first_name() : $student->get_display_name(),
				'{{user.last_name}}'               => $student->get_last_name(),
				'{{user.email}}'                   => $wp_user ? $wp_user->user_email : '',
				'{{user.login}}'                   => $wp_user ? $wp_user->user_login : '',
				'{{user.id}}'                      => (string) $this->get_student_id(),
				'{{user.url}}'                     => $wp_user ? $wp_user->user_url : '',
				'{{user.description}}'             => $wp_user ? get_user_meta( $wp_user->ID, 'description', true ) : '',
				'{{user.registered}}'              => $wp_user ? date_i18n( get_option( 'date_format' ), strtotime( $wp_user->user_registered ) ) : '',
				'{{user.avatar}}'                  => $wp_user ? get_avatar( $wp_user->ID, 64 ) : '',
				'{{user.roles}}'                   => $wp_user ? implode( ', ', (array) $wp_user->roles ) : '',
				'{{site.name}}'                    => get_bloginfo( 'name' ),
				'{{site.tagline}}'                 => get_bloginfo( 'description' ),
				'{{site.url}}'                     => get_site_url(),
				'{{site.home_url}}'                => home_url(),
				'{{site.admin_email}}'             => get_option( 'admin_email' ),
				'{{site.current_date}}'            => date_i18n( get_option( 'date_format' ) ),
				'{{site.current_time}}'            => date_i18n( get_option( 'time_format' ) ),
				'{{site.wp_version}}'              => get_bloginfo( 'version' ),
				'{{site.language}}'                => get_bloginfo( 'language' ),
				'{{post.title}}'                   => $course->get_name(),
				'{{post.author_name}}'             => $instructor_name,
			);
		}

		/**
		 * Filter the merge tag replacements for pdfdraft certificates.
		 *
		 * @since x.x.x
		 *
		 * @param array   $replacements  Map of merge tag => replacement value.
		 * @param boolean $is_preview
		 * @param \Masteriyo\Course\Course|null $course
		 * @param \Masteriyo\Models\User|null   $student
		 */
		return apply_filters( 'masteriyo_pdfdraft_certificate_merge_tags', $replacements, $is_preview, $course ?? null, $student ?? null );
	}

	/**
	 * Replace merge tags in the rendered HTML snapshot.
	 *
	 * @since x.x.x
	 *
	 * @param string  $html       Rendered HTML snapshot.
	 * @param boolean $is_preview Whether this is a preview.
	 * @return string
	 */
	protected function replace_pdfdraft_merge_tags( $html, $is_preview ) {
		$replacements = $this->build_pdfdraft_replacements( $is_preview );
		$html = $this->replace_pdfdraft_data_merge_tag_elements( $html, $replacements );
		$html_value_tags = $this->get_pdfdraft_html_value_tags();
		$escaped         = array();
		foreach ( $replacements as $tag => $value ) {
			$escaped[ $tag ] = in_array( $tag, $html_value_tags, true ) ? $value : esc_html( (string) $value );
		}

		return str_replace( array_keys( $escaped ), array_values( $escaped ), $html );
	}

	/**
	 * Merge tags whose resolved value is intentional, server-generated HTML
	 * markup (rather than plain text) and must therefore NOT be HTML-escaped.
	 *
	 * Every other merge value is user-controlled and is escaped before being
	 * placed into the rendered certificate HTML.
	 *
	 * @since x.x.x
	 *
	 * @return string[]
	 */
	protected function get_pdfdraft_html_value_tags() {
		/**
		 * Filters the merge tags treated as raw HTML in pdfdraft certificates.
		 *
		 * @since x.x.x
		 *
		 * @param string[] $tags Merge tags whose value is trusted HTML.
		 */
		return apply_filters(
			'masteriyo_pdfdraft_certificate_html_value_tags',
			array( '{{user.avatar}}', '{{masteriyo:qr_code}}' )
		);
	}

	/**
	 * Resolve merge tags in a PDFDraft JSON for client-side PDF generation.
	 *
	 * @since x.x.x
	 *
	 * @param array   $json       Decoded certificate JSON.
	 * @param boolean $is_preview
	 * @return array
	 */
	public function resolve_pdfdraft_json_for_download( array $json, $is_preview = false ): array {
		$replacements = $this->build_pdfdraft_replacements( $is_preview );

		$json_str = wp_json_encode( $json );
		if ( ! $json_str ) {
			return $json;
		}

		foreach ( $replacements as $tag => $value ) {
			if ( ! is_string( $value ) ) {
				continue;
			}
			$encoded = wp_json_encode( wp_check_invalid_utf8( $value, true ) );
			if ( ! is_string( $encoded ) || strlen( $encoded ) < 2 ) {
				continue;
			}
			$safe     = substr( $encoded, 1, strlen( $encoded ) - 2 );
			$json_str = str_replace( $tag, $safe, $json_str );
		}

		$resolved = json_decode( $json_str, true );
		if ( ! is_array( $resolved ) ) {
			return $json;
		}

		if ( isset( $resolved['pages'] ) && is_array( $resolved['pages'] ) ) {
			$resolved['pages'] = $this->convert_pdfdraft_element_types( $resolved['pages'], $replacements );
		}

		if ( isset( $resolved['pages'] ) ) {
			$resolved['fonts'] = $this->enrich_pdfdraft_fonts(
				$resolved['pages'],
				is_array( $resolved['fonts'] ?? null ) ? $resolved['fonts'] : array()
			);
		}

		$resolved = $this->inline_pdfdraft_remote_images( $resolved );

		return $resolved;
	}

	/**
	 * Inline remote (http/https) image URLs in a pdfdraft design as base64 data
	 * URIs, so the client-side PDFExporter never fetches cross-origin images
	 * (avoids canvas tainting when a CDN/CloudFront response is missing CORS
	 * headers, which silently drops images from the generated PDF).
	 *
	 * @since x.x.x
	 *
	 * @param array $json Decoded certificate design.
	 * @return array
	 */
	public function inline_pdfdraft_remote_images( array $json ): array {
		if ( isset( $json['pages'] ) && is_array( $json['pages'] ) ) {
			foreach ( $json['pages'] as $page_id => $page ) {
				if ( isset( $page['children'] ) && is_array( $page['children'] ) ) {
					$json['pages'][ $page_id ]['children'] = $this->inline_pdfdraft_image_nodes( $page['children'] );
				}
			}
		}

		if ( isset( $json['settings']['background'] ) && is_array( $json['settings']['background'] ) ) {
			$bg = $json['settings']['background'];

			if ( ! empty( $bg['image'] ) && is_string( $bg['image'] ) ) {
				$bg['image'] = $this->maybe_inline_remote_image( $bg['image'] );
			}

			if ( isset( $bg['imageProps'] ) && is_array( $bg['imageProps'] ) ) {
				foreach ( array( 'src', 'originalSrc' ) as $key ) {
					if ( ! empty( $bg['imageProps'][ $key ] ) && is_string( $bg['imageProps'][ $key ] ) ) {
						$bg['imageProps'][ $key ] = $this->maybe_inline_remote_image( $bg['imageProps'][ $key ] );
					}
				}
			}

			$json['settings']['background'] = $bg;
		}

		return $json;
	}

	/**
	 * Recursively inline image-element src/originalSrc within a node list.
	 *
	 * @since x.x.x
	 *
	 * @param array $nodes
	 * @return array
	 */
	protected function inline_pdfdraft_image_nodes( array $nodes ): array {
		foreach ( $nodes as $i => $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}

			if ( isset( $node['type'], $node['props'] ) && 'image' === $node['type'] && is_array( $node['props'] ) ) {
				foreach ( array( 'src', 'originalSrc' ) as $key ) {
					if ( ! empty( $node['props'][ $key ] ) && is_string( $node['props'][ $key ] ) ) {
						$node['props'][ $key ] = $this->maybe_inline_remote_image( $node['props'][ $key ] );
					}
				}
			}

			if ( isset( $node['children'] ) && is_array( $node['children'] ) ) {
				$node['children'] = $this->inline_pdfdraft_image_nodes( $node['children'] );
			}

			$nodes[ $i ] = $node;
		}

		return $nodes;
	}

	/**
	 * Determine whether a remote URL is safe to fetch server-side (SSRF guard).
	 *
	 * Images served from this site (own media library) are always allowed, even
	 * when the host resolves to a private IP (load balancers / local dev). Any
	 * other host must resolve exclusively to public IP addresses; loopback,
	 * private, link-local (incl. 169.254.169.254 cloud metadata) and reserved
	 * ranges are rejected.
	 *
	 * @since x.x.x
	 *
	 * @param string $url
	 * @return bool
	 */
	protected function is_safe_remote_image_url( $url ) {
		$parsed = wp_parse_url( $url );

		if ( empty( $parsed['scheme'] ) || ! in_array( strtolower( $parsed['scheme'] ), array( 'http', 'https' ), true ) ) {
			return false;
		}
		if ( empty( $parsed['host'] ) ) {
			return false;
		}

		$host      = strtolower( trim( $parsed['host'], '.[]' ) );
		$site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

		if ( $host && $host === $site_host ) {
			return true;
		}

		$ips = array();
		if ( \filter_var( $host, \FILTER_VALIDATE_IP ) ) {
			$ips[] = $host;
		} else {
			$records = function_exists( 'dns_get_record' ) ? @dns_get_record( $host, DNS_A | DNS_AAAA ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( is_array( $records ) ) {
				foreach ( $records as $record ) {
					if ( ! empty( $record['ip'] ) ) {
						$ips[] = $record['ip'];
					}
					if ( ! empty( $record['ipv6'] ) ) {
						$ips[] = $record['ipv6'];
					}
				}
			}
			if ( empty( $ips ) ) {
				$resolved = gethostbyname( $host );
				if ( $resolved && $resolved !== $host ) {
					$ips[] = $resolved;
				}
			}
		}

		if ( empty( $ips ) ) {
			return false;
		}

		foreach ( $ips as $ip ) {
			if ( ! \filter_var( $ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Convert a remote http(s) image URL to a base64 data URI (cached per URL via
	 * a request-static map and a day-long transient). Returns the URL unchanged
	 * for data:/relative URLs or on fetch failure.
	 *
	 * @since x.x.x
	 *
	 * @param string $url
	 * @return string
	 */
	protected function maybe_inline_remote_image( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}
		if ( 0 === strpos( $url, 'data:' ) ) {
			return $url;
		}
		if ( 0 !== strpos( $url, 'http://' ) && 0 !== strpos( $url, 'https://' ) ) {
			return $url;
		}

		if ( ! $this->is_safe_remote_image_url( $url ) ) {
			return $url;
		}

		static $cache = array();
		if ( isset( $cache[ $url ] ) ) {
			return $cache[ $url ];
		}

		$transient_key = 'masteriyo_cert_img_b64_' . md5( $url );
		$cached        = get_transient( $transient_key );
		if ( is_string( $cached ) && '' !== $cached ) {
			$cache[ $url ] = $cached;
			return $cached;
		}

		static $fetch_count = 0;
		if ( $fetch_count >= 50 ) {
			$cache[ $url ] = $url;
			return $url;
		}
		++$fetch_count;

		$response = wp_remote_get(
			$url,
			array(
				'timeout'           => 10,
				'redirection'       => 2,
				'reject_unsafe_urls' => true,
				'sslverify'         => (bool) Setting::get( 'use_ssl_verified' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			$cache[ $url ] = $url;
			return $url;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body ) {
			$cache[ $url ] = $url;
			return $url;
		}

		$mime = wp_remote_retrieve_header( $response, 'content-type' );
		if ( ! is_string( $mime ) || 0 !== strpos( $mime, 'image/' ) ) {
			$mime = 'image/png';
		}

		$data_uri      = 'data:' . $mime . ';base64,' . base64_encode( $body );
		$cache[ $url ] = $data_uri;
		set_transient( $transient_key, $data_uri, DAY_IN_SECONDS );

		return $data_uri;
	}

	/**
	 * Collect font-family values from all page nodes into the fonts map.
	 *
	 * @since x.x.x
	 * @param array $pages PDFDraft pages array.
	 * @param array $fonts Existing fonts map.
	 * @return array
	 */
	protected function enrich_pdfdraft_fonts( array $pages, array $fonts ) {
		$this->collect_fonts_from_nodes( $pages, $fonts );
		return $fonts;
	}

	/**
	 * Recursively walk PDFDraft node tree collecting font-family values.
	 *
	 * @since x.x.x
	 * @param mixed $data  Current node or subtree.
	 * @param array $fonts Fonts map passed by reference.
	 */
	protected function collect_fonts_from_nodes( $data, array &$fonts ) {
		if ( ! is_array( $data ) ) {
			return;
		}

		if ( isset( $data['type'], $data['id'] ) ) {
			$family = $data['style']['fontFamily'] ?? '';
			if ( $family ) {
				$this->add_font_to_fonts_map( (string) $family, $fonts );
			}

			$global_family = $data['props']['globalStyle']['fontFamily'] ?? '';
			if ( $global_family ) {
				$this->add_font_to_fonts_map( (string) $global_family, $fonts );
			}

			$content = $data['props']['content'] ?? '';
			if ( is_string( $content ) && false !== strpos( $content, 'font-family' ) ) {
				preg_match_all( "/font-family:\s*['\"]?([^;'\"]+)/i", $content, $matches );
				foreach ( $matches[1] ?? array() as $raw ) {
					$this->add_font_to_fonts_map( trim( $raw, " \t," ), $fonts );
				}
			}
		}

		foreach ( $data as $value ) {
			if ( is_array( $value ) ) {
				$this->collect_fonts_from_nodes( $value, $fonts );
			}
		}
	}

	/**
	 * Add a single font family to the fonts map if not already present.
	 *
	 * @since x.x.x
	 * @param string $family Raw font-family string.
	 * @param array  $fonts  Fonts map passed by reference.
	 */
	protected function add_font_to_fonts_map( string $family, array &$fonts ) {
		$family  = trim( $family, " \"'\t" );
		$generic = array( 'serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'system-ui', 'inherit', 'initial', 'unset' );

		if ( empty( $family ) || isset( $fonts[ $family ] ) || in_array( strtolower( $family ), $generic, true ) ) {
			return;
		}

		$encoded          = rawurlencode( $family );
		$fonts[ $family ] = array(
			'id'       => $family,
			'family'   => $family,
			'variants' => array( 100, 200, 300, 400, 500, 600, 700, 800, 900 ),
			'subsets'  => array( 'latin' ),
			'url'      => "https://fonts.googleapis.com/css2?family={$encoded}:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap",
		);
	}

	/**
	 * Convert non-native PDFDraft element types to 'text' recursively.
	 *
	 * @since x.x.x
	 *
	 * @param mixed $node         Current node in the JSON tree.
	 * @param array $replacements Resolved {{tag}} => value map.
	 * @return mixed
	 */
	protected function convert_pdfdraft_element_types( $node, array $replacements ) {
		if ( ! is_array( $node ) ) {
			return $node;
		}

		if ( isset( $node['type'], $node['id'] ) && is_string( $node['type'] ) ) {
			$type = $node['type'];

			if ( 0 === strpos( $type, 'masteriyo__' ) ) {
				$node['type'] = 'text';

				$tiptap_html = $node['props']['content'] ?? '';
				if ( $tiptap_html ) {
					if ( empty( $node['style']['fontFamily'] ) && false !== strpos( $tiptap_html, 'font-family' ) ) {
						preg_match( '/font-family:\s*([^;]+)/i', $tiptap_html, $fm );
						if ( ! empty( $fm[1] ) ) {
							$node['style']['fontFamily'] = trim( $fm[1], " \"'\t" );
						}
					}
					if ( empty( $node['style']['fontSize'] ) && false !== strpos( $tiptap_html, 'font-size' ) ) {
						preg_match( '/font-size:\s*([^;]+)/i', $tiptap_html, $fs );
						if ( ! empty( $fs[1] ) ) {
							$node['style']['fontSize'] = trim( $fs[1], " \"'\t" );
						}
					}
					if ( empty( $node['style']['fontWeight'] ) && false !== strpos( $tiptap_html, 'font-weight' ) ) {
						preg_match( '/font-weight:\s*([^;]+)/i', $tiptap_html, $fw );
						if ( ! empty( $fw[1] ) ) {
							$node['style']['fontWeight'] = trim( $fw[1], " \"'\t" );
						}
					}
				}

				if ( ! empty( $node['style']['fontFamily'] ) ) {
					if ( ! isset( $node['props']['globalStyle'] ) || ! is_array( $node['props']['globalStyle'] ) ) {
						$node['props']['globalStyle'] = array();
					}
					if ( empty( $node['props']['globalStyle']['fontFamily'] ) ) {
						$node['props']['globalStyle']['fontFamily'] = $node['style']['fontFamily'];
					}
				}
				if ( ! empty( $node['style']['fontSize'] ) ) {
					if ( ! isset( $node['props']['globalStyle'] ) || ! is_array( $node['props']['globalStyle'] ) ) {
						$node['props']['globalStyle'] = array();
					}
					if ( empty( $node['props']['globalStyle']['fontSize'] ) ) {
						$font_size_num = (float) preg_replace( '/[^0-9.]/', '', (string) $node['style']['fontSize'] );
						if ( $font_size_num > 0 ) {
							$node['props']['globalStyle']['fontSize'] = $font_size_num;
						}
					}
				}
				if ( ! empty( $node['style']['fontWeight'] ) ) {
					if ( ! isset( $node['props']['globalStyle'] ) || ! is_array( $node['props']['globalStyle'] ) ) {
						$node['props']['globalStyle'] = array();
					}
					if ( empty( $node['props']['globalStyle']['fontWeight'] ) ) {
						$node['props']['globalStyle']['fontWeight'] = $node['style']['fontWeight'];
					}
				}

				if ( ! empty( $node['props']['field'] ) ) {
					$tag                      = '{{' . $node['props']['field'] . '}}';
					$resolved                 = $replacements[ $tag ] ?? ( $node['props']['content'] ?? '' );
					$node['props']['content'] = $resolved;
					$node['content']          = $resolved;
				} elseif ( ! empty( $node['props']['content'] ) ) {
					$node['content'] = $node['props']['content'];
				}
			} elseif ( 'wp-data-field' === $type ) {
				if ( ! empty( $node['props']['field'] ) ) {
					$tag                       = '{{' . $node['props']['field'] . '}}';
					$node['props']['fallback'] = $replacements[ $tag ] ?? ( $node['props']['fallback'] ?? '' );
				} elseif ( ! empty( $node['props']['content'] ) ) {
					$node['props']['fallback'] = $node['props']['content'];
				}
				if ( '' !== ( $node['props']['fallback'] ?? '' ) ) {
					$node['content'] = $node['props']['fallback'];
				}
			}
		}

		foreach ( $node as $key => $child ) {
			if ( is_array( $child ) ) {
				$node[ $key ] = $this->convert_pdfdraft_element_types( $child, $replacements );
			}
		}

		return $node;
	}

	/**
	 * Replace elements marked with data-merge-tag in the saved PDFdraft HTML snapshot.
	 *
	 * @since x.x.x
	 *
	 * @param string $html
	 * @param array  $replacements
	 * @return string
	 */
	protected function replace_pdfdraft_data_merge_tag_elements( $html, $replacements ) {
		if ( ! class_exists( '\DOMDocument' ) || ! class_exists( '\DOMXPath' ) ) {
			return $html;
		}

		$previous = libxml_use_internal_errors( true );
		$dom      = new \DOMDocument( '1.0', 'UTF-8' );
		$loaded   = $dom->loadHTML(
			'<?xml encoding="UTF-8"><div id="masteriyo-pdfdraft-root">' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return $html;
		}

		$xpath = new \DOMXPath( $dom );
		$nodes = $xpath->query( '//*[@data-merge-tag]' );

		if ( ! $nodes ) {
			return $html;
		}

		foreach ( $nodes as $node ) {
			$tag = $node->getAttribute( 'data-merge-tag' );

			if ( ! array_key_exists( $tag, $replacements ) ) {
				continue;
			}

			while ( $node->firstChild ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$node->removeChild( $node->firstChild ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}

			$replacement = (string) $replacements[ $tag ];

			if ( in_array( $tag, $this->get_pdfdraft_html_value_tags(), true ) ) {
				$fragment = $dom->createDocumentFragment();

				if ( $fragment->appendXML( $replacement ) ) {
					$node->appendChild( $fragment );
				} else {
					$tmp      = new \DOMDocument( '1.0', 'UTF-8' );
					$tmp_prev = libxml_use_internal_errors( true );
					$ok       = $tmp->loadHTML(
						'<?xml encoding="UTF-8"><div id="mto-frag">' . $replacement . '</div>',
						LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
					);
					libxml_clear_errors();
					libxml_use_internal_errors( $tmp_prev );
					if ( $ok ) {
						$tmp_xpath = new \DOMXPath( $tmp );
						$wrapper   = $tmp_xpath->query( '//*[@id="mto-frag"]' )->item( 0 );
						if ( $wrapper ) {
							foreach ( iterator_to_array( $wrapper->childNodes ) as $child ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
								$node->appendChild( $dom->importNode( $child, true ) );
							}
						}
					}
				}
			} else {
				$node->appendChild( $dom->createTextNode( $replacement ) );
			}

			$node->removeAttribute( 'data-merge-tag' );
		}

		$root = $dom->getElementById( 'masteriyo-pdfdraft-root' );

		if ( ! $root ) {
			return $html;
		}

		$output = '';

		foreach ( $root->childNodes as $child ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$output .= $dom->saveHTML( $child );
		}

		return $output;
	}

	/**
	 * Get QR code image markup for PDFdraft certificates.
	 *
	 * @since x.x.x
	 *
	 * @param string $verification_code Certificate verification code.
	 * @return string
	 */
	protected function get_pdfdraft_qr_code_html( $verification_code ) {
		if ( empty( $verification_code ) ) {
			return '';
		}

		$url     = home_url( '?certificate-verification-id=' . rawurlencode( $verification_code ) );
		$options = new QROptions( array( 'imageTransparent' => true ) );
		$src     = ( new QRCode( $options ) )->render( $url );

		return sprintf(
			'<img src="%s" alt="%s" style="display:block;width:100%%;height:100%%;object-fit:contain;" />',
			esc_attr( $src ),
			esc_attr__( 'QR Code', 'learning-management-system' )
		);
	}

	/**
	 * Base CSS for pdfdraft-format certificates.
	 *
	 * @since x.x.x
	 *
	 * @return string
	 */
	public function prepare_pdfdraft_css() {
		return '
			* { box-sizing: border-box; }
			body { margin: 0; padding: 0; }
			[data-pdfdraft-canvas],
			.PDFDraft-Container { position: relative; overflow: hidden; background-color: #ffffff; }
			.relative   { position: relative; }
			.absolute   { position: absolute; }
			.fixed      { position: fixed; }
			.overflow-hidden { overflow: hidden; }
			.overflow-visible { overflow: visible; }
			.bg-white        { background-color: #ffffff; }
			.bg-transparent  { background-color: transparent; }
			.bg-black        { background-color: #000000; }
			.flex         { display: flex; }
			.inline-flex  { display: inline-flex; }
			.block        { display: block; }
			.inline-block { display: inline-block; }
			.hidden       { display: none; }
			.w-full  { width: 100%; }
			.h-full  { height: 100%; }
			.w-auto  { width: auto; }
			.h-auto  { height: auto; }
			.items-center   { align-items: center; }
			.items-start    { align-items: flex-start; }
			.items-end      { align-items: flex-end; }
			.justify-center { justify-content: center; }
			.justify-start  { justify-content: flex-start; }
			.justify-end    { justify-content: flex-end; }
			.justify-between { justify-content: space-between; }
			.flex-1    { flex: 1 1 0%; }
			.shrink-0  { flex-shrink: 0; }
			.whitespace-nowrap   { white-space: nowrap; }
			.whitespace-pre-wrap { white-space: pre-wrap; }
			.break-words  { overflow-wrap: break-word; }
			.break-all    { word-break: break-all; }
			.text-center  { text-align: center; }
			.text-left    { text-align: left; }
			.text-right   { text-align: right; }
			.font-bold    { font-weight: 700; }
			.font-medium  { font-weight: 500; }
			.font-normal  { font-weight: 400; }
			.italic       { font-style: italic; }
			.underline    { text-decoration: underline; }
			.line-through { text-decoration: line-through; }
			.origin-\\[0px_0px\\] { transform-origin: 0px 0px; }
			.pointer-events-none { pointer-events: none; }
			.object-cover    { object-fit: cover; }
			.object-contain  { object-fit: contain; }
			.object-fill     { object-fit: fill; }
			.inset-0 { top: 0; right: 0; bottom: 0; left: 0; }
			.select-none { user-select: none; }
		';
	}

	/**
	 * Serve certificate preview.
	 *
	 * @since 2.3.7
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

		// Discard any buffered output so nothing corrupts the PDF byte stream.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		$this->mpdf->Output( $this->make_filename( true ), Destination::INLINE );
		die;
	}

	/**
	 * Serve certificate download.
	 *
	 * @since 2.3.7
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
	 * @since 2.3.7
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
		 * @since 2.3.7
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
	 * @since 2.3.7
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
	 * @since 2.3.7
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
	 * @since 2.7.3
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
	 * @since 2.3.7
	 *
	 * @param string $html
	 */
	public function add_html( $html ) {
		$this->html[] = $html;
	}

	/**
	 * Output the content
	 *
	 * @since 2.3.7
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
	 * @since 2.4.4
	 *
	 * @param boolean $is_preview
	 */
	public function set_is_preview( $is_preview ) {
		$this->preview = $is_preview;
	}

	/**
	 * Set course ID.
	 *
	 * @since 2.3.7
	 *
	 * @param integer $course_id
	 */
	public function set_course_id( $course_id ) {
		$this->course_id = $course_id;
	}

	/**
	 * Set student ID.
	 *
	 * @since 2.3.7
	 *
	 * @param integer $student_id
	 */
	public function set_student_id( $student_id ) {
		$this->student_id = $student_id;
	}

	/**
	 * Set template html.
	 *
	 * @since 2.3.7
	 *
	 * @param string $template
	 */
	public function set_template( $template ) {
		$this->template = $template;
	}

	/**
	 * Replace merge tags and strip unsafe attributes from a pdfdraft HTML snapshot.
	 * Returns the processed HTML string ready for client-side PDF generation.
	 *
	 * @since x.x.x
	 *
	 * @param string $html Raw rendered_html from the certificate.
	 * @return string
	 */
	public function process_html_for_download( $html ) {
		$html = $this->replace_pdfdraft_merge_tags( $html, false );
		$html = preg_replace( '/\s+on\w+="[^"]*"/i', '', $html );
		$html = preg_replace( "/\s+on\w+='[^']*'/i", '', $html );
		$html = preg_replace( '/\s+contenteditable="[^"]*"/i', '', $html );
		return $html;
	}

	/**
	 * Set a one-time preview HTML override for pdfdraft format.
	 *
	 * When set, prepare_pdf_pdfdraft() uses this HTML instead of the DB-stored rendered_html.
	 *
	 * @since x.x.x
	 *
	 * @param string $html Pre-rendered HTML snapshot from the designer.
	 */
	public function set_preview_rendered_html( $html ) {
		$this->preview_rendered_html = $html;
	}

	/**
	 * Get preview property.
	 *
	 * True if a certificate preview is being generated. Otherwise false.
	 *
	 * @since 2.4.4
	 *
	 * @return boolean
	 */
	public function is_preview() {
		return $this->preview;
	}

	/**
	 * Get course ID.
	 *
	 * @since 2.3.7
	 *
	 * @return integer
	 */
	public function get_course_id() {
		return $this->course_id;
	}

	/**
	 * Get student ID.
	 *
	 * @since 2.3.7
	 *
	 * @return integer
	 */
	public function get_student_id() {
		return $this->student_id;
	}

	/**
	 * Get template html.
	 *
	 * @since 2.3.7
	 *
	 * @return string
	 */
	public function get_template() {
		return $this->template;
	}
}
