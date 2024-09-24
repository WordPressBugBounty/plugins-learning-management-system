<?php
/**
 * Google CLassroom Integration class.
 *
 * @since 1.8.3
 *
 * @package Masteriyo\PostType;
 */

namespace Masteriyo\Addons\GoogleClassroomIntegration\PostType;

use Masteriyo\PostType\PostType;

/**
 * GoogleClassroomIntegration class.
 */
class GoogleClassroomIntegration extends PostType {

	/**
	 * Constructor.
	 *
	 * @since 1.8.3
	 */
	public function __construct() {
		$debug = masteriyo_is_post_type_debug_enabled();

		$this->labels = array(
			'name'                  => _x( 'Google Classroom', 'Google Classroom General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Google Classroom', 'Google Classroom Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Google Classrooms', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Google Classroom', 'learning-management-system' ),
			'archives'              => __( 'Google Classroom Archives', 'learning-management-system' ),
			'attributes'            => __( 'Google Classroom Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Google Classroom:', 'learning-management-system' ),
			'all_items'             => __( 'All Google Classrooms', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Google Classroom', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Google Classroom', 'learning-management-system' ),
			'update_item'           => __( 'Update Google Classroom', 'learning-management-system' ),
			'view_item'             => __( 'View Google Classroom', 'learning-management-system' ),
			'view_items'            => __( 'View Google Classrooms', 'learning-management-system' ),
			'search_items'          => __( 'Search Google Classroom', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into Google Classroom', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Google Classroom', 'learning-management-system' ),
			'items_list'            => __( 'Google Classrooms list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Google Classrooms list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter Google Classrooms list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Google Classroom', 'learning-management-system' ),
			'description'         => __( 'Google Classroom Description', 'learning-management-system' ),
			'labels'              => $this->labels,
			'supports'            => array( 'title', 'custom-fields' ),
			'taxonomies'          => array(),
			'hierarchical'        => false,
			'menu_position'       => 5,
			'public'              => $debug,
			'show_ui'             => $debug,
			'show_in_menu'        => $debug,
			'show_in_admin_bar'   => $debug,
			'show_in_nav_menus'   => $debug,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'map_meta_cap'        => true,
			'capability_type'     => array( 'google_classroom', 'google_classrooms' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'can_export'          => true,
			'delete_with_user'    => false,
		);
	}
}
