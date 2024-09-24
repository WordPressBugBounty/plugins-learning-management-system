<?php
/**
 * Style course list components.
 *
 * @since 1.11.3
 *
 * @package Masteriyo
 */

namespace Masteriyo\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Style course list components.
 *
 * @since 1.11.3
 */
abstract class CourseComponentStyles {

	/**
	 * Initialize the class instance
	 *
	 * @since 1.11.3
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.11.3
	 */
	protected function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'print_styles' ), PHP_INT_MAX - 10 );
	}

	/**
	 * Print styles
	 *
	 * @since 1.11.3
	 */
	public function print_styles() {
		if ( ! $this->should_print() ) {
			return;
		}

		$styles = $this->get_styles();
		if ( empty( $styles ) ) {
			return;
		}
		wp_add_inline_style( 'masteriyo-public', $styles );
	}

	/**
	 * Prefix selector for course components.
	 *
	 * @since 1.11.3
	 *
	 */
	abstract protected function get_prefix_selector():string;

	/**
	 * Condition to should print or not.
	 *
	 * @since 1.11.3
	 *
	 */
	abstract protected function should_print():bool;

	/**
	 * Get styles to course list components.
	 *
	 * @since 1.11.3
	 *
	 * @return string
	 */
	protected function get_styles() {
		$prefix_selector = $this->get_prefix_selector();

		$components = array(
			"$prefix_selector .masteriyo-single-course--btn" => array(
				'button_size'   => masteriyo_get_setting( 'course_archive.course_card_styles.button_size' ),
				'button_radius' => masteriyo_get_setting( 'course_archive.course_card_styles.button_radius' ),
			),
			"$prefix_selector .masteriyo-course--content__title" => array(
				'course_title_font_size' => masteriyo_get_setting( 'course_archive.course_card_styles.course_title_font_size' ),
			),
			"$prefix_selector .masteriyo-course--content__description" => array(
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
