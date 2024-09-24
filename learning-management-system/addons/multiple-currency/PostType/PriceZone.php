<?php
/**
 * PriceZone class.
 *
 * @since 1.11.0
 *
 * @package Masteriyo\Addons\MultipleCurrency\PostType;
 */

namespace Masteriyo\Addons\MultipleCurrency\PostType;

use Masteriyo\Addons\MultipleCurrency\Enums\PriceZoneStatus;
use Masteriyo\PostType\PostType;

/**
 * PriceZone class.
 */
class PriceZone extends PostType {
	/**
	 * Post slug.
	 *
	 * @since 1.11.0
	 *
	 * @var string
	 */
	protected $slug = PostType::PRICE_ZONE;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$debug = masteriyo_is_post_type_debug_enabled();

		// Register post type ('active' and 'inactive' statuses).
		foreach ( PriceZoneStatus::list() as $status => $values ) {
			if ( ! get_post_status_object( $status ) ) {
				register_post_status( $status, $values );
			}
		}

		$this->labels = array(
			'name'                  => _x( 'Price Zones', 'Price Zone General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Price Zone', 'Price Zone Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Price Zones', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Price Zone', 'learning-management-system' ),
			'archives'              => __( 'Price Zone Archives', 'learning-management-system' ),
			'attributes'            => __( 'Price Zone Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Price Zone:', 'learning-management-system' ),
			'all_items'             => __( 'All Price Zones', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Price Zone', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Price Zone', 'learning-management-system' ),
			'update_item'           => __( 'Update Price Zone', 'learning-management-system' ),
			'view_item'             => __( 'View Price Zone', 'learning-management-system' ),
			'view_items'            => __( 'View Price Zones', 'learning-management-system' ),
			'search_items'          => __( 'Search Price Zone', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into price zone', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this price zone', 'learning-management-system' ),
			'items_list'            => __( 'Price Zones list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Price Zones list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter price zones list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Price Zones', 'learning-management-system' ),
			'description'         => __( 'Price Zones Description', 'learning-management-system' ),
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
			'capability_type'     => array( 'price_zone', 'price_zones' ),
			'exclude_from_search' => false,
			'publicly_queryable'  => is_admin(),
			'can_export'          => true,
			'delete_with_user'    => true,
		);
	}
}
