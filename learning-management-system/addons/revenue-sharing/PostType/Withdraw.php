<?php
/**
 * Withdraw class.
 *
 * @since 1.6.14
 *
 * @package Masteriyo\PostType;
 */

namespace Masteriyo\Addons\RevenueSharing\PostType;

defined( 'ABSPATH' ) || exit;


use Masteriyo\PostType\PostType;

/**
 * Zoom class.
 */
class Withdraw extends PostType {

	/**
	 * Post slug.
	 *
	 * @since 1.6.14
	 *
	 * @var string
	 */
	protected $slug = PostType::WITHDRAW;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$debug = masteriyo_is_post_type_debug_enabled();

		$this->labels = array(
			'name'                  => _x( 'Withdrawals', 'Withdraw General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Withdrawal', 'Withdraw Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Withdrawals', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Withdrawal', 'learning-management-system' ),
			'archives'              => __( 'Withdrawal Archives', 'learning-management-system' ),
			'attributes'            => __( 'Withdrawal Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Withdrawal:', 'learning-management-system' ),
			'all_items'             => __( 'All Withdrawals', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Withdrawal', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Withdrawal', 'learning-management-system' ),
			'update_item'           => __( 'Update Withdrawal', 'learning-management-system' ),
			'view_item'             => __( 'View Withdrawal', 'learning-management-system' ),
			'view_items'            => __( 'View Withdrawals', 'learning-management-system' ),
			'search_items'          => __( 'Search Withdrawals', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into Withdrawal', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Withdrawal', 'learning-management-system' ),
			'items_list'            => __( 'Withdrawals list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Withdrawals list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter withdrawals list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Withdrawals', 'learning-management-system' ),
			'description'         => __( 'Withdrawal Description', 'learning-management-system' ),
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
			'capability_type'     => array( 'withdraw', 'withdraws' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'can_export'          => true,
			'delete_with_user'    => true,
		);
	}
}
