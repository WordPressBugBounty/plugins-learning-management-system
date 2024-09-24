<?php
/**
 * Orders class.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\PostType;
 */

namespace Masteriyo\PostType;

/**
 * Orders class.
 */
class Order extends PostType {
	/**
	 * Post slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $slug = 'mto-order';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$debug = masteriyo_is_post_type_debug_enabled();

		$this->labels = array(
			'name'                  => _x( 'Orders', 'Order General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Order', 'Order Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Orders', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Order', 'learning-management-system' ),
			'archives'              => __( 'Order Archives', 'learning-management-system' ),
			'attributes'            => __( 'Order Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Order:', 'learning-management-system' ),
			'all_items'             => __( 'All Orders', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Order', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Order', 'learning-management-system' ),
			'update_item'           => __( 'Update Order', 'learning-management-system' ),
			'view_item'             => __( 'View Order', 'learning-management-system' ),
			'view_items'            => __( 'View Orders', 'learning-management-system' ),
			'search_items'          => __( 'Search Order', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into order', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this order', 'learning-management-system' ),
			'items_list'            => __( 'Orders list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Orders list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter orders list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Orders', 'learning-management-system' ),
			'description'         => __( 'Orders Description', 'learning-management-system' ),
			'labels'              => $this->labels,
			'supports'            => array( 'title', 'editor', 'author', 'custom-fields', 'post-formats' ),
			'taxonomies'          => array(),
			'hierarchical'        => true,
			'public'              => $debug,
			'menu_position'       => 5,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'show_in_rest'        => true,
			'has_archive'         => true,
			'map_meta_cap'        => true,
			'capability_type'     => array( 'order', 'orders' ),
			'exclude_from_search' => false,
			'publicly_queryable'  => is_admin(),
			'can_export'          => true,
			'delete_with_user'    => true,
		);
	}
}
