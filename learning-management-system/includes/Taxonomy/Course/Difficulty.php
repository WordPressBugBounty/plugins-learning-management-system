<?php
/**
 * Courses Course difficulties.
 */

namespace Masteriyo\Taxonomy\Course;

use Masteriyo\Taxonomy\Taxonomy;

class Difficulty extends Taxonomy {
	/**
	 * Taxonomy.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $taxonomy = 'course_difficulty';


	/**
	 * Post type the taxonomy belongs to.
	 *
	 * @since 1.0.0
	 */
	protected $post_type = 'mto-course';

	/**
	 * Get settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_args() {

		$permalinks = masteriyo_get_permalink_structure();

		/**
		 * Filters arguments for course difficulty taxonomy.
		 *
		 * @since 1.0.0
		 *
		 * @param array $args The arguments for course difficulty taxonomy.
		 */
		return apply_filters(
			'masteriyo_taxonomy_args_course_difficulty',
			array(
				'hierarchical'      => true,
				'label'             => __( 'Course Difficulties', 'learning-management-system' ),
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => true,
				'show_tag_cloud'    => true,
				'query_var'         => true,
				'rewrite'           => array(
					'slug'         => $permalinks['course_difficulty_rewrite_slug'],
					'with_front'   => false,
					'hierarchical' => true,
				),
				'labels'            => array(
					'name'                       => _x( 'Course Difficulties', 'Taxonomy General Name', 'learning-management-system' ),
					'singular_name'              => _x( 'Course Difficulty', 'Taxonomy Singular Name', 'learning-management-system' ),
					'menu_name'                  => __( 'Course Difficulty', 'learning-management-system' ),
					'all_items'                  => __( 'All Course Difficulties', 'learning-management-system' ),
					'parent_item'                => __( 'Parent Course Difficulty', 'learning-management-system' ),
					'parent_item_colon'          => __( 'Parent Course Difficulty:', 'learning-management-system' ),
					'new_item_name'              => __( 'New Course Difficulty Name', 'learning-management-system' ),
					'add_new_item'               => __( 'Add New Course Difficulty', 'learning-management-system' ),
					'edit_item'                  => __( 'Edit Course Difficulty', 'learning-management-system' ),
					'update_item'                => __( 'Update Course Difficulty', 'learning-management-system' ),
					'view_item'                  => __( 'View Course Difficulty', 'learning-management-system' ),
					'separate_items_with_commas' => __( 'Separate course difficulties with commas', 'learning-management-system' ),
					'add_or_remove_items'        => __( 'Add or remove course difficulties', 'learning-management-system' ),
					'choose_from_most_used'      => __( 'Choose from the most used', 'learning-management-system' ),
					'popular_items'              => __( 'Popular Course Difficulties', 'learning-management-system' ),
					'search_items'               => __( 'Search Course Difficulties', 'learning-management-system' ),
					'not_found'                  => __( 'Not Found', 'learning-management-system' ),
					'no_terms'                   => __( 'No course difficulties', 'learning-management-system' ),
					'items_list'                 => __( 'Course Difficulties list', 'learning-management-system' ),
					'items_list_navigation'      => __( 'Course Difficulties list navigation', 'learning-management-system' ),
				),
				'capabilities'      => array(
					'manage_terms' => 'manage_course_difficulties',
					'edit_terms'   => 'edit_course_difficulties',
					'delete_terms' => 'delete_course_difficulties',
					'assign_terms' => 'assign_course_difficulties',
				),
			)
		);
	}
}
