<?php
/**
 * Wishlist item post type.
 *
 * @since 1.12.2
 */

namespace Masteriyo\Addons\WishList\PostType;

use Masteriyo\PostType\PostType;

class WishListItem extends PostType {
	/**
	 * Post slug.
	 *
	 * @since 1.12.2
	 *
	 * @var string
	 */
	protected $slug = 'mto-wishlist-item';

	/**
	 * Constructor.
	 *
	 * @since 1.12.2
	 */
	public function __construct() {
		$debug = masteriyo_is_post_type_debug_enabled();

		$this->labels = array(
			'name'                  => _x( 'Wishlist Items', 'Wishlist Item General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Wishlist Item', 'Wishlist Item Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Wishlist Items', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Wishlist Item', 'learning-management-system' ),
			'archives'              => __( 'Wishlist Item Archives', 'learning-management-system' ),
			'attributes'            => __( 'Wishlist Item Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Wishlist Item:', 'learning-management-system' ),
			'all_items'             => __( 'All Wishlist Items', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Wishlist Item', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Wishlist Item', 'learning-management-system' ),
			'update_item'           => __( 'Update Wishlist Item', 'learning-management-system' ),
			'view_item'             => __( 'View Wishlist Item', 'learning-management-system' ),
			'view_items'            => __( 'View Wishlist Items', 'learning-management-system' ),
			'search_items'          => __( 'Search Wishlist Item', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into wishlist item', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this wishlist item', 'learning-management-system' ),
			'items_list'            => __( 'Wishlist items list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Wishlist items list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter wishlist items list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Wishlist Items', 'learning-management-system' ),
			'description'         => __( 'Wishlist Items Description', 'learning-management-system' ),
			'labels'              => $this->labels,
			'supports'            => array( 'title', 'author', 'custom-fields' ),
			'taxonomies'          => array(),
			'hierarchical'        => true,
			'menu_position'       => 5,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => $debug,
			'show_in_admin_bar'   => $debug,
			'show_in_nav_menus'   => $debug,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'map_meta_cap'        => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'can_export'          => true,
			'delete_with_user'    => true,
		);
	}
}
