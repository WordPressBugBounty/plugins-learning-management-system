<?php
/**
 * Earning class.
 *
 * @since 1.6.14
 *
 * @package Masteriyo\PostType;
 */

namespace Masteriyo\Addons\RevenueSharing\PostType;

use Masteriyo\PostType\PostType;

/**
 * Zoom class.
 */
class Earning extends PostType {

	/**
	 * Post slug.
	 *
	 * @since 1.6.14
	 *
	 * @var string
	 */
	protected $slug = PostType::EARNING;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$debug = masteriyo_is_post_type_debug_enabled();

		$this->labels = array(
			'name'                  => _x( 'Earning', 'Earning General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Earning', 'Earning Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Earnings', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Earning', 'learning-management-system' ),
			'archives'              => __( 'Earning Archives', 'learning-management-system' ),
			'attributes'            => __( 'Earning Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Earning:', 'learning-management-system' ),
			'all_items'             => __( 'All Earnings', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Earning', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Earning', 'learning-management-system' ),
			'update_item'           => __( 'Update Earning', 'learning-management-system' ),
			'view_item'             => __( 'View Earning', 'learning-management-system' ),
			'view_items'            => __( 'View Earnings', 'learning-management-system' ),
			'search_items'          => __( 'Search Earning', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into Earning', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Earning', 'learning-management-system' ),
			'items_list'            => __( 'Earnings list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Earnings list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter earnings list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Earning', 'learning-management-system' ),
			'description'         => __( 'Earning Description', 'learning-management-system' ),
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
			'capability_type'     => array( 'earning', 'earnings' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'can_export'          => true,
			'delete_with_user'    => false,
		);
	}
}
