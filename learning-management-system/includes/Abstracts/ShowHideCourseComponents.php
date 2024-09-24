<?php
/**
 * Show hide course list components.
 *
 * @since 1.6.13
 *
 * @package Masteriyo
 */

namespace Masteriyo\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Show hide course list components.
 *
 * @since 1.6.13
 */
abstract class ShowHideCourseComponents {

	/**
	 * Initialize the class instance
	 *
	 * @since 1.6.13
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @since 1.6.13
	 */
	protected function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'print_styles' ), PHP_INT_MAX - 10 );
	}

	/**
	 * Print styles
	 *
	 * @since 1.6.13
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
	 * @since 1.6.13
	 *
	 */
	abstract protected function get_prefix_selector():string;

	/**
	 * Condition to should print or not.
	 *
	 * @since 1.6.13
	 *
	 */
	abstract protected function should_print():bool;

	/**
	 * Get styles to show/hide components.
	 *
	 * @since 1.6.13
	 *
	 * @return string
	 */
	protected function get_styles() {
		$prefix_selector   = $this->get_prefix_selector();
		$components        = array(
			"$prefix_selector .masteriyo-course--img-wrap, .masteriyo-course-thumbnail, .masteriyo-course-card__thumbnail-image" => masteriyo_get_setting( 'course_archive.components_visibility.thumbnail' ),
			"$prefix_selector .difficulty-badge" => masteriyo_get_setting( 'course_archive.components_visibility.difficulty_badge' ),
			"$prefix_selector .course-featured"  => masteriyo_get_setting( 'course_archive.components_visibility.featured_ribbon' ),
			"$prefix_selector .masteriyo-course--content__category, .masteriyo-course-card__content--category, .masteriyo-archive-card__content--category" => masteriyo_get_setting( 'course_archive.components_visibility.categories' ),
			"$prefix_selector .masteriyo-course--content__title, .masteriyo-course-card__content--course-title, .masteriyo-archive-card__content--course-title" => masteriyo_get_setting( 'course_archive.components_visibility.course_title' ),
			"$prefix_selector .masteriyo-course-author, .masteriyo-author-image" => masteriyo_get_setting( 'course_archive.components_visibility.author' ),
			"$prefix_selector .masteriyo-course-author img, .masteriyo-author-image" => masteriyo_get_setting( 'course_archive.components_visibility.author_avatar' ),
			"$prefix_selector .masteriyo-course-author--name" => masteriyo_get_setting( 'course_archive.components_visibility.author_name' ),
			"$prefix_selector .masteriyo-course--content__description, .masteriyo-course-card__content--desc" => masteriyo_get_setting( 'course_archive.components_visibility.course_description' ),
			"$prefix_selector .masteriyo-course--content__stats, .masteriyo-course-card__content--info, .masteriyo-archive-card__content--info" => masteriyo_get_setting( 'course_archive.components_visibility.metadata' ),
			"$prefix_selector .masteriyo-course-stats-duration, .masteriyo-course-card__content--info-duration, .masteriyo-archive-card__content--info-duration" => masteriyo_get_setting( 'course_archive.components_visibility.course_duration' ),
			"$prefix_selector .masteriyo-course-stats-students, .masteriyo-course-card__content--info-students, .masteriyo-archive-card__content--info-students" => masteriyo_get_setting( 'course_archive.components_visibility.students_count' ),
			"$prefix_selector .masteriyo-course-stats-curriculum, .masteriyo-course-card__content--info-lessons, .masteriyo-archive-card__content--info-lessons" => masteriyo_get_setting( 'course_archive.components_visibility.lessons_count' ),
			"$prefix_selector .masteriyo-time-btn, .masteriyo-archive-card__content--info" => masteriyo_get_setting( 'course_archive.components_visibility.card_footer' ),
			"$prefix_selector .masteriyo-course-price, .masteriyo-course-card__content--amount, .masteriyo-archive-card__content--amount" => masteriyo_get_setting( 'course_archive.components_visibility.price' ),
			"$prefix_selector .masteriyo-rating, .masteriyo-course-card__content--rating, .masteriyo-archive-card__content--rating" => masteriyo_get_setting( 'course_archive.components_visibility.rating' ),
			"$prefix_selector .masteriyo-single-course--btn, .masteriyo-course-card__content--primary-button" => masteriyo_get_setting( 'course_archive.components_visibility.enroll_button' ),
			"$prefix_selector .masteriyo-available-seats-for-students" => masteriyo_get_setting( 'course_archive.components_visibility.seats_for_students' ),
		);
		$hidden_components = array_filter(
			$components,
			function ( $component_status ) {
				return ! $component_status;
			}
		);
		$styles            = '';
		if ( empty( $hidden_components ) ) {
			return $styles;
		}
		$styles .= implode( ',', array_keys( $hidden_components ) );
		$styles .= '{display:none !important;}';
		return $styles;
	}
}
