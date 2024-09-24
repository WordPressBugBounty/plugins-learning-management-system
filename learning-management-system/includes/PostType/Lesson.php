<?php
/**
 * Lessons class.
 */

namespace Masteriyo\PostType;

class Lesson extends PostType {
	/**
	 * Post slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $slug = 'mto-lesson';

	public function __construct() {
		$debug      = masteriyo_is_post_type_debug_enabled();
		$permalinks = masteriyo_get_permalink_structure();

		$this->labels = array(
			'name'                  => _x( 'Lessons', 'Lesson General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Lesson', 'Lesson Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Lessons', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Lesson', 'learning-management-system' ),
			'archives'              => __( 'Lesson Archives', 'learning-management-system' ),
			'attributes'            => __( 'Lesson Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Lesson:', 'learning-management-system' ),
			'all_items'             => __( 'All Lessons', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Lesson', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Lesson', 'learning-management-system' ),
			'update_item'           => __( 'Update Lesson', 'learning-management-system' ),
			'view_item'             => __( 'View Lesson', 'learning-management-system' ),
			'view_items'            => __( 'View Lessons', 'learning-management-system' ),
			'search_items'          => __( 'Search Lesson', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into lesson', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this lesson', 'learning-management-system' ),
			'items_list'            => __( 'Lessons list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Lessons list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter lessons list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Lessons', 'learning-management-system' ),
			'description'         => __( 'Lessons Description', 'learning-management-system' ),
			'labels'              => $this->labels,
			'supports'            => array( 'title', 'editor', 'author', 'comments', 'custom-fields', 'post-formats' ),
			'taxonomies'          => array(),
			'hierarchical'        => false,
			'menu_position'       => 5,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => $debug,
			'show_in_admin_bar'   => $debug,
			'show_in_nav_menus'   => $debug,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'map_meta_cap'        => true,
			'capability_type'     => array( 'lesson', 'lessons' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'can_export'          => true,
			'delete_with_user'    => true,
			'rewrite'             => $permalinks['lesson_rewrite_slug'] ? array(
				'slug'       => $permalinks['lesson_rewrite_slug'],
				'with_front' => false,
				'feeds'      => true,
			) : false,
		);
	}
}
