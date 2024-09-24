<?php
/**
 * Courses Course tags.
 */

namespace Masteriyo\Taxonomy\Course;

use Masteriyo\Taxonomy\Taxonomy;

class Tag extends Taxonomy {

	/**
	 * Taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $taxonomy = 'course_tag';

	/**
	 * Post type the taxonomy belongs to.
	 *
	 * @since 1.0.0
	 */
	protected $post_type = 'mto-course';

	/**
	 * Default labels.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_args() {

		$permalinks = masteriyo_get_permalink_structure();

		/**
		 * Filters arguments for course tag taxonomy.
		 *
		 * @since 1.5.1
		 *
		 * @param array $args The arguments for course tag taxonomy.
		 */
		return apply_filters(
			'masteriyo_taxonomy_args_course_tag',
			array(
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_tag_cloud'    => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'         => $permalinks['course_tag_rewrite_slug'],
					'with_front'   => false,
					'hierarchical' => true,
				),
				'labels'            => array(
					'name'                       => _x( 'Course Tags', 'Taxonomy General Name', 'learning-management-system' ),
					'singular_name'              => _x( 'Course Tag', 'Taxonomy Singular Name', 'learning-management-system' ),
					'menu_name'                  => __( 'Course Tag', 'learning-management-system' ),
					'all_items'                  => __( 'All Course Tags', 'learning-management-system' ),
					'parent_item'                => __( 'Parent Course Tag', 'learning-management-system' ),
					'parent_item_colon'          => __( 'Parent Course Tag:', 'learning-management-system' ),
					'new_item_name'              => __( 'New Course Tag Name', 'learning-management-system' ),
					'add_new_item'               => __( 'Add New Course Tag', 'learning-management-system' ),
					'edit_item'                  => __( 'Edit Course Tag', 'learning-management-system' ),
					'update_item'                => __( 'Update Course Tag', 'learning-management-system' ),
					'view_item'                  => __( 'View Course Tag', 'learning-management-system' ),
					'separate_items_with_commas' => __( 'Separate course tags with commas', 'learning-management-system' ),
					'add_or_remove_items'        => __( 'Add or remove course tags', 'learning-management-system' ),
					'choose_from_most_used'      => __( 'Choose from the most used', 'learning-management-system' ),
					'popular_items'              => __( 'Popular Course Tags', 'learning-management-system' ),
					'search_items'               => __( 'Search Course Tags', 'learning-management-system' ),
					'not_found'                  => __( 'Not Found', 'learning-management-system' ),
					'no_terms'                   => __( 'No course tags', 'learning-management-system' ),
					'items_list'                 => __( 'Course Tags list', 'learning-management-system' ),
					'items_list_navigation'      => __( 'Course Tags list navigation', 'learning-management-system' ),
				),
			)
		);
	}
}
