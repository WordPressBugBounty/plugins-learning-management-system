<?php
/**
 * Course Announcement class.
 *
 * @since 1.6.16
 *
 * @package Masteriyo\PostType;
 */

namespace Masteriyo\Addons\CourseAnnouncement\PostType;

use Masteriyo\PostType\PostType;

/**
 * CourseAnnouncement class.
 */
class CourseAnnouncement extends PostType {

	/**
	 * Post slug.
	 *
	 * @since 1.6.16
	 *
	 * @var string
	 */
	protected $slug = PostType::COURSEANNOUNCEMENT;

	/**
	 * Constructor.
	 *
	 * @since 1.6.16
	 */
	public function __construct() {
		$debug = masteriyo_is_post_type_debug_enabled();

		$this->labels = array(
			'name'                  => _x( 'Course Announcement', 'Course Announcement General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Course Announcement', 'Course Announcement Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Course Announcements', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Course Announcement', 'learning-management-system' ),
			'archives'              => __( 'Course Announcement Archives', 'learning-management-system' ),
			'attributes'            => __( 'Course Announcement Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Course Announcement:', 'learning-management-system' ),
			'all_items'             => __( 'All Course Announcements', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Course Announcement', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Course Announcement', 'learning-management-system' ),
			'update_item'           => __( 'Update Course Announcement', 'learning-management-system' ),
			'view_item'             => __( 'View Course Announcement', 'learning-management-system' ),
			'view_items'            => __( 'View Course Announcements', 'learning-management-system' ),
			'search_items'          => __( 'Search Course Announcement', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into Course Announcement', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Course Announcement', 'learning-management-system' ),
			'items_list'            => __( 'Course Announcements list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Course Announcements list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter course announcements list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Course Announcement', 'learning-management-system' ),
			'description'         => __( 'Course Announcement Description', 'learning-management-system' ),
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
			'capability_type'     => array( 'announcement', 'announcements' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'can_export'          => true,
			'delete_with_user'    => false,
		);
	}
}
