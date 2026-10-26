<?php
/**
 * LearnPress migrations.
 *
 * @since 1.16.0
 *
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool\LMS;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\Helper;
use Masteriyo\Addons\MigrationTool\MigrationMap;
use Masteriyo\Addons\MigrationTool\PostCloner;
use Masteriyo\Enums\CommentStatus;
use Masteriyo\Enums\CommentType;
use Masteriyo\Enums\CourseProgressItemType;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuestionType;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Roles;

/**
 * Class LearnPress.
 *
 * @since 1.16.0
 */
class LearnPress {

	/**
	 * Migrator slug, the key every migration map row is written under.
	 */
	const SLUG = 'learnpress';

	// -------------------------------------------------------------------------
	// Batch API — count / paginate / dispatch
	// -------------------------------------------------------------------------

	/**
	 * Count the source items a step still has to copy.
	 *
	 * Rows already in the migration map are left out, so a second run counts only
	 * what is new.
	 *
	 * @param string $step Step name.
	 * @return int
	 */
	public static function count_source_items( string $step ): int {
		global $wpdb;

		$not_post = MigrationMap::unmapped_sql( 'ID', self::SLUG, MigrationMap::POST );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		switch ( $step ) {
			case 'users':
				$capabilities_key = $wpdb->get_blog_prefix() . 'capabilities';
				$not_user         = MigrationMap::unmapped_sql( 'user_id', self::SLUG, MigrationMap::USER );
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM (
							SELECT DISTINCT u.ID AS user_id
							FROM {$wpdb->users} u
							INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
							WHERE um.meta_key = %s AND um.meta_value LIKE %s
							UNION
							SELECT DISTINCT user_id FROM {$wpdb->prefix}learnpress_user_items
							WHERE item_type = 'lp_course'
						) AS lp_users
						WHERE 1=1 {$not_user}",
						$capabilities_key,
						'%lp_teacher%'
					)
				);

			case 'courses':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'lp_course' {$not_post}" );

			case 'enrollments':
				$not_enrollment = MigrationMap::unmapped_sql( 'user_item_id', self::SLUG, 'enrollments' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}learnpress_user_items WHERE item_type = 'lp_course' {$not_enrollment}" );

			case 'lesson_progress':
				$not_progress = MigrationMap::unmapped_sql( 'user_item_id', self::SLUG, 'lesson_progress' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}learnpress_user_items WHERE item_type IN ('lp_lesson','lp_quiz') {$not_progress}" );

			case 'orders':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'lp_order' {$not_post}" );

			case 'reviews':
				$not_comment = MigrationMap::unmapped_sql( 'c.comment_ID', self::SLUG, MigrationMap::COMMENT );
				return (int) $wpdb->get_var(
					"SELECT COUNT(c.comment_ID)
					 FROM {$wpdb->comments} c
					 INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
					 WHERE p.post_type = 'lp_course'
					   AND c.comment_type IN ('course_rate', 'comment', '')
					   {$not_comment}"
				);

			case 'quiz_results':
				$not_result = MigrationMap::unmapped_sql( 'lur.id', self::SLUG, 'quiz_results' );
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}learnpress_user_item_results lur
					 INNER JOIN {$wpdb->prefix}learnpress_user_items lui
					     ON lur.user_item_id = lui.user_item_id
					 WHERE lui.item_type = 'lp_quiz'
					   {$not_result}"
				);

		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return 0;
	}

	/**
	 * Return one batch of source IDs starting after $cursor.
	 *
	 * Every step pages by ID and leaves out rows already in the migration map. Nothing
	 * is removed from the source, so a cursor is what moves a run forward; $exclude is
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

		$not_post = MigrationMap::unmapped_sql( 'ID', self::SLUG, MigrationMap::POST );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		switch ( $step ) {
			case 'users':
				$capabilities_key = $wpdb->get_blog_prefix() . 'capabilities';
				$not_user         = MigrationMap::unmapped_sql( 'user_id', self::SLUG, MigrationMap::USER );
				$ids              = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_id FROM (
							SELECT DISTINCT u.ID AS user_id
							FROM {$wpdb->users} u
							INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
							WHERE um.meta_key = %s AND um.meta_value LIKE %s
							UNION
							SELECT DISTINCT user_id FROM {$wpdb->prefix}learnpress_user_items
							WHERE item_type = 'lp_course'
						) AS lp_users
						WHERE user_id > %d
						{$not_user}
						ORDER BY user_id ASC
						LIMIT %d",
						$capabilities_key,
						'%lp_teacher%',
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'courses':
				return self::post_ids_after( 'lp_course', $cursor, $limit, $not_post );

			case 'enrollments':
				return self::user_item_ids_after( "item_type = 'lp_course'", 'enrollments', $cursor, $limit );

			case 'lesson_progress':
				return self::user_item_ids_after( "item_type IN ('lp_lesson','lp_quiz')", 'lesson_progress', $cursor, $limit );

			case 'orders':
				return self::post_ids_after( 'lp_order', $cursor, $limit, $not_post );

			case 'reviews':
				$not_comment = MigrationMap::unmapped_sql( 'c.comment_ID', self::SLUG, MigrationMap::COMMENT );
				$ids         = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT c.comment_ID
						 FROM {$wpdb->comments} c
						 INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
						 WHERE p.post_type = 'lp_course'
						   AND c.comment_type IN ('course_rate', 'comment', '')
						   AND c.comment_ID > %d
						   {$not_comment}
						 ORDER BY c.comment_ID ASC
						 LIMIT %d",
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'quiz_results':
				$not_result = MigrationMap::unmapped_sql( 'lur.id', self::SLUG, 'quiz_results' );
				$ids        = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT lur.id
						 FROM {$wpdb->prefix}learnpress_user_item_results lur
						 INNER JOIN {$wpdb->prefix}learnpress_user_items lui
						     ON lur.user_item_id = lui.user_item_id
						 WHERE lui.item_type = 'lp_quiz'
						   AND lur.id > %d
						   {$not_result}
						 ORDER BY lur.id ASC
						 LIMIT %d",
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

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
	 * One page of learnpress_user_items IDs, after the cursor, that are not in the map yet.
	 *
	 * @param string $where  Item type condition, e.g. "item_type = 'lp_course'".
	 * @param string $entity Map entity the step records under.
	 * @param int    $cursor Last processed ID.
	 * @param int    $limit  Batch size.
	 * @return int[]
	 */
	private static function user_item_ids_after( string $where, string $entity, int $cursor, int $limit ): array {
		global $wpdb;

		$not_mapped = MigrationMap::unmapped_sql( 'user_item_id', self::SLUG, $entity );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_item_id FROM {$wpdb->prefix}learnpress_user_items
				 WHERE {$where}
				   AND user_item_id > %d
				 {$not_mapped}
				 ORDER BY user_item_id ASC
				 LIMIT %d",
				$cursor,
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( 'intval', $ids ? $ids : array() );
	}

	/**
	 * Dispatch a single-item migration to the appropriate migrate_single_*() method.
	 *
	 * Called by MigrationProcessJob inside a START TRANSACTION / COMMIT wrapper.
	 * Must be idempotent — safe to call twice for the same (step, item_id) pair.
	 *
	 * @param string $step    Step name.
	 * @param int    $item_id Source item ID.
	 * @throws \Exception Triggers ROLLBACK in the job engine; item is added to the failed list.
	 */
	public static function migrate_item( string $step, int $item_id ): void {
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
			case 'lesson_progress':
				static::migrate_single_lesson_progress( $item_id );
				break;
			case 'orders':
				static::migrate_single_order( $item_id );
				break;
			case 'reviews':
				static::migrate_single_review( $item_id );
				break;
			case 'quiz_results':
				static::migrate_single_quiz_result( $item_id );
				break;
		}
	}

	// -------------------------------------------------------------------------
	// Single-item migrate methods
	// -------------------------------------------------------------------------

	/**
	 * Give a LearnPress teacher or enrolled student the matching Masteriyo role.
	 *
	 * LearnPress's own roles and capabilities stay: LearnPress keeps running until the
	 * site owner deactivates it.
	 *
	 * @param int $user_id WP user ID.
	 * @throws \Exception If the WP user record does not exist.
	 */
	public static function migrate_single_user( int $user_id ): void {
		global $wpdb;

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			throw new \Exception(
				sprintf( 'migrate_single_user: WP user %d not found.', $user_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$roles    = (array) $user->roles;
		$caps     = is_array( $user->caps ) ? $user->caps : array();
		$is_admin = in_array( 'administrator', $roles, true ) || isset( $caps['administrator'] );

		if ( ( in_array( 'lp_teacher', $roles, true ) || isset( $caps['lp_teacher'] ) ) && ! $is_admin ) {
			$user->add_role( Roles::INSTRUCTOR );
			$roles = (array) $user->roles;
			$caps  = is_array( $user->caps ) ? $user->caps : array();
		}

		if ( ! $is_admin
			&& ! in_array( Roles::INSTRUCTOR, $roles, true )
			&& ! isset( $caps[ Roles::INSTRUCTOR ] )
			&& ! in_array( Roles::STUDENT, $roles, true )
			&& ! isset( $caps[ Roles::STUDENT ] )
		) {
			$has_enrollment = (bool) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT 1 FROM {$wpdb->prefix}learnpress_user_items WHERE user_id = %d AND item_type = 'lp_course' LIMIT 1",
					$user_id
				)
			);

			if ( $has_enrollment ) {
				$user->add_role( Roles::STUDENT );
			}
		}

		$lp_user_meta_table = $wpdb->prefix . 'learnpress_user_meta';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $lp_user_meta_table ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$bio = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_value FROM {$lp_user_meta_table} WHERE user_id = %d AND meta_key = '_lp_profile_bio' LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$user_id
				)
			);
			if ( $bio ) {
				wp_update_user(
					array(
						'ID'          => $user_id,
						'description' => $bio,
					)
				);
			}
		}

		MigrationMap::record( self::SLUG, MigrationMap::USER, $user_id, $user_id );
	}

	/**
	 * Copy a LearnPress course with its sections, lessons, quizzes and questions into Masteriyo.
	 *
	 * LearnPress's posts are read, never changed: the course, lessons and quizzes are
	 * cloned and the clones converted; sections and questions are new posts.
	 *
	 * @param int $source_course_id LearnPress lp_course post ID.
	 * @throws \Exception If the post does not exist or is not an lp_course post.
	 */
	public static function migrate_single_course( int $source_course_id ): void {
		$post = get_post( $source_course_id );

		if ( ! $post ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d not found.', $source_course_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		if ( 'lp_course' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d is not an lp_course post (got %s).', $source_course_id, $post->post_type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$course_id = PostCloner::clone_post( self::SLUG, $source_course_id, array( 'post_type' => PostType::COURSE ) );

		self::process_course_curriculum( $source_course_id, $course_id );
		self::update_course_meta( $source_course_id, $course_id );
	}

	/**
	 * Copy a LearnPress enrollment (learnpress_user_items row, item_type lp_course) to a
	 * Masteriyo user_course item on the copied course.
	 *
	 * The source row stays. A user already enrolled in the copied course is recorded as
	 * a skip so the row is not fetched again.
	 *
	 * @param int $user_item_id Primary key of the learnpress_user_items row.
	 * @throws \Exception If the row or its copied course is not found, or the insert fails.
	 */
	public static function migrate_single_enrollment( int $user_item_id ): void {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, item_id, status, start_time, end_time, ref_id, graduation
				 FROM {$wpdb->prefix}learnpress_user_items
				 WHERE user_item_id = %d AND item_type = 'lp_course'",
				$user_item_id
			)
		);

		if ( ! $row ) {
			throw new \Exception(
				sprintf( 'migrate_single_enrollment: learnpress_user_items row %d not found.', $user_item_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$user_id   = (int) $row->user_id;
		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $row->item_id, 'course' );

		if ( masteriyo_is_user_already_enrolled( $user_id, $course_id ) ) {
			MigrationMap::record( self::SLUG, 'enrollments', $user_item_id, 0 );
			return;
		}

		$status_map   = array(
			'enrolled'    => UserCourseStatus::ACTIVE,
			'in-progress' => UserCourseStatus::ACTIVE,
			'completed'   => UserCourseStatus::ACTIVE,
			'finished'    => UserCourseStatus::ACTIVE,
			'blocked'     => UserCourseStatus::INACTIVE,
		);
		$status       = $status_map[ $row->status ] ?? UserCourseStatus::ACTIVE;
		$date_start   = self::parse_lp_datetime( $row->start_time, current_time( 'mysql', true ) );
		$is_completed = in_array( $row->status, array( 'completed', 'finished' ), true )
			|| in_array( $row->graduation, array( 'passed', 'failed' ), true );
		$date_end     = $is_completed
			? self::parse_lp_datetime( $row->end_time, current_time( 'mysql', true ) )
			: '0000-00-00 00:00:00';

		// The price is read from the LearnPress order; the copied order, when it exists
		// already, is what the enrollment links to. Orders copied later link themselves.
		$source_order_id = (int) $row->ref_id;
		$order_id        = $source_order_id ? MigrationMap::target( self::SLUG, MigrationMap::POST, $source_order_id ) : 0;
		$price           = $source_order_id ? (float) get_post_meta( $source_order_id, '_order_total', true ) : null;
		$new_item_id     = Helper::enroll_user( $user_id, $course_id, $date_start, $date_end, $status, $order_id ? $order_id : null, $price );

		if ( null === $new_item_id ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception(
				sprintf( 'migrate_single_enrollment: DB insert failed for user %d course %d: %s', $user_id, $course_id, $wpdb->last_error )
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		MigrationMap::record( self::SLUG, 'enrollments', $user_item_id, $new_item_id );
	}

	/**
	 * Copy a LearnPress lesson or quiz progress row to masteriyo_user_activities.
	 *
	 * The lesson or quiz is resolved to its copy through the migration map, and the
	 * course from the _course_id meta the courses step wrote on that copy. Writes a
	 * course_progress parent activity if one does not exist, then the item activity.
	 * The source row stays.
	 *
	 * @param int $user_item_id Primary key of the learnpress_user_items row.
	 * @throws \Exception If the row or the copied lesson/quiz is not found.
	 */
	public static function migrate_single_lesson_progress( int $user_item_id ): void {
		global $wpdb;

		$now            = gmdate( 'Y-m-d H:i:s' );
		$activities_tbl = $wpdb->prefix . 'masteriyo_user_activities';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, item_id, item_type, status, start_time, end_time
				 FROM {$wpdb->prefix}learnpress_user_items
				 WHERE user_item_id = %d AND item_type IN ('lp_lesson','lp_quiz')",
				$user_item_id
			)
		);

		if ( ! $row ) {
			throw new \Exception(
				sprintf( 'migrate_single_lesson_progress: learnpress_user_items row %d not found.', $user_item_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$user_id   = (int) $row->user_id;
		$is_quiz   = 'lp_quiz' === $row->item_type;
		$item_id   = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $row->item_id, $is_quiz ? 'quiz' : 'lesson' );
		$course_id = (int) get_post_meta( $item_id, '_course_id', true );

		// Records a skip so the row is not fetched again.
		$skip = function() use ( $user_item_id ): void {
			MigrationMap::record( self::SLUG, 'lesson_progress', $user_item_id, 0 );
		};

		if ( ! $course_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: No _course_id on %s %d (user %d) — skipping.', $row->item_type, $item_id, $user_id ),
				array( 'source' => 'migration-tool' )
			);
			$skip();
			return;
		}

		$activity_type = $is_quiz ? CourseProgressItemType::QUIZ : CourseProgressItemType::LESSON;
		$completed     = in_array( $row->status, array( 'completed', 'passed' ), true );
		$date          = self::parse_lp_datetime( $row->start_time, $now );

		$progress_id = Helper::get_or_create_course_progress( $user_id, $course_id, $date );

		if ( ! $progress_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: Could not get/create course_progress for user %d course %d.', $user_id, $course_id ),
				array( 'source' => 'migration-tool' )
			);
			$skip();
			return;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$activities_tbl}
				 WHERE user_id = %d AND item_id = %d AND activity_type = %s AND parent_id = %d
				 LIMIT 1",
				$user_id,
				$item_id,
				$activity_type,
				$progress_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $existing_id ) {
			MigrationMap::record( self::SLUG, 'lesson_progress', $user_item_id, $existing_id );
			return;
		}

		$activity_status = $completed ? CourseProgressStatus::COMPLETED : CourseProgressStatus::STARTED;
		$completed_at    = $completed
			? self::parse_lp_datetime( $row->end_time, $now )
			: '0000-00-00 00:00:00';

		$inserted = $wpdb->insert(
			$activities_tbl,
			array(
				'user_id'         => $user_id,
				'item_id'         => $item_id,
				'activity_type'   => $activity_type,
				'activity_status' => $activity_status,
				'parent_id'       => $progress_id,
				'created_at'      => $date,
				'modified_at'     => $now,
				'completed_at'    => $completed_at,
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception(
				sprintf( 'migrate_single_lesson_progress: failed to insert activity for row %d.', $user_item_id )
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		MigrationMap::record( self::SLUG, 'lesson_progress', $user_item_id, (int) $wpdb->insert_id );
	}

	/**
	 * Copy a LearnPress order to a new Masteriyo mto-order.
	 *
	 * The lp_order post stays. Items and meta are written on the copy, and enrollments
	 * copied earlier from this order are linked to the copy.
	 *
	 * @param int $source_order_id LearnPress lp_order post ID.
	 * @throws \Exception If the post is not an lp_order post or a course on it has no copy.
	 */
	public static function migrate_single_order( int $source_order_id ): void {
		$post = get_post( $source_order_id );

		if ( ! $post ) {
			throw new \Exception(
				sprintf( 'migrate_single_order: post %d not found.', $source_order_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		if ( 'lp_order' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_order: post %d is not an lp_order post (got %s).', $source_order_id, $post->post_type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		// Resolve every course first, so an order on an uncopied course fails before any write.
		$items = self::get_lp_order_items( $source_order_id );
		foreach ( $items as $item ) {
			$item->course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $item->course_id, 'course' );
		}

		$status_map = array(
			'lp-pending'    => OrderStatus::PENDING,
			'lp-checkout'   => OrderStatus::PENDING,
			'lp-processing' => OrderStatus::PROCESSING,
			'lp-completed'  => OrderStatus::COMPLETED,
			'lp-cancelled'  => OrderStatus::CANCELLED,
			'lp-refunded'   => OrderStatus::REFUNDED,
		);
		$mto_status = $status_map[ $post->post_status ] ?? OrderStatus::PENDING;
		$order_time = strtotime( $post->post_date );
		$title      = __( 'Order', 'learning-management-system' ) . ' &ndash; ' . gmdate( get_option( 'date_format' ), $order_time ) . ' @ ' . gmdate( get_option( 'time_format' ), $order_time );

		$order_id = PostCloner::clone_post(
			self::SLUG,
			$source_order_id,
			array(
				'post_type'     => PostType::ORDER,
				'post_status'   => $mto_status,
				'post_title'    => $title,
				'post_password' => masteriyo_generate_order_key(),
			)
		);

		foreach ( $items as $item ) {
			self::migrate_order_item( $item, $order_id );
		}

		self::migrate_order_meta( $order_id );
		self::link_copied_enrollments( $source_order_id, $order_id );
	}

	/**
	 * Point the Masteriyo enrollments copied from a LearnPress order at the order's copy.
	 *
	 * The enrollments step runs before orders, so at that point the copy did not exist.
	 *
	 * @param int $source_order_id LearnPress lp_order post ID.
	 * @param int $order_id        Copied mto-order post ID.
	 */
	private static function link_copied_enrollments( int $source_order_id, int $order_id ): void {
		global $wpdb;

		$source_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_item_id FROM {$wpdb->prefix}learnpress_user_items WHERE item_type = 'lp_course' AND ref_id = %d",
				$source_order_id
			)
		);

		foreach ( MigrationMap::targets( self::SLUG, 'enrollments', array_map( 'intval', (array) $source_ids ) ) as $user_item_id ) {
			$has_order = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT 1 FROM {$wpdb->prefix}masteriyo_user_itemmeta WHERE user_item_id = %d AND meta_key = '_order_id' LIMIT 1",
					$user_item_id
				)
			);

			if ( ! $has_order ) {
				$wpdb->insert(
					$wpdb->prefix . 'masteriyo_user_itemmeta',
					array(
						'user_item_id' => $user_item_id,
						'meta_key'     => '_order_id',
						'meta_value'   => $order_id,
					)
				);
			}
		}
	}

	/**
	 * Copy a LearnPress course comment or rating to a new Masteriyo course_review on the
	 * copied course. The source comment stays.
	 *
	 * @param int $comment_id WP comment ID.
	 * @throws \Exception If the comment or its copied course does not exist.
	 */
	public static function migrate_single_review( int $comment_id ): void {
		$comment = get_comment( $comment_id );

		if ( ! $comment ) {
			throw new \Exception(
				sprintf( 'migrate_single_review: comment %d not found.', $comment_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $comment->comment_post_ID, 'course' );

		PostCloner::clone_comment(
			self::SLUG,
			$comment_id,
			array(
				'comment_post_ID'  => $course_id,
				'comment_type'     => CommentType::COURSE_REVIEW,
				'comment_approved' => 1,
				'comment_agent'    => 'Masteriyo',
				'comment_karma'    => $comment->comment_karma ? (int) $comment->comment_karma : 0,
				'comment_parent'   => 0,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Build Masteriyo sections from the LearnPress curriculum and copy its lessons and quizzes.
	 *
	 * Sections are read from learnpress_sections and written as new posts, keyed in the
	 * map by the LearnPress section ID. Lessons and quizzes are cloned under the new
	 * section; anything else in a section is skipped with a warning.
	 *
	 * @param int $source_course_id LearnPress course post ID.
	 * @param int $course_id        Copied mto-course post ID.
	 */
	private static function process_course_curriculum( int $source_course_id, int $course_id ): void {
		global $wpdb;

		$sections = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT section_id, section_name, section_description
				 FROM {$wpdb->prefix}learnpress_sections
				 WHERE section_course_id = %d
				 ORDER BY section_order ASC, section_id ASC",
				$source_course_id
			)
		);

		$author        = (int) get_post_field( 'post_author', $course_id );
		$section_order = 0;

		foreach ( (array) $sections as $section ) {
			++$section_order;

			$lp_section_id = (int) $section->section_id;
			$section_id    = MigrationMap::target( self::SLUG, 'sections', $lp_section_id );

			if ( ! $section_id || ! get_post( $section_id ) ) {
				$section_id = wp_insert_post(
					array(
						'post_type'    => PostType::SECTION,
						'post_title'   => (string) $section->section_name,
						'post_content' => (string) $section->section_description,
						'post_status'  => PostStatus::PUBLISH,
						'post_author'  => $author,
						'post_parent'  => $course_id,
						'menu_order'   => $section_order,
					),
					true
				);

				if ( ! $section_id || is_wp_error( $section_id ) ) {
					masteriyo_get_logger()->error( 'Migration: Failed to insert section post.', array( 'source' => 'migration-tool' ) );
					continue;
				}

				MigrationMap::record( self::SLUG, 'sections', $lp_section_id, $section_id );
			} else {
				// A section kept from a deleted course copy follows the course's new copy.
				PostCloner::place(
					$section_id,
					array(
						'post_parent' => $course_id,
						'menu_order'  => $section_order,
					)
				);
			}
			update_post_meta( $section_id, '_course_id', $course_id );

			$items = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT item_id FROM {$wpdb->prefix}learnpress_section_items
					 WHERE section_id = %d
					 ORDER BY item_order ASC, section_item_id ASC",
					$lp_section_id
				)
			);

			$item_order = 0;
			foreach ( (array) $items as $item ) {
				++$item_order;

				$source_item_id = (int) $item->item_id;
				$raw_type       = get_post_type( $source_item_id );

				if ( 'lp_quiz' === $raw_type ) {
					$mto_type = PostType::QUIZ;
				} elseif ( 'lp_lesson' === $raw_type ) {
					$mto_type = PostType::LESSON;
				} else {
					masteriyo_get_logger()->warning(
						sprintf( 'Migration: Curriculum item %d of course %d is a %s, not a lesson or quiz — skipping.', $source_item_id, $source_course_id, (string) $raw_type ),
						array( 'source' => 'migration-tool' )
					);
					continue;
				}

				// An item reused in several courses has one copy, kept by the first course.
				if ( Helper::copy_kept_by_another_course( self::SLUG, 'LearnPress', $raw_type, $source_item_id, $course_id ) ) {
					continue;
				}

				$item_id = PostCloner::clone_post(
					self::SLUG,
					$source_item_id,
					array(
						'post_type'   => $mto_type,
						'post_parent' => $section_id,
						'menu_order'  => $item_order,
					)
				);
				update_post_meta( $item_id, '_course_id', $course_id );

				if ( PostType::LESSON === $mto_type ) {
					update_post_meta( $item_id, '_duration', self::parse_lp_duration( $item_id ) );
					self::migrate_single_lesson_preview( $item_id );
				} else {
					self::migrate_quiz_questions( $source_item_id, $item_id, $course_id );
					self::migrate_single_quiz_meta( $item_id );
				}
			}
		}
	}

	/**
	 * Copy the questions of a LearnPress quiz as new mto-question posts under the copied quiz.
	 *
	 * @param int $source_quiz_id LearnPress quiz post ID.
	 * @param int $quiz_id        Copied mto-quiz post ID.
	 * @param int $course_id      Copied mto-course post ID.
	 */
	private static function migrate_quiz_questions( int $source_quiz_id, int $quiz_id, int $course_id ): void {
		global $wpdb;

		$questions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT question_id, questions.post_content, questions.post_author,
					questions.post_status, questions.post_title,
					question_type_meta.meta_value as question_type,
					question_mark_meta.meta_value as question_mark
				FROM {$wpdb->prefix}learnpress_quiz_questions
				LEFT JOIN {$wpdb->posts} questions ON question_id = questions.ID
				LEFT JOIN {$wpdb->postmeta} question_type_meta
					ON question_id = question_type_meta.post_id AND question_type_meta.meta_key = '_lp_type'
				LEFT JOIN {$wpdb->postmeta} question_mark_meta
					ON question_id = question_mark_meta.post_id AND question_mark_meta.meta_key = '_lp_mark'
				WHERE quiz_id = %d",
				$source_quiz_id
			)
		);

		$type_map = array(
			'true_or_false' => QuestionType::TRUE_FALSE,
			'single_choice' => QuestionType::SINGLE_CHOICE,
			'multi_choice'  => QuestionType::MULTIPLE_CHOICE,
		);

		foreach ( $questions as $question ) {
			$lp_question_id = (int) $question->question_id;

			// A LearnPress question can sit in several quizzes; one copy per quiz.
			$existing = get_posts(
				array(
					'post_type'      => PostType::QUESTION,
					'post_parent'    => $quiz_id,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_lp_question_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'meta_value'     => $lp_question_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				)
			);
			if ( ! empty( $existing ) ) {
				// A reused quiz copy keeps its questions; they follow it to the new course.
				update_post_meta( (int) $existing[0], '_course_id', $course_id );
				continue;
			}

			// LP stores blanks as [fib fill="..." id="..."] shortcodes.
			if ( 'fill_in_blanks' === $question->question_type ) {
				$fib_row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT title FROM {$wpdb->prefix}learnpress_question_answers WHERE question_id = %d LIMIT 1",
						$lp_question_id
					)
				);

				if ( ! $fib_row ) {
					continue;
				}

				preg_match_all( '#\[fib fill="([^"]+)" id="[^"]+" \]#', $fib_row->title, $matches );
				$fill_values = $matches[1];
				$fib_title   = preg_replace( '#\[fib fill="[^"]+" id="[^"]+" \]#', '{blank}', $fib_row->title );
				$fib_answers = array();
				foreach ( $fill_values as $val ) {
					$fib_answers[] = array(
						'name'    => $val,
						'correct' => true,
					);
				}

				$question_id = wp_insert_post(
					array(
						'post_type'    => PostType::QUESTION,
						'post_title'   => $fib_title,
						'post_content' => wp_json_encode( $fib_answers ),
						'post_status'  => PostStatus::PUBLISH,
						'post_author'  => $question->post_author,
						'post_parent'  => $quiz_id,
					)
				);

				if ( ! $question_id || is_wp_error( $question_id ) ) {
					masteriyo_get_logger()->error( 'Migration: Failed to insert LearnPress FIB question post.', array( 'source' => 'migration-tool' ) );
					continue;
				}

				update_post_meta( $question_id, '_course_id', $course_id );
				update_post_meta( $question_id, '_type', QuestionType::FILL_IN_THE_BLANKS );
				update_post_meta( $question_id, '_points', $question->question_mark );
				update_post_meta( $question_id, '_parent_id', $quiz_id );
				update_post_meta( $question_id, '_lp_question_id', $lp_question_id );

				$explanation = get_post_meta( $lp_question_id, '_lp_explanation', true );
				if ( $explanation ) {
					update_post_meta( $question_id, '_answer_explanation', $explanation );
				}

				MigrationMap::record( self::SLUG, MigrationMap::POST, $lp_question_id, $question_id );
				continue;
			}

			$question_type = $type_map[ $question->question_type ] ?? null;

			if ( ! $question_type ) {
				continue;
			}

			$answers              = self::get_question_answers( $lp_question_id );
			$question_description = sanitize_text_field( $question->post_content );

			$question_id = wp_insert_post(
				array(
					'post_type'    => PostType::QUESTION,
					'post_title'   => $question->post_title,
					'post_content' => wp_json_encode( $answers ),
					'post_excerpt' => $question_description,
					'post_status'  => PostStatus::PUBLISH,
					'post_author'  => $question->post_author,
					'post_parent'  => $quiz_id,
				)
			);

			if ( ! $question_id || is_wp_error( $question_id ) ) {
				masteriyo_get_logger()->error( 'Migration: Failed to insert LearnPress question post.', array( 'source' => 'migration-tool' ) );
				continue;
			}

			update_post_meta( $question_id, '_course_id', $course_id );
			update_post_meta( $question_id, '_type', $question_type );
			update_post_meta( $question_id, '_points', $question->question_mark );
			update_post_meta( $question_id, '_parent_id', $quiz_id );
			update_post_meta( $question_id, '_lp_question_id', $lp_question_id );

			if ( $question_description ) {
				update_post_meta( $question_id, '_enable_description', true );
			}

			$explanation = get_post_meta( $lp_question_id, '_lp_explanation', true );
			if ( $explanation ) {
				update_post_meta( $question_id, '_answer_explanation', $explanation );
			}

			MigrationMap::record( self::SLUG, MigrationMap::POST, $lp_question_id, $question_id );
		}
	}

	/**
	 * Fetch formatted answers for a LearnPress question.
	 *
	 * @param int $question_id LearnPress question post ID.
	 * @return array<int, array{name: string, correct: bool}>
	 */
	private static function get_question_answers( int $question_id ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT title, is_true FROM {$wpdb->prefix}learnpress_question_answers WHERE question_id = %d",
				$question_id
			),
			ARRAY_A
		);

		$answers = array();
		foreach ( $rows as $row ) {
			$answers[] = array(
				'name'    => $row['title'],
				'correct' => 'yes' === $row['is_true'],
			);
		}

		return $answers;
	}

	/**
	 * Convert the copied course's LearnPress meta to Masteriyo meta.
	 *
	 * The clone carries the source's meta and terms, so everything is read from and
	 * written to the copy; the thumbnail arrived with the meta.
	 *
	 * @param int $source_course_id LearnPress course post ID.
	 * @param int $course_id        Copied mto-course post ID.
	 */
	private static function update_course_meta( int $source_course_id, int $course_id ): void {
		$_lp_price              = floatval( get_post_meta( $course_id, '_lp_regular_price', true ) );
		$_lp_sale_price         = floatval( get_post_meta( $course_id, '_lp_sale_price', true ) );
		$_lp_max_students       = (int) get_post_meta( $course_id, '_lp_max_students', true );
		$_lp_no_required_enroll = get_post_meta( $course_id, '_lp_no_required_enroll', true );
		$_lp_sale_start         = get_post_meta( $course_id, '_lp_sale_start', true );
		$_lp_sale_end           = get_post_meta( $course_id, '_lp_sale_end', true );
		$_lp_level              = get_post_meta( $course_id, '_lp_level', true );
		$_lp_retake_count       = absint( get_post_meta( $course_id, '_lp_retake_count', true ) );

		$price_type = ( $_lp_price > 0 ) ? 'paid' : 'free';
		wp_set_object_terms( $course_id, $price_type, 'course_visibility', false );

		update_post_meta( $course_id, '_was_lp_course', true );
		update_post_meta( $course_id, '_price', $_lp_price );
		update_post_meta( $course_id, '_regular_price', $_lp_price );

		if ( $_lp_sale_price > 0 ) {
			update_post_meta( $course_id, '_sale_price', $_lp_sale_price );
		}

		update_post_meta( $course_id, '_duration', self::parse_lp_duration( $course_id ) );
		update_post_meta( $course_id, '_enrollment_limit', $_lp_max_students );
		update_post_meta( $course_id, '_show_curriculum', true );

		if ( ! empty( $_lp_sale_start ) ) {
			update_post_meta( $course_id, '_date_on_sale_from', $_lp_sale_start );
		}
		if ( ! empty( $_lp_sale_end ) ) {
			update_post_meta( $course_id, '_date_on_sale_to', $_lp_sale_end );
		}

		if ( 'yes' === $_lp_no_required_enroll ) {
			update_post_meta( $course_id, '_access_mode', 'open' );
		} elseif ( 'paid' === $price_type ) {
			update_post_meta( $course_id, '_access_mode', 'one_time' );
		} else {
			update_post_meta( $course_id, '_access_mode', 'need_registration' );
		}

		if ( $_lp_retake_count > 0 ) {
			update_post_meta( $course_id, '_enable_course_retake', 1 );
		}

		Helper::set_course_difficulty( $course_id, $_lp_level );
		Helper::migrate_course_categories_from_to_masteriyo( $course_id );
		Helper::migrate_course_author( $course_id );

		$key_features = maybe_unserialize( get_post_meta( $course_id, '_lp_key_features', true ) );
		if ( is_array( $key_features ) && ! empty( $key_features ) ) {
			$highlights = '';
			foreach ( $key_features as $feature ) {
				$highlights .= "<li>{$feature}</li>";
			}
			update_post_meta( $course_id, '_highlights', $highlights );
		}

		self::migrate_single_course_faqs( $source_course_id, $course_id );
	}

	/**
	 * Get LearnPress order items with the source course each one is for.
	 *
	 * @param int $order_id LearnPress order post ID.
	 * @return array Order item objects (id, name, course_id).
	 */
	private static function get_lp_order_items( int $order_id ): array {
		global $wpdb;

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT oi.order_item_id as id, oi.order_item_name as name,
					oim.meta_value as course_id
				FROM {$wpdb->prefix}learnpress_order_items oi
				INNER JOIN {$wpdb->prefix}learnpress_order_itemmeta oim
					ON oi.order_item_id = oim.learnpress_order_item_id AND oim.meta_key = '_course_id'
				WHERE oi.order_id = %d",
				$order_id
			)
		);
	}

	/**
	 * Insert one LP order item into masteriyo_order_items and copy its meta.
	 *
	 * @param object $lp_item  LP order item object (id, name, course_id); course_id is the copied course.
	 * @param int    $order_id Copied mto-order post ID.
	 */
	private static function migrate_order_item( object $lp_item, int $order_id ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'masteriyo_order_items',
			array(
				'order_item_name' => $lp_item->name,
				'order_item_type' => 'course',
				'order_id'        => $order_id,
			)
		);
		$order_item_id = absint( $wpdb->insert_id );

		$lp_metas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$wpdb->prefix}learnpress_order_itemmeta WHERE learnpress_order_item_id = %d",
				$lp_item->id
			)
		);

		$meta_map = array();
		foreach ( $lp_metas as $m ) {
			$meta_map[ $m->meta_key ] = $m->meta_value;
		}

		$metas = array(
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'course_id',
				'meta_value'    => (int) $lp_item->course_id,
			),
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'quantity',
				'meta_value'    => masteriyo_array_get( $meta_map, '_quantity', 1 ),
			),
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'subtotal',
				'meta_value'    => masteriyo_array_get( $meta_map, '_subtotal', 0 ),
			),
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'total',
				'meta_value'    => masteriyo_array_get( $meta_map, '_total', 0 ),
			),
		);

		foreach ( $metas as $meta ) {
			$wpdb->insert( $wpdb->prefix . 'masteriyo_order_itemmeta', $meta );
		}
	}

	/**
	 * Copy LP order postmeta to Masteriyo postmeta keys.
	 *
	 * @param int $order_id Order post ID.
	 */
	private static function migrate_order_meta( int $order_id ): void {
		global $wpdb;

		$customer_id = get_post_meta( $order_id, '_user_id', true );

		update_post_meta( $order_id, '_customer_id', $customer_id );
		update_post_meta( $order_id, '_customer_ip_address', get_post_meta( $order_id, '_user_ip_address', true ) );
		update_post_meta( $order_id, '_customer_user_agent', get_post_meta( $order_id, '_user_agent', true ) );
		update_post_meta( $order_id, '_total', get_post_meta( $order_id, '_order_total', true ) );
		update_post_meta( $order_id, '_currency', get_post_meta( $order_id, '_order_currency', true ) );
		update_post_meta( $order_id, '_version', get_post_meta( $order_id, '_order_version', true ) );
		update_post_meta( $order_id, '_was_lp_order', true );

		$user_email = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM {$wpdb->users} WHERE ID = %d", $customer_id ) );
		update_post_meta( $order_id, '_billing_address_index', $user_email );
		update_post_meta( $order_id, '_billing_email', $user_email );

		// Payment method and checkout email.
		$payment_method = get_post_meta( $order_id, '_payment_method', true );
		if ( $payment_method ) {
			update_post_meta( $order_id, '_payment_method', $payment_method );
			update_post_meta( $order_id, '_payment_method_title', get_post_meta( $order_id, '_payment_method_title', true ) );
		}

		$checkout_email = get_post_meta( $order_id, '_checkout_email', true );
		if ( $checkout_email ) {
			update_post_meta( $order_id, '_billing_email', $checkout_email );
			update_post_meta( $order_id, '_billing_address_index', $checkout_email );
		}
	}

	/**
	 * Convert a LearnPress DATETIME string to a MySQL DATETIME string.
	 *
	 * Returns $fallback if the value is empty, NULL, or the MySQL zero datetime
	 * ('0000-00-00 00:00:00'), which LP uses as "not set".
	 *
	 * @param string|null $value    Raw value from the learnpress_user_items table.
	 * @param string      $fallback Value to return when $value is absent or zero.
	 * @return string
	 */
	private static function parse_lp_datetime( $value, string $fallback ): string {
		if ( ! $value || '0000-00-00 00:00:00' === $value ) {
			return $fallback;
		}
		$ts = strtotime( $value );
		return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : $fallback;
	}

	/**
	 * Convert the _lp_duration meta string to minutes.
	 *
	 * @param int $post_id Post ID with _lp_duration meta.
	 * @return int Duration in minutes, 0 if not parseable.
	 */
	private static function parse_lp_duration( int $post_id ): int {
		$parts = explode( ' ', get_post_meta( $post_id, '_lp_duration', true ) );

		if ( count( $parts ) < 2 ) {
			return 0;
		}

		$n    = absint( $parts[0] );
		$unit = strtolower( $parts[1] );

		switch ( $unit ) {
			case 'minute':
			case 'minutes':
				return $n;
			case 'hour':
			case 'hours':
				return $n * 60;
			case 'day':
			case 'days':
				return $n * 1440;
			case 'week':
			case 'weeks':
				return $n * 10080;
		}

		return 0;
	}

	/**
	 * Bulk-update course_progress status once all lesson_progress items are migrated.
	 * Replaces the per-item recount queries — runs once after the step completes.
	 *
	 * @param string $step Step name.
	 */
	public static function finalize_step( string $step ): void {
		if ( 'lesson_progress' !== $step ) {
			return;
		}

		global $wpdb;
		$tbl = $wpdb->prefix . 'masteriyo_user_activities';
		// Only this run's copies: a course the site made natively keeps its own dates.
		$copied = MigrationMap::targets_sql( self::SLUG, MigrationMap::POST );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"UPDATE {$tbl} AS parent
			 INNER JOIN (
			     SELECT CAST( pm.meta_value AS UNSIGNED ) AS course_id,
			            COUNT( DISTINCT p.ID )            AS curriculum_total
			     FROM   {$wpdb->posts}    AS p
			     INNER  JOIN {$wpdb->postmeta} AS pm
			            ON  p.ID = pm.post_id AND pm.meta_key = '_course_id'
			     WHERE  p.post_type IN ( 'mto-lesson', 'mto-quiz' )
			     GROUP  BY pm.meta_value
			 ) AS curriculum ON parent.item_id = curriculum.course_id
			 INNER JOIN (
			     SELECT parent_id,
			            COUNT( DISTINCT item_id ) AS done
			     FROM   {$tbl}
			     WHERE  activity_type IN ( 'lesson', 'quiz' )
			       AND  activity_status = 'completed'
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
			   AND parent.item_id IN ( {$copied} )"
		);
		// phpcs:enable
	}

	/**
	 * Copy LearnPress quiz-level settings to Masteriyo postmeta on the copied quiz.
	 *
	 * @param int $quiz_id Copied mto-quiz post ID.
	 */
	private static function migrate_single_quiz_meta( int $quiz_id ): void {
		$pass_mark = get_post_meta( $quiz_id, '_lp_passing_grade', true );
		if ( '' !== $pass_mark ) {
			update_post_meta( $quiz_id, '_pass_mark', absint( $pass_mark ) );
		}

		$duration_mins = self::parse_lp_duration( $quiz_id );
		if ( $duration_mins > 0 ) {
			update_post_meta( $quiz_id, '_duration', $duration_mins * 60 );
		}

		$attempts = get_post_meta( $quiz_id, '_lp_retake_count', true );
		if ( '' !== $attempts ) {
			update_post_meta( $quiz_id, '_attempts_allowed', absint( $attempts ) );
		}

		$pagination = get_post_meta( $quiz_id, '_lp_pagination', true );
		if ( '' !== $pagination ) {
			update_post_meta( $quiz_id, '_questions_display_per_page', absint( $pagination ) );
		}

		$review = get_post_meta( $quiz_id, '_lp_review', true );
		if ( '' !== $review ) {
			update_post_meta( $quiz_id, '_reveal_mode', 'yes' === $review ? 1 : 0 );
		}
	}

	/**
	 * Copy one LearnPress quiz result row to masteriyo_quiz_attempts against the copied quiz.
	 *
	 * The source row stays. A result already present for the same quiz, user and start
	 * time is recorded as a skip.
	 *
	 * @param int $result_id Primary key of the learnpress_user_item_results row.
	 * @throws \Exception If the row or the copied quiz is not found, or the insert fails.
	 */
	private static function migrate_single_quiz_result( int $result_id ): void {
		global $wpdb;

		$attempts_tbl = $wpdb->prefix . 'masteriyo_quiz_attempts';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT lur.result, lui.user_id, lui.item_id AS quiz_id, lui.start_time, lui.end_time
				 FROM {$wpdb->prefix}learnpress_user_item_results lur
				 INNER JOIN {$wpdb->prefix}learnpress_user_items lui
				     ON lur.user_item_id = lui.user_item_id
				 WHERE lur.id = %d AND lui.item_type = 'lp_quiz'",
				$result_id
			)
		);

		if ( ! $row ) {
			throw new \Exception(
				sprintf( 'migrate_single_quiz_result: result row %d not found.', $result_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$user_id   = (int) $row->user_id;
		$quiz_id   = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $row->quiz_id, 'quiz' );
		$course_id = (int) get_post_meta( $quiz_id, '_course_id', true );

		// Records a skip so the row is not fetched again.
		$skip = function() use ( $result_id ): void {
			MigrationMap::record( self::SLUG, 'quiz_results', $result_id, 0 );
		};

		if ( ! $course_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: No _course_id on quiz %d (user %d) — skipping quiz result.', $quiz_id, $user_id ),
				array( 'source' => 'migration-tool' )
			);
			$skip();
			return;
		}

		$data         = json_decode( $row->result, true );
		$total_marks  = isset( $data['mark']['total'] ) ? floatval( $data['mark']['total'] ) : 0.0;
		$earned_marks = isset( $data['mark']['mark'] ) ? floatval( $data['mark']['mark'] ) : 0.0;
		$total_q      = isset( $data['question']['total'] ) ? absint( $data['question']['total'] ) : 0;
		$answered_q   = isset( $data['question']['completed'] ) ? absint( $data['question']['completed'] ) : 0;
		$correct_q    = isset( $data['question']['passed'] ) ? absint( $data['question']['passed'] ) : 0;
		$incorrect_q  = isset( $data['question']['failed'] ) ? absint( $data['question']['failed'] ) : 0;
		$now          = gmdate( 'Y-m-d H:i:s' );
		$started_at   = self::parse_lp_datetime( $row->start_time, $now );
		$ended_at     = self::parse_lp_datetime( $row->end_time, $now );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$already = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$attempts_tbl} WHERE quiz_id = %d AND user_id = %d AND attempt_started_at = %s",
				$quiz_id,
				$user_id,
				$started_at
			)
		);

		if ( $already ) {
			$skip();
			return;
		}

		$total_attempts_seq = 1 + (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$attempts_tbl} WHERE quiz_id = %d AND user_id = %d",
				$quiz_id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$inserted = $wpdb->insert(
			$attempts_tbl,
			array(
				'course_id'                => $course_id,
				'quiz_id'                  => $quiz_id,
				'user_id'                  => $user_id,
				'total_questions'          => $total_q,
				'total_answered_questions' => $answered_q,
				'total_marks'              => $total_marks,
				'total_attempts'           => $total_attempts_seq,
				'total_correct_answers'    => $correct_q,
				'total_incorrect_answers'  => $incorrect_q,
				'earned_marks'             => $earned_marks,
				'answers'                  => null,
				'attempt_status'           => 'attempt_ended',
				'attempt_started_at'       => $started_at,
				'attempt_ended_at'         => $ended_at,
			),
			array( '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception(
				sprintf( 'migrate_single_quiz_result: failed to insert attempt for result %d.', $result_id )
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		MigrationMap::record( self::SLUG, 'quiz_results', $result_id, (int) $wpdb->insert_id );
	}

	/**
	 * Enable preview on a copied lesson whose LearnPress meta has _lp_preview = 'yes'.
	 *
	 * The copy carries the source's meta, which stays as it is on both.
	 *
	 * @param int $lesson_id Copied mto-lesson post ID.
	 */
	private static function migrate_single_lesson_preview( int $lesson_id ): void {
		if ( 'yes' === get_post_meta( $lesson_id, '_lp_preview', true ) ) {
			update_post_meta( $lesson_id, '_enable_preview', 1 );
		}
	}

	/**
	 * Copy a course's _lp_faqs to mto_course_faq comments on the copied course.
	 *
	 * Each FAQ entry becomes one wp_comments row (comment_type = mto_course_faq) with
	 * the answer in comment_content and the question in _title commentmeta. The source
	 * meta stays; the course is recorded under 'course_faqs' so a second run skips it.
	 *
	 * @param int $source_course_id LearnPress course post ID.
	 * @param int $course_id        Copied mto-course post ID.
	 */
	private static function migrate_single_course_faqs( int $source_course_id, int $course_id ): void {
		if ( MigrationMap::is_mapped( self::SLUG, 'course_faqs', $source_course_id ) ) {
			return;
		}

		$faqs = maybe_unserialize( get_post_meta( $source_course_id, '_lp_faqs', true ) );

		if ( ! is_array( $faqs ) || empty( $faqs ) ) {
			MigrationMap::record( self::SLUG, 'course_faqs', $source_course_id, $course_id );
			return;
		}

		$now        = gmdate( 'Y-m-d H:i:s' );
		$menu_order = 0;

		foreach ( $faqs as $faq ) {
			$question = isset( $faq['question'] ) ? sanitize_text_field( $faq['question'] ) : '';
			$answer   = isset( $faq['answer'] ) ? wp_kses_post( $faq['answer'] ) : '';

			if ( ! $question && ! $answer ) {
				continue;
			}

			$comment_id = wp_insert_comment(
				array(
					'comment_post_ID'  => $course_id,
					'comment_content'  => $answer,
					'comment_karma'    => $menu_order,
					'comment_approved' => CommentStatus::APPROVE,
					'comment_type'     => 'mto_course_faq',
					'comment_parent'   => 0,
					'comment_date'     => $now,
					'comment_date_gmt' => $now,
				)
			);

			if ( $comment_id && ! is_wp_error( $comment_id ) ) {
				add_comment_meta( $comment_id, '_title', $question );
			}

			++$menu_order;
		}

		MigrationMap::record( self::SLUG, 'course_faqs', $source_course_id, $course_id );
	}
}
