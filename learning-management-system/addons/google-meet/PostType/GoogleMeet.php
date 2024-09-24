<?php
/**
 * GoogleMeet class.
 *
 * @since 1.11.0
 *
 * @package Masteriyo\PostType;
 */

namespace Masteriyo\Addons\GoogleMeet\PostType;

use Masteriyo\PostType\PostType;

/**
 * Google Meet class.
 */
class GoogleMeet extends PostType {
	/**
	 * Post slug.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $slug = 'mto-google-meet';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$debug = masteriyo_is_post_type_debug_enabled();

		$this->labels = array(
			'name'                  => _x( 'Google Meet', 'Assignment General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Google Meet', 'Assignment Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Google Meet', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Google Meet', 'learning-management-system' ),
			'archives'              => __( 'Google Meet Archives', 'learning-management-system' ),
			'attributes'            => __( 'Google Meet Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Google Meet:', 'learning-management-system' ),
			'all_items'             => __( 'All Google Meet Meetings', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Meeting', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Meeting', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Meeting', 'learning-management-system' ),
			'update_item'           => __( 'Update Meeting', 'learning-management-system' ),
			'view_item'             => __( 'View Meeting', 'learning-management-system' ),
			'view_items'            => __( 'View Meeting', 'learning-management-system' ),
			'search_items'          => __( 'Search Meeting', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into Google Meet', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this google meet', 'learning-management-system' ),
			'items_list'            => __( 'Meeting list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Meeting list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter meeting list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Google Meet', 'learning-management-system' ),
			'description'         => __( 'Google Meet Description', 'learning-management-system' ),
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
			'capability_type'     => array( 'google-meet', 'google-meets' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'can_export'          => true,
			'delete_with_user'    => true,
		);
	}
}
