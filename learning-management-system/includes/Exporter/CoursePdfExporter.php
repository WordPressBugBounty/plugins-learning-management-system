<?php
/**
/**
 * Course PDF Export Handler.
 *
 * Handles the generation and export of course content as PDF documents.
 * Uses mPDF library to create well-formatted PDF files with course details,
 * curriculum, lessons, quizzes, and other course components.
 *
 * @since 2.21.0
 *
 * @package Masteriyo\Exporter
 */

namespace Masteriyo\Exporter;

use Exception;
use Masteriyo\AdminFileDownloadHandler;
use Masteriyo\FileHandler;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Masteriyo\Models\Course;
use Masteriyo\PostType\PostType;
use Mpdf\HTMLParserMode;

defined( 'ABSPATH' ) || exit();

/**
 * Course PDF Export Handler.
 *
 * Main class responsible for generating PDF exports of course content.
 * Provides methods for creating, formatting, and downloading course PDFs.
 *
 * @since 2.21.0
 */
class CoursePdfExporter {
	/**
	 * The course object.
	 *
	 * Stores the course data that will be exported to PDF.
	 *
	 * @since 2.21.0
	 *
	 * @var Course
	 */
	protected $course;

	/**
	 * The mPDF instance.
	 *
	 * Handles the PDF generation functionality.
	 *
	 * @since 2.21.0
	 *
	 * @var Mpdf
	 */
	public $mpdf;

	/**
	 * The ID used to generate download URL for exported courses file.
	 *
	 * Used as an identifier for the file download handler.
	 *
	 * @since 2.21.0
	 */
	const FILE_PATH_ID = 'export_courses';

	/**
	 * The directory where export files will be stored.
	 *
	 * Relative path within the WordPress uploads directory.
	 *
	 * @since 2.21.0
	 */
	const EXPORT_DIRECTORY = 'export/pdf/courses';

	/**
	 * Constructor.
	 *
	 * Initializes the PDF exporter with a course object and sets up the mPDF instance.
	 *
	 * @since 2.21.0
	 *
	 * @param Course $course The course object to export.
	 */
	public function __construct( Course $course ) {
		$this->course = $course;
		$this->initialize_mpdf();
	}

	/**
	 * Initialize mPDF instance with better defaults.
	 *
	 * Sets up the mPDF configuration with appropriate page size, margins,
	 * fonts, and other settings for optimal PDF output.
	 *
	 * @since 2.21.0
	 *
	 * @return void
	 */
	protected function initialize_mpdf() {
		if ( $this->mpdf instanceof Mpdf ) {
			return;
		}

		// Create temp directory for mPDF
		$temp_dir = masteriyo_get_temp_dir() . '/mpdf';
		if ( ! file_exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		try {
			// Try with UTF-8 mode and default fonts
			$this->mpdf = new Mpdf(
				array(
					'tempDir'           => $temp_dir,
					'mode'              => 'utf-8',
					'format'            => 'A4',
					'margin_left'       => 20,
					'margin_right'      => 20,
					'margin_top'        => 30,
					'margin_bottom'     => 25,
					'margin_header'     => 10,
					'margin_footer'     => 10,
					'default_font_size' => 11,
					'simpleTables'      => false,
					'packTableData'     => false,
					'useSubstitutions'  => false,
					'useAdobeCJK'       => false,
					'autoScriptToLang'  => false,
					'autoLangToFont'    => false,
				)
			);
		} catch ( \Exception $e ) {
			// If that fails, use core fonts mode which doesn't require TTF files
			$this->mpdf = new Mpdf(
				array(
					'tempDir'           => $temp_dir,
					'mode'              => 'c', // Core fonts mode
					'format'            => 'A4',
					'margin_left'       => 20,
					'margin_right'      => 20,
					'margin_top'        => 30,
					'margin_bottom'     => 25,
					'margin_header'     => 10,
					'margin_footer'     => 10,
					'default_font_size' => 11,
				)
			);
		}

		$this->mpdf->setMBencoding( 'UTF-8' );
		$this->mpdf->SetDisplayMode( 'fullpage' );
		$this->mpdf->setAutoTopMargin       = 'stretch';
		$this->mpdf->setAutoBottomMargin    = 'stretch';
		$this->mpdf->shrink_tables_to_fit   = 1;
		$this->mpdf->keep_table_proportions = true;
		$this->mpdf->use_kwt                = true;
	}

	/**
	 * Generate the PDF content.
	 *
	 * Main method that orchestrates the PDF generation process by adding
	 * styles, cover page, course details, and curriculum content.
	 *
	 * @since 2.21.0
	 *
	 * @return string The PDF content as string.
	 * @throws Exception If PDF generation fails.
	 */
	public function generate() {
		try {
			$this->add_styles();
			$this->add_cover_page();
			$this->add_course_details();
			$this->add_curriculum();
			return $this->mpdf->Output( '', Destination::STRING_RETURN );
		} catch ( \Mpdf\MpdfException $e ) {
			throw new \Exception( 'Failed to generate PDF: ' . esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Add global styles for the PDF.
	 *
	 * Defines CSS styles for the PDF document to ensure consistent
	 * formatting and visual appearance throughout the document.
	 *
	 * @since 2.21.0
	 *
	 * @return void
	 */
	protected function add_styles() {
		$css = '
        <style>
				* {
					margin: 0px;
					padding: 0px;
					box-sizing: border-box;
					font-family: sans-serif;
				}

				h1, h2, h3, h4, h5, h6 {
					font-family: sans-serif;
					font-weight: 600;
					color: #222222;
					margin-bottom: 12px;
					padding: 0;
				}

				h1 {
					font-size: 36px;
				}

				h2 {
					font-size: 26px;
				}

				h3 {
					font-size: 20px;
				}

				h4 {
					font-size: 16px;
				}

				p {
					font-family: sans-serif;
					font-size: 16px;
					color: #383838;
					font-weight: 400;
				}
				ul, ol {
					margin-bottom: 15px;
					padding-left: 30px;
				}

				li {
					margin-bottom: 8px;
					line-height: 1.5;
				}
        .masteriyo-course-curriculum--content_answer {
            margin: 15px 0;
            padding: 10px 15px;
            background-color: #F0F8FF;
            border-radius: 6px;
        }
        .masteriyo-course-curriculum--content_answer .masteriyo-text {
            font-weight: 600;
            color: #4584FF;
            display: block;
            margin-bottom: 5px;
        }
        .masteriyo-course-curriculum--content_answer .masteriyo-correct-answer {
            color: #2E7D32;
            font-weight: 500;
        }
				.masteriyo-course-curriculum--content_desc-link {
					margin-top: 24px;
				}
				.masteriyo-curriculum-link {
					color: #4584FF;
					font-size: 16px;
					font-weight: 400;
				}
        .masteriyo-course-curriculum--content_meeting-table {
          width: 100%;
        }
        .masteriyo-course-curriculum--content_meeting-table tr:nth-child(odd) {
            background-color: #F9F9F9;
        }
        .masteriyo-course-curriculum--content_meeting-table td{
					padding: 12px 10px;
					border-bottom: 1px solid #F0F0F0;
        }
				.masteriyo-desc, .masteriyo-desc p {
					margin-top: 0px !important;
					margin-bottom: 0px !important;
				}
    		</style>';

		$this->mpdf->WriteHTML( $css, HTMLParserMode::HEADER_CSS );
	}

	/**
	 * Add cover page to the PDF with modern design.
	 *
	 * Creates an attractive cover page with course title, featured image,
	 * author information, and publication date.
	 *
	 * @since 2.21.0
	 *
	 * @return void
	 * @throws Exception If adding the cover page fails.
	 */
	protected function add_cover_page() {
		$cover_css = '
        <style>
					.masteriyo-course-pdf-cover-page {
						padding: 100px 0;
					}
					.masteriyo-course-pdf-cover-page .masteriyo-title {
						text-align: center;
						text-transform: uppercase;
						margin-bottom: 24px;
					}
					.masteriyo-course-pdf-cover-page img {
						width: 100%;
						max-height: 600px;
						border-radius: 8px;
						object-fit: cover;
					}
					.masteriyo-course-pdf-author-date-wrapper {
						width: 100%;
						margin: 20px 0 0;
					}
					.masteriyo-course-pdf-author-date-wrapper table {
						width: 100%;
						margin: 0 auto;
					}
					.masteriyo-course-pdf-author-date-wrapper .masteriyo-label,
					.masteriyo-course-pdf-author-date-wrapper .masteriyo-text {
						font-size: 18px;
						font-weight: 600;
					}
        </style>';

		$this->mpdf->WriteHTML( $cover_css, HTMLParserMode::HEADER_CSS );

		// Start cover page HTML.
		$cover_html = '<div class="masteriyo-course-pdf-cover-page">';

		// Course title
		$cover_html .= '<h1 class="masteriyo-title">' . esc_html( $this->course->get_name() ) . '</h1>';

		// Featured image
		$featured_image_url = $this->course->get_featured_image_url();
		if ( ! empty( $featured_image_url ) ) {
			$featured_image_url = $this->convert_url_for_mpdf( $this->course->get_featured_image_url( 'masteriyo_single' ) );

			$cover_html .= '<div style="text-align: center;"><a href="' . esc_url( $featured_image_url ) . '"><img src="' . esc_url( $featured_image_url ) . '" alt="' . esc_attr( $this->course->get_name() ) . '" style="display: block; margin: 0 auto;"></a></div>';
		}

		// Author and publish date using table layout.
		$author       = masteriyo_get_user( $this->course->get_author_id() );
		$author_name  = $author ? esc_html( $author->get_display_name() ) : __( 'Unknown Author', 'learning-management-system' );
		$publish_date = $this->course->get_date_created() ?
			esc_html( masteriyo_format_datetime( $this->course->get_date_created(), 'F j, Y' ) ) :
			__( 'Unknown Date', 'learning-management-system' );

		$cover_html .= '
			<div class="masteriyo-course-pdf-author-date-wrapper">
				<table>
					<tr>
						<td align="left">
							<div class="masteriyo-course-pdf-info">
								<span class="masteriyo-label">' . esc_html__( 'Created By:', 'learning-management-system' ) . '</span>
								<span class="masteriyo-text">' . $author_name . '</span>
							</div>
						</td>
						<td align="right">
							<div class="masteriyo-course-pdf-info">
								<span class="masteriyo-label">' . esc_html__( 'Published On:', 'learning-management-system' ) . '</span>
								<span class="masteriyo-text">' . $publish_date . '</span>
							</div>
						</td>
					</tr>
				</table>
			</div>';

		$cover_html .= '</div>';

		try {
			$this->mpdf->WriteHTML( $cover_html, HTMLParserMode::HTML_BODY );
			$this->mpdf->AddPage();
		} catch ( \Mpdf\MpdfException $e ) {
			throw new Exception( 'Failed to add cover page: ' . esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Add course details section with improved layout.
	 *
	 * Creates a section with course metadata including difficulty, categories,
	 * duration, ratings, available seats, and other course information.
	 * Uses table-based layout for mPDF compatibility.
	 *
	 * @since 2.21.0
	 *
	 * @return void
	 * @throws Exception If adding course details fails.
	 */
	protected function add_course_details() {
		$css = '
        <style>
					.masteriyo-course-wrapper {
						max-width: 1200px;
						margin: 40px auto;
					}
					.masteriyo-course-details-table table {
						width: 100%;
						border-collapse: collapse;
					}
					.masteriyo-course-details-table td {
						font-size: 16px;
						color: #383838;
						padding: 12px 0;
						border-bottom: 1px solid #F0F0F0;
						border-top: none;
						border-left: none;
						border-right: none;
					}
					.masteriyo-course-highlights {
						margin-top: 0px;
						margin-bottom: 0px;
					}
					.masteriyo-course-highlights ul li {
						color: #383838;
						font-size: 16px;
						font-weight: 400;
						line-height: 26px;
						margin-bottom: 8px;
					}
					.masteriyo-course-highlights ul li p {
						margin-top: 0px !important;
						margin-bottom: 0px !important;
						padding: 0px !important;
					}
					.masteriyo-course-curriculum {
						margin-bottom: 30px;
						padding-bottom: 30px;
					}
					.masteriyo-course-details .masteriyo-title {
						margin-bottom: 2px;
					}
					.masteriyo-course-highlights .masteriyo-title {
						margin-bottom: 2px;
					}
        </style>';

		$this->mpdf->WriteHTML( $css, HTMLParserMode::HEADER_CSS );

		$html = '
        <div class="masteriyo-course-details masteriyo-course-wrapper" style="margin-bottom:0px;">
            <h2 class="masteriyo-title">' . esc_html__( 'Course Details', 'learning-management-system' ) . '</h2>
            <div class="masteriyo-course-details-table">
                <table border="0" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                <tbody>';

		// Difficulty
		$difficulty = $this->course->get_difficulty();
		if ( $difficulty ) {
			$html .= '
                        <tr>
                            <td>' . esc_html__( 'Difficulty', 'learning-management-system' ) . '</td>
                            <td>' . esc_html( $difficulty['name'] ?? '' ) . '</td>
                        </tr>';
		}

		// Categories
		$categories = $this->course->get_categories();
		if ( ! empty( $categories ) ) {
			$category_names = array_map(
				function ( $category ) {
					return esc_html( $category->get_name() );
				},
				$categories
			);
			$html          .= '
                        <tr>
                            <td>' . esc_html__( 'Categories', 'learning-management-system' ) . '</td>
                            <td>' . implode( ', ', $category_names ) . '</td>
                        </tr>';
		}

		// Last Updated
		$modified_date  = strtotime( $this->course->get_date_modified() );
		$formatted_date = gmdate( 'F j, Y', $modified_date );
		$html          .= '
                        <tr>
                            <td>' . esc_html__( 'Last Updated', 'learning-management-system' ) . '</td>
                            <td>' . esc_html( $formatted_date ) . '</td>
                        </tr>';

		// Duration
		$html .= '
                        <tr>
                            <td>' . esc_html__( 'Duration', 'learning-management-system' ) . '</td>
                            <td>' . esc_html( masteriyo_minutes_to_time_length_string( $this->course->get_duration() ) ) . '</td>
                        </tr>';

		// Rating
		$html .= '
                        <tr>
                            <td>' . esc_html__( 'Rating', 'learning-management-system' ) . '</td>
                            <td>' . esc_html( masteriyo_format_decimal( $this->course->get_average_rating(), 1, true ) ) . ' (' . esc_html( $this->course->get_review_count() ) . ' ' . esc_html__( 'reviews', 'learning-management-system' ) . ')</td>
                        </tr>';

		// Available Seats
		if ( $this->course->get_enrollment_limit() > 0 ) {
			$remaining_seats = $this->course->get_enrollment_limit() - masteriyo_count_enrolled_users( $this->course->get_id() );
			$html           .= '
                        <tr>
                            <td>' . esc_html( _nx( 'Available Seat', 'Available Seats', $remaining_seats, 'Available Seats Count', 'learning-management-system' ) ) . '</td>
                            <td>' . esc_html( $remaining_seats ) . '</td>
                        </tr>';
		}

		// Course Start URL.
		$html .= '
                        <tr>
                            <td>' . esc_html__( 'Course Start URL', 'learning-management-system' ) . '</td>
                            <td><a href="' . esc_url( $this->course->start_course_url() ) . '">' . wp_specialchars_decode( $this->course->get_name() ) . '</a></td>
                        </tr>';

		$html .= '
                    </tbody>
                </table>
            </div>
        </div>';

		// Course Description
		if ( ! empty( trim( $this->course->get_description() ) ) ) {
			$html .= '
        <div class="masteriyo-course-description masteriyo-course-wrapper">
            <h2 class="masteriyo-title">' . esc_html__( 'Description', 'learning-management-system' ) . '</h2>
            <div class="masteriyo-desc">' . wp_kses_post( $this->course->get_description() ) . '</div>
        </div>';
		}

		// Course Highlights
		if ( $this->course->get_highlights() ) {
			$html .= '
        <div class="masteriyo-course-highlights masteriyo-course-wrapper">
            <h2 class="masteriyo-title">' . esc_html__( 'Course Highlights', 'learning-management-system' ) . '</h2>' . wp_kses_post( masteriyo_format_course_highlights( $this->course->get_highlights() ) ) . ' </div>';
		}

		try {
			$this->mpdf->WriteHTML( $html, HTMLParserMode::HTML_BODY );
			$this->mpdf->AddPage();
		} catch ( \Mpdf\MpdfException $e ) {
			throw new Exception( 'Failed to add course details: ' . esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Add curriculum section with improved design.
	 *
	 * Creates sections for course curriculum including lessons, quizzes,
	 * assignments, and meetings. Replaces flex layouts with table-based
	 * layouts for mPDF compatibility.
	 *
	 * @since 2.21.0
	 *
	 * @return void
	 * @throws Exception If adding curriculum fails.
	 */
	protected function add_curriculum() {
		$sections = masteriyo_get_course_structure( $this->course->get_id() );
		if ( empty( $sections ) ) {
			return;
		}

		$css = '
        <style>
					.masteriyo-course-curriculum {
						margin-bottom: 50px;
						padding-bottom: 60px;
						border-bottom: 1px solid #E2E2E2;
					}
					.masteriyo-course-curriculum:last-child {
						margin-bottom: 0;
						padding-bottom: 0;
						border-bottom: 0;
					}
					.masteriyo-course-curriculum .masteriyo-title {
						margin-bottom: 12px;
					}
					.masteriyo-course-info-table {
						border-collapse: separate !important;
						border-spacing: 8px 0 !important; /* Horizontal spacing only */
						margin-bottom: 24px;
					}
					.masteriyo-course-info-table tr {
						display: table-row !important;
					}
					.masteriyo-course-info-table td {
						color: #383838;
						font-size: 16px;
						font-weight: 400;
						padding: 0 !important;
						white-space: nowrap;
						vertical-align: middle !important;
						position: relative;
					}
					.masteriyo-course-info-table td.separator {
						width: 16px !important;
						text-align: center !important;
					}
					.masteriyo-course-info-table .dot {
						display: inline-block !important;
						background: #383838 !important;
						width: 6px !important;
						height: 6px !important;
						border-radius: 50% !important;
						position: absolute !important;
						top: 50% !important;
						left: 50% !important;
						transform: translate(-50%, -50%) !important;
					}
					.masteriyo-course-curriculum--items {
						margin-bottom: 40px;
					}
					.masteriyo-course-curriculum--items:last-child {
						margin-bottom: 0;
					}
					.masteriyo-course-curriculum--header {
						background: #F2F2F2;
						padding: 12px 16px;
						border-radius: 8px 8px 0 0;
						border: 1px solid #F2F2F2;
					}
					.masteriyo-course-curriculum--header-table {
						width: 100%;
						border-collapse: collapse;
					}
					.masteriyo-course-curriculum--header-table td {
						padding: 0;
					}
					.masteriyo-course-curriculum--header-table td.icon-cell {
						width: 34px;
						vertical-align: middle;
						padding-right: 10px;
					}
					.masteriyo-course-curriculum--header-table td.title-cell {
						vertical-align: middle;
					}
					.masteriyo-course-curriculum--header svg {
						width: 24px;
						height: 24px;
						fill: #222222;
					}
					.masteriyo-course-curriculum--header .masteriyo-title {
						margin-bottom: 0;
					}
					.masteriyo-course-curriculum--content {
						padding: 20px;
						border: 1px solid #E2E2E2;
						border-top: 0;
						border-radius: 0 0 8px 8px;
					}
					.masteriyo-course-curriculum--content-table {
						width: 100%;
						border-collapse: collapse;
					}
					.masteriyo-course-curriculum--content-table td {
						vertical-align: top;
						padding: 0;
					}
					.masteriyo-course-curriculum--content-table td.image-cell {
						width: 500px;
						padding-right: 24px;
					}
					.masteriyo-course-curriculum--content img {
						max-width: 500px;
						width: 100%;
						border-radius: 4px;
						object-fit: cover;
					}
					.masteriyo-course-curriculum--content_desc .masteriyo-title, .masteriyo-course-curriculum--content_desc .masteriyo-desc {
						padding: 0px !important;
						margin-top: 0px !important;
						margin-bottom: 0px !important;
					}
					.masteriyo-course-curriculum--content_desc .masteriyo-desc p {
						padding: 0px !important;
						margin-top: 0px !important;
						margin-bottom: 0px !important;
						line-height: 1.5;
					}
					.masteriyo-course-curriculum--content_desc ul li {
						color: #383838;
						font-size: 16px;
						font-weight: 400;
						line-height: 26px;
						margin-bottom: 8px;
					}
					.masteriyo-course-curriculum--content_desc ul li p {
						margin-top: 0px !important;
						margin-bottom: 0px !important;
						padding: 0px !important;
					}
					.masteriyo-course-curriculum--content_desc-link {
						margin-top: 0px;
					}
					.masteriyo-course-curriculum--content_desc-link .masteriyo-curriculum-link {
						color: #4584FF;
						font-size: 16px;
						font-weight: 400;
					}
					.masteriyo-course-curriculum--content-wrapper {
						margin-bottom: 30px;
						padding-left: 15px;
						margin-bottom: 0px;
					}
					.masteriyo-course-curriculum--content-wrapper:last-child {
						margin-bottom: 0px;
					}
					.masteriyo-course-curriculum--content_quiz {
						padding: 0px 16px;
						font-size: 16px;
						font-weight: 600;
						color: #383838;
					}
					.masteriyo-course-curriculum--content_quiz-item {
						margin-bottom: 30px;
					}
					.masteriyo-course-curriculum--content_quiz-item:last-child {
						margin-bottom: 0px;
					}
					.masteriyo-course-curriculum--content_question {
						margin: 8px 0 10px;
					}
					.masteriyo-course-curriculum--content_question .masteriyo-question {
						margin-left: -20px;
						font-weight: 500;
					}
					.masteriyo-course-curriculum--content_question ol {
						margin-top: 4px;
					}
					.masteriyo-course-curriculum--content_question ol li {
						font-weight: 400;
						margin-bottom: 0px;
					}
					.masteriyo-course-curriculum--content_answer {
						margin-left: -20px;
					}
					.masteriyo-course-curriculum--content_answer .masteriyo-text {
						font-weight: 500;
					}
					.masteriyo-course-curriculum--content_answer .masteriyo-correct-answer {
						font-weight: 400;
					}
					.masteriyo-course-curriculum--content_assignment-table {
						width: 100%;
						margin-bottom: 16px;
					}
					.masteriyo-course-curriculum--content_assignment__items .masteriyo-text {
						font-size: 16px;
						color: #383838;
						font-weight: 600;
					}
					.masteriyo-course-curriculum--content_assignment__items .masteriyo-points {
						font-size: 16px;
						color: #383838;
						font-weight: 400;
					}
					.masteriyo-course-curriculum--content_meeting-table {
						width: 100%;
						border-collapse: collapse;
					}
					.masteriyo-course-curriculum--content_meeting-table td {
						padding: 8px;
					}
					.masteriyo-course-curriculum--content_meeting-table td.label {
						font-size: 16px;
						color: #383838;
						font-weight: 600;
					}
					.masteriyo-course-curriculum--content_meeting-table td.value {
						font-size: 16px;
						color: #383838;
						font-weight: 400;
						text-align: right;
					}
        </style>';

		$this->mpdf->WriteHTML( $css, HTMLParserMode::HEADER_CSS );

		$html = '
        <div class="masteriyo-course-learn masteriyo-course-wrapper">
            <h2 class="masteriyo-title">' . esc_html__( 'What You\'ll Learn', 'learning-management-system' ) . '</h2>';

		foreach ( $sections as $section ) {
			$lesson_count      = get_course_section_children_count_by_section( $section->get_id(), PostType::LESSON );
			$quiz_count        = get_course_section_children_count_by_section( $section->get_id(), PostType::QUIZ );
			$google_meet_count = get_course_section_children_count_by_section( $section->get_id(), PostType::GOOGLEMEET );
			$zoom_count        = get_course_section_children_count_by_section( $section->get_id(), PostType::ZOOM );
			$assignment_count  = get_course_section_children_count_by_section( $section->get_id(), PostType::ASSIGNMENT );

			$html .= '
            <div class="masteriyo-course-curriculum">
                <h3 class="masteriyo-title">' . esc_html( $section->get_name() ) . '</h3>';

			$meta_parts = array();
			if ( $lesson_count > 0 ) {
				/* translators: %1$s: Lessons count */
				$meta_parts[] = sprintf( _nx( '%d Lesson', '%d Lessons', $lesson_count, 'Lessons count', 'learning-management-system' ), $lesson_count );
			}
			if ( $quiz_count > 0 ) {
				/* translators: %1$s: Quizzes count */
				$meta_parts[] = sprintf( _nx( '%d Quiz', '%d Quizzes', $quiz_count, 'Quizzes count', 'learning-management-system' ), $quiz_count );
			}
			if ( $google_meet_count > 0 ) {
				/* translators: %1$s: Meetings count */
				$meta_parts[] = sprintf( _nx( '%d Meeting', '%d Meetings', $google_meet_count, 'Google Meet count', 'learning-management-system' ), $google_meet_count );
			}
			if ( $zoom_count > 0 ) {
				/* translators: %1$s: Meetings count */
				$meta_parts[] = sprintf( _nx( '%d Zoom', '%d Zooms', $zoom_count, 'Zoom count', 'learning-management-system' ), $zoom_count );
			}
			if ( $assignment_count > 0 ) {
				/* translators: %1$s: Assignments count */
				$meta_parts[] = sprintf( _nx( '%d Assignment', '%d Assignments', $assignment_count, 'Assignments count', 'learning-management-system' ), $assignment_count );
			}

			if ( ! empty( $meta_parts ) ) {
				$html .= '<table class="masteriyo-course-info-table" style="border-collapse: separate; border-spacing: 12px 0; margin-bottom: 24px;">';
				$html .= '<tr>';

				foreach ( $meta_parts as $index => $part ) {
					if ( $index > 0 ) {
						$html .= '<td class="separator" style="width: 24px; text-align: center; padding: 0 6px; vertical-align: middle;">';
						$html .= '<span style="display: inline-block; font-size: 20px; color: #383838; vertical-align: middle; line-height: 1; font-weight: bold;">•</span>';
						$html .= '</td>';
					}
					$html .= '<td style="padding: 0; white-space: nowrap; vertical-align: middle;">' . $part . '</td>';
				}

				$html .= '</tr>';
				$html .= '</table>';
			}

			$objects = get_course_section_children_by_section( $section->get_id() );
			if ( ! empty( $objects ) ) {
				foreach ( $objects as $object ) {
					$html .= $this->get_curriculum_item_html( $object );
				}
			}

			$html .= '
            </div>';
		}
		$html .= '
        </div>';

		try {
			$this->mpdf->WriteHTML( $html, HTMLParserMode::HTML_BODY );
		} catch ( \Mpdf\MpdfException $e ) {
			throw new Exception( 'Failed to add curriculum: ' . esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Get HTML for a curriculum item based on its type.
	 *
	 * Generates the appropriate HTML structure for different curriculum item types
	 * (lessons, quizzes, assignments, meetings) with consistent formatting.
	 *
	 * @since 2.21.0
	 *
	 * @param object $item The curriculum item.
	 * @return string HTML for the curriculum item.
	 */
	protected function get_curriculum_item_html( $item ) {
		$html = '
        <div class="masteriyo-course-curriculum--items">
            <div class="masteriyo-course-curriculum--header">
                <table class="masteriyo-course-curriculum--header-table">
                    <tr>
                        <td class="icon-cell">' . $item->get_icon() . '</td>
                        <td class="title-cell"><h4 class="masteriyo-title">' . esc_html( $item->get_name() ) . '</h4></td>
                    </tr>
                </table>
            </div>

            <div class="masteriyo-course-curriculum--content">';

		// Handle different item types
		switch ( $item->get_object_type() ) {
			case 'lesson':
				$html .= $this->get_lesson_content( $item );
				break;
			case 'quiz':
				$html .= $this->get_quiz_content( $item );
				break;
			case 'assignment':
				$html .= $this->get_assignment_content( $item );
				break;
			case 'google-meet':
				$html .= $this->get_googlemeet_content( $item );
				break;
			case 'zoom':
				$html .= $this->get_zoom_content( $item );
				break;
			default:
				if ( method_exists( $item, 'get_description' ) && $item->get_description() ) {
					$html .= '<div class="masteriyo-course-curriculum--content_desc">
                        <h4 class="masteriyo-title">' . esc_html__( 'Description', 'learning-management-system' ) . '</h4>
                        <p class="masteriyo-desc">' . wp_kses_post( wpautop( $item->get_description() ) ) . '</p>
                    </div>';
				}
		}

		$html .= '
            </div>
        </div>';

		return $html;
	}

	/**
	 * Get formatted content for a lesson.
	 *
	 * Formats lesson content including featured image, description,
	 * and media URLs (video, PDF, audio) for display in the PDF.
	 *
	 * @since 2.21.0
	 *
	 * @param \Masteriyo\Models\Lesson $lesson The lesson object.
	 * @return string HTML content.
	 */
	protected function get_lesson_content( $lesson ) {
		$type = '';
		$url  = '';
		if ( ! empty( $lesson->get_video_source_url() ) ) {
			if ( 'live-stream' === $lesson->get_video_source() ) {
				$type = 'Live Stream';
			} else {
				$type = 'Video';
			}
			$url = $lesson->get_video_source_url();
		} elseif ( ! empty( $lesson->get_pdf() ) ) {
			$urls = array();

			foreach ( $lesson->get_pdf() as $pdf_url ) {
				if ( ! empty( $pdf_url ) ) {
					$urls[] = $pdf_url['url'] ?? '';
				}
			}
			$url = implode( ', ', array_filter( $urls ) );
			if ( count( $urls ) > 1 ) {
				$type = 'PDFs';
			} else {
				$type = 'PDF';
			}
		} elseif ( ! empty( trim( $lesson->get_audio_source_url() ) ) || ! empty( $lesson->get_audio_source_files() ) ) {
			$type = 'Audio';
			$url  = trim( $lesson->get_audio_source_url() );
			if ( empty( $url ) ) {
				$file = current( $lesson->get_audio_source_files() );
				$url  = wp_get_attachment_url( $file );
			}
		}

		$image_url = $this->convert_url_for_mpdf( $lesson->get_featured_image_url( 'masteriyo_single' ) );

		$html = '';

		// Use table-based layout if image is present
		if ( ! empty( $lesson->get_featured_image() ) && ! empty( $image_url ) ) {
			$html .= '
            <table style="width: 100%; margin-bottom: 12px;">
                <tr>
                    <td style="text-align: center;">
                        <a href="' . esc_url( $image_url ) . '">
                            <img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $lesson->get_name() ) . '" style="max-width: 100%; height: auto; border-radius: 4px;" />
                        </a>
                    </td>
                </tr>
            </table>';
		}

		$html .= '<div class="masteriyo-course-curriculum--content_desc">';

		if ( ! empty( trim( $lesson->get_description() ) ) ) {
				$html .= '
						<h3 class="masteriyo-title">' . esc_html__( 'Description', 'learning-management-system' ) . '</h3>
						<p class="masteriyo-desc" style="margin-top: 0px;">' . wp_kses_post( $lesson->get_description() ) . '</p>';
		}

		if ( ! empty( $url ) ) {
				$html .= '
						<div class="masteriyo-course-curriculum--content_desc-link">
								<h4 class="masteriyo-title">' . esc_html( $type ) . ' URL</h4>
								<a href="' . esc_url( $url ) . '" class="masteriyo-curriculum-link">' . esc_url( $url ) . '</a>
						</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Get formatted content for a quiz.
	 *
	 * Formats quiz content including description, metadata (questions count,
	 * pass mark, duration), and individual quiz questions with answers.
	 *
	 * @since 2.21.0
	 *
	 * @param \Masteriyo\Models\Quiz $quiz The quiz object.
	 * @return string HTML content.
	 */
	protected function get_quiz_content( $quiz ) {
		$html = '
		<div class="masteriyo-course-curriculum--content_quiz">';

		// Description section
		if ( ! empty( trim( $quiz->get_description() ) ) ) {
			$html .= '
			<div class="masteriyo-course-curriculum--content_desc">';
			if ( $quiz->get_description() ) {
				$html .= '
				<h3 class="masteriyo-title">' . esc_html__( 'Description', 'learning-management-system' ) . '</h3>
				<p class="masteriyo-desc">' . wp_kses_post( wpautop( $quiz->get_description() ) ) . '</p>';
			}
			$html .= '
			</div>';
		}

		// Quiz meta items - replace with table
		$html .= '
			<table class="masteriyo-course-curriculum--content_meeting-table">
				<tr>
					<td class="label">' . esc_html__( 'Questions:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( $quiz->get_questions_count() ) . '</td>
				</tr>
				<tr>
					<td class="label">' . esc_html__( 'Pass Mark:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( $quiz->get_pass_mark() ) . '</td>
				</tr>
				<tr>
					<td class="label">' . esc_html__( 'Full Mark:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( $quiz->get_full_mark() ) . '</td>
				</tr>';

		if ( $quiz->get_duration() ) {
			$html .= '
				<tr>
					<td class="label">' . esc_html__( 'Duration:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( masteriyo_minutes_to_time_length_string( $quiz->get_duration() ) ) . '</td>
				</tr>';
		}

		$html .= '
			</table>';

		$args = array(
			'post_parent'    => $quiz->get_id(),
			'posts_per_page' => 100,
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
		);

		// Quiz questions
		$results   = masteriyo_get_questions_by_quiz_with_pagination( $args );
		$questions = $results['objects'] ?? array();

		if ( ! empty( $questions ) ) {
			$html           .= '
			<div class="masteriyo-course-curriculum--content_quiz-items">';
			$question_number = 1;
			foreach ( $questions as $question ) {
				$html .= $this->format_quiz_question( $question, $question_number );
				++$question_number;
			}
			$html .= '
			</div>';
		}

		$html .= '
		</div>';

		return $html;
	}

	/**
	 * Format a quiz question for display.
	 *
	 * Creates a formatted representation of a quiz question including the question text,
	 * answer options, correct answers, and explanations based on question type.
	 *
	 * @since 2.21.0
	 *
	 * @param \Masteriyo\Models\Question\Question $question The question object.
	 * @param int $question_number The question number.
	 * @return string HTML content.
	 */
	protected function format_quiz_question( $question, $question_number ) {
		$question_type = $question->get_type();

		$html = '
		<div class="masteriyo-course-curriculum--content_quiz-item">
			<div class="masteriyo-course-curriculum--content-wrapper">
				<div class="masteriyo-course-curriculum--content_question">
					<span class="masteriyo-question">
						<span class="masteriyo-question-label">Q' . $question_number . '. ' . wp_kses_post( $question->get_name() ) . '</span> </span>';

		$answers = $question->get_answers();

		if ( in_array( $question_type, array( 'audio', 'video' ), true ) ) {
			$files = array_filter(
				is_array( $question->get_meta( '_files' ) ) ? $question->get_meta( '_files' ) : array(),
				function ( $file ) {
					if ( isset( $file['source'] ) && 'self-hosted' === $file['source'] ) {
						$post = get_post( $file['id'] );
						return $post && 'attachment' === $post->post_type;
					}
					return true;
				}
			);

			$file = current( $files );

			$url = ! empty( $file ) && isset( $file['url'] ) ? $file['url'] : '';
			if ( ! empty( $url ) ) {
				$html .= '
					<div class="masteriyo-course-curriculum--content_desc-link">
						<h4 class="masteriyo-title">' . ucfirst( $question_type ) . ' URL</h4>
						<a href="' . esc_url( $url ) . '" class="masteriyo-curriculum-link">' . esc_html( $url ) . '</a>
					</div>';
			}
		} elseif ( ! empty( $answers ) || in_array( $question_type, array( 'true-false', 'text-answer', 'fill-in-the-blanks', 'sortable' ), true ) ) {
			$html .= '
					<div class="question-answers">';
			switch ( $question_type ) {
				case 'true-false':
					$html .= '
					<ol type="A">
						<li>True</li>
						<li>False</li>
					</ol>';
					break;
				case 'single-choice':
				case 'multiple-choice':
					$html .= '
					<ol type="A">';
					foreach ( $answers as $index => $answer ) {
						$answer      = (array) $answer;
						$answer_text = isset( $answer['name'] ) ? $answer['name'] : '';
						$html       .= '
						<li>' . esc_html( $answer_text ) . '</li>';
					}
					$html .= '
					</ol>';
					break;
				case 'fill-in-the-blanks':
					if ( is_string( $answers ) && preg_match( '/\{\{\s*(.*?)\s*\}\}/', $answers ) ) {
						$question_text = preg_replace( '/\{\{\s*.*?\s*\}\}/', '_______', $answers );

						$html .= '<br />' . wpautop( wp_kses_post( $question_text ) );
					}
					break;
				case 'text-answer':
					// No answer options displayed
					break;
				case 'matching':
					// Collect all matches
					$matches = array_filter(
						array_map(
							function ( $answer ) {
								$answer = (array) $answer;
								$type   = isset( $answer['type'] ) ? $answer['type'] : '';
								$match  = array(
									'match' => isset( $answer['match'] ) ? $answer['match'] : '',
								);

								if ( 'ImageToImage' === $type ) {
									$match['imageMatchUrl'] = isset( $answer['imageMatchUrl'] ) ? $answer['imageMatchUrl'] : '';
								}

								if ( $match['match'] ) {
									return $match;
								}

								return null;
							},
							$answers
						)
					);

					$html .= '
					<ol type="A">';
					foreach ( $answers as $answer ) {
						$answer = (array) $answer;
						$prompt = isset( $answer['prompt'] ) ? $answer['prompt'] : '';
						$type   = isset( $answer['type'] ) ? $answer['type'] : '';

						if ( $prompt ) {
							$display_prompt = esc_html( $prompt );

							if ( 'ImageToText' === $type && ! empty( $answer['imageUrl'] ) ) {
								$image_url      = $this->convert_url_for_mpdf( $answer['imageUrl'] );
								$display_prompt = '<a href="' . $image_url . '"><img src="' . $image_url . '" alt="Prompt Image" style="max-width: 100px; height: auto;" /></a>';
							} elseif ( 'ImageToImage' === $type && ! empty( $answer['imagePromptUrl'] ) ) {
								$image_prompt_url = $this->convert_url_for_mpdf( $answer['imagePromptUrl'] );
								$display_prompt   = '<a href="' . $image_prompt_url . '"><img src="' . $image_prompt_url . '" alt="Prompt Image" style="max-width: 100px; height: auto;" /></a>';
							}

							$html .= '
						<li>' . $display_prompt . '
							<ol type="1">';
							// Display all matches
							foreach ( $matches as $match ) {
								$display_match = esc_html( $match['match'] );
								if ( ! empty( $match['imageMatchUrl'] ) ) {
									$image_match_url = $this->convert_url_for_mpdf( $match['imageMatchUrl'] );
									$display_match   = '<a href="' . $image_match_url . '"><img src="' . $image_match_url . '" alt="Match Image" style="max-width: 100px; height: auto;" /></a>';
								}
								$html .= '
								<li>' . $display_match . '</li>';
							}
							$html .= '
							</ol>
						</li>';
						}
					}
					$html .= '
					</ol>';
					break;
				case 'sortable':
					$html .= '
					<ol type="A">';
					foreach ( $answers as $index => $answer ) {
						$answer      = (array) $answer;
						$answer_text = isset( $answer['name'] ) ? $answer['name'] : '';
						$html       .= '
						<li>' . esc_html( $answer_text ) . '</li>';
					}
					$html .= '
					</ol>';
					break;
			}
			$html .= '
					</div>';
		}

		$html .= '
				</div>
				<div class="masteriyo-course-curriculum--content_answer">
					<span class="masteriyo-text">' . esc_html__( 'Correct Answer:', 'learning-management-system' ) . '</span>
					<span class="masteriyo-correct-answer">';

		switch ( $question_type ) {
			case 'true-false':
				foreach ( $answers as $answer ) {
					$answer = (array) $answer;
					if ( isset( $answer['correct'] ) && $answer['correct'] ) {
						$html .= esc_html( isset( $answer['name'] ) ? ucfirst( $answer['name'] ) : '' );
						break;
					}
				}
				break;
			case 'single-choice':
				foreach ( $answers as $index => $answer ) {
					$answer = (array) $answer;
					if ( isset( $answer['correct'] ) && $answer['correct'] ) {
						$answer_text = isset( $answer['name'] ) ? $answer['name'] : '';
						$letter      = chr( 65 + $index ); // Convert index to letter (A, B, C, ...)
						$html       .= esc_html( $letter . '. ' . $answer_text );
						break;
					}
				}
				break;
			case 'multiple-choice':
				$correct_answers = array();
				foreach ( $answers as $index => $answer ) {
					$answer = (array) $answer;
					if ( isset( $answer['correct'] ) && $answer['correct'] ) {
						$answer_text       = isset( $answer['name'] ) ? $answer['name'] : '';
						$letter            = chr( 65 + $index );
						$correct_answers[] = $letter . '. ' . $answer_text;
					}
				}
				$html .= esc_html( implode( ', ', $correct_answers ) );
				break;
			case 'fill-in-the-blanks':
				if ( is_string( $answers ) && preg_match( '/\{\{\s*(.*?)\s*\}\}/', $answers, $matches ) ) {
					$correct_answer = $matches[1];
					$html          .= esc_html( $correct_answer );
				} else {
					$html .= esc_html__( 'This question requires a written answer.', 'learning-management-system' );
				}
				break;
			case 'text-answer':
				$html .= esc_html__( 'This question requires a written answer.', 'learning-management-system' );
				break;
			case 'matching':
				$matches = array();
				foreach ( $answers as $answer ) {
					$answer = (array) $answer;
					$prompt = isset( $answer['prompt'] ) ? $answer['prompt'] : '';
					$match  = isset( $answer['match'] ) ? $answer['match'] : '';
					$type   = isset( $answer['type'] ) ? $answer['type'] : '';

					if ( $prompt && $match ) {
						$display_prompt = esc_html( $prompt );
						$display_match  = esc_html( $match );

						if ( 'ImageToText' === $type && ! empty( $answer['imageUrl'] ) ) {
							$image_url      = $this->convert_url_for_mpdf( $answer['imageUrl'] );
							$display_prompt = '<a href="' . $image_url . '"><img src="' . $image_url . '" alt="Prompt Image" style="max-width: 100px; height: auto;" /></a>';
						} elseif ( 'ImageToImage' === $type ) {
							if ( ! empty( $answer['imagePromptUrl'] ) ) {
								$image_prompt_url = $this->convert_url_for_mpdf( $answer['imagePromptUrl'] );
								$display_prompt   = '<a href="' . $image_prompt_url . '"><img src="' . $image_prompt_url . '" alt="Prompt Image" style="max-width: 100px; height: auto;" /></a>';
							}
							if ( ! empty( $answer['imageMatchUrl'] ) ) {
								$image_match_url = $this->convert_url_for_mpdf( $answer['imageMatchUrl'] );
								$display_match   = '<a href="' . $image_match_url . '"><img src="' . $image_match_url . '" alt="Match Image" style="max-width: 100px; height: auto;" /></a>';
							}
						}
						$matches[] = $display_prompt . ' → ' . $display_match;
					}
				}
				$html .= implode( ', ', $matches );
				break;
			case 'sortable':
				$sorted_answers = array();
				foreach ( $answers as $index => $answer ) {
					$answer           = (array) $answer;
					$answer_text      = isset( $answer['name'] ) ? $answer['name'] : '';
					$letter           = chr( 65 + $index );
					$sorted_answers[] = $letter . '. ' . esc_html( $answer_text );
				}
				$html .= esc_html( implode( ', ', $sorted_answers ) );
				break;
			case 'audio':
			case 'video':
				$html .= esc_html__( 'This question requires a written answer.', 'learning-management-system' );
				break;
		}

		$html .= '
					</span>
				</div>';

		// Question explanation
		if ( ! empty( trim( $question->get_answer_explanation() ) ) ) {
			$html .= '
				<div class="masteriyo-course-curriculum--content_desc-link">
					<h4 class="masteriyo-title">' . esc_html__( 'Explanation', 'learning-management-system' ) . '</h4>
					<p class="masteriyo-desc">' . wp_kses_post( $question->get_answer_explanation() ) . '</p>
				</div>';
		}

		$html .= '
			</div>
		</div>';

		return $html;
	}

	/**
	 * Get formatted content for an assignment.
	 *
	 * Formats assignment content including points, due date, description,
	 * and any associated media for display in the PDF.
	 *
	 * @since 2.21.0
	 *
	 * @param object $assignment The assignment object, as the assignment addon models it.
	 * @return string HTML content.
	 */
	protected function get_assignment_content( $assignment ) {
		$html = '
		<div class="masteriyo-course-curriculum--content_assignment">';

		// Assignment meta items
		$html .= '
			<table class="masteriyo-course-curriculum--content_assignment-table" width="100%" cellspacing="0" cellpadding="0" border="0">
				<tr>';

		$html .= '
					<td width="25%" align="left">
						<div class="masteriyo-course-curriculum--content_assignment__items">
							<span class="masteriyo-text">' . esc_html__( 'Total Points:', 'learning-management-system' ) . '</span>
							<span class="masteriyo-points">' . esc_html( $assignment->get_total_points() ) . '</span>
						</div>
					</td>';

		$html .= '
					<td width="25%" align="center">
						<div class="masteriyo-course-curriculum--content_assignment__items">
							<span class="masteriyo-text">' . esc_html__( 'Pass Points:', 'learning-management-system' ) . '</span>
							<span class="masteriyo-points">' . esc_html( $assignment->get_pass_points() ) . '</span>
						</div>
					</td>';

		if ( $assignment->get_due_date() ) {
			$html .= '
					<td width="50%" align="right">
						<div class="masteriyo-course-curriculum--content_assignment__items">
							<span class="masteriyo-text">' . esc_html__( 'Due Date:', 'learning-management-system' ) . '</span>
							<span class="masteriyo-points">' . esc_html( masteriyo_format_datetime( $assignment->get_due_date(), 'Y-m-d, g:i A' ) ) . '</span>
						</div>
					</td>';
		} else {
			$html = str_replace( 'align="center"', 'align="right"', $html );
		}

		$html .= '
				</tr>
			</table>';

		// Description and video URL
		$html .= '
			<div class="masteriyo-course-curriculum--content_desc">';

		if ( $assignment->get_description() ) {
			$html .= '
				<h4 class="masteriyo-title">' . esc_html__( 'Description', 'learning-management-system' ) . '</h4>
				<p class="masteriyo-desc">' . wp_kses_post( wpautop( $assignment->get_description() ) ) . '</p>';
		}

		// Video URL if available
		if ( $assignment->get_video_source_url() ) {
			$html .= '
				<div class="masteriyo-course-curriculum--content_desc-link">
					<h4 class="masteriyo-title">' . esc_html__( 'Video URL', 'learning-management-system' ) . '</h4>
					<a href="' . esc_url( $assignment->get_video_source_url() ) . '">' . esc_html( $assignment->get_video_source_url() ) . '</a>
				</div>';
		}

		$html .= '
			</div>';

		$html .= '
		</div>';

		return $html;
	}

	/**
	 * Get formatted content for a Google Meet.
	 *
	 * Formats Google Meet content including meeting URL, ID, start/end dates,
	 * time zone, and description for display in the PDF.
	 *
	 * @since 2.21.0
	 *
	 * @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $meet The Google Meet object.
	 * @return string HTML content.
	 */
	protected function get_googlemeet_content( $meet ) {
		$html = '
		<div class="masteriyo-course-curriculum--content_meeting">';

		// Meeting meta items - replace with table
		$html .= '
			<table class="masteriyo-course-curriculum--content_meeting-table">';

		if ( $meet->get_meet_url( $meet ) ) {
			$meeting_url = $meet->get_meet_url( $meet );
			$html       .= '
				<tr>
					<td class="label">' . esc_html__( 'Meeting URL:', 'learning-management-system' ) . '</td>
					<td class="value"><a href="' . esc_url( $meeting_url ) . '" class="masteriyo-curriculum-link">' . esc_html( $meeting_url ) . '</a></td>
				</tr>';
		}

		if ( $meet->get_meeting_id() ) {
			$html .= '
				<tr>
					<td class="label">' . esc_html__( 'Meeting ID:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( $meet->get_meeting_id() ) . '</td>
				</tr>';
		}

		if ( $meet->get_starts_at() ) {
			$html .= '
				<tr>
					<td class="label">' . esc_html__( 'Start Date:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( gmdate( 'Y-m-d, g:i A', strtotime( $meet->get_starts_at() ) ) ) . '</td>
				</tr>';
		}

		if ( $meet->get_ends_at() ) {
			$html .= '
				<tr>
					<td class="label">' . esc_html__( 'End Date:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( gmdate( 'Y-m-d, g:i A', strtotime( $meet->get_ends_at() ) ) ) . '</td>
				</tr>';
		}

		if ( $meet->get_time_zone() ) {
			$html .= '
				<tr>
					<td class="label">' . esc_html__( 'Time Zone:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( $meet->get_time_zone() ) . '</td>
				</tr>';
		}

		$html .= '
			</table>';

		// Description section
		$html .= '
			<div class="masteriyo-course-curriculum--content_desc">';

		if ( $meet->get_description() ) {
			$html .= '
				<h3 class="masteriyo-title">' . esc_html__( 'Description', 'learning-management-system' ) . '</h3>
				<p class="masteriyo-desc">' . wp_kses_post( wpautop( $meet->get_description() ) ) . '</p>';
		}

		$html .= '
			</div>';

		$html .= '
		</div>';

		return $html;
	}

	/**
	 * Get formatted content for a Zoom meeting.
	 *
	 * Formats Zoom meeting content including join URL, meeting ID, password,
	 * start date, duration, meeting type, time zone, and description.
	 *
	 * @since 2.21.0
	 *
	 * @param object $zoom The Zoom meeting object, as the zoom addon models it.
	 * @return string HTML content.
	 */
	protected function get_zoom_content( $zoom ) {
		$html = '
		<div class="masteriyo-course-curriculum--content_meeting">';

		// Meeting meta items - replace with table
		$html .= '
			<table class="masteriyo-course-curriculum--content_meeting-table">';

		if ( $zoom->get_join_url() ) {
			$join_url = $zoom->get_join_url();
			$html    .= '
				<tr>
					<td class="label">' . esc_html__( 'Join URL:', 'learning-management-system' ) . '</td>
					<td class="value"><a href="' . esc_url( $join_url ) . '" class="masteriyo-curriculum-link">' . esc_html( $join_url ) . '</a></td>
				</tr>';
		}

		if ( $zoom->get_meeting_id() ) {
			$html .= '
				<tr>
					<td class="label">' . esc_html__( 'Meeting ID:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( $zoom->get_meeting_id() ) . '</td>
				</tr>';
		}

		if ( $zoom->get_password() ) {
			$html .= '
				<tr>
					<td class="label">' . esc_html__( 'Password:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( $zoom->get_password() ) . '</td>
				</tr>';
		}

		if ( $zoom->get_starts_at() ) {
			$html .= '
				<tr>
					<td class="label">' . esc_html__( 'Start Date:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( masteriyo_format_datetime( $zoom->get_starts_at(), 'Y-m-d, g:i A' ) ) . '</td>
				</tr>';
		}

		if ( $zoom->get_duration() ) {
			$html .= '
				<tr>
					<td class="label">' . esc_html__( 'Duration:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( masteriyo_minutes_to_time_length_string( $zoom->get_duration() ) ) . '</td>
				</tr>';
		}

		if ( $zoom->get_type() ) {
			$meeting_type = $zoom->get_type();
			$type_label   = '';
			switch ( $meeting_type ) {
				case 1:
					$type_label = __( 'Instant Meeting', 'learning-management-system' );
					break;
				case 2:
					$type_label = __( 'Scheduled Meeting', 'learning-management-system' );
					break;
				case 3:
					$type_label = __( 'Recurring Meeting (no fixed time)', 'learning-management-system' );
					break;
				case 8:
					$type_label = __( 'Recurring Meeting (fixed time)', 'learning-management-system' );
					break;
				default:
					$type_label = __( 'Regular Meeting', 'learning-management-system' );
			}
			$html .= '
				<tr>
					<td class="label">' . esc_html__( 'Meeting Type:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( $type_label ) . '</td>
				</tr>';
		}

		if ( $zoom->get_time_zone() ) {
			$html .= '
				<tr>
					<td class="label">' . esc_html__( 'Time Zone:', 'learning-management-system' ) . '</td>
					<td class="value">' . esc_html( $zoom->get_time_zone() ) . '</td>
				</tr>';
		}

		$html .= '
			</table>';

		// Description section
		$html .= '
			<div class="masteriyo-course-curriculum--content_desc">';

		if ( $zoom->get_description() ) {
			$html .= '
				<h3 class="masteriyo-title">' . esc_html__( 'Description', 'learning-management-system' ) . '</h3>
				<p class="masteriyo-desc">' . wp_kses_post( wpautop( $zoom->get_description() ) ) . '</p>';
		}

		$html .= '
			</div>';

		$html .= '
		</div>';

		return $html;
	}

	/**
	 * Generate and download the PDF.
	 *
	 * Creates the PDF file and sends it to the browser for download
	 * with appropriate headers and filename.
	 *
	 * @since 2.21.0
	 *
	 * @return void
	 */
	public function download() {
		// Ensure no output is sent before PDF generation
		if ( headers_sent( $file, $line ) ) {
			$error_message = sprintf(
			/* translators: %1$s: file name, %2$d: line number */
				esc_html__( 'Headers already sent in %1$s on line %2$d. Cannot generate PDF.', 'learning-management-system' ),
				esc_html( $file ),
				absint( $line )
			);

			wp_die(
				wp_kses_post( $error_message ),
				esc_html__( 'PDF Generation Error', 'learning-management-system' ),
				array(
					'response'  => 500,
					'back_link' => true,
				)
			);
		}

		if ( ob_get_length() ) {
			ob_end_clean();
		}

		try {
			$this->generate();
			$filename = sanitize_file_name(
				sanitize_title( $this->course->get_name() ) . '-course-' . gmdate( 'Y-m-d' ) . '.pdf'
			);
			$this->mpdf->Output( $filename, Destination::DOWNLOAD );
			exit();
		} catch ( \Mpdf\MpdfException $e ) {
			$error_message = sprintf(
			/* translators: %s: error message */
				esc_html__( 'Failed to download PDF: %s', 'learning-management-system' ),
				esc_html( $e->getMessage() )
			);

			wp_die(
				wp_kses_post( $error_message ),
				esc_html__( 'PDF Generation Error', 'learning-management-system' ),
				array(
					'response'  => 500,
					'back_link' => true,
				)
			);
		}
	}

	/**
	 * Generate and return the PDF as string.
	 *
	 * Creates the PDF and returns it as a string for further processing
	 * or storage rather than direct download.
	 *
	 * @since 2.21.0
	 *
	 * @return string The PDF content as a binary string.
	 * @throws Exception If PDF generation fails.
	 */
	public function output() {
		try {
			return $this->mpdf->Output( '', Destination::STRING_RETURN );
		} catch ( \Mpdf\MpdfException $e ) {
			throw new Exception(
				sprintf(
					/* translators: %s: error message */
					esc_html__( 'Failed to output PDF: %s', 'learning-management-system' ),
					esc_html( $e->getMessage() )
				)
			);
		}
	}

	/**
	 * Return the folder name where exported courses files are stored.
	 *
	 * Provides the absolute path to the directory where PDF exports are stored.
	 *
	 * @since 2.21.0
	 *
	 * @return string Absolute path to the export directory.
	 */
	public static function get_file_path() {
		$upload_dir = wp_upload_dir();
		$export_dir = DIRECTORY_SEPARATOR . MASTERIYO_UPLOAD_DIR . DIRECTORY_SEPARATOR . self::EXPORT_DIRECTORY;

		return $upload_dir['basedir'] . $export_dir;
	}

	/**
	 * Clean up old exported PDF files.
	 *
	 * Removes previously generated PDF files to prevent accumulation
	 * of unused files in the export directory.
	 *
	 * @since 2.21.0
	 *
	 * @return void
	 */
	public function create_export_file() {
		$this->cleanup_old_exports();

		$file_handler = new FileHandler();

		$filename = sanitize_file_name(
			sanitize_title( $this->course->get_name() ) . '-course-' . gmdate( 'Y-m-d' ) . '.pdf'
		);
		$result   = $file_handler->create_file( self::EXPORT_DIRECTORY, $filename );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'filepath'     => $result['file_path'],
			'filename'     => $result['filename'],
			'download_url' => AdminFileDownloadHandler::get_download_url( self::FILE_PATH_ID, $result['filename'] ),
		);
	}

	/**
	 * Clean up old exported PDF files.
	 *
	 * @since 2.21.0
	 *
	 * @return void
	 */
	private function cleanup_old_exports() {
		$file_handler = new FileHandler();
		$exports_dir  = $file_handler->get_base_dir() . '/' . self::EXPORT_DIRECTORY;

		if ( ! file_exists( $exports_dir ) ) {
			return;
		}

		$files = glob( $exports_dir . '/*.pdf' );

		foreach ( $files as $file ) {
			wp_delete_file( $file );
		}
	}

	/**
	 * Convert URLs for mPDF compatibility.
	 *
	 * Converts URLs to file paths when possible, or handles HTTPS based on SSL verification.
	 *
	 * @since 2.21.0
	 *
	 * @param string $url The URL to convert.
	 * @return string The converted URL or file path.
	 */
	protected function convert_url_for_mpdf( $url ) {
		if ( empty( $url ) ) {
			return $url;
		}

		// If SSL is not verified (local environment), convert HTTPS to HTTP
		if ( ! is_ssl() && strpos( $url, 'https://' ) === 0 ) {
			return str_replace( 'https://', 'http://', $url );
		}

		return $url;
	}
}
