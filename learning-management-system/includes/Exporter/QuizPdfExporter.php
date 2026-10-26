<?php
/**
 * Quiz PDF export handler.
 *
 * @package Masteriyo\Exporter
 */

namespace Masteriyo\Exporter;

use Masteriyo\Models\Quiz;
use Mpdf\HTMLParserMode;
use Mpdf\Output\Destination;

defined( 'ABSPATH' ) || exit();

/**
 * Exports a quiz with its answer key and pass criteria as a printable PDF.
 */
class QuizPdfExporter extends CoursePdfExporter {

	/**
	 * The quiz to export.
	 *
	 * @var Quiz
	 */
	protected $quiz;

	/**
	 * Constructor.
	 *
	 * @param Quiz $quiz The quiz to export.
	 */
	public function __construct( Quiz $quiz ) {
		$this->quiz = $quiz;
		$this->initialize_mpdf();
	}

	/**
	 * Generate the PDF content.
	 *
	 * @return string The PDF content.
	 * @throws \Exception If PDF generation fails.
	 */
	public function generate() {
		$course = masteriyo_get_course( $this->quiz->get_course_id() );

		$html = '<h1>' . esc_html( $this->quiz->get_name() ) . '</h1>';

		if ( $course ) {
			/* translators: %s: Course name */
			$html .= '<p>' . esc_html( sprintf( __( 'Course: %s', 'learning-management-system' ), $course->get_name() ) ) . '</p>';
		}

		try {
			$this->add_styles();
			$this->add_curriculum_styles();
			$this->mpdf->WriteHTML( $html . $this->get_quiz_content( $this->quiz ), HTMLParserMode::HTML_BODY );

			return $this->mpdf->Output( '', Destination::STRING_RETURN );
		} catch ( \Mpdf\MpdfException $e ) {
			throw new \Exception( 'Failed to generate PDF: ' . esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Name of the exported PDF file.
	 *
	 * @return string
	 */
	public function get_export_filename() {
		return sanitize_file_name( sanitize_title( $this->quiz->get_name() ) . '-quiz-' . gmdate( 'Y-m-d' ) . '.pdf' );
	}
}
