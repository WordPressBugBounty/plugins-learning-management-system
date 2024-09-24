<?php
/**
 * Custom Course Lists Module Class
 *
 * @since 1.10.0
 */


namespace Masteriyo\Addons\BeaverIntegration\CoursesLists;

class CoursesListsModule extends \FLBuilderModule {

	public function __construct() {
		parent::__construct(
			array(
				'name'            => __( 'Courses List', 'learning-management-system' ),
				'description'     => __( 'A Collection of courses that will be displayed in builders', 'learning-management-system' ),
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
		return file_get_contents( MASTERIYO_ELEMENTOR_INTEGRATION_DIR . '/svg/course-list-widget-icon.svg' );
	}
}

