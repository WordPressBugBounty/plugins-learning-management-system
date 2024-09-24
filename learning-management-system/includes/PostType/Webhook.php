<?php
/**
 * Webhook post type class.
 *
 * @since 1.6.9
 */

namespace Masteriyo\PostType;

/**
 * Webhook post type class.
 *
 * @since 1.6.9
 */
class Webhook extends PostType {

	/**
	 * Post slug.
	 *
	 * @since 1.6.9
	 *
	 * @var string
	 */
	protected $slug = PostType::WEBHOOK;

	/**
	 * Constructor.
	 *
	 * @since 1.6.9
	 */
	public function __construct() {
		$debug    = masteriyo_is_post_type_debug_enabled();
		$supports = array( 'title', 'editor', 'custom-fields', 'author', 'publicize', 'wpcom-markdown' );

		$this->labels = array(
			'name'                  => _x( 'Webhooks', 'Webhook General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Webhook', 'Webhook Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Webhooks', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Webhook', 'learning-management-system' ),
			'archives'              => __( 'Webhook Archives', 'learning-management-system' ),
			'attributes'            => __( 'Webhook Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Webhook:', 'learning-management-system' ),
			'all_items'             => __( 'All Webhooks', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Webhook', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Webhook', 'learning-management-system' ),
			'update_item'           => __( 'Update Webhook', 'learning-management-system' ),
			'view_item'             => __( 'View Webhook', 'learning-management-system' ),
			'view_items'            => __( 'View Webhooks', 'learning-management-system' ),
			'search_items'          => __( 'Search Webhook', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into webhook', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this webhook', 'learning-management-system' ),
			'items_list'            => __( 'Webhooks list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Webhooks list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter webhooks list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Webhooks', 'learning-management-system' ),
			'description'         => __( 'Webhooks Description', 'learning-management-system' ),
			'labels'              => $this->labels,
			'supports'            => $supports,
			'hierarchical'        => false,
			'menu_position'       => 5,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => $debug,
			'show_in_admin_bar'   => $debug,
			'show_in_nav_menus'   => $debug,
			'can_export'          => true,
			'show_in_rest'        => true,
			'has_archive'         => false,
			'map_meta_cap'        => true,
			'capability_type'     => array( 'mto_webhook', 'mto_webhooks' ),
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'can_export'          => true,
			'delete_with_user'    => true,
		);
	}
}
