<?php

/**
* Show hide single course list components.
*
* @since 1.6.13
*
* @package Masteriyo
*/
namespace Masteriyo\ShowHideComponents;

use Masteriyo\Abstracts\ShowHideCourseComponents;

defined( 'ABSPATH' ) || exit;

class SHowHideSingleCourseComponents extends ShowHideCourseComponents {

	/**
	 * Show hide single course components.
	 *
	 * @since 1.6.13
	 *
	 * @return string
	 */
	protected function get_prefix_selector(): string {
		$layout = masteriyo_get_setting( 'single_course.layout' ) ?? 'default';

		if ( 'layout1' === $layout ) {
			return '.masteriyo-single';
		}

		return '.masteriyo-single-course';
	}

	/**
	 * Should print if single course page.
	 *
	 * @since 1.6.13
	 *
	 * @return bool
	 */
	protected function should_print(): bool {
		return masteriyo_is_single_course_page();
	}

	/**
	 * Get styles to show/hide components in single course page.
	 *
	 * @since 1.6.13
	 *
	 * @return string
	 */
	protected function get_styles() {
		$components        = array(
			"{$this->get_prefix_selector()} .masteriyo-course--img-wrap, .masteriyo-single-header__image" => masteriyo_get_setting( 'course_archive.components_visibility.thumbnail' ),
			"{$this->get_prefix_selector()} .difficulty-badge, .masteriyo-single-header__content--info-items.difficulty" => masteriyo_get_setting( 'course_archive.components_visibility.difficulty_badge' ),
			"{$this->get_prefix_selector()} .course-featured" => masteriyo_get_setting( 'course_archive.components_visibility.featured_ribbon' ),
			"{$this->get_prefix_selector()} .masteriyo-course--content__category, .masteriyo-single-header__content--category" => masteriyo_get_setting( 'course_archive.components_visibility.categories' ),
			"{$this->get_prefix_selector()} .masteriyo-single-course--title, .masteriyo-single-header__content--title" => masteriyo_get_setting( 'course_archive.components_visibility.course_title' ),
			"{$this->get_prefix_selector()} .masteriyo-course-author, .masteriyo-single--author" => masteriyo_get_setting( 'course_archive.components_visibility.author' ),
			"{$this->get_prefix_selector()} .masteriyo-course-author img, .masteriyo-single--author-img" => masteriyo_get_setting( 'course_archive.components_visibility.author_avatar' ),
			"{$this->get_prefix_selector()} .masteriyo-course-author--name, .masteriyo-single--author-name" => masteriyo_get_setting( 'course_archive.components_visibility.author_name' ),
			"{$this->get_prefix_selector()} .masteriyo-course--content__description" => masteriyo_get_setting( 'course_archive.components_visibility.course_description' ),
			"{$this->get_prefix_selector()} .masteriyo-single-course--main__content" => masteriyo_get_setting( 'course_archive.components_visibility.course_description' ),
			"{$this->get_prefix_selector()} .masteriyo-single-course-stats, .masteriyo-single-header__content--info" => masteriyo_get_setting( 'course_archive.components_visibility.metadata' ),
			"{$this->get_prefix_selector()} .duration"   => masteriyo_get_setting( 'course_archive.components_visibility.course_duration' ),
			"{$this->get_prefix_selector()} .student"    => masteriyo_get_setting( 'course_archive.components_visibility.students_count' ),
			"{$this->get_prefix_selector()} .difficulty" => masteriyo_get_setting( 'course_archive.components_visibility.lessons_count' ),
			"{$this->get_prefix_selector()} .masteriyo-time-btn" => masteriyo_get_setting( 'course_archive.components_visibility.card_footer' ),
			"{$this->get_prefix_selector()} .masteriyo-course-price, .masteriyo-single-body__aside--price-wrapper" => masteriyo_get_setting( 'course_archive.components_visibility.price' ),
			"{$this->get_prefix_selector()} .masteriyo-rating, .masteriyo-single-header__content--rating" => masteriyo_get_setting( 'course_archive.components_visibility.rating' ),
			"{$this->get_prefix_selector()} .masteriyo-single-course--btn, .masteriyo-single-body__aside--enroll" => masteriyo_get_setting( 'course_archive.components_visibility.enroll_button' ),
			"{$this->get_prefix_selector()} .masteriyo-available-seats-for-students, .masteriyo-single-header__content--info-items.available-seats" => masteriyo_get_setting( 'course_archive.components_visibility.seats_for_students' ),
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

