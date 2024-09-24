<?php
/**
 * Courses class.
 */

namespace Masteriyo\PostType;

class Course extends PostType {
	/**
	 * Post slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $slug = 'mto-course';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$debug           = masteriyo_is_post_type_debug_enabled();
		$permalinks      = masteriyo_get_permalink_structure();
		$courses_page_id = masteriyo_get_page_id( 'courses' );
		$supports        = array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'author', 'publicize', 'wpcom-markdown' );

		if ( $courses_page_id && get_post( $courses_page_id ) ) {
			$has_archive = urldecode( get_page_uri( $courses_page_id ) );
		} else {
			$has_archive = 'courses';
		}

		$this->labels = array(
			'name'                  => _x( 'Courses', 'Course General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Course', 'Course Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Courses', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Course', 'learning-management-system' ),
			'archives'              => __( 'Course Archives', 'learning-management-system' ),
			'attributes'            => __( 'Course Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Course:', 'learning-management-system' ),
			'all_items'             => __( 'All Courses', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Course', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Course', 'learning-management-system' ),
			'update_item'           => __( 'Update Course', 'learning-management-system' ),
			'view_item'             => __( 'View Course', 'learning-management-system' ),
			'view_items'            => __( 'View Courses', 'learning-management-system' ),
			'search_items'          => __( 'Search Course', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into course', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this course', 'learning-management-system' ),
			'items_list'            => __( 'Courses list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Courses list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter courses list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Courses', 'learning-management-system' ),
			'description'         => __( 'Courses Description', 'learning-management-system' ),
			'labels'              => $this->labels,
			'supports'            => $supports,
			'taxonomies'          => array( 'course_cat', 'course_tag', 'course_difficulty', 'course_visibility' ),
			'hierarchical'        => false,
			'menu_position'       => 5,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => $debug,
			'show_in_admin_bar'   => $debug,
			'show_in_nav_menus'   => $debug,
			'can_export'          => true,
			'show_in_rest'        => true,
			'has_archive'         => $has_archive,
			'map_meta_cap'        => true,
			'capability_type'     => array( 'course', 'courses' ),
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'can_export'          => true,
			'delete_with_user'    => true,
			'rewrite'             => $permalinks['course_rewrite_slug'] ? array(
				'slug'       => $permalinks['course_rewrite_slug'],
				'with_front' => false,
				'feeds'      => true,
			) : false,
		);
	}
}
