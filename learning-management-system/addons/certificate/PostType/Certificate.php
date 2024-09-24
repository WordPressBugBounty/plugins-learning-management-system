<?php
/**
 * Certificate post type.
 *
 * @since 1.13.0
 *
 * @package Masteriyo\Addons
 * @subpackage Masteriyo\Addons\Certificate
 */

namespace Masteriyo\Addons\Certificate\PostType;

use Masteriyo\PostType\PostType;

class Certificate extends PostType {
	/**
	 * Post slug.
	 *
	 * @since 1.13.0
	 *
	 * @var string
	 */
	protected $slug = 'mto-certificate';

	public function __construct() {
		$debug = masteriyo_is_post_type_debug_enabled();

		$this->labels = array(
			'name'                  => _x( 'Certificates', 'Certificate General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Certificate', 'Certificate Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Certificates', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Certificate', 'learning-management-system' ),
			'archives'              => __( 'Certificate Archives', 'learning-management-system' ),
			'attributes'            => __( 'Certificate Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Certificate:', 'learning-management-system' ),
			'all_items'             => __( 'All Certificates', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Certificate', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Certificate', 'learning-management-system' ),
			'update_item'           => __( 'Update Certificate', 'learning-management-system' ),
			'view_item'             => __( 'View Certificate', 'learning-management-system' ),
			'view_items'            => __( 'View Certificates', 'learning-management-system' ),
			'search_items'          => __( 'Search Certificate', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into certificate', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this certificate', 'learning-management-system' ),
			'items_list'            => __( 'Certificates list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Certificates list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter certificates list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Certificates', 'learning-management-system' ),
			'description'         => __( 'Certificates Description', 'learning-management-system' ),
			'labels'              => $this->labels,
			'supports'            => array( 'title', 'editor' ),
			'taxonomies'          => array(),
			'hierarchical'        => false,
			'menu_position'       => 5,
			'public'              => $debug,
			'show_ui'             => true,
			'show_in_menu'        => $debug,
			'show_in_admin_bar'   => $debug,
			'show_in_nav_menus'   => $debug,
			'show_in_rest'        => true,
			'has_archive'         => false,
			'map_meta_cap'        => true,
			'capability_type'     => array( 'certificate', 'certificates' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => true,
			'can_export'          => true,
			'delete_with_user'    => false,
			'template'            => array( array( 'masteriyo/certificate' ) ),
			'template_lock'       => 'all',
		);
	}
}
