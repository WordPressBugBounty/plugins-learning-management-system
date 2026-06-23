<?php
/**
 * LearnDash migrations.
 *
 * @since 1.16.0
 *
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool\LMS;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\Helper;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CourseProgressItemType;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuestionType;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Roles;
use LDLMS_Factory_Post;
use WpProQuiz_Model_AnswerTypes;

/**
 * Class LearnDash.
 *
 * @since 1.16.0
 */
class LearnDash {

	// ──────────────────────────────────────────────────────────────────────────
	// Batch API — called by LearnDashMigrator
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Return the total number of source items for the given migration step.
	 *
	 * @since x.x.x
	 *
	 * @param string $step
	 * @return int
	 */
	public static function count_source_items( string $step ): int {
		global $wpdb;

		switch ( $step ) {
			case 'users':
				$ld_activity_table = $wpdb->prefix . 'learndash_user_activity';
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM (
							SELECT DISTINCT u.ID
							FROM {$wpdb->users} u
							INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
							WHERE um.meta_key = %s AND um.meta_value LIKE %s
							UNION
							SELECT DISTINCT user_id AS ID
							FROM {$ld_activity_table}
							WHERE activity_type = %s
						) AS combined",
						'wp_capabilities',
						'%group_leader%',
						'access'
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			case 'courses':
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
						'sfwd-courses'
					)
				);

			case 'enrollments':
				$table = $wpdb->prefix . 'learndash_user_activity';
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table} WHERE activity_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						'access'
					)
				);

			case 'lesson_progress':
				$table = $wpdb->prefix . 'learndash_user_activity';
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$table} WHERE activity_type IN ('lesson', 'topic', 'quiz')" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				);

			case 'orders':
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
						'sfwd-transactions'
					)
				);

			case 'quiz_attempts':
				$ref_table = $wpdb->prefix . 'learndash_pro_quiz_statistic_ref';
				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $ref_table ) ) !== $ref_table ) {
					return 0;
				}
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$ref_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		return 0;
	}

	/**
	 * Return a paginated list of source item IDs for the given migration step.
	 *
	 * Courses, enrollments, lesson_progress, and orders are self-excluding: the row
	 * disappears from the source query after migration (CPT rename or row deletion), so
	 * offset is always 0. Users use OFFSET-based pagination since role assignment does not
	 * remove the user from the query.
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

		// NOT IN clause for ID-column self-cleaning steps.
		$not_in      = '';
		$not_in_args = array();
		if ( ! empty( $exclude ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
			$not_in       = "AND ID NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$not_in_args  = array_map( 'intval', $exclude );
		}

		// NOT IN clause for activity_id-column steps.
		$act_not_in      = '';
		$act_not_in_args = array();
		if ( ! empty( $exclude ) ) {
			$placeholders    = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
			$act_not_in      = "AND activity_id NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$act_not_in_args = array_map( 'intval', $exclude );
		}

		switch ( $step ) {
			case 'users':
				// Cursor-based — user rows persist after role assignment; $exclude ignored.
				$ld_activity_table = $wpdb->prefix . 'learndash_user_activity';
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				return array_map(
					'intval',
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT id FROM (
								SELECT DISTINCT u.ID AS id
								FROM {$wpdb->users} u
								INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
								WHERE um.meta_key = %s AND um.meta_value LIKE %s
								UNION
								SELECT DISTINCT user_id AS id
								FROM {$ld_activity_table}
								WHERE activity_type = %s
							) AS combined
							WHERE id > %d
							ORDER BY id ASC
							LIMIT %d",
							'wp_capabilities',
							'%group_leader%',
							'access',
							$cursor,
							$limit
						)
					)
				);
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			case 'courses':
				return array_map(
					'intval',
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s {$not_in} ORDER BY ID ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							array_merge( array( 'sfwd-courses' ), $not_in_args, array( $limit ) )
						)
					)
				);

			case 'enrollments':
				$table = $wpdb->prefix . 'learndash_user_activity';
				return array_map(
					'intval',
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT activity_id FROM {$table} WHERE activity_type = %s {$act_not_in} ORDER BY activity_id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							array_merge( array( 'access' ), $act_not_in_args, array( $limit ) )
						)
					)
				);

			case 'lesson_progress':
				$table = $wpdb->prefix . 'learndash_user_activity';
				return array_map(
					'intval',
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT activity_id FROM {$table} WHERE activity_type IN ('lesson', 'topic', 'quiz') {$act_not_in} ORDER BY activity_id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
							array_merge( $act_not_in_args, array( $limit ) )
						)
					)
				);

			case 'orders':
				return array_map(
					'intval',
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s {$not_in} ORDER BY ID ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							array_merge( array( 'sfwd-transactions' ), $not_in_args, array( $limit ) )
						)
					)
				);

			case 'quiz_attempts':
				// Cursor-based — source rows are not deleted; $exclude ignored.
				$ref_table = $wpdb->prefix . 'learndash_pro_quiz_statistic_ref';
				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $ref_table ) ) !== $ref_table ) {
					return array();
				}
				return array_map(
					'intval',
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT statistic_ref_id FROM {$ref_table} WHERE statistic_ref_id > %d ORDER BY statistic_ref_id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$cursor,
							$limit
						)
					)
				);
		}

		return array();
	}

	/**
	 * Migrate a single item for the given step.
	 *
	 * @since x.x.x
	 *
	 * @param string $step
	 * @param int    $item_id
	 */
	public static function migrate_item( string $step, int $item_id ): void {
		switch ( $step ) {
			case 'users':
				self::migrate_single_user( $item_id );
				break;
			case 'courses':
				self::migrate_single_course( $item_id );
				break;
			case 'enrollments':
				self::migrate_single_enrollment( $item_id );
				break;
			case 'lesson_progress':
				self::migrate_single_lesson_progress( $item_id );
				break;
			case 'orders':
				self::migrate_single_order( $item_id );
				break;
			case 'quiz_attempts':
				self::migrate_single_quiz_attempt( $item_id );
				break;
		}
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Per-item migration
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Assign the Masteriyo instructor role to a LearnDash group_leader user.
	 *
	 * @since x.x.x
	 *
	 * @param int $user_id
	 */
	private static function migrate_single_user( int $user_id ): void {
		$caps = get_user_meta( $user_id, 'wp_capabilities', true );
		if ( is_array( $caps ) && isset( $caps['group_leader'] ) ) {
			Helper::update_user_role( $user_id, Roles::INSTRUCTOR );
			$wp_user = new \WP_User( $user_id );
			$wp_user->remove_cap( 'group_leader' );
		} else {
			Helper::update_user_role( $user_id, Roles::STUDENT );
		}
	}

	/**
	 * Migrate a single LearnDash course: rename CPT, rebuild curriculum, copy metadata.
	 *
	 * The post ID is preserved (rename-in-place) so existing user-progress references
	 * remain valid.
	 *
	 * @since x.x.x
	 *
	 * @param int $course_id
	 */
	private static function migrate_single_course( int $course_id ): void {
		$result = wp_update_post(
			array(
				'ID'        => $course_id,
				'post_type' => PostType::COURSE,
			)
		);

		if ( is_wp_error( $result ) || 0 === $result ) {
			masteriyo_get_logger()->error(
				'Migration: failed to rename sfwd-courses CPT for course ID: ' . $course_id,
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		self::build_course_curriculum( $course_id );
		self::update_masteriyo_course_from_ld( $course_id );
		Helper::migrate_course_author( $course_id );
	}

	/**
	 * Rebuild the course curriculum: sections, lessons, topics, and quizzes.
	 *
	 * LearnDash stores section headings in the `course_sections` post-meta as a JSON
	 * array of `{order, post_title}` objects, where `order` is the 0-based index of the
	 * section heading in the COMBINED flat list of all course items (section headings +
	 * lessons). Because section headings themselves occupy positions in that list, `order`
	 * is NOT a direct lesson index. To convert: lesson_index = order − idx, where idx is
	 * the 0-based position of this section in the sections-sorted-by-order array (i.e.,
	 * the number of section headings that precede it).
	 *
	 * @since x.x.x
	 *
	 * @param int $course_id
	 */
	private static function build_course_curriculum( int $course_id ): void {
		$total_data = self::get_course_steps( $course_id );

		if ( empty( $total_data ) ) {
			return;
		}

		$author_id = (int) get_post_field( 'post_author', $course_id );

		// Build a map of lesson-index → section title.
		$raw_headings = get_post_meta( $course_id, 'course_sections', true );
		$raw_headings = $raw_headings ? json_decode( $raw_headings, true ) : array();

		$sorted_headings = array_values( (array) $raw_headings );
		usort(
			$sorted_headings,
			function( $a, $b ) {
				return (int) $a['order'] - (int) $b['order'];
			}
		);

		$sections_at = array();
		foreach ( $sorted_headings as $idx => $heading ) {
			if ( isset( $heading['order'] ) ) {
				$lesson_index                 = (int) $heading['order'] - $idx;
				$sections_at[ $lesson_index ] = isset( $heading['post_title'] )
					? $heading['post_title']
					: __( 'Section', 'learning-management-system' );
			}
		}

		// Guarantee a section at position 0 so no lessons are ever orphaned.
		if ( ! isset( $sections_at[0] ) ) {
			$sections_at[0] = __( 'Section', 'learning-management-system' );
		}

		$section_id = 0;
		$menu_order = 0;
		$i          = 0;

		if ( ! empty( $total_data['sfwd-lessons'] ) ) {
			foreach ( $total_data['sfwd-lessons'] as $lesson_key => $lesson_data ) {
				// Create a new section when this lesson index is a boundary.
				if ( isset( $sections_at[ $i ] ) ) {
					$section_id = Helper::insert_post( $sections_at[ $i ], '', $author_id, PostType::SECTION, $menu_order, $course_id );
					self::update_course_item_meta( $section_id, $course_id, $course_id );
					++$menu_order;
				}

				$lesson_id = Helper::update_post( $lesson_key, PostType::LESSON, $menu_order, $section_id );
				self::update_course_item_meta( $lesson_id, $course_id, $section_id );
				++$menu_order;

				// LearnDash topics are sub-lessons → Masteriyo lessons in the same section.
				foreach ( (array) ( isset( $lesson_data['sfwd-topic'] ) ? $lesson_data['sfwd-topic'] : array() ) as $topic_key => $topic_data ) {
					$topic_id = Helper::update_post( $topic_key, PostType::LESSON, $menu_order, $section_id );
					self::update_course_item_meta( $topic_id, $course_id, $section_id );
					++$menu_order;

					foreach ( array_keys( (array) ( isset( $topic_data['sfwd-quiz'] ) ? $topic_data['sfwd-quiz'] : array() ) ) as $quiz_key ) {
						$quiz_id = Helper::update_post( $quiz_key, PostType::QUIZ, $menu_order, $section_id );
						self::update_course_item_meta( $quiz_id, $course_id, $section_id );
						++$menu_order;
						self::migrate_ld_quiz( $quiz_id, $course_id );
					}
				}

				// Quizzes directly under this lesson (not under a topic).
				foreach ( array_keys( (array) ( isset( $lesson_data['sfwd-quiz'] ) ? $lesson_data['sfwd-quiz'] : array() ) ) as $quiz_key ) {
					$quiz_id = Helper::update_post( $quiz_key, PostType::QUIZ, $menu_order, $section_id );
					self::update_course_item_meta( $quiz_id, $course_id, $section_id );
					++$menu_order;
					self::migrate_ld_quiz( $quiz_id, $course_id );
				}

				++$i;
			}

			// Top-level quizzes not nested under any lesson.
			if ( ! empty( $total_data['sfwd-quiz'] ) ) {
				foreach ( array_keys( $total_data['sfwd-quiz'] ) as $quiz_key ) {
					$quiz_id = Helper::update_post( $quiz_key, PostType::QUIZ, $menu_order, $section_id );
					self::update_course_item_meta( $quiz_id, $course_id, $section_id );
					++$menu_order;
					self::migrate_ld_quiz( $quiz_id, $course_id );
				}
			}
		}

		// Courses that contain only standalone quizzes (no lessons at all).
		if ( empty( $total_data['sfwd-lessons'] ) && ! empty( $total_data['sfwd-quiz'] ) ) {
			$section_id = Helper::insert_post( __( 'Section', 'learning-management-system' ), '', $author_id, PostType::SECTION, 0, $course_id );
			self::update_course_item_meta( $section_id, $course_id, $course_id );

			foreach ( array_keys( $total_data['sfwd-quiz'] ) as $quiz_key ) {
				$quiz_id = Helper::update_post( $quiz_key, PostType::QUIZ, $menu_order, $section_id );
				self::update_course_item_meta( $quiz_id, $course_id, $section_id );
				++$menu_order;
				self::migrate_ld_quiz( $quiz_id, $course_id );
			}
		}
	}

	/**
	 * Migrate a single LearnDash enrollment (activity_type='access') to masteriyo_user_items.
	 *
	 * The source row is deleted after a successful insert so that offset-0 pagination
	 * always returns unseen rows.
	 *
	 * @since x.x.x
	 *
	 * @param int $activity_id  Primary key of the wp_learndash_user_activity row.
	 */
	private static function migrate_single_enrollment( int $activity_id ): void {
		global $wpdb;

		$ld_table = $wpdb->prefix . 'learndash_user_activity';
		$activity = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$ld_table} WHERE activity_id = %d", $activity_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $activity ) {
			return;
		}

		$user_id   = (int) $activity['user_id'];
		$course_id = (int) $activity['course_id'];

		// Idempotent: skip if already enrolled.
		$already = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %d AND item_id = %d AND item_type = 'user_course'",
				$user_id,
				$course_id
			)
		);

		if ( $already ) {
			$wpdb->delete( $ld_table, array( 'activity_id' => $activity_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return;
		}

		$date_start    = $activity['activity_started']
			? gmdate( 'Y-m-d H:i:s', (int) $activity['activity_started'] )
			: current_time( 'mysql', true );
		$date_modified = $activity['activity_updated']
			? gmdate( 'Y-m-d H:i:s', (int) $activity['activity_updated'] )
			: $date_start;
		$date_end      = ( ! empty( $activity['activity_completed'] ) && (int) $activity['activity_completed'] > 0 )
			? gmdate( 'Y-m-d H:i:s', (int) $activity['activity_completed'] )
			: null;

		// Find the associated transaction so we can record order_id and price meta.
		$order_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = %s
				 INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
				 WHERE CAST(pm1.meta_value AS UNSIGNED) = %d
				   AND CAST(pm2.meta_value AS UNSIGNED) = %d
				   AND p.post_type IN ('sfwd-transactions','mto-order')
				 LIMIT 1",
				'course_id',
				'user_id',
				$course_id,
				$user_id
			)
		);

		$price = 0.0;
		if ( $order_id ) {
			$course_meta = get_post_meta( $course_id, '_sfwd-courses', true );
			$price       = isset( $course_meta['sfwd-courses_course_price'] )
				? floatval( $course_meta['sfwd-courses_course_price'] )
				: 0.0;
		}

		$user_item_id = Helper::enroll_user( $user_id, $course_id, $date_start, $date_end, UserCourseStatus::ACTIVE, $order_id ? $order_id : null, $price, $date_modified );
		if ( null === $user_item_id ) {
			masteriyo_get_logger()->error(
				'Migration: failed to insert enrollment for user ' . $user_id . ' course ' . $course_id,
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		// Source cleanup — row deleted so offset-0 pagination always sees fresh rows.
		$wpdb->delete( $ld_table, array( 'activity_id' => $activity_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Migrate a single LearnDash lesson/topic/quiz activity to masteriyo_user_activities.
	 *
	 * Finds or creates the parent course_progress row, records the individual item
	 * completion, then recalculates the overall course-progress status. The source row
	 * is deleted after a successful write.
	 *
	 * @since x.x.x
	 *
	 * @param int $activity_id  Primary key of the wp_learndash_user_activity row.
	 */
	private static function migrate_single_lesson_progress( int $activity_id ): void {
		global $wpdb;

		$ld_table = $wpdb->prefix . 'learndash_user_activity';
		$activity = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$ld_table} WHERE activity_id = %d", $activity_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $activity ) {
			return;
		}

		$user_id   = (int) $activity['user_id'];
		$course_id = (int) $activity['course_id'];
		$post_id   = (int) $activity['post_id'];

		if ( ! $post_id ) {
			$wpdb->delete( $ld_table, array( 'activity_id' => $activity_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return;
		}

		$item_type = ( 'quiz' === $activity['activity_type'] )
			? CourseProgressItemType::QUIZ
			: CourseProgressItemType::LESSON;
		$act_table = $wpdb->prefix . 'masteriyo_user_activities';

		// Find or create the course-level progress row.
		$started_at  = $activity['activity_started']
			? gmdate( 'Y-m-d H:i:s', (int) $activity['activity_started'] )
			: current_time( 'mysql', true );
		$progress_id = Helper::get_or_create_course_progress( $user_id, $course_id, $started_at );

		if ( ! $progress_id ) {
			masteriyo_get_logger()->error(
				'Migration: could not create course_progress row for user ' . $user_id . ' course ' . $course_id,
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		// Insert the individual item row (skip if already recorded — idempotent).
		$item_exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$act_table} WHERE user_id = %d AND item_id = %d AND parent_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$post_id,
				$progress_id
			)
		);

		if ( ! $item_exists ) {
			$item_status  = $activity['activity_status']
				? CourseProgressStatus::COMPLETED
				: CourseProgressStatus::STARTED;
			$completed_at = ( CourseProgressStatus::COMPLETED === $item_status && ! empty( $activity['activity_completed'] ) )
				? gmdate( 'Y-m-d H:i:s', (int) $activity['activity_completed'] )
				: null;
			$created_at   = $activity['activity_started']
				? gmdate( 'Y-m-d H:i:s', (int) $activity['activity_started'] )
				: current_time( 'mysql', true );

			$wpdb->insert(
				$act_table,
				array(
					'user_id'         => $user_id,
					'item_id'         => $post_id,
					'activity_type'   => $item_type,
					'activity_status' => $item_status,
					'parent_id'       => $progress_id,
					'created_at'      => $created_at,
					'modified_at'     => $created_at,
					'completed_at'    => $completed_at,
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
			);
		}

		// Source cleanup.
		$wpdb->delete( $ld_table, array( 'activity_id' => $activity_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Migrate a single LearnDash transaction (sfwd-transactions) to an mto-order.
	 *
	 * All LD transactions represent successful payments so they map to
	 * OrderStatus::COMPLETED.
	 *
	 * @since x.x.x
	 *
	 * @param int $order_id
	 */
	private static function migrate_single_order( int $order_id ): void {
		$post = get_post( $order_id );
		if ( ! $post ) {
			return;
		}

		$order_time = strtotime( $post->post_date );
		$title      = __( 'Order', 'learning-management-system' )
			. ' &ndash; '
			. gmdate( get_option( 'date_format' ), $order_time )
			. ' @ '
			. gmdate( get_option( 'time_format' ), $order_time );

		wp_update_post(
			array(
				'ID'            => $order_id,
				'post_type'     => PostType::ORDER,
				'post_status'   => OrderStatus::COMPLETED,
				'post_title'    => $title,
				'post_password' => masteriyo_generate_order_key(),
			)
		);

		self::migrate_order_item_from_ld( $order_id );
		self::update_order_meta_from_ld( $order_id );
	}

	/**
	 * Migrate a single WPProQuiz quiz attempt (statistic_ref row + its statistic rows)
	 * to the masteriyo_quiz_attempts table.
	 *
	 * The source rows are deleted after a successful insert so cursor-based pagination
	 * always sees fresh rows.
	 *
	 * @since x.x.x
	 *
	 * @param int $ref_id  Primary key of the wp_learndash_pro_quiz_statistic_ref row.
	 */
	private static function migrate_single_quiz_attempt( int $ref_id ): void {
		global $wpdb;

		$ref_table  = $wpdb->prefix . 'learndash_pro_quiz_statistic_ref';
		$stat_table = $wpdb->prefix . 'learndash_pro_quiz_statistic';

		$ref = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$ref_table} WHERE statistic_ref_id = %d", $ref_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		if ( ! $ref ) {
			return;
		}

		$stats = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$stat_table} WHERE statistic_ref_id = %d", $ref_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		$total_questions = count( $stats );
		$total_correct   = 0;
		$total_incorrect = 0;
		$earned_marks    = 0.0;
		$has_essay       = false;
		$answers_json    = array();

		// Determine pro_quiz question table once — same logic used by migrate_ld_quiz.
		$qm_table = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', "{$wpdb->prefix}learndash_pro_quiz_question" )
		) ? "{$wpdb->prefix}learndash_pro_quiz_question" : "{$wpdb->prefix}pro_quiz_question";

		foreach ( $stats as $stat ) {
			$correct          = (int) $stat['correct_count'];
			$total_correct   += ( $correct >= 1 ) ? 1 : 0;
			$total_incorrect += ( 0 === $correct ) ? 1 : 0;
			$earned_marks    += (float) $stat['points'];
			$answers_json[]   = array(
				'question_id'   => (int) $stat['question_post_id'],
				'correct_count' => $correct,
				'points'        => (float) $stat['points'],
				'answer_data'   => maybe_unserialize( $stat['answer_data'] ),
			);

			// Check essay type via the pro_quiz table — _type post-meta is on the new mto-question
			// post (created by migrate_ld_quiz), not on the original sfwd-question post referenced
			// by question_post_id in the statistic row.
			$pro_id = (int) get_post_meta( (int) $stat['question_post_id'], 'question_pro_id', true );
			if ( $pro_id ) {
				$answer_type = $wpdb->get_var(
					$wpdb->prepare( "SELECT answer_type FROM {$qm_table} WHERE id = %d", $pro_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				);
				if ( in_array( $answer_type, array( 'free_answer', 'essay' ), true ) ) {
					$has_essay = true;
				}
			}
		}

		// Compute total_marks by summing question points from the pro-quiz question table.
		$quiz_id     = (int) $ref['quiz_post_id'];
		$quiz_pro_id = (int) get_post_meta( $quiz_id, 'quiz_pro_id', true );
		$total_marks = 0.0;
		if ( $quiz_pro_id ) {
			$total_marks = (float) $wpdb->get_var(
				$wpdb->prepare( "SELECT SUM(points) FROM {$qm_table} WHERE quiz_id = %d", $quiz_pro_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
		}

		$pass_mark     = (float) get_post_meta( $quiz_id, '_pass_mark', true );
		$pass_mark_pts = ( $total_marks > 0 && $pass_mark > 0 ) ? ( $pass_mark / 100 ) * $total_marks : 0;

		if ( $has_essay ) {
			$attempt_status = 'pending';
		} elseif ( $pass_mark > 0 ) {
			$attempt_status = $earned_marks >= $pass_mark_pts ? 'passed' : 'failed';
		} else {
			$attempt_status = 'passed';
		}

		$started_at = gmdate( 'Y-m-d H:i:s', (int) $ref['create_time'] );

		$wpdb->insert(
			$wpdb->prefix . 'masteriyo_quiz_attempts',
			array(
				'quiz_id'                  => $quiz_id,
				'course_id'                => (int) $ref['course_post_id'],
				'user_id'                  => (int) $ref['user_id'],
				'total_questions'          => $total_questions,
				'total_answered_questions' => $total_questions,
				'total_marks'              => $total_marks,
				'total_attempts'           => 1,
				'total_correct_answers'    => $total_correct,
				'total_incorrect_answers'  => $total_incorrect,
				'earned_marks'             => $earned_marks,
				'answers'                  => maybe_serialize( $answers_json ),
				'attempt_status'           => $attempt_status,
				'attempt_started_at'       => $started_at,
				'attempt_ended_at'         => $started_at,
			),
			array( '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s' )
		);

		// Delete source rows — self-exclusion for cursor-based pagination.
		$wpdb->delete( $ref_table, array( 'statistic_ref_id' => $ref_id ), array( '%d' ) );
		$wpdb->delete( $stat_table, array( 'statistic_ref_id' => $ref_id ), array( '%d' ) );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Private helpers
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Migrate quiz questions for a given quiz post.
	 *
	 * Handles single-choice, multiple-choice, and true/false types. Unsupported types
	 * are skipped with `continue` (not `return`) so sibling questions are not lost;
	 * their raw answer_type is stored under _migrated_* for reference.
	 *
	 * @since 1.16.0
	 *
	 * @param int $quiz_id
	 * @param int $course_id
	 */
	private static function migrate_ld_quiz( int $quiz_id, int $course_id ): void {
		global $wpdb;

		$question_ids = get_post_meta( $quiz_id, 'ld_quiz_questions', true );
		if ( empty( $question_ids ) ) {
			return;
		}

		// Read quiz_master row for quiz-level settings.
		$quiz_pro_id = (int) get_post_meta( $quiz_id, 'quiz_pro_id', true );
		if ( $quiz_pro_id ) {
			$master_table = $wpdb->prefix . 'learndash_pro_quiz_master';
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $master_table ) ) !== $master_table ) {
				$master_table = $wpdb->prefix . 'pro_quiz_master';
			}
			$master = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT time_limit, questions_per_page, question_random, quiz_run_once, quiz_run_once_type FROM {$master_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$quiz_pro_id
				),
				ARRAY_A
			);
			if ( $master ) {
				if ( (int) $master['time_limit'] > 0 ) {
					update_post_meta( $quiz_id, '_duration', (int) $master['time_limit'] );
				}
				update_post_meta( $quiz_id, '_questions_display_per_page', max( 1, (int) $master['questions_per_page'] ) );
				$attempts = ( '1' === (string) $master['quiz_run_once'] && 'user' === (string) $master['quiz_run_once_type'] ) ? 1 : 0;
				update_post_meta( $quiz_id, '_attempts_allowed', $attempts );
			}
		}

		$sfwd_meta = get_post_meta( $quiz_id, '_sfwd-quiz', true );
		$sfwd_meta = is_array( $sfwd_meta ) ? $sfwd_meta : array();
		$pass_mark = (float) ( isset( $sfwd_meta['sfwd-quiz_passing_percentage'] ) ? $sfwd_meta['sfwd-quiz_passing_percentage'] : 0 );
		if ( $pass_mark > 0 ) {
			update_post_meta( $quiz_id, '_pass_mark', $pass_mark );
		}

		$has_new_table = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', "{$wpdb->prefix}learndash_pro_quiz_question" )
		);
		$q_table       = $has_new_table
			? "{$wpdb->prefix}learndash_pro_quiz_question"
			: "{$wpdb->prefix}pro_quiz_question";

		$menu_order = 0;

		foreach ( array_keys( (array) $question_ids ) as $question_post_id ) {
			++$menu_order;

			$pro_id = (int) get_post_meta( $question_post_id, 'question_pro_id', true );
			$row    = $wpdb->get_row(
				$wpdb->prepare( "SELECT id, question, points, answer_type, answer_data FROM {$q_table} WHERE id = %d", $pro_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			);

			if ( ! $row ) {
				continue;
			}

			$serialized     = maybe_unserialize( $row['answer_data'] );
			$answer_objects = array();
			foreach ( (array) $serialized as $ao ) {
				if ( $ao instanceof WpProQuiz_Model_AnswerTypes ) {
					$answer_objects[] = $ao;
				}
			}

			$answers      = array();
			$post_content = ''; // may be overridden for cloze questions

			// Map all WPProQuiz answer types to Masteriyo question types.
			switch ( $row['answer_type'] ) {
				case 'single':
					$question_type = QuestionType::SINGLE_CHOICE;
					foreach ( $answer_objects as $ao ) {
						$answers[] = array(
							'name'    => $ao->getAnswer(),
							'correct' => (bool) $ao->isCorrect(),
						);
					}
					break;

				case 'multiple':
					$question_type = QuestionType::MULTIPLE_CHOICE;
					foreach ( $answer_objects as $ao ) {
						$answers[] = array(
							'name'    => $ao->getAnswer(),
							'correct' => (bool) $ao->isCorrect(),
						);
					}
					break;

				case 'bool':
					$question_type = QuestionType::TRUE_FALSE;
					foreach ( $answer_objects as $ao ) {
						$answers[] = array(
							'name'    => $ao->getAnswer(),
							'correct' => (bool) $ao->isCorrect(),
						);
					}
					break;

				case 'free_answer':
				case 'essay':
					// Open-ended text answer — no answer array needed.
					$question_type = 'text-answer';
					$answers       = array();
					break;

				case 'cloze_answer':
					// Fill-in-the-blanks — replace {correct_value} tokens with {{blank}}.
					$question_type = 'fill-in-the-blanks';
					$post_content  = preg_replace( '/\{[^}]+\}/', '{{blank}}', $row['question'] );
					foreach ( $answer_objects as $ao ) {
						$answers[] = array(
							'name'    => $ao->getAnswer(),
							'correct' => true,
						);
					}
					break;

				case 'sort_answer':
					// Sortable — correct order comes from _sortString; fall back to _answer.
					$question_type = 'sortable';
					foreach ( $answer_objects as $ao ) {
						$sort = $ao->getSortString();
						$answers[] = array(
							'name' => '' !== $sort ? $sort : $ao->getAnswer(),
						);
					}
					break;

				case 'matrix_sort':
					// Matching — left column from _answer, right column from _sortString.
					$question_type = 'matching';
					foreach ( $answer_objects as $ao ) {
						$answers[] = array(
							'name'  => $ao->getAnswer(),
							'match' => $ao->getSortString(),
						);
					}
					break;

				default:
					// assessment and any unknown types — skip silently.
					Helper::store_unmigrated_meta( (int) $question_post_id, 'ld_answer_type', $row['answer_type'] );
					continue 2;
			}

			$new_id = wp_insert_post(
				array(
					'post_type'    => PostType::QUESTION,
					'post_title'   => sanitize_text_field( $row['question'] ),
					'post_content' => '' !== $post_content ? $post_content : wp_json_encode( $answers ),
					'post_status'  => PostStatus::PUBLISH,
					'post_author'  => get_post_field( 'post_author', $quiz_id ),
					'post_parent'  => $quiz_id,
					'menu_order'   => $menu_order,
				)
			);

			if ( is_wp_error( $new_id ) ) {
				masteriyo_get_logger()->error(
					'Migration: failed to insert question for quiz ' . $quiz_id,
					array( 'source' => 'migration-tool' )
				);
				continue;
			}

			update_post_meta( $new_id, '_course_id', $course_id );
			update_post_meta( $new_id, '_type', $question_type );
			update_post_meta( $new_id, '_points', $row['points'] );
			update_post_meta( $new_id, '_parent_id', $quiz_id );

			// For cloze questions, also store answers separately so they can be graded.
			if ( 'fill-in-the-blanks' === $question_type ) {
				update_post_meta( $new_id, '_answers', wp_json_encode( $answers ) );
			}
		}
	}

	/**
	 * Copy LearnDash course metadata to Masteriyo post-meta keys.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id
	 */
	private static function update_masteriyo_course_from_ld( int $course_id ): void {
		$meta = get_post_meta( $course_id, '_sfwd-courses', true );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		$price        = floatval( isset( $meta['sfwd-courses_course_price'] ) ? $meta['sfwd-courses_course_price'] : 0 );
		$max_students = absint( isset( $meta['sfwd-courses_course_seats_limit'] ) ? $meta['sfwd-courses_course_seats_limit'] : 0 );
		$price_type   = isset( $meta['sfwd-courses_course_price_type'] ) ? $meta['sfwd-courses_course_price_type'] : 'open';
		$disable_toc  = isset( $meta['sfwd-courses_course_disable_content_table'] ) ? $meta['sfwd-courses_course_disable_content_table'] : '';

		update_post_meta( $course_id, '_price', $price );
		update_post_meta( $course_id, '_regular_price', $price );
		update_post_meta( $course_id, '_enrollment_limit', $max_students );
		// disable_content_table='on' means the TOC is hidden → _show_curriculum=false.
		update_post_meta( $course_id, '_show_curriculum', 'on' !== $disable_toc );
		update_post_meta( $course_id, '_was_ld_course', true );

		$visibility = ( 'paynow' === $price_type || 'subscribe' === $price_type ) ? 'paid' : 'free';
		wp_set_object_terms( $course_id, $visibility, 'course_visibility', false );

		switch ( $price_type ) {
			case 'paynow':
				update_post_meta( $course_id, '_access_mode', CourseAccessMode::ONE_TIME );
				break;
			case 'subscribe':
				update_post_meta( $course_id, '_access_mode', CourseAccessMode::RECURRING );
				break;
			case 'free':
				update_post_meta( $course_id, '_access_mode', CourseAccessMode::NEED_REGISTRATION );
				break;
			default: // 'open' and any unrecognised value
				update_post_meta( $course_id, '_access_mode', CourseAccessMode::OPEN );
				break;
		}

		Helper::migrate_course_categories_from_to_masteriyo( $course_id, 'ld_course_category' );

		// Migrate course tags (ld_course_tag → course_tag).
		Helper::migrate_course_categories_from_to_masteriyo( $course_id, 'ld_course_tag', 'course_tag' );

		// Parse course duration string → integer minutes.
		$duration_raw = isset( $meta['sfwd-courses_course_duration'] ) ? trim( $meta['sfwd-courses_course_duration'] ) : '';
		if ( ! empty( $duration_raw ) && preg_match( '/^(\d+(?:\.\d+)?)\s*(hour|day|week|month|minute)?s?$/i', $duration_raw, $m ) ) {
			$n           = (float) $m[1];
			$unit        = strtolower( isset( $m[2] ) ? $m[2] : 'hour' );
			$multipliers = array(
				'minute' => 1,
				'hour'   => 60,
				'day'    => 1440,
				'week'   => 10080,
				'month'  => 43200,
			);
			$minutes     = (int) round( $n * ( isset( $multipliers[ $unit ] ) ? $multipliers[ $unit ] : 60 ) );
			if ( $minutes > 0 ) {
				update_post_meta( $course_id, '_duration', $minutes );
			}
		}

		// Auto-assign a certificate template to courses that had one in LearnDash.
		// LearnDash's certificate design cannot be converted — Sample 1 (fallback: blank) is used.
		$ld_cert   = get_post_meta( $course_id, '_ld_certificate', true );
		$author_id = (int) get_post_field( 'post_author', $course_id );
		if ( $ld_cert ) {
			Helper::assign_certificate_template( $course_id, $author_id );
		}
	}

	/**
	 * Insert order items into masteriyo_order_items / masteriyo_order_itemmeta.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id
	 */
	private static function migrate_order_item_from_ld( int $order_id ): void {
		global $wpdb;

		$course_id = (int) get_post_meta( $order_id, 'course_id', true );
		if ( ! $course_id ) {
			return;
		}

		$wpdb->insert(
			$wpdb->prefix . 'masteriyo_order_items',
			array(
				'order_item_name' => get_the_title( $course_id ),
				'order_item_type' => 'course',
				'order_id'        => $order_id,
			)
		);

		$order_item_id = (int) $wpdb->insert_id;
		if ( ! $order_item_id ) {
			return;
		}

		$course_meta = get_post_meta( $course_id, '_sfwd-courses', true );
		$price       = isset( $course_meta['sfwd-courses_course_price'] )
			? floatval( $course_meta['sfwd-courses_course_price'] )
			: 0;

		$metas = array(
			array( 'course_id', $course_id ),
			array( 'quantity', 1 ),
			array( 'subtotal', $price ),
			array( 'total', $price ),
		);

		foreach ( $metas as $pair ) {
			$wpdb->insert(
				$wpdb->prefix . 'masteriyo_order_itemmeta',
				array(
					'order_item_id' => $order_item_id,
					'meta_key'      => $pair[0],
					'meta_value'    => $pair[1],
				)
			);
		}
	}

	/**
	 * Write Masteriyo order post-meta from the LearnDash transaction's metadata.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id
	 */
	private static function update_order_meta_from_ld( int $order_id ): void {
		global $wpdb;

		$course_id   = (int) get_post_meta( $order_id, 'course_id', true );
		$customer_id = (int) get_post_meta( $order_id, 'user_id', true );

		$course_meta = get_post_meta( $course_id, '_sfwd-courses', true );
		$total       = isset( $course_meta['sfwd-courses_course_price'] )
			? floatval( $course_meta['sfwd-courses_course_price'] )
			: 0;

		// LD stores currency in a meta key that ends with `_currency`.
		$currency = '';
		foreach ( (array) get_post_meta( $order_id ) as $key => $value ) {
			if ( preg_match( '/_currency$/', $key ) ) {
				$currency = $value[0];
				break;
			}
		}

		$version    = get_post_meta( $order_id, 'learndash_version', true );
		$user_email = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM {$wpdb->users} WHERE ID = %d", $customer_id ) );

		update_post_meta( $order_id, '_customer_id', $customer_id );
		update_post_meta( $order_id, '_total', $total );
		update_post_meta( $order_id, '_currency', $currency );
		update_post_meta( $order_id, '_version', $version );
		update_post_meta( $order_id, '_billing_email', $user_email );
		update_post_meta( $order_id, '_billing_address_index', $user_email );
		update_post_meta( $order_id, '_was_ld_order', true );

		// Billing name and payment method.
		$first_name = get_user_meta( $customer_id, 'first_name', true );
		$last_name  = get_user_meta( $customer_id, 'last_name', true );
		if ( ! $first_name && ! $last_name ) {
			$user = get_user_by( 'id', $customer_id );
			if ( $user ) {
				$parts      = explode( ' ', $user->display_name, 2 );
				$first_name = $parts[0];
				$last_name  = isset( $parts[1] ) ? $parts[1] : '';
			}
		}
		update_post_meta( $order_id, '_billing_first_name', sanitize_text_field( $first_name ) );
		update_post_meta( $order_id, '_billing_last_name', sanitize_text_field( $last_name ) );

		$charge_id = get_post_meta( $order_id, 'stripe_charge_id', true );
		$intent_id = get_post_meta( $order_id, 'stripe_payment_intent_id', true );
		$paypal_id = get_post_meta( $order_id, 'paypal_txn_id', true );

		if ( $charge_id ) {
			update_post_meta( $order_id, '_transaction_id', $charge_id );
			update_post_meta( $order_id, '_payment_method', 'stripe' );
		} elseif ( $intent_id ) {
			update_post_meta( $order_id, '_transaction_id', $intent_id );
			update_post_meta( $order_id, '_payment_method', 'stripe' );
		} elseif ( $paypal_id ) {
			update_post_meta( $order_id, '_transaction_id', $paypal_id );
			update_post_meta( $order_id, '_payment_method', 'paypal' );
		}
	}

	/**
	 * Retrieve the LDLMS course steps for a given course.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id
	 * @return array
	 */
	private static function get_course_steps( int $course_id ): array {
		$steps = LDLMS_Factory_Post::course_steps( $course_id );
		return $steps ? $steps->get_steps() : array();
	}

	/**
	 * Set _course_id and _parent_id post-meta on a course item (section, lesson, quiz).
	 *
	 * @since 1.16.0
	 *
	 * @param int $item_id
	 * @param int $course_id
	 * @param int $parent_id
	 */
	private static function update_course_item_meta( int $item_id, int $course_id, int $parent_id ): void {
		update_post_meta( $item_id, '_course_id', $course_id );
		update_post_meta( $item_id, '_parent_id', $parent_id );
	}

	/**
	 * Bulk-update course_progress status once all lesson_progress items are migrated.
	 * Replaces the per-item recount queries — runs once after the step completes.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 */
	public static function finalize_step( string $step ): void {
		if ( 'enrollments' === $step ) {
			// Catch group-enrolled users who never visited the course
			// and therefore have no activity row in wp_learndash_user_activity.
			self::migrate_group_enrollments();
			return;
		}

		if ( 'lesson_progress' !== $step ) {
			return;
		}

		global $wpdb;
		$tbl = $wpdb->prefix . 'masteriyo_user_activities';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"UPDATE {$tbl} AS parent
			 INNER JOIN (
			     SELECT parent_id,
			            COUNT(*)                                     AS total_all,
			            SUM( activity_status = 'completed' )         AS total_done
			     FROM   {$tbl}
			     WHERE  parent_id > 0
			     GROUP  BY parent_id
			 ) AS counts ON parent.id = counts.parent_id
			 SET parent.activity_status = CASE
			         WHEN counts.total_all > 0 AND counts.total_done >= counts.total_all THEN 'completed'
			         WHEN counts.total_done > 0                                          THEN 'progress'
			         ELSE                                                                     'started'
			     END,
			     parent.completed_at = IF(
			         counts.total_all > 0 AND counts.total_done >= counts.total_all,
			         UTC_TIMESTAMP(),
			         NULL
			     ),
			     parent.modified_at  = UTC_TIMESTAMP()
			 WHERE parent.activity_type = 'course_progress'"
		);
		// phpcs:enable
	}

	/**
	 * Enroll users who were added to a LearnDash Group but never visited the course page,
	 * so they have no row in wp_learndash_user_activity (activity_type='access').
	 *
	 * Reads group membership from `learndash_group_users_{group_id}` post meta and
	 * course assignments from `ld_auto_enroll_group_course_ids` post meta.
	 * Inserts into masteriyo_user_items only when the pair is not already enrolled.
	 *
	 * @since x.x.x
	 */
	private static function migrate_group_enrollments(): void {
		global $wpdb;

		$group_ids = $wpdb->get_col(
			$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'", 'groups' )
		);

		if ( empty( $group_ids ) ) {
			return;
		}

		foreach ( $group_ids as $group_id ) {
			$group_id = (int) $group_id;

			$course_ids = get_post_meta( $group_id, 'ld_auto_enroll_group_course_ids', true );
			if ( empty( $course_ids ) || ! is_array( $course_ids ) ) {
				continue;
			}

			$member_ids = get_post_meta( $group_id, "learndash_group_users_{$group_id}", true );
			if ( empty( $member_ids ) || ! is_array( $member_ids ) ) {
				continue;
			}

			foreach ( $course_ids as $course_id ) {
				$course_id = (int) $course_id;
				if ( ! $course_id ) {
					continue;
				}

				foreach ( $member_ids as $user_id ) {
					$user_id = (int) $user_id;
					if ( ! $user_id ) {
						continue;
					}

					// Skip if already enrolled via the main enrollments step.
					$exists = (int) $wpdb->get_var(
						$wpdb->prepare(
							"SELECT id FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %d AND item_id = %d AND item_type = 'user_course'",
							$user_id,
							$course_id
						)
					);
					if ( $exists ) {
						continue;
					}

					$enrolled_ts = get_user_meta( $user_id, "learndash_group_{$group_id}_enrolled_at", true );
					$date_start  = $enrolled_ts
						? gmdate( 'Y-m-d H:i:s', (int) $enrolled_ts )
						: current_time( 'mysql', true );

					$wpdb->insert(
						$wpdb->prefix . 'masteriyo_user_items',
						array(
							'item_id'       => $course_id,
							'user_id'       => $user_id,
							'item_type'     => 'user_course',
							'date_start'    => $date_start,
							'date_end'      => '0000-00-00 00:00:00',
							'date_modified' => $date_start,
							'parent_id'     => 0,
							'status'        => UserCourseStatus::ACTIVE,
						),
						array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
					);

					Helper::update_user_role( $user_id, Roles::STUDENT );
				}
			}
		}
	}
}
