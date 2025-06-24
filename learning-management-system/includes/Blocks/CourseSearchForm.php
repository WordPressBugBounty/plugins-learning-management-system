<?php

/**
 * Course search form block class.
 *
 * @since 1.18.2
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;

/**
 * Class CourseSearchForm
 *
 * Handles the rendering of the course search form block.
 *
 * @since 1.18.2
 */
class CourseSearchForm extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.18.2
	 * @var string
	 */
	protected $block_name = 'course-search-form';

	/**
	 * Build HTML output for the block.
	 *
	 * @since 1.18.2
	 *
	 * @param string $content Not used. The original block content.
	 * @return string Rendered HTML output.
	 */
	protected function build_html( $content ) {
		$attr      = $this->attributes;
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		$style = '';
		if ( isset( $attr['height'] ) ) {
			$height = $attr['height'];
			$style .= " height: $height;";
		}
		if ( isset( $attr['width'] ) ) {
			$width = $attr['width'];

			$style .= " width: $width;";
		}

		\ob_start();

		/**
		 * Fires before rendering course search form in course-course-search form block.
		 *
		 * @since 1.12.1 [Free]
		 *
		 * @param array $attr Block attributes.
		 * @param \Masteriyo\Models\CourseCategory[] $course-search form The course search form objects.
		 */
			do_action( 'masteriyo_blocks_before_course_search_form', $attr );

			masteriyo_course_search_form();
			/**
			 * Fires after rendering course highlights in course_search_form block.
			 *
			 * @since 1.12.1 [Free]
			 *
			 * @param array $attr Block attributes.
			 */
		do_action( 'masteriyo_blocks_after_course_search_form', $attr );
		return \ob_get_clean();
	}
}
