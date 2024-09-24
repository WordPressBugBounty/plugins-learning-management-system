<?php
/**
 * Groups class.
 *
 * @since 1.9.0
 *
 * @package Masteriyo\Addons\GroupCourses\PostType;
 */

namespace Masteriyo\Addons\GroupCourses\PostType;

use Masteriyo\PostType\PostType;

/**
 * Groups class.
 */
class Group extends PostType {
	/**
	 * Post slug.
	 *
	 * @since 1.9.0
	 *
	 * @var string
	 */
	protected $slug = PostType::GROUP;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$debug = masteriyo_is_post_type_debug_enabled();

		$this->labels = array(
			'name'                  => _x( 'Groups', 'Group General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Group', 'Group Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Groups', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Group', 'learning-management-system' ),
			'archives'              => __( 'Group Archives', 'learning-management-system' ),
			'attributes'            => __( 'Group Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Group:', 'learning-management-system' ),
			'all_items'             => __( 'All Groups', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Group', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Group', 'learning-management-system' ),
			'update_item'           => __( 'Update Group', 'learning-management-system' ),
			'view_item'             => __( 'View Group', 'learning-management-system' ),
			'view_items'            => __( 'View Groups', 'learning-management-system' ),
			'search_items'          => __( 'Search Group', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into group', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this group', 'learning-management-system' ),
			'items_list'            => __( 'Groups list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Groups list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter groups list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Groups', 'learning-management-system' ),
			'description'         => __( 'Groups Description', 'learning-management-system' ),
			'labels'              => $this->labels,
			'supports'            => array( 'title', 'editor', 'author', 'custom-fields', 'post-formats' ),
			'taxonomies'          => array(),
			'hierarchical'        => false,
			'public'              => $debug,
			'menu_position'       => 5,
			'show_in_admin_bar'   => $debug,
			'show_in_nav_menus'   => $debug,
			'can_export'          => true,
			'show_in_rest'        => $debug,
			'has_archive'         => true,
			'map_meta_cap'        => true,
			'capability_type'     => array( 'group', 'groups' ),
			'exclude_from_search' => false,
			'publicly_queryable'  => is_admin(),
			'can_export'          => true,
			'delete_with_user'    => true,
		);
	}
}
