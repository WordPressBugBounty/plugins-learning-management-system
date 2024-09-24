<?php
/**
 * Sections class.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\PostType;
 */

namespace Masteriyo\PostType;

/**
 * Sections class.
 */
class Section extends PostType {
	/**
	 * Post slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $slug = 'mto-section';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$debug      = masteriyo_is_post_type_debug_enabled();
		$permalinks = masteriyo_get_permalink_structure();

		$this->labels = array(
			'name'                  => _x( 'Sections', 'Section General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Section', 'Section Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Sections', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Section', 'learning-management-system' ),
			'archives'              => __( 'Section Archives', 'learning-management-system' ),
			'attributes'            => __( 'Section Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Section:', 'learning-management-system' ),
			'all_items'             => __( 'All Sections', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Section', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Section', 'learning-management-system' ),
			'update_item'           => __( 'Update Section', 'learning-management-system' ),
			'view_item'             => __( 'View Section', 'learning-management-system' ),
			'view_items'            => __( 'View Sections', 'learning-management-system' ),
			'search_items'          => __( 'Search Section', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into section', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this section', 'learning-management-system' ),
			'items_list'            => __( 'Sections list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Sections list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter sections list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Sections', 'learning-management-system' ),
			'description'         => __( 'Sections Description', 'learning-management-system' ),
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
			'capability_type'     => array( 'section', 'sections' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'can_export'          => true,
			'delete_with_user'    => true,
			'rewrite'             => $permalinks['section_rewrite_slug'] ? array(
				'slug'       => $permalinks['section_rewrite_slug'],
				'with_front' => false,
				'feeds'      => true,
			) : false,
		);
	}
}
