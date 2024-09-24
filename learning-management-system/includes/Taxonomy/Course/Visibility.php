<?php
/**
 * Courses Course visibilities.
 */

namespace Masteriyo\Taxonomy\Course;

use Masteriyo\Taxonomy\Taxonomy;

class Visibility extends Taxonomy {

	/**
	 * Taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $taxonomy = 'course_visibility';

	/**
	 * Post type the taxonomy belongs to.
	 *
	 * @since 1.0.0
	 */
	protected $post_type = 'mto-course';

	/**
	 * Get labels.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_args() {
		/**
		 * Filters arguments for course visibility taxonomy.
		 *
		 * @since 1.5.1
		 *
		 * @param array $args The arguments for course visibility taxonomy.
		 */
		return apply_filters(
			'masteriyo_taxonomy_args_course_visibility',
			array(
				'hierarchical' => false,
				'show_ui'      => true,
				'rewrite'      => false,
				'public'       => false,
				'query_var'    => is_admin(),
				'labels'       => array(
					'name'                       => _x( 'Course Visibilities', 'Taxonomy General Name', 'learning-management-system' ),
					'singular_name'              => _x( 'Course Visibility', 'Taxonomy Singular Name', 'learning-management-system' ),
					'menu_name'                  => __( 'Course Visibility', 'learning-management-system' ),
					'all_items'                  => __( 'All Course Visibilities', 'learning-management-system' ),
					'parent_item'                => __( 'Parent Course Visibility', 'learning-management-system' ),
					'parent_item_colon'          => __( 'Parent Course Visibility:', 'learning-management-system' ),
					'new_item_name'              => __( 'New Course Visibility Name', 'learning-management-system' ),
					'add_new_item'               => __( 'Add New Course Visibility', 'learning-management-system' ),
					'edit_item'                  => __( 'Edit Course Visibility', 'learning-management-system' ),
					'update_item'                => __( 'Update Course Visibility', 'learning-management-system' ),
					'view_item'                  => __( 'View Course Visibility', 'learning-management-system' ),
					'separate_items_with_commas' => __( 'Separate course visibilities with commas', 'learning-management-system' ),
					'add_or_remove_items'        => __( 'Add or remove course visibilities', 'learning-management-system' ),
					'choose_from_most_used'      => __( 'Choose from the most used', 'learning-management-system' ),
					'popular_items'              => __( 'Popular Course Visibilities', 'learning-management-system' ),
					'search_items'               => __( 'Search Course Visibilities', 'learning-management-system' ),
					'not_found'                  => __( 'Not Found', 'learning-management-system' ),
					'no_terms'                   => __( 'No course visibilities', 'learning-management-system' ),
					'items_list'                 => __( 'Course Visibilities list', 'learning-management-system' ),
					'items_list_navigation'      => __( 'Course Visibilities list navigation', 'learning-management-system' ),
				),
			)
		);
	}
}
