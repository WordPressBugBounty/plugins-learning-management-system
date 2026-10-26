<?php
/**
 * Migration Tool helper class.
 *
 * @since 1.13.0
 */
namespace Masteriyo\Addons\MigrationTool\LMS;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\Helper;
use Masteriyo\Addons\MigrationTool\MigrationMap;
use Masteriyo\Addons\MigrationTool\PostCloner;
use Masteriyo\Enums\CommentStatus;
use Masteriyo\Enums\CommentType;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CoursePriceType;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Roles;
use WP_Query;

class TutorLMS {

	/**
	 * Migrator slug, the key every migration map row is written under.
	 */
	const SLUG = 'tutor';

	/**
	 * Register an addon service provider in the DI container on-demand.
	 *
	 * Cannot use AbstractLMSMigrator::ensure_service_provider() here because TutorLMS
	 * is a static helper class, not a subclass of AbstractLMSMigrator. Inlines the same
	 * logic so the container key is available before the migrate_single_*() call.
	 *
	 * @param string $service_key    Container key (e.g. 'wishlist-item').
	 * @param string $provider_class Fully-qualified service provider class name.
	 * @throws \Exception If the provider class file is not installed.
	 */
	private static function register_service_provider( string $service_key, string $provider_class ): void {
		global $masteriyo;

		if ( $masteriyo->has( $service_key ) ) {
			return;
		}

		if ( ! class_exists( $provider_class ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'Addon class %s not found — install the required addon to migrate this data.', $provider_class ) );
		}

		$masteriyo->addServiceProvider( new $provider_class() );
	}

	/**
	 * Dispatch per-item migration to the correct single-item method.
	 *
	 * Must be idempotent — safe to call twice for the same (step, item_id) pair.
	 *
	 * @param string $step    Step name matching a key in get_steps().
	 * @param int    $item_id Source item ID (post ID, comment ID, or table row ID).
	 * @throws \Exception Triggers ROLLBACK in the job engine; item is added to the failed list.
	 */
	public static function migrate_item( string $step, int $item_id ): void {
		// Some steps call tutor_utils() without a per-call guard. Fail fast here with a
		// clear message rather than letting PHP throw an undefined-function Error mid-item.
		if ( in_array( $step, array( 'courses', 'questions_n_answers' ), true ) && ! function_exists( 'tutor_utils' ) ) {
			throw new \Exception(
				sprintf( 'TutorLMS is not loaded — "%s" step requires tutor_utils(). Ensure TutorLMS is active.', $step ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		switch ( $step ) {
			case 'users':
				static::migrate_single_user( $item_id );
				break;
			case 'courses':
				static::migrate_single_course( $item_id );
				break;
			case 'enrollments':
				static::migrate_single_enrollment( $item_id );
				break;
			case 'orders':
				static::migrate_single_order( $item_id );
				break;
			case 'reviews':
				static::migrate_single_review( $item_id );
				break;
			case 'announcement':
				static::migrate_single_announcement( $item_id );
				break;
			case 'questions_n_answers':
				static::migrate_single_qa( $item_id );
				break;
			case 'progress':
				static::migrate_single_progress( $item_id );
				break;
			case 'quiz_attempts':
				static::migrate_single_quiz_attempt( $item_id );
				break;
			case 'wishlists':
				static::register_service_provider(
					'wishlist-item',
					\Masteriyo\Addons\WishList\Providers\WishListServiceProvider::class
				);
				static::migrate_single_wishlist_user( $item_id );
				break;
			case 'google_meet':
				static::migrate_single_google_meet( $item_id );
				break;
		}
	}

	/**
	 * Count the source items a step still has to copy. Fast COUNT queries, no records loaded.
	 *
	 * Rows already in the migration map are left out, so a second run counts only
	 * what is new. Progress and wishlists are re-read every run: their source is
	 * per-user state that grows, and copying them is idempotent.
	 *
	 * @param string $step Step name.
	 * @return int
	 */
	public static function count_source_items( string $step ): int {
		global $wpdb;

		$not_post    = MigrationMap::unmapped_sql( 'ID', self::SLUG, MigrationMap::POST );
		$not_comment = MigrationMap::unmapped_sql( 'comment_ID', self::SLUG, MigrationMap::COMMENT );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		switch ( $step ) {
			case 'users':
				$capabilities_key = $wpdb->get_blog_prefix() . 'capabilities';
				$not_user         = MigrationMap::unmapped_sql( 'u.ID', self::SLUG, MigrationMap::USER );
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT u.ID)
						 FROM {$wpdb->users} u
						 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
						 WHERE um.meta_key = %s
						   AND ( um.meta_value LIKE %s OR um.meta_value LIKE %s )
						   {$not_user}",
						$capabilities_key,
						'%tutor_student%',
						'%tutor_instructor%'
					)
				);

			case 'courses':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'courses' {$not_post}" );

			case 'enrollments':
				$not_enrollment = MigrationMap::unmapped_sql( 'ID', self::SLUG, 'enrollments' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'tutor_enrolled' {$not_enrollment}" );

			case 'orders':
				if ( ! function_exists( 'tutor_utils' ) ) {
					return 0;
				}
				$monetize_by = \tutor_utils()->get_option( 'monetize_by' );
				if ( 'wc' === $monetize_by ) {
					return 0; // WC orders handled inside enrollments step.
				}
				if ( 'edd' === $monetize_by ) {
					return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'edd_payment' {$not_post}" );
				}
				if ( 'tutor' === $monetize_by ) {
					$orders_tbl = $wpdb->prefix . 'tutor_orders';
					if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $orders_tbl ) ) ) {
						return 0;
					}
					$not_order = MigrationMap::unmapped_sql( 'id', self::SLUG, 'orders' );
					return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$orders_tbl} WHERE 1=1 {$not_order}" );
				}
				return 0;

			case 'reviews':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'tutor_course_rating' {$not_comment}" );

			case 'announcement':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'tutor_announcements' {$not_post}" );

			case 'questions_n_answers':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'tutor_q_and_a' AND comment_parent = 0 {$not_comment}" );

			case 'progress':
				// Enrollments whose student has completed at least one lesson in Tutor.
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT p.ID)
						 FROM {$wpdb->posts} p
						 WHERE p.post_type = 'tutor_enrolled'
						   AND p.post_status != 'trash'
						   AND EXISTS (
						       SELECT 1 FROM {$wpdb->usermeta} um
						       WHERE um.user_id = p.post_author
						         AND um.meta_key LIKE %s
						         AND um.meta_value != ''
						   )",
						$wpdb->esc_like( '_tutor_completed_lesson_id_' ) . '%'
					)
				);

			case 'quiz_attempts':
				$not_attempt = MigrationMap::unmapped_sql( 'attempt_id', self::SLUG, 'quiz_attempts' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_quiz_attempts WHERE 1=1 {$not_attempt}" );

			case 'google_meet':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'tutor-google-meet' {$not_post}" );

			case 'wishlists':
				return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = '_tutor_course_wishlist'" );
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return 0;
	}

	/**
	 * Return one batch of source IDs starting after $cursor.
	 *
	 * Every step pages by ID and leaves out rows already in the migration map. Nothing
	 * is removed from the source, so nothing is "self-cleaning" any more; $exclude is
	 * unused because a cursor never revisits a failed ID within a run.
	 *
	 * @param string $step    Step name.
	 * @param int    $limit   Batch size.
	 * @param int    $cursor  Last processed ID (0 = first batch).
	 * @param int[]  $exclude Unused.
	 * @return int[]
	 */
	public static function get_source_ids( string $step, int $limit, int $cursor, array $exclude = array() ): array {
		global $wpdb;

		$not_post    = MigrationMap::unmapped_sql( 'ID', self::SLUG, MigrationMap::POST );
		$not_comment = MigrationMap::unmapped_sql( 'comment_ID', self::SLUG, MigrationMap::COMMENT );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		switch ( $step ) {
			case 'users':
				$capabilities_key = $wpdb->get_blog_prefix() . 'capabilities';
				$not_user         = MigrationMap::unmapped_sql( 'u.ID', self::SLUG, MigrationMap::USER );
				$ids              = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT u.ID
						 FROM {$wpdb->users} u
						 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
						 WHERE um.meta_key = %s
						   AND ( um.meta_value LIKE %s OR um.meta_value LIKE %s )
						   AND u.ID > %d
						   {$not_user}
						 ORDER BY u.ID ASC
						 LIMIT %d",
						$capabilities_key,
						'%tutor_student%',
						'%tutor_instructor%',
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'courses':
				return self::post_ids_after( 'courses', $cursor, $limit, $not_post );

			case 'enrollments':
				return self::post_ids_after( 'tutor_enrolled', $cursor, $limit, MigrationMap::unmapped_sql( 'ID', self::SLUG, 'enrollments' ) );

			case 'orders':
				if ( ! function_exists( 'tutor_utils' ) ) {
					return array();
				}
				$monetize_by = \tutor_utils()->get_option( 'monetize_by' );
				if ( 'wc' === $monetize_by ) {
					return array(); // WC orders handled inside enrollments step.
				}
				if ( 'edd' === $monetize_by ) {
					return self::post_ids_after( 'edd_payment', $cursor, $limit, $not_post );
				}
				if ( 'tutor' === $monetize_by ) {
					$orders_tbl = $wpdb->prefix . 'tutor_orders';
					if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $orders_tbl ) ) ) {
						return array();
					}
					$not_order = MigrationMap::unmapped_sql( 'id', self::SLUG, 'orders' );
					$ids       = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT id FROM {$orders_tbl}
							 WHERE id > %d
							 {$not_order}
							 ORDER BY id ASC
							 LIMIT %d",
							$cursor,
							$limit
						)
					);
					return array_map( 'intval', $ids ? $ids : array() );
				}
				return array();

			case 'reviews':
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT comment_ID FROM {$wpdb->comments}
						 WHERE comment_type = 'tutor_course_rating'
						   AND comment_ID > %d
						 {$not_comment}
						 ORDER BY comment_ID ASC
						 LIMIT %d",
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'announcement':
				return self::post_ids_after( 'tutor_announcements', $cursor, $limit, $not_post );

			case 'questions_n_answers':
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT comment_ID FROM {$wpdb->comments}
						 WHERE comment_type = 'tutor_q_and_a' AND comment_parent = 0
						   AND comment_ID > %d
						 {$not_comment}
						 ORDER BY comment_ID ASC
						 LIMIT %d",
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'progress':
				// Re-read every run: a student can complete more lessons in Tutor between runs.
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT p.ID
						 FROM {$wpdb->posts} p
						 WHERE p.post_type = 'tutor_enrolled'
						   AND p.post_status != 'trash'
						   AND p.ID > %d
						   AND EXISTS (
						       SELECT 1 FROM {$wpdb->usermeta} um
						       WHERE um.user_id = p.post_author
						         AND um.meta_key LIKE %s
						         AND um.meta_value != ''
						   )
						 ORDER BY p.ID ASC
						 LIMIT %d",
						$cursor,
						$wpdb->esc_like( '_tutor_completed_lesson_id_' ) . '%',
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'quiz_attempts':
				$not_attempt = MigrationMap::unmapped_sql( 'attempt_id', self::SLUG, 'quiz_attempts' );
				$ids         = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT attempt_id FROM {$wpdb->prefix}tutor_quiz_attempts
						 WHERE attempt_id > %d
						 {$not_attempt}
						 ORDER BY attempt_id ASC
						 LIMIT %d",
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'wishlists':
				// Re-read every run: wishlists grow, and copying an entry twice is a no-op.
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT user_id FROM {$wpdb->usermeta}
						 WHERE meta_key = '_tutor_course_wishlist'
						   AND user_id > %d
						 ORDER BY user_id ASC
						 LIMIT %d",
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'google_meet':
				return self::post_ids_after( 'tutor-google-meet', $cursor, $limit, $not_post );

		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array();
	}

	/**
	 * One page of post IDs of a type, after the cursor, that are not in the map yet.
	 *
	 * @param string $post_type Source post type.
	 * @param int    $cursor    Last processed ID.
	 * @param int    $limit     Batch size.
	 * @param string $not_post  Map exclusion fragment for the ID column.
	 * @return int[]
	 */
	private static function post_ids_after( string $post_type, int $cursor, int $limit, string $not_post ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				 WHERE post_type = %s
				   AND ID > %d
				 {$not_post}
				 ORDER BY ID ASC
				 LIMIT %d",
				$post_type,
				$cursor,
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( 'intval', $ids ? $ids : array() );
	}

	/**
	 * Bulk-update course_progress status once all progress items are migrated.
	 * Replaces the per-item recount queries — runs once after the step completes.
	 *
	 * @param string $step Step name.
	 */
	public static function finalize_step( string $step ): void {
		if ( 'courses' === $step ) {
			Helper::relink_prerequisites( self::SLUG );
			return;
		}

		if ( 'progress' !== $step ) {
			return;
		}

		global $wpdb;
		$tbl = $wpdb->prefix . 'masteriyo_user_activities';
		// Only this run's copies: a course the site made natively keeps its own dates.
		// A course already completed keeps the date it was first completed on a rerun.
		$copied = MigrationMap::targets_sql( self::SLUG, MigrationMap::POST );

		$lesson_type = PostType::LESSON;
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"UPDATE {$tbl} AS parent
			 INNER JOIN (
			     SELECT CAST( pm.meta_value AS UNSIGNED ) AS course_id,
			            COUNT( DISTINCT p.ID )            AS curriculum_total
			     FROM   {$wpdb->posts}    AS p
			     INNER  JOIN {$wpdb->postmeta} AS pm
			            ON  p.ID = pm.post_id AND pm.meta_key = '_course_id'
			     WHERE  p.post_type = '{$lesson_type}'
			     GROUP  BY pm.meta_value
			 ) AS curriculum ON parent.item_id = curriculum.course_id
			 INNER JOIN (
			     SELECT parent_id,
			            COUNT( DISTINCT item_id ) AS done
			     FROM   {$tbl}
			     WHERE  activity_type = 'lesson' AND activity_status = 'completed'
			     GROUP  BY parent_id
			 ) AS completed ON parent.id = completed.parent_id
			 SET parent.activity_status = CASE
			         WHEN curriculum.curriculum_total > 0 AND completed.done >= curriculum.curriculum_total THEN 'completed'
			         ELSE 'progress'
			     END,
			     parent.completed_at = IF(
			         curriculum.curriculum_total > 0 AND completed.done >= curriculum.curriculum_total,
			         COALESCE( NULLIF( parent.completed_at, '0000-00-00 00:00:00' ), UTC_TIMESTAMP() ),
			         '0000-00-00 00:00:00'
			     ),
			     parent.modified_at  = UTC_TIMESTAMP()
			 WHERE parent.activity_type = 'course_progress'
			   AND completed.done       > 0
			   AND parent.item_id IN ( {$copied} )"
		);
		// phpcs:enable
	}

	/**
	 * Give a Tutor student or instructor the matching Masteriyo role.
	 *
	 * Tutor's own roles and capabilities stay: Tutor keeps running until the site
	 * owner deactivates it.
	 *
	 * @param int $user_id WP user ID.
	 * @throws \Exception If user does not exist.
	 */
	public static function migrate_single_user( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			throw new \Exception( sprintf( 'migrate_single_user: WP user %d not found.', $user_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$roles = (array) $user->roles;
		$caps  = is_array( $user->caps ) ? $user->caps : array();

		if ( in_array( 'tutor_student', $roles, true ) || isset( $caps['tutor_student'] ) ) {
			$user->add_role( Roles::STUDENT );
		}

		if ( in_array( 'tutor_instructor', $roles, true ) || isset( $caps['tutor_instructor'] ) ) {
			if ( ! in_array( 'administrator', $roles, true ) && ! isset( $caps['administrator'] ) ) {
				$user->add_role( Roles::INSTRUCTOR );
			}
		}

		// Instructors are also handled via migrate_single_course; the guard prevents double-processing.
		if ( ! get_user_meta( $user_id, '_masteriyo_profile_migrated', true ) ) {
			static::migrate_instructor_profile( $user_id );
			update_user_meta( $user_id, '_masteriyo_profile_migrated', '1' );
		}

		MigrationMap::record( self::SLUG, MigrationMap::USER, $user_id, $user_id );
	}

	/**
	 * Copy a Tutor course with its topics, lessons, quizzes and questions into Masteriyo.
	 *
	 * Tutor's posts are read, never changed: each is cloned and the clone converted.
	 * Enrollments are owned by the 'enrollments' step.
	 *
	 * @param int $source_course_id Tutor course post ID.
	 * @throws \Exception If the post record does not exist.
	 */
	public static function migrate_single_course( int $source_course_id ): void {
		$course = get_post( $source_course_id );
		if ( ! $course ) {
			throw new \Exception( sprintf( 'migrate_single_course: post %d not found.', $source_course_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$course_id = PostCloner::clone_post(
			self::SLUG,
			$source_course_id,
			array(
				'post_type'   => PostType::COURSE,
				'post_status' => 'pending' === $course->post_status ? 'draft' : $course->post_status,
			)
		);

		if ( 0 !== (int) $course->post_author ) {
			$user_id = (int) $course->post_author;
			$user    = new \WP_User( $user_id );
			if ( $user->exists() ) {
				$user->remove_role( Roles::STUDENT );
				if ( ! in_array( 'administrator', (array) $user->roles, true ) ) {
					$user->add_role( Roles::INSTRUCTOR );
				}
				if ( ! get_user_meta( $user_id, '_masteriyo_profile_migrated', true ) ) {
					static::migrate_instructor_profile( $user_id );
					update_user_meta( $user_id, '_masteriyo_profile_migrated', '1' );
				}
			}
		}

		static::update_masteriyo_course_from_tutor( $course_id );

		// For EDD: point the download at the copied course so migrate_single_order() can resolve it.
		if ( function_exists( 'tutor_utils' ) && 'edd' === \tutor_utils()->get_option( 'monetize_by' ) ) {
			$product_id = (int) get_post_meta( $course_id, '_tutor_course_product_id', true );
			if ( $product_id && function_exists( 'edd_get_download' ) && \edd_get_download( $product_id ) ) {
				update_post_meta( $course_id, '_edd_download_id', $product_id );
				update_post_meta( $product_id, '_is_masteriyo_course', 'yes' );
				update_post_meta( $product_id, '_masteriyo_course_id', $course_id );
				delete_post_meta( $course_id, '_tutor_course_product_id' );
			}
		}

		$sections = get_posts(
			array(
				'post_type'      => 'topics',
				'post_parent'    => $source_course_id,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'post_status'    => 'any',
			)
		);

		foreach ( $sections as $section ) {
			$section_id = PostCloner::clone_post(
				self::SLUG,
				$section->ID,
				array(
					'post_type'   => PostType::SECTION,
					'post_parent' => $course_id,
				)
			);

			$mto_section = masteriyo_get_section( $section_id );
			if ( $mto_section ) {
				$mto_section->set_course_id( $course_id );
				$mto_section->save();
			}

			$items = get_posts(
				array(
					'post_type'      => array( 'lesson', 'tutor_quiz', 'tutor_assignments' ),
					'post_parent'    => $section->ID,
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
					'post_status'    => 'any',
				)
			);

			foreach ( $items as $item ) {
				static::update_tutor_lesson_to_masteriyo( $item, $section_id, $course_id );
				static::update_tutor_course_quiz_to_masteriyo( $item, $section_id, $course_id );
				/**
				 * Fires for each TutorLMS curriculum item during the courses step.
				 *
				 * TutorLMS section children the shared migration does not handle —
				 * assignments — are copied here.
				 *
				 * @param \WP_Post $item       TutorLMS curriculum item post object (the source).
				 * @param int      $section_id Masteriyo section post ID (the copy).
				 * @param int      $course_id  Masteriyo course post ID (the copy).
				 */
				do_action( 'masteriyo_migration_tool_tutorlms_curriculum_item', $item, $section_id, $course_id );
			}
		}
	}

	/**
	 * Copy a single TutorLMS enrollment (tutor_enrolled CPT) to a Masteriyo user_course record.
	 *
	 * The tutor_enrolled post stays.
	 *
	 * @param int $enrollment_post_id Post ID of the tutor_enrolled CPT.
	 * @throws \Exception If the enrollment post, its user or its copied course does not exist, or the Masteriyo enrollment did not save.
	 */
	public static function migrate_single_enrollment( int $enrollment_post_id ): void {
		$enrolled_post = get_post( $enrollment_post_id );
		if ( ! $enrolled_post || 'tutor_enrolled' !== $enrolled_post->post_type ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception(
				sprintf( 'migrate_single_enrollment: tutor_enrolled post %d not found.', $enrollment_post_id )
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $enrolled_post->post_parent, 'course' );
		$user_id   = (int) $enrolled_post->post_author;
		$user      = get_userdata( $user_id );

		if ( ! $user ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception(
				sprintf( 'migrate_single_enrollment: user %d not found for enrollment %d.', $user_id, $enrollment_post_id )
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$email          = sanitize_email( $user->user_email );
		$user_course_id = static::update_tutor_enrolled_user_to_masteriyo_enrolled_user( $course_id, $email, $enrolled_post );

		if ( function_exists( 'tutor_utils' ) ) {
			$order_id    = get_post_meta( $enrollment_post_id, '_tutor_enrolled_by_order_id', true );
			$monetize_by = \tutor_utils()->get_option( 'monetize_by' );
			if ( 'wc' === $monetize_by && $order_id ) {
				static::sync_wc_order_with_masteriyo( (int) $order_id, $course_id );
			}
		}

		// Not the post entity: the target is a user-items row, not a post.
		MigrationMap::record( self::SLUG, 'enrollments', $enrollment_post_id, $user_course_id );
	}

	/**
	 * Copy a single order for EDD or Tutor native checkout to a Masteriyo mto-order.
	 *
	 * WC orders are handled inside migrate_single_enrollment() — this method is a no-op for WC.
	 * The EDD payment and the Tutor order row stay.
	 *
	 * @param int $order_id EDD payment post ID or Tutor native order ID.
	 * @throws \Exception If the order or its copied course cannot be resolved.
	 */
	public static function migrate_single_order( int $order_id ): void {
		global $wpdb;

		if ( ! function_exists( 'tutor_utils' ) ) {
			return;
		}

		$monetize_by = \tutor_utils()->get_option( 'monetize_by' );

		if ( 'edd' === $monetize_by ) {
			if ( ! function_exists( 'edd_get_payment' ) ) {
				return;
			}
			$payment = \edd_get_payment( $order_id );
			if ( ! $payment || ! $payment->ID ) {
				throw new \Exception( sprintf( 'migrate_single_order: EDD payment %d not found.', $order_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}

			// The courses step points the download at the copied course.
			$course_id = 0;
			foreach ( (array) $payment->cart_details as $item ) {
				$product_id = (int) ( $item['id'] ?? 0 );
				if ( $product_id ) {
					$course_id = (int) get_post_meta( $product_id, '_masteriyo_course_id', true );
					if ( $course_id ) {
						break;
					}
				}
			}

			if ( ! $course_id || PostType::COURSE !== get_post_type( $course_id ) ) {
				masteriyo_get_logger()->warning(
					sprintf( 'Order %d skipped: no copied course is linked to it. Make sure the Courses step is complete before Orders.', $order_id ),
					array( 'source' => 'migration-tool' )
				);
				MigrationMap::record( self::SLUG, MigrationMap::POST, $order_id, 0 );
				return;
			}

			$copy_id = static::insert_edd_order( $order_id, $course_id );
			if ( ! $copy_id ) {
				throw new \Exception( sprintf( 'migrate_single_order: could not copy EDD payment %d.', $order_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
			MigrationMap::record( self::SLUG, MigrationMap::POST, $order_id, $copy_id );
			return;
		}

		if ( 'tutor' === $monetize_by ) {
			$source_course_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT item_id FROM {$wpdb->prefix}tutor_order_items WHERE order_id = %d LIMIT 1",
					$order_id
				)
			);

			if ( ! $source_course_id ) {
				$source_course_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT p.post_parent
						 FROM {$wpdb->postmeta} pm
						 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
						 WHERE pm.meta_key = '_tutor_enrolled_by_order_id'
						   AND pm.meta_value = %s
						   AND p.post_type = 'tutor_enrolled'
						 LIMIT 1",
						(string) $order_id
					)
				);
			}

			if ( ! $source_course_id ) {
				// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new \Exception(
					sprintf( 'migrate_single_order: cannot resolve course_id for Tutor order %d.', $order_id )
				);
				// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}

			$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_course_id, 'course' );

			$copy_id = static::insert_tutor_native_order( $order_id, $course_id );
			if ( ! $copy_id ) {
				throw new \Exception( sprintf( 'migrate_single_order: could not copy Tutor order %d.', $order_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}
			MigrationMap::record( self::SLUG, 'orders', $order_id, $copy_id );
			return;
		}
	}

	/**
	 * Copy a single TutorLMS course rating comment to a new Masteriyo course_review.
	 *
	 * @param int $comment_id WP comment ID of the tutor_course_rating comment.
	 * @throws \Exception If the comment or its copied course does not exist.
	 */
	public static function migrate_single_review( int $comment_id ): void {
		$review = get_comment( $comment_id );
		if ( ! $review ) {
			throw new \Exception( sprintf( 'migrate_single_review: comment %d not found.', $comment_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $review->comment_post_ID, 'course' );

		$rating_raw    = get_comment_meta( $comment_id, 'tutor_rating', true );
		$rating        = ( '' !== $rating_raw && false !== $rating_raw ) ? $rating_raw : $review->comment_karma;
		$content       = $review->comment_content ?? '';
		$content_title = wp_trim_words( $content, 15, '...' );

		$copy_id = PostCloner::clone_comment(
			self::SLUG,
			$comment_id,
			array(
				'comment_post_ID'  => $course_id,
				'comment_approved' => in_array( $review->comment_approved ?? '', array( 'approved', '1', 1 ), true ) ? CommentStatus::APPROVE : CommentStatus::HOLD,
				'comment_type'     => CommentType::COURSE_REVIEW,
				'comment_agent'    => 'Masteriyo',
				'comment_karma'    => (int) $rating,
				'comment_parent'   => 0,
			)
		);

		update_comment_meta( $copy_id, '_title', $content_title );
		update_comment_meta( $copy_id, '_rating', (float) $rating );
		delete_comment_meta( $copy_id, 'tutor_rating' );
	}

	/**
	 * Copy a single tutor-announcements post to a new Masteriyo course announcement.
	 *
	 * @param int $post_id tutor-announcements post ID.
	 * @throws \Exception If the post or its copied course does not exist.
	 */
	public static function migrate_single_announcement( int $post_id ): void {
		$announcement = get_post( $post_id );
		if ( ! $announcement ) {
			throw new \Exception( sprintf( 'migrate_single_announcement: post %d not found.', $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $announcement->post_parent, 'course' );

		$copy_id = PostCloner::clone_post(
			self::SLUG,
			$post_id,
			array(
				'post_type'   => PostType::COURSEANNOUNCEMENT,
				'post_status' => in_array( $announcement->post_status, array( 'publish', 'draft' ), true )
					? $announcement->post_status
					: PostStatus::PUBLISH,
				'ping_status' => 'closed',
				'post_parent' => 0,
			)
		);

		update_post_meta( $copy_id, '_course_id', $course_id );
	}

	/**
	 * Copy a single TutorLMS Q&A thread (parent question + all replies) to new Masteriyo course_qa comments.
	 *
	 * @param int $comment_id Parent Q&A comment ID.
	 * @throws \Exception If the parent comment or its copied course does not exist.
	 */
	public static function migrate_single_qa( int $comment_id ): void {
		$question = get_comment( $comment_id );
		if ( ! $question ) {
			throw new \Exception( sprintf( 'migrate_single_qa: comment %d not found.', $comment_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $question->comment_post_ID, 'course' );
		$approved  = function( $comment ) {
			return in_array( $comment->comment_approved ?? '', array( 'approved', '1', 1 ), true ) ? CommentStatus::APPROVE : CommentStatus::HOLD;
		};

		$copy_id = PostCloner::clone_comment(
			self::SLUG,
			$comment_id,
			array(
				'comment_post_ID'  => $course_id,
				'comment_type'     => CommentType::COURSE_QA,
				'comment_approved' => $approved( $question ),
				'comment_agent'    => 'Masteriyo',
				'comment_parent'   => 0,
			)
		);

		foreach ( (array) \tutor_utils()->get_qa_answer_by_question( $comment_id ) as $qa ) {
			if ( (int) $qa->comment_ID === $comment_id ) {
				continue;
			}
			PostCloner::clone_comment(
				self::SLUG,
				(int) $qa->comment_ID,
				array(
					'comment_post_ID'  => $course_id,
					'comment_type'     => CommentType::COURSE_QA,
					'comment_approved' => $approved( $qa ),
					'comment_agent'    => 'Masteriyo',
					'comment_parent'   => $copy_id,
				)
			);
		}
	}

	/**
	 * Copy the lesson completions behind one Tutor enrollment onto the copied course.
	 *
	 * Idempotent, and the Tutor completion meta stays, so the step re-reads every
	 * enrollment on every run and picks up lessons completed since the last one.
	 *
	 * @param int $enrollment_post_id Post ID of the tutor_enrolled CPT.
	 * @throws \Exception If the enrollment post or its copied course does not exist.
	 */
	public static function migrate_single_progress( int $enrollment_post_id ): void {
		global $wpdb;
		$now            = gmdate( 'Y-m-d H:i:s' );
		$activities_tbl = $wpdb->prefix . 'masteriyo_user_activities';

		$enrolled_post = get_post( $enrollment_post_id );
		if ( ! $enrolled_post || 'tutor_enrolled' !== $enrolled_post->post_type ) {
			throw new \Exception( sprintf( 'migrate_single_progress: tutor_enrolled post %d not found.', $enrollment_post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$user_id   = (int) $enrolled_post->post_author;
		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $enrolled_post->post_parent, 'course' );

		$lesson_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				 WHERE p.post_type = %s
				   AND pm.meta_key = '_course_id'
				   AND pm.meta_value = %d",
				PostType::LESSON,
				$course_id
			)
		);
		$lesson_ids = array_map( 'intval', $lesson_ids );

		if ( empty( $lesson_ids ) ) {
			return;
		}

		$completions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT CAST(REPLACE(meta_key, '_tutor_completed_lesson_id_', '') AS UNSIGNED) AS lesson_id,
				        meta_value AS completed_ts
				 FROM {$wpdb->usermeta}
				 WHERE user_id = %d
				   AND meta_key LIKE %s
				   AND meta_value != ''",
				$user_id,
				$wpdb->esc_like( '_tutor_completed_lesson_id_' ) . '%'
			)
		);

		// Completions name Tutor's lesson IDs; the activities need the copies'.
		$copied_lessons    = MigrationMap::targets( self::SLUG, MigrationMap::POST, wp_list_pluck( $completions, 'lesson_id' ) );
		$lesson_ids_set    = array_flip( $lesson_ids );
		$valid_completions = array();
		foreach ( $completions as $c ) {
			$lid = $copied_lessons[ (int) $c->lesson_id ] ?? 0;
			if ( $lid && isset( $lesson_ids_set[ $lid ] ) ) {
				// Tutor stores a Unix timestamp; older data may hold a date string.
				$ts                  = is_numeric( $c->completed_ts ) ? (int) $c->completed_ts : (int) strtotime( (string) $c->completed_ts );
				$valid_completions[] = array(
					'lesson_id'    => $lid,
					'completed_at' => $ts > 0 ? gmdate( 'Y-m-d H:i:s', $ts ) : $now,
				);
			}
		}

		if ( empty( $valid_completions ) ) {
			return;
		}

		$progress_id = Helper::get_or_create_course_progress( $user_id, $course_id, $now );

		if ( ! $progress_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Could not create progress record for user %d in course %d.', $user_id, $course_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$existing_acts = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT item_id FROM {$activities_tbl}
				 WHERE user_id = %d AND activity_type = 'lesson' AND parent_id = %d",
				$user_id,
				$progress_id
			)
		);
		$existing_set = array();
		foreach ( $existing_acts as $a ) {
			$existing_set[ (int) $a->item_id ] = true;
		}

		foreach ( $valid_completions as $c ) {
			if ( isset( $existing_set[ $c['lesson_id'] ] ) ) {
				continue;
			}
			$wpdb->insert(
				$activities_tbl,
				array(
					'user_id'         => $user_id,
					'item_id'         => $c['lesson_id'],
					'activity_type'   => 'lesson',
					'activity_status' => 'completed',
					'parent_id'       => $progress_id,
					'created_at'      => $c['completed_at'],
					'modified_at'     => $now,
					'completed_at'    => $c['completed_at'],
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
			);
		}
	}

	/**
	 * Copy a single Tutor quiz attempt to masteriyo_quiz_attempts. The Tutor rows stay.
	 *
	 * Idempotent via (quiz_id, user_id, attempt_started_at) deduplication guard.
	 *
	 * @param int $attempt_id tutor_quiz_attempts.attempt_id.
	 * @throws \Exception If the attempt record, its copied course or quiz is missing, or the insert fails.
	 */
	public static function migrate_single_quiz_attempt( int $attempt_id ): void {
		global $wpdb;
		$now          = gmdate( 'Y-m-d H:i:s' );
		$attempts_tbl = $wpdb->prefix . 'masteriyo_quiz_attempts';

		$attempt = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}tutor_quiz_attempts WHERE attempt_id = %d",
				$attempt_id
			)
		);

		if ( ! $attempt ) {
			throw new \Exception( sprintf( 'migrate_single_quiz_attempt: attempt %d not found.', $attempt_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// Records a skip so the row is not fetched again.
		$skip = function() use ( $attempt_id ): void {
			MigrationMap::record( self::SLUG, 'quiz_attempts', $attempt_id, 0 );
		};

		if ( empty( $attempt->attempt_started_at ) ) {
			$skip();
			return;
		}

		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $attempt->course_id, 'course' );
		$quiz_id   = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $attempt->quiz_id, 'quiz' );

		$already_exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$attempts_tbl}
				 WHERE quiz_id = %d AND user_id = %s AND attempt_started_at = %s",
				$quiz_id,
				$attempt->user_id,
				$attempt->attempt_started_at
			)
		);

		if ( $already_exists ) {
			$skip();
			return;
		}

		$raw_answers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT aa.question_id, aa.given_answer, aa.achieved_mark, aa.is_correct,
				        q.question_title, q.question_type
				 FROM {$wpdb->prefix}tutor_quiz_attempt_answers aa
				 LEFT JOIN {$wpdb->prefix}tutor_quiz_questions q ON aa.question_id = q.question_id
				 WHERE aa.quiz_attempt_id = %d",
				$attempt_id
			)
		);

		$tutor_question_ids = array_unique( array_map( fn( $a ) => (int) $a->question_id, $raw_answers ) );
		$mto_question_map   = array();

		if ( ! empty( $tutor_question_ids ) ) {
			$q_ids_in = implode( ',', array_fill( 0, count( $tutor_question_ids ), '%d' ) );
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$q_results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta}
					 WHERE meta_key = '_tutor_question_id' AND meta_value IN ({$q_ids_in})",
					$tutor_question_ids
				)
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			foreach ( $q_results as $r ) {
				$mto_question_map[ (int) $r->meta_value ] = (int) $r->post_id;
			}
		}

		$total_correct   = 0;
		$total_incorrect = 0;
		$answers_php     = array();

		foreach ( $raw_answers as $raw ) {
			$raw->is_correct ? $total_correct++ : $total_incorrect++;
			$answers_php[] = array(
				'id'            => $mto_question_map[ (int) $raw->question_id ] ?? 0,
				'name'          => $raw->question_title ?? '',
				'type'          => $raw->question_type ?? '',
				'given_answer'  => $raw->given_answer,
				'achieved_mark' => (float) $raw->achieved_mark,
				'is_correct'    => (bool) $raw->is_correct,
			);
		}

		$status_map     = array(
			'attempt_started' => 'attempt_started',
			'attempt_ended'   => 'attempt_ended',
			'review_required' => 'attempt_ended',
			'pass'            => 'pass',
			'fail'            => 'fail',
		);
		$attempt_status = $status_map[ $attempt->attempt_status ] ?? 'attempt_ended';

		$total_attempts_seq = 1 + (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$attempts_tbl} WHERE quiz_id = %d AND user_id = %d",
				$quiz_id,
				$attempt->user_id
			)
		);

		$inserted = $wpdb->insert(
			$attempts_tbl,
			array(
				'course_id'                => $course_id,
				'quiz_id'                  => $quiz_id,
				'user_id'                  => (int) $attempt->user_id,
				'total_questions'          => (int) $attempt->total_questions,
				'total_answered_questions' => (int) $attempt->total_answered_questions,
				'total_marks'              => (float) $attempt->total_marks,
				'total_attempts'           => $total_attempts_seq,
				'total_correct_answers'    => $total_correct,
				'total_incorrect_answers'  => $total_incorrect,
				'earned_marks'             => (float) $attempt->earned_marks,
				'answers'                  => maybe_serialize( $answers_php ),
				'attempt_status'           => $attempt_status,
				'attempt_started_at'       => $attempt->attempt_started_at,
				'attempt_ended_at'         => $attempt->attempt_ended_at,
			),
			array( '%d', '%d', '%s', '%d', '%d', '%f', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception(
				sprintf( 'migrate_single_quiz_attempt: failed to insert attempt %d.', $attempt_id )
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		MigrationMap::record( self::SLUG, 'quiz_attempts', $attempt_id, (int) $wpdb->insert_id );

		if ( 'pass' === $attempt_status ) {
			$activities_tbl = $wpdb->prefix . 'masteriyo_user_activities';
			$progress_id    = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT id FROM {$activities_tbl}
					 WHERE item_id = %d AND user_id = %d AND activity_type = 'course_progress'
					 LIMIT 1",
					$course_id,
					(int) $attempt->user_id
				)
			);

			if ( $progress_id ) {
				$already = $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT id FROM {$activities_tbl}
						 WHERE item_id = %d AND user_id = %d AND activity_type = 'quiz' AND parent_id = %d
						 LIMIT 1",
						$quiz_id,
						(int) $attempt->user_id,
						$progress_id
					)
				);
				if ( ! $already ) {
					$ended = $attempt->attempt_ended_at ?? $now;
					$wpdb->insert(
						$activities_tbl,
						array(
							'user_id'         => (int) $attempt->user_id,
							'item_id'         => $quiz_id,
							'activity_type'   => 'quiz',
							'activity_status' => 'completed',
							'parent_id'       => $progress_id,
							'created_at'      => $ended,
							'modified_at'     => $ended,
							'completed_at'    => $ended,
						),
						array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
					);
				}
			}
		}
	}

	/**
	 * Copy a single tutor-google-meet post to a new mto-google-meet.
	 *
	 * Maps the meta keys that differ between the TutorLMS and Masteriyo Google Meet schemas.
	 *
	 * @param int $post_id tutor-google-meet post ID.
	 * @throws \Exception If the post or its copied course does not exist.
	 */
	public static function migrate_single_google_meet( int $post_id ): void {
		if ( ! get_post( $post_id ) ) {
			throw new \Exception( sprintf( 'migrate_single_google_meet: post %d not found.', $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) get_post_meta( $post_id, '_tutor_google_meet_course_id', true ), 'course' );
		// The parent is the Tutor topic or course; the copy sits under its copy.
		$parent_id = MigrationMap::target( self::SLUG, MigrationMap::POST, (int) get_post_field( 'post_parent', $post_id ) );
		$copy_id   = PostCloner::clone_post(
			self::SLUG,
			$post_id,
			array(
				'post_type'   => PostType::GOOGLEMEET,
				'post_parent' => $parent_id ? $parent_id : $course_id,
			)
		);

		// Read the TutorLMS meta keys, which the copy carries.
		$meet_url      = get_post_meta( $copy_id, '_tutor_google_meet_meeting_url', true );
		$starts_at     = get_post_meta( $copy_id, '_tutor_google_meet_start_datetime', true );
		$ends_at       = get_post_meta( $copy_id, '_tutor_google_meet_end_datetime', true );
		$author_id_val = get_post_meta( $copy_id, '_tutor_google_meet_instructor_id', true );
		$event_details = get_post_meta( $copy_id, '_tutor_google_meet_event_details', true );

		// Write Masteriyo meta keys.
		update_post_meta( $copy_id, 'meet_url', $meet_url );
		update_post_meta( $copy_id, 'starts_at', $starts_at );
		update_post_meta( $copy_id, 'ends_at', $ends_at );
		update_post_meta( $copy_id, 'course_id', $course_id );
		update_post_meta( $copy_id, 'author_id', (int) $author_id_val );

		// Parse event_details JSON → individual Masteriyo keys.
		$event = is_string( $event_details ) && '' !== $event_details
			? json_decode( $event_details, true )
			: array();
		$event = is_array( $event ) ? $event : array();

		if ( ! empty( $event['id'] ) ) {
			update_post_meta( $copy_id, 'meeting_id', sanitize_text_field( $event['id'] ) );
		}
		if ( ! empty( $event['timezone'] ) ) {
			update_post_meta( $copy_id, 'time_zone', sanitize_text_field( $event['timezone'] ) );
		}
		// 'calender_url' is the intentional Masteriyo spelling.
		$calendar_url = $event['hangoutLink'] ?? $event['htmlLink'] ?? '';
		if ( $calendar_url ) {
			update_post_meta( $copy_id, 'calender_url', esc_url_raw( $calendar_url ) );
		}

		// The copy needs none of the TutorLMS keys.
		$tutor_keys = array(
			'_tutor_google_meet_meeting_url',
			'_tutor_google_meet_start_datetime',
			'_tutor_google_meet_end_datetime',
			'_tutor_google_meet_course_id',
			'_tutor_google_meet_instructor_id',
			'_tutor_google_meet_event_details',
		);
		foreach ( $tutor_keys as $key ) {
			delete_post_meta( $copy_id, $key );
		}
	}


	/**
	 * Links a WooCommerce order to Masteriyo by setting _masteriyo_course_id on
	 * the matching order item. No mto-order post is created — WC orders are the
	 * source of truth for the WC integration, exactly as in the live WC addon.
	 *
	 * @param int $order_id WC order ID.
	 * @param int $course_id Masteriyo course post ID.
	 */
	public static function sync_wc_order_with_masteriyo( $order_id, $course_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$wc_order = \wc_get_order( $order_id );
		if ( ! $wc_order ) {
			return;
		}

		$product_id = (int) get_post_meta( $course_id, '_wc_product_id', true );

		foreach ( $wc_order->get_items() as $order_item ) {
			if ( ! is_a( $order_item, 'WC_Order_Item_Product' ) ) {
				continue;
			}
			// Tag the order item if it matches this course's WC product.
			if ( $product_id && (int) $order_item->get_product_id() === $product_id ) {
				$order_item->update_meta_data( '_masteriyo_course_id', $course_id );
				$order_item->save_meta_data();
			}
		}

		$buyer_user_id = (int) $wc_order->get_customer_id();
		if ( $buyer_user_id ) {
			$user_course = masteriyo_get_user_course_by_user_and_course( $buyer_user_id, $course_id );
			if ( $user_course ) {
				$user_course->update_meta_data( '_wc_order_id', $order_id );
				$user_course->save_meta_data();
			}
		}
	}

	/**
	 * Creates a Masteriyo native order (mto-order) from an EDD payment record.
	 *
	 * @param int $payment_id EDD payment post ID.
	 * @param int $course_id  Masteriyo course post ID.
	 * @return int The new order ID, or 0 when none was created.
	 */
	public static function insert_edd_order( $payment_id, $course_id ) {
		if ( ! function_exists( 'edd_get_payment' ) ) {
			return 0;
		}

		$payment = edd_get_payment( $payment_id );
		if ( ! $payment || ! $payment->ID ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Order %d skipped: EDD payment record not found.', $payment_id ),
				array( 'source' => 'migration-tool' )
			);
			return 0;
		}

		$status_map = array(
			'complete'    => OrderStatus::COMPLETED,
			'publish'     => OrderStatus::COMPLETED,
			'pending'     => OrderStatus::PENDING,
			'processing'  => OrderStatus::PENDING,
			'refunded'    => OrderStatus::REFUNDED,
			'failed'      => OrderStatus::FAILED,
			'cancelled'   => OrderStatus::CANCELLED,
			'revoked'     => OrderStatus::CANCELLED,
			'preapproval' => OrderStatus::PENDING,
			'abandoned'   => OrderStatus::CANCELLED,
		);

		$masteriyo_status = $status_map[ $payment->status ] ?? OrderStatus::PENDING;
		$payment_date     = $payment->date ? $payment->date : time();
		$user_info        = is_array( $payment->user_info ) ? $payment->user_info : array();
		$edd_currency     = function_exists( 'edd_get_currency' ) ? edd_get_currency() : get_option( 'woocommerce_currency', 'USD' );

		/** @var \Masteriyo\Models\Order\Order $order */
		$order = masteriyo( 'order' );
		$order->set_status( $masteriyo_status );
		$order->set_customer_id( absint( $payment->user_id ) );
		$order->set_total( (float) $payment->total );
		$order->set_currency( ! empty( $payment->currency ) ? $payment->currency : $edd_currency );
		$order->set_transaction_id( $payment->transaction_id ?? '' );
		$order->set_payment_method( $payment->gateway ?? '' );
		$order->set_payment_method_title( $payment->gateway ?? '' );
		$order->set_billing_email( $payment->email ?? '' );
		$order->set_billing_first_name( $user_info['first_name'] ?? '' );
		$order->set_billing_last_name( $user_info['last_name'] ?? '' );
		$order->set_date_created( $payment_date );
		$order->set_created_via( 'migration' );

		if ( in_array( $payment->status, array( 'complete', 'publish' ), true ) ) {
			// EDD $payment->date is the order creation date; completed_date is when payment actually succeeded.
			$paid_date = ! empty( $payment->completed_date ) ? $payment->completed_date : $payment_date;
			$order->set_date_paid( $paid_date );
		}

		masteriyo( 'order.store' )->create( $order );

		if ( ! $order->get_id() ) {
			masteriyo_get_logger()->error(
				sprintf( 'Could not create order for EDD payment %d.', $payment_id ),
				array( 'source' => 'migration-tool' )
			);
			return 0;
		}

		$order_item = masteriyo( 'order-item.course' );
		$order_item->set_order_id( $order->get_id() );
		$order_item->set_course_id( $course_id );
		$order_item->set_name( get_the_title( $course_id ) );
		$order_item->set_quantity( 1 );
		// Use pre-discount subtotal; $payment->total is the post-discount amount.
		$order_item->set_subtotal( isset( $payment->subtotal ) && $payment->subtotal > 0 ? (float) $payment->subtotal : (float) $payment->total );
		$order_item->set_total( (float) $payment->total );

		masteriyo( 'order-item.course.store' )->create( $order_item );

		// Link the order ID to the enrollment so payment history appears on the student account page.
		$user_course = masteriyo_get_user_course_by_user_and_course( absint( $payment->user_id ), $course_id );
		if ( $user_course ) {
			$user_course->set_order_id( $order->get_id() );
			$user_course->save();
		}

		return (int) $order->get_id();
	}

	/**
	 * Creates a Masteriyo native order (mto-order) from a Tutor native checkout record.
	 *
	 * @param int $order_id  Tutor order ID (tutor_orders.id).
	 * @param int $course_id Masteriyo course post ID.
	 * @return int The new order ID, or 0 when none was created.
	 */
	public static function insert_tutor_native_order( $order_id, $course_id ) {
		global $wpdb;

		$customers_table = $wpdb->prefix . 'tutor_customers';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$customers_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $customers_table ) ) === $customers_table;

		if ( $customers_table_exists ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$tutor_order = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT o.*, c.billing_first_name, c.billing_last_name, c.billing_email,
					        c.billing_phone, c.billing_address, c.billing_city, c.billing_state,
					        c.billing_country, c.billing_zip_code
					 FROM {$wpdb->prefix}tutor_orders o
					 LEFT JOIN {$customers_table} c ON c.user_id = o.user_id
					 WHERE o.id = %d",
					$order_id
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$tutor_order = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT o.* FROM {$wpdb->prefix}tutor_orders o WHERE o.id = %d",
					$order_id
				)
			);
		}

		if ( ! $tutor_order ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Order %d skipped: Tutor order record not found.', $order_id ),
				array( 'source' => 'migration-tool' )
			);
			return 0;
		}

		// Map Tutor order/payment status to Masteriyo OrderStatus.
		$masteriyo_status = OrderStatus::PENDING;
		if ( 'paid' === $tutor_order->payment_status || 'completed' === $tutor_order->order_status ) {
			$masteriyo_status = OrderStatus::COMPLETED;
		} elseif ( in_array( $tutor_order->payment_status, array( 'refunded', 'partially-refunded' ), true ) ) {
			$masteriyo_status = OrderStatus::REFUNDED;
		} elseif ( 'failed' === $tutor_order->payment_status ) {
			$masteriyo_status = OrderStatus::FAILED;
		} elseif ( in_array( $tutor_order->order_status, array( 'cancelled', 'trash' ), true ) ) {
			$masteriyo_status = OrderStatus::CANCELLED;
		}

		$order_date   = $tutor_order->created_at_gmt ? masteriyo_string_to_timestamp( $tutor_order->created_at_gmt ) : time();
		$total        = isset( $tutor_order->total_price ) ? (float) $tutor_order->total_price : 0.0;
		$tutor_option = get_option( 'tutor_option' );
		// Prefer the per-order currency column; fall back to the site-wide setting.
		$currency = ! empty( $tutor_order->currency )
			? $tutor_order->currency
			: ( ( is_array( $tutor_option ) && ! empty( $tutor_option['tutor_currency'] ) )
				? $tutor_option['tutor_currency']
				: get_option( 'woocommerce_currency', 'USD' ) );

		/** @var \Masteriyo\Models\Order\Order $order */
		$order = masteriyo( 'order' );
		$order->set_status( $masteriyo_status );
		$order->set_customer_id( absint( $tutor_order->user_id ) );
		$order->set_total( $total );
		$order->set_currency( $currency );
		$order->set_transaction_id( $tutor_order->transaction_id ?? '' );
		$order->set_payment_method( $tutor_order->payment_method ?? '' );
		$order->set_payment_method_title( $tutor_order->payment_method ?? '' );
		$order->set_billing_email( $tutor_order->billing_email ?? '' );
		$order->set_billing_first_name( $tutor_order->billing_first_name ?? '' );
		$order->set_billing_last_name( $tutor_order->billing_last_name ?? '' );
		$order->set_billing_phone( $tutor_order->billing_phone ?? '' );
		$order->set_billing_address_1( $tutor_order->billing_address ?? '' );
		$order->set_billing_city( $tutor_order->billing_city ?? '' );
		$order->set_billing_state( $tutor_order->billing_state ?? '' );
		$order->set_billing_country( $tutor_order->billing_country ?? '' );
		$order->set_billing_postcode( $tutor_order->billing_zip_code ?? '' );
		$order->set_date_created( $order_date );
		$order->set_created_via( 'migration' );
		$order->set_customer_note( sanitize_textarea_field( $tutor_order->note ?? '' ) );

		if ( 'paid' === $tutor_order->payment_status ) {
			$order->set_date_paid( $order_date );
		}

		// Tax and discount via model setters before saving.
		if ( ! empty( $tutor_order->tax_amount ) ) {
			$order->set_tax_total( (float) $tutor_order->tax_amount );
		}
		$discount_amount = ! empty( $tutor_order->discount_amount ) ? (float) $tutor_order->discount_amount : 0.0;
		if ( $discount_amount > 0 ) {
			$order->set_discount_total( $discount_amount );
		}

		masteriyo( 'order.store' )->create( $order );

		if ( ! $order->get_id() ) {
			masteriyo_get_logger()->error(
				sprintf( 'Could not create order for Tutor order %d.', $order_id ),
				array( 'source' => 'migration-tool' )
			);
			return 0;
		}

		if ( ! empty( $tutor_order->tax_type ) ) {
			update_post_meta( $order->get_id(), '_tax_type', sanitize_text_field( $tutor_order->tax_type ) );
		}

		// Coupon line item. Coupons are pro's, so free migrates the order without the
		// discount line rather than failing the migration. The FAQ path asks the same
		// question the same way.
		if ( ! empty( $tutor_order->coupon_code ) && masteriyo_service_provider_exists( 'order-item.coupon' ) ) {
			$coupon_item = masteriyo( 'order-item.coupon' );
			$coupon_item->set_order_id( $order->get_id() );
			$coupon_item->set_code( sanitize_text_field( $tutor_order->coupon_code ) );
			$coupon_item->set_discount( $discount_amount );
			masteriyo( 'order-item.coupon.store' )->create( $coupon_item );
		}

		$order_item = masteriyo( 'order-item.course' );
		$order_item->set_order_id( $order->get_id() );
		$order_item->set_course_id( $course_id );
		$order_item->set_name( get_the_title( $course_id ) );
		$order_item->set_quantity( 1 );
		$order_item->set_subtotal( isset( $tutor_order->sub_total ) ? (float) $tutor_order->sub_total : $total );
		$order_item->set_total( $total );

		masteriyo( 'order-item.course.store' )->create( $order_item );

		// Link the order ID to the enrollment so payment history appears on the student account page.
		$user_course = masteriyo_get_user_course_by_user_and_course( absint( $tutor_order->user_id ), $course_id );
		if ( $user_course ) {
			$user_course->set_order_id( $order->get_id() );
			$user_course->save();
		}

		return (int) $order->get_id();
	}


	/**
	 * Copy all TutorLMS wishlist entries for a single user to Masteriyo wishlist items.
	 *
	 * Reads the user's _tutor_course_wishlist usermeta rows (Tutor adds one row per course;
	 * older sites may hold a serialized array) and creates an mto-wishlist-item post for
	 * each wishlisted course whose copy exists and has no item yet. The usermeta stays.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return void
	 */
	private static function migrate_single_wishlist_user( int $user_id ): void {
		global $wpdb;

		$wishlist = array();
		foreach ( (array) get_user_meta( $user_id, '_tutor_course_wishlist', false ) as $row ) {
			foreach ( (array) maybe_unserialize( $row ) as $course_id ) {
				$wishlist[] = (int) $course_id;
			}
		}
		$wishlist = array_values( array_unique( array_filter( $wishlist ) ) );
		if ( empty( $wishlist ) ) {
			return;
		}

		$already_migrated = array_map(
			'intval',
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_parent FROM {$wpdb->posts}
					 WHERE post_type = 'mto-wishlist-item' AND post_author = %d",
					$user_id
				)
			)
		);

		$copied_courses = MigrationMap::targets( self::SLUG, MigrationMap::POST, $wishlist );

		foreach ( array_map( 'intval', $wishlist ) as $source_course_id ) {
			$course_id = $copied_courses[ $source_course_id ] ?? 0;
			if ( ! $course_id || in_array( $course_id, $already_migrated, true ) ) {
				continue;
			}

			$mto_course = masteriyo_get_course( $course_id );

			$item = masteriyo( 'wishlist-item' );
			$item->set_course_id( $course_id );
			$item->set_author_id( $user_id );

			if ( $mto_course ) {
				$item->set_course_title( $mto_course->get_name() );
				$item->set_course_price( (float) $mto_course->get_regular_price() );

				$cat_ids = $mto_course->get_category_ids();
				if ( ! empty( $cat_ids ) ) {
					$item->set_course_category_ids( $cat_ids );
				}

				$difficulty_id = $mto_course->get_difficulty_id();
				if ( $difficulty_id ) {
					$term = get_term( $difficulty_id );
					if ( $term && ! is_wp_error( $term ) ) {
						$item->set_course_difficulty( $term->slug );
					}
				}
			}

			$item->save();

			if ( ! $item->get_id() ) {
				masteriyo_get_logger()->warning(
					sprintf( 'Could not save wishlist item for user %d, course %d.', $user_id, $course_id ),
					array( 'source' => 'migration-tool' )
				);
			}
		}
	}

	/**
	 * Copy a Tutor LMS quiz post to a new Masteriyo quiz (mto-quiz) with its questions.
	 *
	 * @param \WP_Post $item       Tutor LMS quiz post object (the source).
	 * @param int      $section_id Masteriyo section post ID (the copy).
	 * @param int      $course_id  Masteriyo course post ID (the copy).
	 * @return void
	 */
	public static function update_tutor_course_quiz_to_masteriyo( $item, $section_id, $course_id ) {
		if ( 'tutor_quiz' !== $item->post_type ) {
			return;
		}

		$quiz_id = PostCloner::clone_post(
			self::SLUG,
			$item->ID,
			array(
				'post_type'   => PostType::QUIZ,
				'post_parent' => $section_id,
			)
		);

		// Questions live in Tutor's own tables, keyed by the source quiz.
		$questions   = \tutor_utils()->get_questions_by_quiz( $item->ID );
		$total_marks = 0;
		foreach ( (array) $questions as $question ) {
			static::process_question_migration_from_tutor( $question, $quiz_id, $course_id );
			$total_marks += (int) $question->question_mark;
		}

		$mto_quiz = masteriyo_get_quiz( $quiz_id );
		if ( ! $mto_quiz ) {
			masteriyo_get_logger()->error(
				sprintf( 'Could not load quiz %d after copying — it may not have been saved correctly.', $quiz_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		// From the source: the copy loses this meta below, and a reused copy has none.
		$quiz_options = get_post_meta( $item->ID, 'tutor_quiz_option', true );
		$quiz_options = is_array( $quiz_options ) ? $quiz_options : array();

		$pass_pct  = (float) ( $quiz_options['passing_grade'] ?? 0 );
		$pass_mark = $pass_pct > 0 ? round( ( $pass_pct / 100 ) * $total_marks, 2 ) : 0.0;

		$duration_secs = static::quiz_duration_to_seconds( $quiz_options['time_limit'] ?? array() );

		$mto_quiz->set_course_id( $course_id );
		$mto_quiz->set_parent_id( $section_id );
		$mto_quiz->set_full_mark( (float) $total_marks );
		$mto_quiz->set_pass_mark( $pass_mark );
		$mto_quiz->set_duration( $duration_secs );
		$mto_quiz->set_questions_display_per_page( (int) ( $quiz_options['max_questions_for_answer'] ?? 0 ) );
		$mto_quiz->set_attempts_allowed( (int) ( $quiz_options['attempts_allowed'] ?? 0 ) );

		if ( method_exists( $mto_quiz, 'set_pass_is_required' ) ) {
			$mto_quiz->set_pass_is_required( ! empty( $quiz_options['pass_is_required'] ) );
		}

		// Pass mark type via model setter.
		$mto_quiz->set_pass_mark_type( $quiz_options['passing_grade_by'] ?? 'percentage' );

		// Randomize questions via model setter.
		$mto_quiz->set_randomize( ! empty( $quiz_options['randomize_question'] ) );

		// Feedback mode → reveal_mode + show_details.
		// TutorLMS modes: 'reveal_mode' (per-question reveal), 'retry' (no reveal), 'default' (show at end).
		$feedback_mode = $quiz_options['feedback_mode'] ?? 'default';
		$mto_quiz->set_reveal_mode( 'reveal_mode' === $feedback_mode );
		$mto_quiz->set_show_details( 'default' === $feedback_mode || '' === $feedback_mode );

		$mto_quiz->save();

		update_post_meta( $quiz_id, '_mto_quiz_options', $quiz_options );
		delete_post_meta( $quiz_id, 'tutor_quiz_option' );

		/**
		 * Fires after a TutorLMS curriculum item has been copied.
		 *
		 * Carries the per-item schedules the shared migration has no home for.
		 *
		 * @param int $item_id   Post ID of the copied lesson/quiz.
		 * @param int $course_id Course post ID (the copy).
		 */
		do_action( 'masteriyo_migration_tool_tutorlms_item_migrated', $quiz_id, $course_id );
	}

	/**
	 * Copy a Tutor LMS lesson post to a new Masteriyo lesson (mto-lesson).
	 *
	 * Sets enable_preview via model setter and migrates content drip schedule.
	 *
	 * @param \WP_Post $item       Tutor LMS lesson post object (the source).
	 * @param int      $section_id Masteriyo section post ID (the copy).
	 * @param int      $course_id  Masteriyo course post ID (the copy).
	 * @return void
	 */
	public static function update_tutor_lesson_to_masteriyo( $item, $section_id, $course_id ) {
		if ( 'lesson' !== $item->post_type ) {
			return;
		}

		$lesson_id = PostCloner::clone_post(
			self::SLUG,
			$item->ID,
			array(
				'post_type'   => PostType::LESSON,
				'post_parent' => $section_id,
			)
		);

		$mto_lesson = masteriyo_get_lesson( $lesson_id );
		if ( ! $mto_lesson ) {
			masteriyo_get_logger()->error(
				sprintf( 'Could not load lesson %d after copying — it may not have been saved correctly.', $lesson_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$mto_lesson->set_course_id( $course_id );
		$mto_lesson->set_parent_id( $section_id );

		// Featured image.
		$thumb_id = (int) get_post_thumbnail_id( $lesson_id );
		if ( $thumb_id ) {
			$mto_lesson->set_featured_image( $thumb_id );
		}

		$video = static::map_video_meta( $lesson_id );
		if ( ! empty( $video ) ) {
			$mto_lesson->set_video_source( $video['source'] );
			// Lesson::get_video_source_id() reads video_source_url as absint(), so the attachment ID must be stored as the URL string for self-hosted videos.
			if ( 'self-hosted' === $video['source'] && ! empty( $video['id'] ) ) {
				$mto_lesson->set_video_source_url( (string) $video['id'] );
			} else {
				$mto_lesson->set_video_source_url( $video['url'] );
			}
		}

		// Attachments — tutor stores in _tutor_attachments; we write _download_materials.
		$attachments = \tutor_utils()->get_attachments( $item->ID );
		if ( ! empty( $attachments ) ) {
			$mto_lesson->set_download_materials( wp_list_pluck( $attachments, 'post_id' ) );
		}

		// Lesson preview — set via model setter (Lesson model has set_enable_preview).
		$is_preview = get_post_meta( $lesson_id, '_is_preview', true );
		if ( '1' === $is_preview || 'yes' === $is_preview ) {
			$mto_lesson->set_enable_preview( true );
		}

		$mto_lesson->save();

		/** This action is documented in addons/migration-tool/LMS/TutorLMS.php */
		do_action( 'masteriyo_migration_tool_tutorlms_item_migrated', $lesson_id, $course_id );
	}

	/**
	 * Enroll a Tutor student into the copied Masteriyo course.
	 *
	 * The student gains the Masteriyo role and keeps the Tutor one.
	 *
	 * @since 1.13.0
	 *
	 * @param int    $course_id     Masteriyo course post ID (the copy).
	 * @param string $email         Enrolled user email.
	 * @param object $enrolled_user Enrollment post object (tutor_enrolled CPT).
	 * @return int Masteriyo user-course ID.
	 * @throws \Exception If no user has the email or the enrollment cannot be saved.
	 */
	public static function update_tutor_enrolled_user_to_masteriyo_enrolled_user( $course_id, $email, $enrolled_user ) {
		global $wpdb;

		$user_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->users} WHERE user_email = %s",
				$email
			)
		);

		if ( ! $user_row || empty( $user_row->ID ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'Enrollment %d: user with email "%s" not found.', $enrolled_user->ID, $email ) );
		}

		$wp_user = new \WP_User( $user_row->ID );

		if ( $wp_user->ID ) {
			$masteriyo_roles = array( Roles::ADMIN, Roles::MANAGER, Roles::INSTRUCTOR, Roles::STUDENT );
			if ( empty( array_intersect( $masteriyo_roles, (array) $wp_user->roles ) ) ) {
				$wp_user->add_role( Roles::STUDENT );
			}
		}

		$status_map = array(
			'completed' => UserCourseStatus::ACTIVE,
			'cancelled' => UserCourseStatus::INACTIVE,
		);
		$mto_status = $status_map[ $enrolled_user->post_status ] ?? UserCourseStatus::INACTIVE;

		$user_course = masteriyo( 'user-course' );
		$user_course->set_course_id( $course_id );
		$user_course->set_user_id( $wp_user->ID );
		$user_course->set_status( $mto_status );
		$user_course->set_date_start( gmdate( 'Y-m-d H:i:s', strtotime( $enrolled_user->post_date ) ) );
		$user_course->save();

		$inserted_id = $user_course->get_id();

		// UserCourseRepository::create() silently skips duplicates and leaves get_id() = 0.
		if ( ! $inserted_id ) {
			$existing    = masteriyo_get_user_course_by_user_and_course( $wp_user->ID, $course_id );
			$inserted_id = $existing ? $existing->get_id() : 0;
		}

		if ( ! $inserted_id ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'Enrollment %d: could not save the Masteriyo enrollment for user %d on course %d.', $enrolled_user->ID, $wp_user->ID, $course_id ) );
		}

		return (int) $inserted_id;
	}



	/**
	 * Write all Masteriyo course meta from TutorLMS post meta via the Course model layer.
	 *
	 * Additionally sets WC subscription → RECURRING access mode, writes prerequisites
	 * directly to _prerequisites_courses, and sets content drip type on the course.
	 *
	 * @param int $course_id Course post ID (the mto-course copy, carrying Tutor's meta).
	 * @return void
	 */
	public static function update_masteriyo_course_from_tutor( $course_id ) {
		$mto_course = masteriyo_get_course( $course_id );
		if ( ! $mto_course ) {
			masteriyo_get_logger()->error(
				sprintf( 'Could not load course %d after migration — it may not have been saved correctly.', $course_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		if ( ! function_exists( 'tutor_utils' ) ) {
			return; // TutorLMS inactive — course copied but pricing/settings skipped.
		}

		// --- Pricing ---
		$regular_price = '';
		$sale_price    = '';
		$product_id    = \tutor_utils()->get_course_product_id( $course_id );
		$monetize_by   = \tutor_utils()->get_option( 'monetize_by' );
		$wc_product    = null;

		if ( 'wc' === $monetize_by && function_exists( 'wc_get_product' ) ) {
			$wc_product = \wc_get_product( $product_id );
			if ( $wc_product ) {
				$regular_price = \wc_get_price_to_display( $wc_product, array( 'price' => $wc_product->get_regular_price() ) );
				$sale_price    = \wc_get_price_to_display( $wc_product, array( 'price' => $wc_product->get_sale_price() ) );

				// The product keeps its type while Tutor still sells it; Masteriyo's
				// WooCommerce integration finds the course through _masteriyo_course_id
				// and completes its paid orders as it does a course product's.
				update_post_meta( $course_id, '_wc_product_id', $product_id );
				update_post_meta( $product_id, '_masteriyo_course_id', $course_id );
			}
		} elseif ( 'edd' === $monetize_by && \tutor_utils()->has_edd() ) {
			if ( function_exists( 'edd_has_variable_prices' ) && \edd_has_variable_prices( $product_id ) ) {
				$prices        = \edd_get_variable_prices( $product_id );
				$amounts       = wp_list_pluck( $prices, 'amount' );
				$regular_price = (string) max( $amounts );
				$sale_price    = (string) min( $amounts );
			} else {
				$regular_price = (string) \edd_get_download_price( $product_id );
			}
		} else {
			// Tutor native checkout — prices stored without underscore prefix.
			$regular_price = (string) get_post_meta( $course_id, 'tutor_course_price', true );
			$sale_price    = (string) get_post_meta( $course_id, 'tutor_course_sale_price', true );
		}

		$mto_course->set_regular_price( '' !== $regular_price ? $regular_price : '0' );
		$mto_course->set_sale_price( '' !== $sale_price ? $sale_price : '0' );

		// --- Access mode & price type ---
		$public_course = get_post_meta( $course_id, '_tutor_is_public_course', true ) === 'yes';
		$purchasable   = \tutor_utils()->is_course_purchasable( $course_id );

		if ( $public_course ) {
			$mto_course->set_price_type( CoursePriceType::FREE );
			$mto_course->set_access_mode( CourseAccessMode::OPEN );
		} elseif ( $purchasable ) {
			$mto_course->set_price_type( CoursePriceType::PAID );
			$mto_course->set_access_mode( CourseAccessMode::ONE_TIME );
		} else {
			$mto_course->set_price_type( CoursePriceType::FREE );
			$mto_course->set_access_mode( CourseAccessMode::NEED_REGISTRATION );
		}

		// WC subscription product → recurring access mode.
		if ( 'wc' === $monetize_by && $wc_product ) {
			if ( $wc_product->is_type( 'subscription' ) || $wc_product->is_type( 'variable-subscription' ) ) {
				$mto_course->set_access_mode( CourseAccessMode::RECURRING );
			}
		}

		// --- Course settings ---
		$course_maximum_student = \tutor_utils()->get_course_settings( $course_id, 'maximum_students', 0 );
		$mto_course->set_enrollment_limit( (int) $course_maximum_student );

		// TutorLMS allows HTML in the course benefits field; sanitize before writing.
		$highlights = wp_kses_post( (string) get_post_meta( $course_id, '_tutor_course_benefits', true ) );
		$mto_course->set_highlights( $highlights );

		$duration_raw = maybe_unserialize( get_post_meta( $course_id, '_course_duration', true ) );
		if ( is_array( $duration_raw ) && isset( $duration_raw['hours'] ) ) {
			$duration = ( (int) ( $duration_raw['hours'] ?? 0 ) * 60 ) + (int) ( $duration_raw['minutes'] ?? 0 );
		} else {
			$duration = static::convert_time_limit_to_minutes( $duration_raw );
		}
		$mto_course->set_duration( $duration );

		$course_settings = maybe_unserialize( get_post_meta( $course_id, '_tutor_course_settings', true ) );
		$retake_enabled  = ! empty( $course_settings['enable_course_retake'] );
		$mto_course->set_enable_course_retake( $retake_enabled );

		$thumb_id = (int) get_post_thumbnail_id( $course_id );
		if ( $thumb_id ) {
			$mto_course->set_featured_image( $thumb_id );
		}

		$mto_course->set_show_curriculum( true );
		$mto_course->set_reviews_allowed( true );
		$mto_course->set_review_after_course_completion( true );

		// --- Difficulty ---
		$course_level  = (string) get_post_meta( $course_id, '_tutor_course_level', true );
		$difficulty_id = static::resolve_course_difficulty_term( $course_level );
		$mto_course->set_difficulty_id( $difficulty_id );

		// --- Taxonomy: categories and tags ---
		$category_ids = static::migrate_course_categories_from_tutor_to_masteriyo( $course_id );
		$mto_course->set_category_ids( $category_ids );

		$tag_ids = static::migrate_course_tags_from_tutor_to_masteriyo( $course_id );
		$mto_course->set_tag_ids( $tag_ids );

		$mto_course->save();

		// --- Fields with no model setter — write directly after save() ---

		// Q&A enabled flag.
		$enable_qa = get_post_meta( $course_id, '_tutor_enable_qa', true );
		if ( '' !== $enable_qa ) {
			update_post_meta( $course_id, '_enable_course_qa', 'yes' === $enable_qa ? 'yes' : 'no' );
		}

		// Certificate: create and assign a Masteriyo certificate template for every course that had
		// a TutorLMS certificate assigned. Tutor's design cannot be converted — Sample 1 is used.
		$tutor_cert_template = get_post_meta( $course_id, 'tutor_course_certificate_template', true );
		if ( $tutor_cert_template && 'none' !== $tutor_cert_template ) {
			$course_obj = get_post( $course_id );
			Helper::assign_certificate_template( $course_id, (int) ( $course_obj ? $course_obj->post_author : 0 ) );
		}

		// Course intro video — Course model uses _featured_video_source / _featured_video_url.
		// For self-hosted, _featured_video_url must store the attachment ID (not the URL) because
		// the course editor's SelfHostedFeaturedVideo component calls parseInt() on this value and
		// fetches the media via the WP REST API (/wp/v2/media/<id>).
		$course_video = static::map_video_meta( $course_id );
		if ( ! empty( $course_video ) ) {
			update_post_meta( $course_id, '_featured_video_source', $course_video['source'] );
			if ( 'self-hosted' === $course_video['source'] ) {
				if ( ! empty( $course_video['id'] ) ) {
					update_post_meta( $course_id, '_featured_video_url', (string) $course_video['id'] );
				}
			} elseif ( ! empty( $course_video['url'] ) ) {
				update_post_meta( $course_id, '_featured_video_url', $course_video['url'] );
			}
		}

		// Course FAQ.
		$raw_faq = maybe_unserialize( get_post_meta( $course_id, '_tutor_course_faq', true ) );
		if ( ! empty( $raw_faq ) && is_array( $raw_faq ) && masteriyo_service_provider_exists( 'course-faq' ) ) {
			foreach ( $raw_faq as $entry ) {
				$question = sanitize_text_field( $entry['question'] ?? '' );
				$answer   = wp_kses_post( $entry['answer'] ?? '' );
				if ( '' === $question ) {
					continue;
				}
				$faq_item = masteriyo( 'course-faq' );
				$faq_item->set_course_id( $course_id );
				$faq_item->set_title( $question );
				$faq_item->set_content( $answer );
				$faq_item->set_status( 'approve' );
				masteriyo( 'course-faq.store' )->create( $faq_item );
			}
		}

		// Course attachments.
		$raw_attachments = maybe_unserialize( get_post_meta( $course_id, '_tutor_attachments', true ) );
		if ( ! empty( $raw_attachments ) && is_array( $raw_attachments ) ) {
			$attachment_ids = array_values( array_filter( array_map( 'intval', $raw_attachments ) ) );
			if ( ! empty( $attachment_ids ) ) {
				update_post_meta( $course_id, '_attachments', $attachment_ids );
			}
		}

		// Prerequisites point at the copies; one not copied yet keeps its Tutor ID until finalize_step().
		$raw_prerequisite_ids = maybe_unserialize( get_post_meta( $course_id, '_tutor_course_prerequisites_ids', true ) );
		if ( ! empty( $raw_prerequisite_ids ) && is_array( $raw_prerequisite_ids ) ) {
			$prerequisite_ids = array_values( array_filter( array_map( 'intval', $raw_prerequisite_ids ) ) );
			if ( ! empty( $prerequisite_ids ) ) {
				$copies = MigrationMap::targets( self::SLUG, MigrationMap::POST, $prerequisite_ids );
				update_post_meta(
					$course_id,
					'_prerequisites_courses',
					array_map(
						function( $id ) use ( $copies ) {
							return $copies[ $id ] ?? $id;
						},
						$prerequisite_ids
					)
				);
				update_post_meta( $course_id, '_prerequisites_enable', true );
			}
		}

		// Course flow / content drip type — stored in _flow (read by CourseFlow::SEQUENTIAL check in Pro.php
		// and ContentDripType::DATE/DAYS check in ContentDripAddon). ContentDripAddon meta is also written
		// for date/days so the addon's REST response and notification scheduling works correctly.
		if ( is_array( $course_settings ) ) {
			$tutor_drip_type = $course_settings['content_drip_type'] ?? '';
			$flow_map        = array(
				'unlock_sequentially' => 'sequential',
				'unlock_by_date'      => 'date',
				'specific_days'       => 'days',
				// after_finishing_prerequisites has no Masteriyo equivalent; leave as free-flow.
			);
			$mto_flow = $flow_map[ $tutor_drip_type ] ?? '';
			if ( $mto_flow ) {
				update_post_meta( $course_id, '_flow', $mto_flow );
				if ( in_array( $mto_flow, array( 'date', 'days' ), true ) ) {
					update_post_meta( $course_id, '_content_drip_enable', true );
					update_post_meta( $course_id, '_content_drip_type', $mto_flow );
				}
			}

			// No Masteriyo equivalent for 'after_finishing_prerequisites' drip — preserve for admin review after migration.
			if ( 'after_finishing_prerequisites' === $tutor_drip_type ) {
				Helper::store_unmigrated_meta( $course_id, 'tutor_drip_after_prerequisites', true );
			}
		}

		// No direct Masteriyo equivalent — stored under _migrated_* for post-migration reference.
		$requirements = get_post_meta( $course_id, '_tutor_course_requirements', true );
		if ( ! empty( $requirements ) ) {
			Helper::store_unmigrated_meta( $course_id, 'tutor_course_requirements', $requirements );
		}
		$target_audience = get_post_meta( $course_id, '_tutor_course_target_audience', true );
		if ( ! empty( $target_audience ) ) {
			Helper::store_unmigrated_meta( $course_id, 'tutor_course_target_audience', $target_audience );
		}

		update_post_meta( $course_id, '_was_tutor_course', true );
	}

	/**
	 * Resolve (or create) the Masteriyo course_difficulty term for a TutorLMS level slug.
	 *
	 * @param string $tutor_level TutorLMS level slug.
	 * @return int Masteriyo course_difficulty term ID, or 0 if none.
	 */
	public static function resolve_course_difficulty_term( string $tutor_level ): int {
		// 'all_level' means no specific difficulty in TutorLMS — skip to avoid creating a spurious taxonomy term.
		if ( '' === $tutor_level || 'all_level' === $tutor_level ) {
			return 0;
		}

		$term = get_term_by( 'slug', $tutor_level, 'course_difficulty' );
		if ( $term && ! is_wp_error( $term ) ) {
			return (int) $term->term_id;
		}

		$inserted = wp_insert_term( ucfirst( $tutor_level ), 'course_difficulty', array( 'slug' => $tutor_level ) );
		if ( is_wp_error( $inserted ) ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Could not create difficulty level "%s" — %s', $tutor_level, $inserted->get_error_message() ),
				array( 'source' => 'migration-tool' )
			);
			return 0;
		}

		return (int) $inserted['term_id'];
	}

	/**
	 * Resolve (or create) Masteriyo course_cat terms for all TutorLMS course-category terms,
	 * preserving the parent-child hierarchy.
	 *
	 * @param int    $course_id Course post ID.
	 * @param string $taxonomy  TutorLMS category taxonomy. Default 'course-category'.
	 * @return int[] Masteriyo course_cat term IDs.
	 */
	public static function migrate_course_categories_from_tutor_to_masteriyo( $course_id, $taxonomy = 'course-category' ): array {
		$categories = wp_get_post_terms( $course_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			return array();
		}

		$hierarchy = array();
		foreach ( $categories as $cat_id ) {
			$cat = get_term( $cat_id, $taxonomy );
			if ( is_object( $cat ) && ! is_wp_error( $cat ) ) {
				$hierarchy[ $cat->term_id ] = array(
					'name'   => $cat->name,
					'parent' => $cat->parent,
				);
			}
		}

		$masteriyo_id_map = array();

		// Top-level first so parent IDs are available when processing children.
		foreach ( array( 0, 1 ) as $pass ) {
			foreach ( $hierarchy as $cat_id => $cat_info ) {
				$is_child = ( 0 !== (int) $cat_info['parent'] );
				if ( ( 0 === $pass && $is_child ) || ( 1 === $pass && ! $is_child ) ) {
					continue;
				}

				$parent_id        = $is_child ? ( $masteriyo_id_map[ $cat_info['parent'] ] ?? 0 ) : 0;
				$existing         = term_exists( $cat_info['name'], 'course_cat', $is_child ? $parent_id : 0 );
				$masteriyo_cat_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;

				if ( ! $masteriyo_cat_id ) {
					$args    = $is_child ? array( 'parent' => $parent_id ) : array();
					$created = wp_insert_term( $cat_info['name'], 'course_cat', $args );
					if ( is_wp_error( $created ) ) {
						masteriyo_get_logger()->warning(
							sprintf( 'Could not create course category "%s" — %s', $cat_info['name'], $created->get_error_message() ),
							array( 'source' => 'migration-tool' )
						);
						continue;
					}
					$masteriyo_cat_id = (int) $created['term_id'];
					$image_id         = get_term_meta( $cat_id, 'thumbnail_id', true );
					if ( $image_id ) {
						add_term_meta( $masteriyo_cat_id, '_featured_image', $image_id );
					}
				}

				$masteriyo_id_map[ $cat_id ] = $masteriyo_cat_id;
			}
		}

		return array_values( $masteriyo_id_map );
	}

	/**
	 * Resolve (or create) Masteriyo course_tag terms for all TutorLMS course-tag terms.
	 *
	 * @param int $course_id Course post ID.
	 * @return int[] Masteriyo course_tag term IDs.
	 */
	public static function migrate_course_tags_from_tutor_to_masteriyo( $course_id ): array {
		$tags = wp_get_post_terms( $course_id, 'course-tag', array( 'fields' => 'names' ) );

		if ( empty( $tags ) || is_wp_error( $tags ) ) {
			return array();
		}

		$term_ids = array();
		foreach ( $tags as $tag_name ) {
			$existing = get_term_by( 'name', $tag_name, 'course_tag' );
			if ( $existing && ! is_wp_error( $existing ) ) {
				$term_ids[] = (int) $existing->term_id;
			} else {
				$created = wp_insert_term( $tag_name, 'course_tag' );
				if ( ! is_wp_error( $created ) ) {
					$term_ids[] = (int) $created['term_id'];
				}
			}
		}

		return $term_ids;
	}

	/**
	 * Get enrolled users for a course from the tutor_enrolled CPT.
	 *
	 * @since 1.13.0
	 *
	 * @param int $course_id Course post ID.
	 * @return object[]|null
	 */
	public static function get_enrolled_users_by_course_id( $course_id ) {
		global $wpdb;

		$sql = "SELECT p.ID, p.post_title, p.post_date, p.post_status, u.user_login, u.user_email
		        FROM {$wpdb->prefix}posts AS p
		        INNER JOIN {$wpdb->prefix}users AS u ON p.post_author = u.ID
		        WHERE p.post_parent = %d
		          AND p.post_type = 'tutor_enrolled'
		          AND p.post_status != 'trash'";

		$prepared_sql = $wpdb->prepare( $sql, $course_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $prepared_sql );          // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get all Tutor LMS course reviews from the comments table.
	 *
	 * @since 1.13.0
	 *
	 * @return array|object|null
	 */
	public static function get_tutor_course_reviews() {
		global $wpdb;

		$select_columns = '_reviews.comment_ID,
			_reviews.comment_post_ID,
			_reviews.comment_author,
			_reviews.comment_author_email,
			_reviews.comment_date,
			_reviews.comment_content,
			_reviews.comment_approved AS comment_status,
			_reviews.user_id,
			_reviews.comment_parent,
			_rev_meta.meta_value AS rating,
			_reviewer.display_name';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$query = $wpdb->prepare(
			"SELECT {$select_columns}
			 FROM   {$wpdb->comments} _reviews
			        INNER JOIN {$wpdb->commentmeta} _rev_meta
			               ON _reviews.comment_ID = _rev_meta.comment_id
			        LEFT JOIN {$wpdb->users} _reviewer
			               ON _reviews.user_id = _reviewer.ID
			 WHERE  _reviews.comment_type = 'tutor_course_rating'
			        AND _rev_meta.meta_key = 'tutor_rating'
			 ORDER BY _reviews.comment_ID"
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Processes migration for a single Tutor quiz question.
	 *
	 * Image_matching, ordering, and gap_match have no Masteriyo equivalent — they are logged and skipped. Essay maps to text-answer.
	 *
	 * @since 1.13.0
	 *
	 * @param object $question   Tutor quiz question data.
	 * @param int    $quiz_id    Tutor quiz ID.
	 * @param int    $course_id  Masteriyo course ID.
	 * @return void
	 */
	public static function process_question_migration_from_tutor( $question, $quiz_id, $course_id ) {
		global $wpdb;

		// h5p has no Masteriyo equivalent — skip gracefully.
		$unsupported = array( 'h5p' );
		if ( in_array( $question->question_type, $unsupported, true ) ) {
			masteriyo_get_logger()->warning(
				sprintf(
					'Question %d skipped: question type "%s" is not supported in Masteriyo.',
					$question->question_id,
					$question->question_type
				),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$question_type = static::map_question_type( $question->question_type );

		if ( is_null( $question_type ) ) {
			masteriyo_get_logger()->warning(
				sprintf(
					'Question %d skipped: question type "%s" has no Masteriyo equivalent.',
					$question->question_id,
					$question->question_type
				),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$answers_tutor        = static::answer_list_by_question( $question->question_id, $question->question_type );
		$question_description = sanitize_text_field( $question->question_description ?? '' );
		$answers              = array();

		if ( 'image_sequencing' === $question->question_type ) {
			masteriyo_get_logger()->warning(
				sprintf(
					'Question %d (image_sequencing) migrated as sortable — image attachments dropped; only text labels preserved.',
					$question->question_id
				),
				array( 'source' => 'migration-tool' )
			);
		}

		foreach ( $answers_tutor as $answer_tutor ) {
			if ( 'true_false' === $answer_tutor->belongs_question_type ) {
				$answers[] = array(
					'name'    => $answer_tutor->answer_title,
					'correct' => masteriyo_string_to_bool( $answer_tutor->is_correct ),
				);
			}
			if ( 'single_choice' === $answer_tutor->belongs_question_type ) {
				$answers[] = array(
					'name'               => $answer_tutor->answer_title,
					'correct'            => masteriyo_string_to_bool( $answer_tutor->is_correct ),
					'answer_view_format' => $answer_tutor->answer_view_format ?? '',
					'image_id'           => $answer_tutor->image_id ?? '',
				);
			}
			if ( 'multiple_choice' === $answer_tutor->belongs_question_type ) {
				$answers[] = array(
					'name'               => $answer_tutor->answer_title,
					'correct'            => masteriyo_string_to_bool( $answer_tutor->is_correct ),
					'answer_view_format' => $answer_tutor->answer_view_format ?? '',
					'image_id'           => $answer_tutor->image_id ?? '',
				);
			}
			if ( 'fill_in_the_blank' === $answer_tutor->belongs_question_type ) {
				$answers[] = array( 'name' => '{{' . ( $answer_tutor->answer_one_gap_match ?? '' ) . '}}' );
				if ( ! empty( $answer_tutor->answer_two_gap_match ) ) {
					$answers[] = array( 'name' => '{{' . $answer_tutor->answer_two_gap_match . '}}' );
				}
			}

			// gap_match → fill-in-the-blanks: each answer row is one blank.
			if ( 'gap_match' === $answer_tutor->belongs_question_type ) {
				$answers[] = array( 'name' => '{{' . $answer_tutor->answer_title . '}}' );
			}

			// ordering → sortable: rows are already sorted by answer_order ASC.
			if ( 'ordering' === $answer_tutor->belongs_question_type ) {
				$answers[] = array( 'name' => $answer_tutor->answer_title );
			}

			// image_matching → matching: text-based prompt/match pairs (image IDs not preserved — no Masteriyo ImageToImage support for imported data).
			if ( 'image_matching' === $answer_tutor->belongs_question_type ) {
				$answers[] = array(
					'prompt' => $answer_tutor->answer_title,
					'match'  => $answer_tutor->answer_two_gap_match ?? '',
				);
			}

			// match_lists → matching: left column = prompt, right column = match value.
			if ( 'match_lists' === $answer_tutor->belongs_question_type ) {
				$answers[] = array(
					'prompt' => $answer_tutor->answer_title,
					'match'  => $answer_tutor->answer_two_gap_match ?? '',
				);
			}

			// image_sequencing → sortable: images become text labels (image IDs dropped — warning logged above).
			if ( 'image_sequencing' === $answer_tutor->belongs_question_type ) {
				$answers[] = array( 'name' => $answer_tutor->answer_title );
			}

			// text-answer, short_answer, open_ended, essay: no stored answers in Masteriyo — $answers stays empty.
		}

		$existing_question_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				 WHERE meta_key = '_tutor_question_id' AND meta_value = %d
				 LIMIT 1",
				$question->question_id
			)
		);

		if ( $existing_question_id ) {
			// A reused quiz copy keeps its questions; they follow it to the new course.
			update_post_meta( (int) $existing_question_id, '_course_id', $course_id );
			return;
		}

		// Direct DB insert bypasses the full wp_insert_post() hook chain (~15 actions per question).
		// Questions are internal CPTs with no public URL, taxonomy, or GUID requirement.
		$now = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->posts,
			array(
				'post_title'            => $question->question_title,
				'post_content'          => wp_json_encode( $answers ),
				'post_excerpt'          => $question_description,
				'post_type'             => PostType::QUESTION,
				'post_status'           => PostStatus::PUBLISH,
				'post_author'           => (int) ( $question->post_author ?? 0 ),
				'post_parent'           => $quiz_id,
				'post_date'             => $now,
				'post_date_gmt'         => get_gmt_from_date( $now ),
				'post_modified'         => $now,
				'post_modified_gmt'     => get_gmt_from_date( $now ),
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'to_ping'               => '',
				'pinged'                => '',
				'post_content_filtered' => '',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$question_id = (int) $wpdb->insert_id;
		if ( ! $question_id ) {
			masteriyo_get_logger()->error( 'Could not save quiz question — direct DB insert failed.', array( 'source' => 'migration-tool' ) );
			return;
		}

		$meta_rows = array(
			array( $question_id, '_course_id', (string) $course_id ),
			array( $question_id, '_type', $question_type ),
			array( $question_id, '_points', (string) $question->question_mark ),
			array( $question_id, '_parent_id', (string) $quiz_id ),
			array( $question_id, '_tutor_question_id', (string) $question->question_id ),
		);

		if ( $question_description ) {
			$meta_rows[] = array( $question_id, '_enable_description', '1' );
		}

		if ( ! empty( $question->answer_explanation ) ) {
			$meta_rows[] = array( $question_id, '_answer_explanation', wp_kses_post( $question->answer_explanation ) );
		}

		$placeholders = implode( ', ', array_fill( 0, count( $meta_rows ), '(%d, %s, %s)' ) );
		$values       = array();
		foreach ( $meta_rows as $row ) {
			$values[] = $row[0];
			$values[] = $row[1];
			$values[] = $row[2];
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES {$placeholders}", ...$values ) );
	}

	/**
	 * Gets the list of answers for a given question ID and type.
	 *
	 * @since 1.13.0
	 *
	 * @param int    $question_id   Tutor quiz question ID.
	 * @param string $question_type Tutor question type slug.
	 * @return array
	 */
	public static function answer_list_by_question( int $question_id, string $question_type ): array {
		global $wpdb;
		$answers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}tutor_quiz_question_answers
				 WHERE belongs_question_id = %d
				   AND belongs_question_type = %s
				 ORDER BY answer_order ASC",
				$question_id,
				$question_type
			)
		);
		return is_array( $answers ) && count( $answers ) ? $answers : array();
	}

	/**
	 * Add the masteriyo_student user role when the user has no Masteriyo role yet.
	 *
	 * @since 1.13.0
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public static function update_user_role_to_masteriyo_student( $user_id ) {
		$user = new \WP_User( $user_id );

		if ( ! $user || ! isset( $user->ID ) || 0 === $user->ID || ! $user->exists() ) {
			return;
		}

		if (
			! in_array( Roles::ADMIN, (array) $user->roles, true ) &&
			! in_array( Roles::MANAGER, (array) $user->roles, true ) &&
			! in_array( Roles::STUDENT, (array) $user->roles, true ) &&
			! in_array( Roles::INSTRUCTOR, (array) $user->roles, true )
		) {
			$user->add_role( Roles::STUDENT );
		}
	}

	/**
	 * Get TutorLMS announcements data.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	public static function get_tutor_announcements_data() {
		$args = array(
			'post_type'      => 'tutor_announcements',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		);

		$the_query     = new WP_Query( $args );
		$announcements = $the_query->have_posts() ? $the_query->posts : array();
		return $announcements;
	}


	/**
	 * Convert WooCommerce order status to Masteriyo order status.
	 *
	 * @since 1.13.0
	 *
	 * @param string $status WooCommerce order status.
	 * @return string Masteriyo OrderStatus value.
	 */
	public static function convert_wc_status( $status ) {
		$map = array(
			'processing'    => OrderStatus::PROCESSING,
			'pending'       => OrderStatus::PENDING,
			'cancelled'     => OrderStatus::CANCELLED,
			'on-hold'       => OrderStatus::ON_HOLD,
			'completed'     => OrderStatus::COMPLETED,
			'refunded'      => OrderStatus::REFUNDED,
			'failed'        => OrderStatus::FAILED,
			'wc-processing' => OrderStatus::PROCESSING,
			'wc-pending'    => OrderStatus::PENDING,
			'wc-cancelled'  => OrderStatus::CANCELLED,
			'wc-on-hold'    => OrderStatus::ON_HOLD,
			'wc-completed'  => OrderStatus::COMPLETED,
			'wc-refunded'   => OrderStatus::REFUNDED,
			'wc-failed'     => OrderStatus::FAILED,
		);

		$new_status = $map[ $status ] ?? 'pending';
		return OrderStatus::PROCESSING === $new_status ? 'pending' : $new_status;
	}

	/**
	 * Convert a TutorLMS time_limit array to minutes.
	 *
	 * @since 1.13.0
	 *
	 * @param mixed $time_limit TutorLMS time_limit option array.
	 * @return int Duration in minutes.
	 */
	public static function convert_time_limit_to_minutes( $time_limit ) {
		$time_value = isset( $time_limit['time_value'] ) ? (int) $time_limit['time_value'] : 0;
		$time_type  = isset( $time_limit['time_type'] ) ? $time_limit['time_type'] : '';

		switch ( $time_type ) {
			case 'hours':
				return $time_value * 60;
			case 'days':
				return $time_value * 24 * 60;
			case 'time_limit_seconds':
				return (int) round( $time_value / 60 );
			case 'minutes':
			default:
				return $time_value;
		}
	}

	/**
	 * Extract video metadata from a TutorLMS post's _video meta.
	 *
	 * @param int $post_id TutorLMS post ID (course or lesson).
	 * @return array{source: string, url: string, id: int, runtime: int}
	 */
	public static function map_video_meta( int $post_id ): array {
		$video = get_post_meta( $post_id, '_video', true );
		if ( empty( $video ) || ! is_array( $video ) ) {
			return array();
		}

		$raw_source = $video['source'] ?? '';

		switch ( $raw_source ) {
			case 'source_youtube':
				return array(
					'source'  => 'youtube',
					'url'     => $video['source_youtube'] ?? '',
					'id'      => 0,
					'runtime' => (int) ( $video['runtime'] ?? 0 ),
				);
			case 'source_vimeo':
				return array(
					'source'  => 'vimeo',
					'url'     => $video['source_vimeo'] ?? '',
					'id'      => 0,
					'runtime' => (int) ( $video['runtime'] ?? 0 ),
				);
			case 'source_embedded':
				return array(
					'source'  => 'embed-video',
					'url'     => $video['source_embedded'] ?? '',
					'id'      => 0,
					'runtime' => (int) ( $video['runtime'] ?? 0 ),
				);
			case 'source_external_url':
				return array(
					'source'  => 'external',
					'url'     => $video['source_external_url'] ?? '',
					'id'      => 0,
					'runtime' => (int) ( $video['runtime'] ?? 0 ),
				);
			case 'html5':
				return array(
					'source'  => 'self-hosted',
					'url'     => '',
					'id'      => (int) ( $video['source_video_id'] ?? 0 ),
					'runtime' => (int) ( $video['runtime'] ?? 0 ),
				);
			default:
				return array();
		}
	}

	/**
	 * Map a TutorLMS question type slug to the Masteriyo equivalent.
	 *
	 * Returns null for types that have no Masteriyo equivalent (caller should log and skip).
	 *
	 * @param string $tutor_type TutorLMS question_type value.
	 * @return string|null Masteriyo question type, or null if unsupported.
	 */
	public static function map_question_type( string $tutor_type ): ?string {
		$map = array(
			'true_false'        => 'true-false',
			'single_choice'     => 'single-choice',
			'multiple_choice'   => 'multiple-choice',
			'fill_in_the_blank' => 'fill-in-the-blanks',
			'gap_match'         => 'fill-in-the-blanks',
			'ordering'          => 'sortable',
			'image_matching'    => 'matching',
			'match_lists'       => 'matching',
			'image_sequencing'  => 'sortable',
			'open_ended'        => 'text-answer',
			'text_answer'       => 'text-answer',
			'short_answer'      => 'text-answer',
			'essay'             => 'text-answer',
		);
		return $map[ $tutor_type ] ?? null;
	}

	/**
	 * Map a TutorLMS order status to a Masteriyo OrderStatus value.
	 *
	 * @param string $tutor_status Value from tutor_orders.order_status or payment_status.
	 * @return string Masteriyo OrderStatus constant value.
	 */
	public static function map_order_status( string $tutor_status ): string {
		$map = array(
			'paid'               => OrderStatus::COMPLETED,
			'completed'          => OrderStatus::COMPLETED,
			'refunded'           => OrderStatus::REFUNDED,
			'partially-refunded' => OrderStatus::REFUNDED,
			'failed'             => OrderStatus::FAILED,
			'cancelled'          => OrderStatus::CANCELLED,
			'trash'              => OrderStatus::CANCELLED,
			'pending'            => OrderStatus::PENDING,
			'processing'         => OrderStatus::PENDING,
		);
		return $map[ $tutor_status ] ?? OrderStatus::PENDING;
	}

	/**
	 * Convert a TutorLMS quiz time_limit option array to seconds.
	 *
	 * @param array $time_limit TutorLMS time_limit option with time_value and time_type keys.
	 * @return int Duration in seconds. Returns 0 if no limit set.
	 */
	public static function quiz_duration_to_seconds( array $time_limit ): int {
		$value    = (int) ( $time_limit['time_value'] ?? 0 );
		$type     = $time_limit['time_type'] ?? 'minutes';
		$unit_map = array(
			'seconds' => 1,
			'minutes' => MINUTE_IN_SECONDS,
			'hours'   => HOUR_IN_SECONDS,
			'days'    => DAY_IN_SECONDS,
			'weeks'   => WEEK_IN_SECONDS,
		);
		return $value * ( $unit_map[ $type ] ?? MINUTE_IN_SECONDS );
	}

	/**
	 * Migrate TutorLMS instructor profile metadata to WordPress/Masteriyo equivalents.
	 *
	 * @param int $user_id WordPress user ID of the instructor.
	 * @return void
	 */
	public static function migrate_instructor_profile( int $user_id ): void {
		$bio      = get_user_meta( $user_id, '_tutor_profile_bio', true );
		$photo_id = (int) get_user_meta( $user_id, '_tutor_profile_photo', true );
		$website  = get_user_meta( $user_id, '_tutor_profile_website', true );

		if ( $bio ) {
			wp_update_user(
				array(
					'ID'          => $user_id,
					'description' => wp_kses_post( $bio ),
				)
			);
		}
		if ( $website ) {
			wp_update_user(
				array(
					'ID'       => $user_id,
					'user_url' => esc_url_raw( $website ),
				)
			);
		}
		if ( $photo_id ) {
			update_user_meta( $user_id, '_profile_image_id', $photo_id );
		}

		$job_title = get_user_meta( $user_id, '_tutor_profile_job_title', true );
		if ( $job_title ) {
			update_user_meta( $user_id, '_migrated_tutor_job_title', sanitize_text_field( $job_title ) );
		}

		// Pro model-backed social link keys (written even in free context — harmless no-ops if Pro absent).
		$pro_social_map = array(
			'facebook' => '_public_profile_facebook_url',
			'linkedin' => '_public_profile_linkedin_url',
		);

		foreach ( array( 'facebook', 'twitter', 'linkedin', 'github' ) as $network ) {
			$handle = get_user_meta( $user_id, "_tutor_profile_{$network}", true );
			if ( $handle ) {
				$clean = sanitize_text_field( $handle );
				update_user_meta( $user_id, "_migrated_tutor_social_{$network}", $clean );

				if ( isset( $pro_social_map[ $network ] ) ) {
					update_user_meta( $user_id, $pro_social_map[ $network ], $clean );
				}
			}
		}
	}
}
