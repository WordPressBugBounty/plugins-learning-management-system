<?php
/**
 * Custom Course Categories Module Class
 *
 * @since 1.10.0
 */


namespace Masteriyo\Addons\BeaverIntegration\CoursesCategories;

class CoursesCategoriesModule extends \FLBuilderModule {

	public function __construct() {
		parent::__construct(
			array(
				'name'            => __( 'Course Categories', 'learning-management-system' ),
				'description'     => __( 'Categories of courses as a whole', 'learning-management-system' ),
				'category'        => __( 'Masteriyo', 'learning-management-system' ),
				'dir'             => __DIR__,
				'url'             => __DIR__,
				'editor_export'   => true,
				'enabled'         => true,
				'partial_refresh' => false,
				'include_wrapper' => false,
			)
		);
	}

	public function get_icon( $icon = '' ) {
		return file_get_contents( MASTERIYO_ELEMENTOR_INTEGRATION_DIR . '/svg/categories-of-course-widget-icon.svg' );
	}
}
