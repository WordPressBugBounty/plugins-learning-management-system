<?php
/**
 * Quizes post type.
 *
 * @since 1.0.0
 *
 * @package PostType;
 */

namespace Masteriyo\PostType;

/**
 * Quizes post type.
 *
 * @since 1.0.0
 */
class Quiz extends PostType {
	/**
	 * Post slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $slug = 'mto-quiz';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$debug      = masteriyo_is_post_type_debug_enabled();
		$permalinks = masteriyo_get_permalink_structure();

		$this->labels = array(
			'name'                  => _x( 'Quizzes', 'Quiz General Name', 'learning-management-system' ),
			'singular_name'         => _x( 'Quiz', 'Quiz Singular Name', 'learning-management-system' ),
			'menu_name'             => __( 'Quizzes', 'learning-management-system' ),
			'name_admin_bar'        => __( 'Quiz', 'learning-management-system' ),
			'archives'              => __( 'Quiz Archives', 'learning-management-system' ),
			'attributes'            => __( 'Quiz Attributes', 'learning-management-system' ),
			'parent_item_colon'     => __( 'Parent Quiz:', 'learning-management-system' ),
			'all_items'             => __( 'All Quizzes', 'learning-management-system' ),
			'add_new_item'          => __( 'Add New Item', 'learning-management-system' ),
			'add_new'               => __( 'Add New', 'learning-management-system' ),
			'new_item'              => __( 'New Quiz', 'learning-management-system' ),
			'edit_item'             => __( 'Edit Quiz', 'learning-management-system' ),
			'update_item'           => __( 'Update Quiz', 'learning-management-system' ),
			'view_item'             => __( 'View Quiz', 'learning-management-system' ),
			'view_items'            => __( 'View Quizzes', 'learning-management-system' ),
			'search_items'          => __( 'Search Quiz', 'learning-management-system' ),
			'not_found'             => __( 'Not found', 'learning-management-system' ),
			'not_found_in_trash'    => __( 'Not found in Trash.', 'learning-management-system' ),
			'featured_image'        => __( 'Featured Image', 'learning-management-system' ),
			'set_featured_image'    => __( 'Set featured image', 'learning-management-system' ),
			'remove_featured_image' => __( 'Remove featured image', 'learning-management-system' ),
			'use_featured_image'    => __( 'Use as featured image', 'learning-management-system' ),
			'insert_into_item'      => __( 'Insert into quiz', 'learning-management-system' ),
			'uploaded_to_this_item' => __( 'Uploaded to this quiz', 'learning-management-system' ),
			'items_list'            => __( 'Quizzes list', 'learning-management-system' ),
			'items_list_navigation' => __( 'Quizzes list navigation', 'learning-management-system' ),
			'filter_items_list'     => __( 'Filter quizzes list', 'learning-management-system' ),
		);

		$this->args = array(
			'label'               => __( 'Quizzes', 'learning-management-system' ),
			'description'         => __( 'Quizzes Description', 'learning-management-system' ),
			'labels'              => $this->labels,
			'supports'            => array( 'title', 'editor', 'author', 'comments', 'custom-fields', 'page-attributes', 'post-formats' ),
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
			'capability_type'     => array( 'quiz', 'quizzes' ),
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'can_export'          => true,
			'delete_with_user'    => true,
			'rewrite'             => $permalinks['quiz_rewrite_slug'] ? array(
				'slug'       => $permalinks['quiz_rewrite_slug'],
				'with_front' => false,
				'feeds'      => true,
			) : false,
		);
	}


}
