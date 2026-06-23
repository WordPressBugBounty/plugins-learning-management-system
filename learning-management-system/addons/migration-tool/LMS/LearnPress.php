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

	// -------------------------------------------------------------------------
	// Batch API — count / paginate / dispatch
	// -------------------------------------------------------------------------

	/**
	 * Return the total number of source items for a migration step.
	 *
	 * @since x.x.x
	 *
	 * @param string $step Step name.
	 * @return int
	 */
	public static function count_source_items( string $step ): int {
		global $wpdb;

		switch ( $step ) {
			case 'users':
				$capabilities_key = $wpdb->get_blog_prefix() . 'capabilities';
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM (
							SELECT DISTINCT u.ID
							FROM {$wpdb->users} u
							INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
							WHERE um.meta_key = %s AND um.meta_value LIKE %s
							UNION
							SELECT DISTINCT user_id FROM {$wpdb->prefix}learnpress_user_items
							WHERE item_type = 'lp_course'
						) AS lp_users",
						$capabilities_key,
						'%lp_teacher%'
					)
				);

			case 'courses':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'lp_course'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'enrollments':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}learnpress_user_items WHERE item_type = 'lp_course'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'lesson_progress':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}learnpress_user_items WHERE item_type IN ('lp_lesson','lp_quiz')" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'orders':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'lp_order'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'reviews':
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(c.comment_ID)
						 FROM {$wpdb->comments} c
						 INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
						 WHERE (
						     p.post_type = 'lp_course'
						     OR (
						         p.post_type = %s
						         AND EXISTS (
						             SELECT 1 FROM {$wpdb->postmeta}
						             WHERE post_id = p.ID AND meta_key = '_was_lp_course'
						         )
						     )
						 )
						   AND c.comment_type IN ('course_rate', 'comment', '')
						   AND c.comment_type != %s",
						PostType::COURSE,
						CommentType::COURSE_REVIEW
					)
				);

			case 'quiz_results':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}learnpress_user_item_results lur
					 INNER JOIN {$wpdb->prefix}learnpress_user_items lui
					     ON lur.user_item_id = lui.user_item_id
					 WHERE lui.item_type = 'lp_quiz'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);
		}

		return 0;
	}

	/**
	 * Return one batch of source IDs starting after $cursor.
	 *
	 * Self-cleaning steps ignore $cursor — processed rows vanish from the WHERE clause
	 * automatically. The users step uses WHERE user_id > $cursor because role removal
	 * does not remove the user from the UNION result set.
	 *
	 * @since x.x.x
	 *
	 * @param string $step   Step name.
	 * @param int    $limit  Batch size.
	 * @param int    $cursor Last processed ID (0 = first batch).
	 * @return int[]
	 */
	public static function get_source_ids( string $step, int $limit, int $cursor, array $exclude = array() ): array {
		global $wpdb;

		// NOT IN for ID-column steps.
		$not_in      = '';
		$not_in_args = array();
		if ( ! empty( $exclude ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
			$not_in       = "AND ID NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$not_in_args  = array_map( 'intval', $exclude );
		}

		// NOT IN for user_item_id-column steps.
		$ui_not_in      = '';
		$ui_not_in_args = array();
		if ( ! empty( $exclude ) ) {
			$placeholders   = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
			$ui_not_in      = "AND user_item_id NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$ui_not_in_args = array_map( 'intval', $exclude );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		switch ( $step ) {
			case 'users':
				// Cursor-based — user rows persist after role assignment; $exclude ignored.
				$capabilities_key = $wpdb->get_blog_prefix() . 'capabilities';
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
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'lp_course' {$not_in} ORDER BY ID ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'enrollments':
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_item_id FROM {$wpdb->prefix}learnpress_user_items
						 WHERE item_type = 'lp_course'
						 {$ui_not_in}
						 ORDER BY user_item_id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $ui_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'lesson_progress':
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_item_id FROM {$wpdb->prefix}learnpress_user_items
						 WHERE item_type IN ('lp_lesson','lp_quiz')
						 {$ui_not_in}
						 ORDER BY user_item_id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $ui_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'orders':
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'lp_order' {$not_in} ORDER BY ID ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'reviews':
				$rev_not_in      = '';
				$rev_not_in_args = array();
				if ( ! empty( $exclude ) ) {
					$placeholders    = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
					$rev_not_in      = "AND c.comment_ID NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$rev_not_in_args = array_map( 'intval', $exclude );
				}
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT c.comment_ID
						 FROM {$wpdb->comments} c
						 INNER JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
						 WHERE (
						     p.post_type = 'lp_course'
						     OR (
						         p.post_type = %s
						         AND EXISTS (
						             SELECT 1 FROM {$wpdb->postmeta}
						             WHERE post_id = p.ID AND meta_key = '_was_lp_course'
						         )
						     )
						 )
						   AND c.comment_type IN ('course_rate', 'comment', '')
						   AND c.comment_type != %s
						   {$rev_not_in}
						 ORDER BY c.comment_ID ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( array( PostType::COURSE, CommentType::COURSE_REVIEW ), $rev_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'quiz_results':
				$qr_not_in      = '';
				$qr_not_in_args = array();
				if ( ! empty( $exclude ) ) {
					$placeholders   = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
					$qr_not_in      = "AND lur.id NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$qr_not_in_args = array_map( 'intval', $exclude );
				}
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT lur.id
						 FROM {$wpdb->prefix}learnpress_user_item_results lur
						 INNER JOIN {$wpdb->prefix}learnpress_user_items lui
						     ON lur.user_item_id = lui.user_item_id
						 WHERE lui.item_type = 'lp_quiz'
						 {$qr_not_in}
						 ORDER BY lur.id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $qr_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array();
	}

	/**
	 * Dispatch a single-item migration to the appropriate migrate_single_*() method.
	 *
	 * Called by MigrationProcessJob inside a START TRANSACTION / COMMIT wrapper.
	 * Must be idempotent — safe to call twice for the same (step, item_id) pair.
	 *
	 * @since x.x.x
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
	 * Assign Masteriyo roles to a single LearnPress user.
	 *
	 * lp_teacher → Masteriyo instructor; enrolled students receive the student role.
	 *
	 * @since x.x.x
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

		if ( in_array( 'lp_teacher', $roles, true ) || isset( $caps['lp_teacher'] ) ) {
			if ( ! $is_admin ) {
				$user->add_role( Roles::INSTRUCTOR );
			}
			$user->remove_role( 'lp_teacher' );
			$user->remove_cap( 'lp_teacher' );
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
	}

	/**
	 * Migrate a single LearnPress course to a Masteriyo mto-course.
	 *
	 * Renames lp_lesson → mto-lesson and lp_quiz → mto-quiz in-place, creates
	 * sections, migrates questions, and updates all course meta. Idempotent.
	 *
	 * @since x.x.x
	 *
	 * @param int $course_id LearnPress lp_course post ID.
	 * @throws \Exception If the post does not exist or is not an lp_course post.
	 */
	public static function migrate_single_course( int $course_id ): void {
		$post = get_post( $course_id );

		if ( ! $post ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d not found.', $course_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		if ( PostType::COURSE === $post->post_type ) {
			return; // Already migrated.
		}

		if ( 'lp_course' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d is not an lp_course post (got %s).', $course_id, $post->post_type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		self::process_course_curriculum( $course_id );
		self::update_course_meta( $course_id );
	}

	/**
	 * Migrate a single LearnPress enrollment to a Masteriyo user_course item.
	 *
	 * Operates on one learnpress_user_items row (item_type = 'lp_course').
	 * Idempotent: skips insert if the user is already enrolled in Masteriyo.
	 * Deletes the source row after successful migration.
	 *
	 * @since x.x.x
	 *
	 * @param int $user_item_id Primary key of the learnpress_user_items row.
	 * @throws \Exception If the row is not found or the DB insert fails.
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
		$course_id = (int) $row->item_id;

		if ( masteriyo_is_user_already_enrolled( $user_id, $course_id ) ) {
			$wpdb->delete( $wpdb->prefix . 'learnpress_user_items', array( 'user_item_id' => $user_item_id ), array( '%d' ) );
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

		$order_id    = (int) $row->ref_id;
		$price       = $order_id ? (float) get_post_meta( $order_id, '_order_total', true ) : null;
		$new_item_id = Helper::enroll_user( $user_id, $course_id, $date_start, $date_end, $status, $order_id ? $order_id : null, $price );

		if ( null === $new_item_id ) {
			masteriyo_get_logger()->error(
				sprintf(
					'Migration: DB insert failed for enrollment user %d course %d: %s',
					$user_id,
					$course_id,
					$wpdb->last_error
				),
				array( 'source' => 'migration-tool' )
			);
			$wpdb->delete( $wpdb->prefix . 'learnpress_user_items', array( 'user_item_id' => $user_item_id ), array( '%d' ) );
			return;
		}

		$wpdb->delete( $wpdb->prefix . 'learnpress_user_items', array( 'user_item_id' => $user_item_id ), array( '%d' ) );
	}

	/**
	 * Migrate a single LearnPress lesson or quiz progress row to masteriyo_user_activities.
	 *
	 * Gets course_id from the _course_id postmeta on the lesson/quiz post (set during
	 * the courses step). Writes a course_progress parent activity if one does not exist,
	 * then writes the lesson/quiz activity. Recounts completed items to keep
	 * course_progress status accurate. Deletes the source row after migrating.
	 *
	 * @since x.x.x
	 *
	 * @param int $user_item_id Primary key of the learnpress_user_items row.
	 * @throws \Exception If the row is not found.
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
		$item_id   = (int) $row->item_id;
		$course_id = (int) get_post_meta( $item_id, '_course_id', true );

		if ( ! $course_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: No _course_id on %s %d (user %d) — skipping.', $row->item_type, $item_id, $user_id ),
				array( 'source' => 'migration-tool' )
			);
			$wpdb->delete( $wpdb->prefix . 'learnpress_user_items', array( 'user_item_id' => $user_item_id ), array( '%d' ) );
			return;
		}

		$activity_type = ( 'lp_quiz' === $row->item_type ) ? CourseProgressItemType::QUIZ : CourseProgressItemType::LESSON;
		$completed     = in_array( $row->status, array( 'completed', 'passed' ), true );
		$date          = self::parse_lp_datetime( $row->start_time, $now );

		// Find or create the course_progress parent activity.
		$progress_id = Helper::get_or_create_course_progress( $user_id, $course_id, $date );

		if ( ! $progress_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: Could not get/create course_progress for user %d course %d.', $user_id, $course_id ),
				array( 'source' => 'migration-tool' )
			);
			$wpdb->delete( $wpdb->prefix . 'learnpress_user_items', array( 'user_item_id' => $user_item_id ), array( '%d' ) );
			return;
		}

		// Idempotency: skip if activity already exists.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$already_exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$activities_tbl}
				 WHERE user_id = %d AND item_id = %d AND activity_type = %s AND parent_id = %d",
				$user_id,
				$item_id,
				$activity_type,
				$progress_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $already_exists ) {
			$activity_status = $completed ? CourseProgressStatus::COMPLETED : CourseProgressStatus::STARTED;
			$completed_at    = $completed
				? self::parse_lp_datetime( $row->end_time, $now )
				: '0000-00-00 00:00:00';

			$wpdb->insert(
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
		}

		$wpdb->delete( $wpdb->prefix . 'learnpress_user_items', array( 'user_item_id' => $user_item_id ), array( '%d' ) );
	}

	/**
	 * Migrate a single LearnPress order to a Masteriyo mto-order.
	 *
	 * Renames lp_order → mto-order in-place with accurate status mapping.
	 * Idempotent: returns early if already an mto-order.
	 *
	 * @since x.x.x
	 *
	 * @param int $order_id LearnPress lp_order post ID.
	 * @throws \Exception If the post does not exist or is not an lp_order post.
	 */
	public static function migrate_single_order( int $order_id ): void {
		$post = get_post( $order_id );

		if ( ! $post ) {
			throw new \Exception(
				sprintf( 'migrate_single_order: post %d not found.', $order_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		if ( PostType::ORDER === $post->post_type ) {
			return; // Already migrated.
		}

		if ( 'lp_order' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_order: post %d is not an lp_order post (got %s).', $order_id, $post->post_type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
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

		wp_update_post(
			array(
				'ID'            => $order_id,
				'post_status'   => $mto_status,
				'post_type'     => PostType::ORDER,
				'post_title'    => $title,
				'post_password' => masteriyo_generate_order_key(),
			)
		);

		foreach ( self::get_lp_order_items( $order_id ) as $lp_order_item ) {
			self::migrate_order_item( $lp_order_item, $order_id );
		}

		self::migrate_order_meta( $order_id );
	}

	/**
	 * Update a LearnPress review comment to a Masteriyo course_review.
	 *
	 * Updates comment_type in-place. Idempotent: returns early if already migrated.
	 *
	 * @since x.x.x
	 *
	 * @param int $comment_id WP comment ID.
	 * @throws \Exception If the comment does not exist.
	 */
	public static function migrate_single_review( int $comment_id ): void {
		global $wpdb;

		$comment = get_comment( $comment_id );

		if ( ! $comment ) {
			throw new \Exception(
				sprintf( 'migrate_single_review: comment %d not found.', $comment_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		if ( CommentType::COURSE_REVIEW === $comment->comment_type ) {
			return; // Already migrated.
		}

		$wpdb->update(
			$wpdb->comments,
			array(
				'comment_type'     => CommentType::COURSE_REVIEW,
				'comment_approved' => 1,
				'comment_agent'    => 'Masteriyo',
				'comment_karma'    => $comment->comment_karma ? $comment->comment_karma : 0,
			),
			array( 'comment_ID' => $comment_id )
		);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Build Masteriyo sections from the LearnPress curriculum and rename lesson/quiz posts.
	 *
	 * @since x.x.x
	 *
	 * @param int $course_id LearnPress course post ID.
	 */
	private static function process_course_curriculum( int $course_id ): void {
		if ( ! function_exists( 'learn_press_get_course' ) ) {
			return;
		}

		$course = \learn_press_get_course( $course_id );

		if ( ! $course ) {
			return;
		}

		$curriculum = $course->get_curriculum();

		if ( ! $curriculum ) {
			return;
		}

		$section_order = 0;
		foreach ( $curriculum as $section ) {
			++$section_order;

			$section_id = wp_insert_post(
				array(
					'post_type'    => PostType::SECTION,
					'post_title'   => $section->get_title(),
					'post_content' => $section->get_description(),
					'post_status'  => PostStatus::PUBLISH,
					'post_author'  => $course->get_author( 'id' ),
					'post_parent'  => $course_id,
					'menu_order'   => $section_order,
				)
			);

			if ( is_wp_error( $section_id ) ) {
				masteriyo_get_logger()->error( 'Migration: Failed to insert section post.', array( 'source' => 'migration-tool' ) );
				continue;
			}

			update_post_meta( $section_id, '_course_id', $course_id );

			$item_order = 0;
			foreach ( $section->get_items() as $item ) {
				++$item_order;

				$raw_type = \learn_press_get_post_type( $item->get_id() );

				if ( 'lp_quiz' === $raw_type ) {
					$mto_type = PostType::QUIZ;
					self::migrate_quiz_questions( $item->get_id(), $course_id );
				} elseif ( 'lp_lesson' === $raw_type ) {
					$mto_type = PostType::LESSON;
				} else {
					$mto_type = $raw_type;
				}

				$item_id = $item->get_id();
				wp_update_post(
					array(
						'ID'          => $item_id,
						'post_type'   => $mto_type,
						'post_parent' => $section_id,
						'menu_order'  => $item_order,
					)
				);
				update_post_meta( $item_id, '_course_id', $course_id );

				if ( 'lp_lesson' === $raw_type ) {
					update_post_meta( $item_id, '_duration', self::parse_lp_duration( $item_id ) );
				} elseif ( 'lp_quiz' === $raw_type ) {
					self::migrate_single_quiz_meta( $item_id );
				}
			}
		}
	}

	/**
	 * Migrate all questions for a LearnPress quiz.
	 *
	 * @since x.x.x
	 *
	 * @param int $quiz_id   Quiz post ID.
	 * @param int $course_id Course post ID.
	 */
	private static function migrate_quiz_questions( int $quiz_id, int $course_id ): void {
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
				$quiz_id
			)
		);

		$type_map = array(
			'true_or_false' => QuestionType::TRUE_FALSE,
			'single_choice' => QuestionType::SINGLE_CHOICE,
			'multi_choice'  => QuestionType::MULTIPLE_CHOICE,
		);

		foreach ( $questions as $question ) {
			// Handle fill_in_blanks separately — LP stores blanks as [fib fill="..." id="..."] shortcodes.
			if ( 'fill_in_blanks' === $question->question_type ) {
				$fib_row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT title FROM {$wpdb->prefix}learnpress_question_answers WHERE question_id = %d LIMIT 1",
						$question->question_id
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

				if ( is_wp_error( $question_id ) ) {
					masteriyo_get_logger()->error( 'Migration: Failed to insert LearnPress FIB question post.', array( 'source' => 'migration-tool' ) );
					continue;
				}

				update_post_meta( $question_id, '_course_id', $course_id );
				update_post_meta( $question_id, '_type', 'fill-in-the-blanks' );
				update_post_meta( $question_id, '_points', $question->question_mark );
				update_post_meta( $question_id, '_parent_id', $quiz_id );

				$explanation = get_post_meta( $question->question_id, '_lp_explanation', true );
				if ( $explanation ) {
					update_post_meta( $question_id, '_answer_explanation', $explanation );
				}

				continue;
			}

			$question_type = $type_map[ $question->question_type ] ?? null;

			if ( ! $question_type ) {
				continue;
			}

			$answers              = self::get_question_answers( $question->question_id );
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

			if ( is_wp_error( $question_id ) ) {
				masteriyo_get_logger()->error( 'Migration: Failed to insert LearnPress question post.', array( 'source' => 'migration-tool' ) );
				continue;
			}

			update_post_meta( $question_id, '_course_id', $course_id );
			update_post_meta( $question_id, '_type', $question_type );
			update_post_meta( $question_id, '_points', $question->question_mark );
			update_post_meta( $question_id, '_parent_id', $quiz_id );

			if ( $question_description ) {
				update_post_meta( $question_id, '_enable_description', true );
			}

			// Copy answer explanation.
			$explanation = get_post_meta( $question->question_id, '_lp_explanation', true );
			if ( $explanation ) {
				update_post_meta( $question_id, '_answer_explanation', $explanation );
			}
		}
	}

	/**
	 * Fetch formatted answers for a LearnPress question.
	 *
	 * @since x.x.x
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
	 * Rename the lp_course post and migrate all course-level meta.
	 *
	 * @since x.x.x
	 *
	 * @param int $course_id Course post ID.
	 */
	private static function update_course_meta( int $course_id ): void {
		$_lp_price              = floatval( get_post_meta( $course_id, '_lp_regular_price', true ) );
		$_lp_sale_price         = floatval( get_post_meta( $course_id, '_lp_sale_price', true ) );
		$_lp_max_students       = (int) get_post_meta( $course_id, '_lp_max_students', true );
		$_lp_thumbnail_id       = (int) get_post_meta( $course_id, '_thumbnail_id', true );
		$_lp_no_required_enroll = get_post_meta( $course_id, '_lp_no_required_enroll', true );
		$_lp_sale_start         = get_post_meta( $course_id, '_lp_sale_start', true );
		$_lp_sale_end           = get_post_meta( $course_id, '_lp_sale_end', true );
		$_lp_level              = get_post_meta( $course_id, '_lp_level', true );
		$_lp_retake_count       = absint( get_post_meta( $course_id, '_lp_retake_count', true ) );

		$price_type = ( $_lp_price > 0 ) ? 'paid' : 'free';
		wp_set_object_terms( $course_id, $price_type, 'course_visibility', false );

		wp_update_post(
			array(
				'ID'        => $course_id,
				'post_type' => PostType::COURSE,
			)
		);

		update_post_meta( $course_id, '_was_lp_course', true );
		update_post_meta( $course_id, '_price', $_lp_price );
		update_post_meta( $course_id, '_regular_price', $_lp_price );

		if ( $_lp_sale_price > 0 ) {
			update_post_meta( $course_id, '_sale_price', $_lp_sale_price );
		}

		update_post_meta( $course_id, '_duration', self::parse_lp_duration( $course_id ) );
		update_post_meta( $course_id, '_enrollment_limit', $_lp_max_students );
		update_post_meta( $course_id, '_thumbnail_id', $_lp_thumbnail_id );
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
	}

	/**
	 * Get LearnPress order items.
	 *
	 * @since x.x.x
	 *
	 * @param int $order_id Order post ID.
	 * @return array Order item objects.
	 */
	private static function get_lp_order_items( int $order_id ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT oi.order_item_id as id, oi.order_item_name as name,
					oim.meta_value as course_id
				FROM {$wpdb->learnpress_order_items} oi
				INNER JOIN {$wpdb->learnpress_order_itemmeta} oim
					ON oi.order_item_id = oim.learnpress_order_item_id AND oim.meta_key = '_course_id'
				WHERE oi.order_id = %d",
				$order_id
			)
		);
	}

	/**
	 * Insert one LP order item into masteriyo_order_items and copy its meta.
	 *
	 * @since x.x.x
	 *
	 * @param object $lp_item  LP order item object (id, name, course_id).
	 * @param int    $order_id Order post ID.
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
				'meta_value'    => masteriyo_array_get( $meta_map, '_course_id', 0 ),
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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
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
	 * @since x.x.x
	 * @param string $step Step name.
	 */
	public static function finalize_step( string $step ): void {
		if ( 'lesson_progress' !== $step ) {
			return;
		}

		global $wpdb;
		$tbl = $wpdb->prefix . 'masteriyo_user_activities';

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
			         UTC_TIMESTAMP(),
			         '0000-00-00 00:00:00'
			     ),
			     parent.modified_at  = UTC_TIMESTAMP()
			 WHERE parent.activity_type = 'course_progress'"
		);
		// phpcs:enable
	}

	/**
	 * Copy LearnPress quiz-level settings to Masteriyo postmeta.
	 *
	 * @since x.x.x
	 *
	 * @param int $quiz_id Quiz post ID (already renamed to mto-quiz).
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
	 * Migrate one LearnPress quiz result row to masteriyo_quiz_attempts.
	 *
	 * @since x.x.x
	 *
	 * @param int $result_id Primary key of the learnpress_user_item_results row.
	 * @throws \Exception If the row is not found.
	 */
	private static function migrate_single_quiz_result( int $result_id ): void {
		global $wpdb;

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
		$quiz_id   = (int) $row->quiz_id;
		$course_id = (int) get_post_meta( $quiz_id, '_course_id', true );

		if ( ! $course_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: No _course_id on quiz %d (user %d) — skipping quiz result.', $quiz_id, $user_id ),
				array( 'source' => 'migration-tool' )
			);
			$wpdb->delete( $wpdb->prefix . 'learnpress_user_item_results', array( 'id' => $result_id ), array( '%d' ) );
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

		// Idempotency: skip if already migrated.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$already = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_quiz_attempts WHERE quiz_id = %d AND user_id = %d",
				$quiz_id,
				$user_id
			)
		);

		if ( ! $already ) {
			$wpdb->insert(
				$wpdb->prefix . 'masteriyo_quiz_attempts',
				array(
					'course_id'                => $course_id,
					'quiz_id'                  => $quiz_id,
					'user_id'                  => $user_id,
					'total_questions'          => $total_q,
					'total_answered_questions' => $answered_q,
					'total_marks'              => $total_marks,
					'total_attempts'           => 1,
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
		}

		$wpdb->delete( $wpdb->prefix . 'learnpress_user_item_results', array( 'id' => $result_id ), array( '%d' ) );
	}
}
