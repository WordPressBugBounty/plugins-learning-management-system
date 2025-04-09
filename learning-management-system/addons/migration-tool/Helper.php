<?php
/**
 * Migration tool helper functions.
 *
 * @since 1.16.0
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Roles;

class Helper {

	/**
	 * Updates the user role based on the given user ID and desired role.
	 * If the given role is not already assigned to the user, it will be added.
	 * If the user does not have any of the valid roles (admin, manager, instructor, student), they will be assigned the student role.
	 *
	 * @since 1.16.0
	 *
	 * @param int $user_id User ID.
	 * @param string $role Desired role.
	 */
	public static function update_user_role( $user_id, $role = Roles::STUDENT ) {
		$user = new \WP_User( $user_id );

		if ( ! $user || ! isset( $user->ID ) || ! $user->roles ) {
			return;
		}

		$valid_roles = array( Roles::ADMIN, Roles::MANAGER, Roles::INSTRUCTOR, Roles::STUDENT );

		if ( ! empty( $role ) && ! in_array( $role, (array) $user->roles, true ) ) {
			$user->add_role( $role );
		}

		if ( empty( array_intersect( $valid_roles, (array) $user->roles ) ) ) {
			$user->set_role( Roles::STUDENT );
		}
	}

	/**
	 * Determine the video source for a given URL.
	 *
	 * @since 1.16.0
	 *
	 * @param string $url URL of video.
	 *
	 * @return array Array with the video source (embed, youtube, vimeo, external) and the URL.
	 */
	public static function determine_video_source_from_url( $url ) {
		$pattern = '/<iframe[^>]*>.*?<\/iframe>/i';

		preg_match_all( $pattern, $url, $match );

		if ( $match[0] ) {
			return array( 'embed-video', $url );
		} elseif (
			strpos( $url, 'youtube.com' ) !== false ||
			strpos( $url, 'youtu.be' ) !== false
		) {
			return array( 'youtube', $url );
		} elseif (
			strpos( $url, 'vimeo.com' ) !== false ||
			strpos( $url, 'player.vimeo.com' ) !== false
		) {
			return array( 'vimeo', $url );
		} else {
			return array( 'external', $url );
		}
	}

	/**
	 * Inserts a new post with specified parameters.
	 *
	 * This function creates a new post using the WordPress function `wp_insert_post`.
	 * It sets various properties of the post such as title, content, author, type,
	 * menu order, and parent post based on the provided arguments.
	 *
	 * @since 1.8.0
	 *
	 * @param string $post_title    Title of the post.
	 * @param string $post_content  Content of the post.
	 * @param int    $author_id     ID of the author creating the post.
	 * @param string $post_type     Type of the post. Default is PostType::SECTION.
	 * @param int    $menu_order    Order of the post in the menu. Default is 0.
	 * @param int|string $post_parent  Parent post ID. Default is an empty string.
	 * @return int|WP_Error         The post ID on success, WP_Error on failure.
	 */
	public static function insert_post( $post_title, $post_content, $author_id, $post_type = PostType::SECTION, $menu_order = 0, $post_parent = '' ) {
		$post_arg = array(
			'post_type'    => $post_type,
			'post_title'   => $post_title,
			'post_content' => $post_content,
			'post_status'  => PostStatus::PUBLISH,
			'post_author'  => $author_id,
			'post_parent'  => $post_parent,
			'menu_order'   => $menu_order,
		);
		return wp_insert_post( $post_arg );
	}

	/**
	 * Updates an existing post with specified parameters.
	 *
	 * This function updates a post identified by $post_id using the WordPress function `wp_update_post`.
	 * It allows updating the post type, menu order, and parent post. If the update fails, it returns false.
	 *
	 * @since 1.8.0
	 *
	 * @param int    $post_id       ID of the post to update.
	 * @param string $post_type     New type of the post. Default is 'topics'.
	 * @param int    $menu_order    New order of the post in the menu. Default is 0.
	 * @param int|string $post_parent  New parent post ID. Default is an empty string.
	 * @return int|false            The updated post ID on success, or false on failure.
	 */
	public static function update_post( $post_id, $post_type = PostType::SECTION, $menu_order = 0, $post_parent = '' ) {
		$post_arg = array(
			'ID'          => $post_id,
			'post_type'   => $post_type,
			'post_parent' => $post_parent,
			'menu_order'  => $menu_order,
		);
		$post_id  = wp_update_post( $post_arg );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		return $post_id;
	}

	/**
	 * Migrates course categories from LearnPress to Masteriyo.
	 *
	 * This function retrieves the course categories associated with a given course from LearnPress
	 * and assigns them to the same course in Masteriyo.
	 *
	 * @since 1.8.0
	 *
	 * @param int $course_id The ID of the course for which categories are to be migrated.
	 *                      This should be the Masteriyo course ID which corresponds to the LearnPress course.
	 *
	 * @return void This function does not return anything. It operates by side effect, updating the course taxonomy.
	 */
	public static function migrate_course_categories_from_to_masteriyo( $course_id, $taxonomy = 'course_category' ) {
		$categories = wp_get_post_terms( $course_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			foreach ( $categories as $cat_id ) {
				$cat = get_term( $cat_id, $taxonomy );

				if ( ! is_wp_error( $cat ) ) {
					// Check if the term exists in the 'course_cat' taxonomy.
					$masteriyo_cat_id = term_exists( $cat->name, 'course_cat' );

					if ( 0 === $masteriyo_cat_id || null === $masteriyo_cat_id ) {
						$masteriyo_cat = wp_insert_term( $cat->name, 'course_cat' );

						if ( ! is_wp_error( $masteriyo_cat ) ) {
							$masteriyo_cat_id = $masteriyo_cat['term_id'];
						}
					} else {
						$masteriyo_cat_id = $masteriyo_cat_id['term_id'];
					}

					$masteriyo_categories[] = (int) $masteriyo_cat_id;
				}
			}

			if ( ! empty( $masteriyo_categories ) ) {
				wp_set_object_terms( $course_id, $masteriyo_categories, 'course_cat', false );
			}
		}
	}

	/**
	 * Deletes remaining migrated items after all items have been migrated.
	 *
	 * @since 1.16.0
	 */
	public static function delete_remaining_migrated_items() {
		delete_option( 'masteriyo_remaining_migrated_items' );
	}

	/**
	 * Migrate course author from LifterLMS.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id LifterLMS course ID.
	 */
	public static function migrate_course_author( $course_id ) {
		$post_author = get_post_field( 'post_author', $course_id );

		if ( ! $post_author ) {
			return;
		}

		Helper::update_user_role( $post_author, Roles::INSTRUCTOR );
	}
}
