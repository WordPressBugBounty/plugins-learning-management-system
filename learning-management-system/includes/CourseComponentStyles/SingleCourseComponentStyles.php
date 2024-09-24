<?php
/**
* Style single course list components.
*
* @since 1.11.3
*
* @package Masteriyo
*/
namespace Masteriyo\CourseComponentStyles;

use Masteriyo\Abstracts\CourseComponentStyles;

defined( 'ABSPATH' ) || exit;

class SingleCourseComponentStyles extends CourseComponentStyles {

	/**
	 * Style single course components.
	 *
	 * @since 1.11.3
	 *
	 * @return string
	 */
	protected function get_prefix_selector(): string {
		return '.masteriyo-single-course';
	}

	/**
	 * Should print if single course page.
	 *
	 * @since 1.11.3
	 *
	 * @return bool
	 */
	protected function should_print(): bool {
		return masteriyo_is_single_course_page();
	}

	/**
	 * Get styles to course list components in single course page.
	 *
	 * @since 1.11.3
	 *
	 * @return string
	 */
	protected function get_styles() {

		$components = array(
			'.masteriyo-single-course .masteriyo-single-course--btn' => array(
				'button_size'   => masteriyo_get_setting( 'course_archive.course_card_styles.button_size' ),
				'button_radius' => masteriyo_get_setting( 'course_archive.course_card_styles.button_radius' ),
			),
			'.masteriyo-single-course  ..masteriyo-single-course--title'       => array(
				'course_title_font_size' => masteriyo_get_setting( 'course_archive.course_card_styles.course_title_font_size' ),
			),
			'.masteriyo-single-course  .masteriyo-course--content__description' => array(
				'highlight_side' => masteriyo_get_setting( 'course_archive.course_card_styles.highlight_side' ),
			),
		);

		$styles = '';

		foreach ( $components as $selector => $properties ) {
			if ( empty( array_filter( $properties ) ) ) {
					continue;
			}

			$styles .= $selector . '{';

			if ( ! empty( $properties['button_size'] ) ) {
					$styles .= "font-size: {$properties['button_size']}px !important;";
			}

			if ( ! empty( $properties['button_radius'] ) ) {
					$styles .= "border-radius: {$properties['button_radius']}px !important;";
			}

			if ( isset( $properties['course_title_font_size'] ) && ! empty( $properties['course_title_font_size'] ) ) {
					$styles .= "font-size: {$properties['course_title_font_size']}px !important;";
			}

			if ( isset( $properties['highlight_side'] ) && ! empty( $properties['highlight_side'] ) ) {
				$styles .= "text-align: {$properties['highlight_side']} !important;";
			}

			$styles .= '}';
		}

		return $styles;
	}

}

