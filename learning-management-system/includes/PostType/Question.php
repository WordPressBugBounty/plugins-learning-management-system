<?php
/**
 * Question post type.
 *
 * @since 1.0.0
 */

namespace Masteriyo\PostType;

class Question extends PostType {
	/**
	 * Post slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $slug = 'mto-question';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$debug = masteriyo_is_post_type_debug_enabled();

		$this->labels = array(
			'name'                  => _x( 'Questions', 'Question General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Question', 'Question Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Questions', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Question', 'learning-management-system' ),
			'archives'              => __( 'Question Archives', 'learning-management-system' ),
			'attributes'            => __( 'Question Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Question:', 'learning-management-system' ),
			'all_items'             => __( 'All Questions', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Question', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Question', 'learning-management-system' ),
			'update_item'           => __( 'Update Question', 'learning-management-system' ),
			'view_item'             => __( 'View Question', 'learning-management-system' ),
			'view_items'            => __( 'View Questions', 'learning-management-system' ),
			'search_items'          => __( 'Search Question', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into question', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this question', 'learning-management-system' ),
			'items_list'            => __( 'Questions list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Questions list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter questions list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Questions', 'learning-management-system' ),
			'description'         => __( 'Questions Description', 'learning-management-system' ),
			'labels'              => $this->labels,
			'supports'            => array( 'title', 'editor', 'author', 'comments', 'custom-fields', 'page-attributes', 'post-formats' ),
			'taxonomies'          => array(),
			'hierarchical'        => false,
			'menu_position'       => 5,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => $debug,
			'show_in_admin_bar'   => $debug,
			'show_in_nav_menus'   => $debug,
			'can_export'          => true,
			'show_in_rest'        => false,
			'has_archive'         => true,
			'map_meta_cap'        => true,
			'capability_type'     => array( 'question', 'questions' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'can_export'          => true,
			'delete_with_user'    => true,
		);
	}
}
