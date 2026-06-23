<?php
/**
 * Migration tool helper functions.
 *
 * @since 1.16.0
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Enums\CourseProgressStatus;
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
	public static function migrate_course_categories_from_to_masteriyo( $course_id, $taxonomy = 'course_category', $target_taxonomy = 'course_cat' ) {
		// Static cache keyed by target taxonomy: term name → Masteriyo term_id.
		// Persists for the lifetime of the AS job (reset on next job invocation), eliminating
		// repeated term_exists() DB queries when many courses share the same categories.
		static $masteriyo_cat_cache = array();

		$categories = wp_get_post_terms( $course_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$masteriyo_categories = array();

			foreach ( $categories as $cat_id ) {
				$cat = get_term( $cat_id, $taxonomy );

				if ( ! is_wp_error( $cat ) ) {
					$cache_key = $target_taxonomy . ':' . $cat->name;

					if ( isset( $masteriyo_cat_cache[ $cache_key ] ) ) {
						$masteriyo_categories[] = $masteriyo_cat_cache[ $cache_key ];
						continue;
					}

					// Check if the term exists in the target taxonomy.
					$masteriyo_cat_id = term_exists( $cat->name, $target_taxonomy );

					if ( 0 === $masteriyo_cat_id || null === $masteriyo_cat_id ) {
						$masteriyo_cat = wp_insert_term( $cat->name, $target_taxonomy );

						if ( ! is_wp_error( $masteriyo_cat ) ) {
							$masteriyo_cat_id = $masteriyo_cat['term_id'];
						}
					} else {
						$masteriyo_cat_id = $masteriyo_cat_id['term_id'];
					}

					if ( $masteriyo_cat_id ) {
						$masteriyo_cat_cache[ $cache_key ] = (int) $masteriyo_cat_id;
						$masteriyo_categories[]            = (int) $masteriyo_cat_id;
					}
				}
			}

			if ( ! empty( $masteriyo_categories ) ) {
				wp_set_object_terms( $course_id, $masteriyo_categories, $target_taxonomy, false );
			}
		}
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

	/**
	 * Insert a user course enrollment into masteriyo_user_items and optionally write order meta.
	 *
	 * Shared by all migrators. Inserts the enrollment row, assigns the student role,
	 * and writes _order_id / _price to masteriyo_user_itemmeta when provided.
	 * Returns the new user_item_id on success, null on failure (caller decides how to handle).
	 *
	 * @since x.x.x
	 *
	 * @param int         $user_id       WordPress user ID.
	 * @param int         $course_id     Masteriyo course post ID.
	 * @param string      $date_start    Enrollment start date (MySQL datetime, UTC).
	 * @param string|null $date_end      Completion/expiry date, or null if not yet complete.
	 * @param string      $status        UserCourseStatus value (default 'active').
	 * @param int|null    $order_id      Masteriyo order ID to store in itemmeta, or null.
	 * @param float|null  $price         Order price to store in itemmeta, or null.
	 * @param string|null $date_modified Last-modified date override (MySQL datetime, UTC); defaults to $date_start.
	 * @return int|null New user_item_id, or null if the insert failed.
	 */
	public static function enroll_user(
		int $user_id,
		int $course_id,
		string $date_start,
		?string $date_end = null,
		string $status = 'active',
		?int $order_id = null,
		?float $price = null,
		?string $date_modified = null
	): ?int {
		global $wpdb;

		$data    = array(
			'item_id'       => $course_id,
			'user_id'       => $user_id,
			'item_type'     => 'user_course',
			'date_start'    => $date_start,
			'date_modified' => $date_modified ?? $date_start,
			'date_end'      => $date_end,
			'parent_id'     => 0,
			'status'        => $status,
		);
		$formats = array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' );

		$inserted = $wpdb->insert( $wpdb->prefix . 'masteriyo_user_items', $data, $formats );

		if ( false === $inserted ) {
			return null;
		}

		$user_item_id = (int) $wpdb->insert_id;

		static::update_user_role( $user_id, Roles::STUDENT );

		if ( $order_id ) {
			$wpdb->insert(
				$wpdb->prefix . 'masteriyo_user_itemmeta',
				array(
					'user_item_id' => $user_item_id,
					'meta_key'     => '_order_id',
					'meta_value'   => $order_id,
				)
			);
		}

		if ( null !== $price ) {
			$wpdb->insert(
				$wpdb->prefix . 'masteriyo_user_itemmeta',
				array(
					'user_item_id' => $user_item_id,
					'meta_key'     => '_price',
					'meta_value'   => $price,
				)
			);
		}

		return $user_item_id;
	}

	/**
	 * Find or create the course_progress parent activity row in masteriyo_user_activities.
	 *
	 * Shared by all migrators. Returns the existing row ID if found; otherwise inserts
	 * a new 'started' course_progress row and returns its ID.
	 * Fixes TutorLMS bug: uses CourseProgressStatus::STARTED constant and null for
	 * completed_at (correct for an in-progress course) instead of a zero-date string.
	 *
	 * @since x.x.x
	 *
	 * @param int    $user_id    WordPress user ID.
	 * @param int    $course_id  Masteriyo course post ID.
	 * @param string $created_at Row creation timestamp (MySQL datetime, UTC).
	 * @return int Progress activity ID, or 0 if the insert failed.
	 */
	public static function get_or_create_course_progress( int $user_id, int $course_id, string $created_at ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'masteriyo_user_activities';

		$progress_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND item_id = %d AND activity_type = 'course_progress' LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$course_id
			)
		);

		if ( ! $progress_id ) {
			$wpdb->insert(
				$table,
				array(
					'user_id'         => $user_id,
					'item_id'         => $course_id,
					'activity_type'   => 'course_progress',
					'activity_status' => CourseProgressStatus::STARTED,
					'parent_id'       => 0,
					'created_at'      => $created_at,
					'modified_at'     => $created_at,
					'completed_at'    => null,
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
			);
			$progress_id = (int) $wpdb->insert_id;
		}

		return $progress_id;
	}

	/**
	 * Resolve (or create) a course_difficulty term and set it on the course.
	 *
	 * Shared by all migrators. Looks up the term by slug; inserts it if missing.
	 * Writes _difficulty_id post meta and sets the taxonomy term on the course.
	 *
	 * @since x.x.x
	 *
	 * @param int    $course_id Masteriyo course post ID.
	 * @param string $slug      Difficulty slug (e.g. 'beginner'). Empty string is a no-op.
	 * @return void
	 */
	public static function set_course_difficulty( int $course_id, string $slug ): void {
		if ( ! $slug ) {
			update_post_meta( $course_id, '_difficulty_id', 0 );
			return;
		}

		$term = get_term_by( 'slug', $slug, 'course_difficulty' );

		if ( ! $term || is_wp_error( $term ) ) {
			$inserted = wp_insert_term( ucfirst( $slug ), 'course_difficulty', array( 'slug' => $slug ) );

			if ( is_wp_error( $inserted ) ) {
				update_post_meta( $course_id, '_difficulty_id', 0 );
				return;
			}

			$term_id = $inserted['term_id'];
		} else {
			$term_id = $term->term_id;
		}

		update_post_meta( $course_id, '_difficulty_id', $term_id );
		wp_set_object_terms( $course_id, $term_id, 'course_difficulty', false );
	}

	/**
	 * Create and assign a Masteriyo certificate template to a course.
	 *
	 * Called during the courses migration step for any source LMS that stored a certificate
	 * reference on the course. The source LMS certificate design cannot be converted, so
	 * Certificate Sample 1 is auto-assigned (falls back to the blank template if CDN is
	 * unreachable). Writes `_certificate_id` and `_certificate_enabled = yes` on the course.
	 *
	 * @since x.x.x
	 *
	 * @param int $course_id  Masteriyo mto-course post ID.
	 * @param int $author_id  Post author user ID (used for the certificate post).
	 * @return void
	 */
	public static function assign_certificate_template( int $course_id, int $author_id ): void {
		// Idempotency: skip if this course already has a valid certificate assigned.
		$existing_cert_id = (int) get_post_meta( $course_id, '_certificate_id', true );
		if ( $existing_cert_id && 'mto-certificate' === get_post_type( $existing_cert_id ) ) {
			return;
		}

		$shared_cert_id = static::get_or_create_migration_certificate( $author_id );

		if ( $shared_cert_id ) {
			update_post_meta( $course_id, '_certificate_id', $shared_cert_id );
			update_post_meta( $course_id, '_certificate_enabled', 'yes' );
		} else {
			// Fallback: at minimum mark enabled so the course isn't left broken.
			update_post_meta( $course_id, '_certificate_enabled', 'yes' );
		}
	}

	/**
	 * Return the ID of the shared migration certificate post, creating it on first call.
	 *
	 * One `mto-certificate` post is created for the entire migration run and flagged with
	 * `_masteriyo_migration_certificate = 1`. All migrated courses point to this single
	 * post via `_certificate_id`, avoiding one certificate post per course. The ID is
	 * cached in a static variable so only one DB lookup occurs per request.
	 *
	 * @since x.x.x
	 *
	 * @param int $author_id Post author user ID (used only on first creation).
	 * @return int Certificate post ID, or 0 on failure.
	 */
	public static function get_or_create_migration_certificate( int $author_id ): int {
		static $cert_id = null;

		if ( null !== $cert_id ) {
			return $cert_id;
		}

		// Check if a shared migration certificate was already created in a previous run.
		$existing = get_posts(
			array(
				'post_type'      => 'mto-certificate',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_key'       => '_masteriyo_migration_certificate', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1',                                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			$cert_id = (int) reset( $existing );
			return $cert_id;
		}

		// Build certificate HTML — try CDN Sample 1 first, fall back to blank template.
		$cert_html = '';

		if ( function_exists( 'masteriyo_get_certificate_templates' ) ) {
			$templates = masteriyo_get_certificate_templates();
			if ( is_array( $templates ) && ! empty( $templates ) ) {
				$first_template = reset( $templates );
				if ( ! empty( $first_template['content'] ) ) {
					$cert_html = function_exists( 'masteriyo_process_content_for_import' )
						? masteriyo_process_content_for_import( $first_template['content'] )
						: $first_template['content'];
				}
			}
		}

		if ( '' === $cert_html && function_exists( 'masteriyo_get_blank_certificate_template' ) ) {
			$cert_html = masteriyo_get_blank_certificate_template();
		}

		if ( '' === $cert_html ) {
			$cert_id = 0;
			return $cert_id;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'mto-certificate',
				'post_status'  => 'publish',
				'post_title'   => __( 'Migration Certificate', 'learning-management-system' ),
				'post_content' => $cert_html,
				'post_author'  => $author_id,
			)
		);

		if ( ! $new_id || is_wp_error( $new_id ) ) {
			masteriyo_get_logger()->warning(
				'Could not create shared migration certificate post.',
				array( 'source' => 'migration-tool' )
			);
			$cert_id = 0;
			return $cert_id;
		}

		// Flag so it can be found and reused across migration runs.
		update_post_meta( $new_id, '_masteriyo_migration_certificate', '1' );

		masteriyo_get_logger()->info(
			sprintf(
				'Shared migration certificate created (ID %d). Original LMS certificate designs cannot be converted — customize this template at Masteriyo > Certificates.',
				$new_id
			),
			array( 'source' => 'migration-tool' )
		);

		$cert_id = $new_id;
		return $cert_id;
	}

	/**
	 * Preserve a TutorLMS field that has no direct Masteriyo equivalent.
	 *
	 * Data is stored under _migrated_{key} so it can be referenced by Pro add-ons
	 * or cleaned up in bulk after migration.
	 *
	 * @since x.x.x
	 *
	 * @param int    $post_id Target Masteriyo post ID.
	 * @param string $key     Original field name (leading underscores are stripped).
	 * @param mixed  $value   Value to store. Empty values are not written.
	 * @return void
	 */
	public static function store_unmigrated_meta( int $post_id, string $key, $value ): void {
		if ( '' === $value || null === $value || array() === $value ) {
			return;
		}
		update_post_meta( $post_id, '_migrated_' . ltrim( $key, '_' ), $value );
	}
}
