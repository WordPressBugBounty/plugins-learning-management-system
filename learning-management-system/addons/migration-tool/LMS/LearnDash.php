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
use Masteriyo\Addons\MigrationTool\MigrationMap;
use Masteriyo\Addons\MigrationTool\PostCloner;
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

	/**
	 * Migrator slug, the key every migration map row is written under.
	 */
	const SLUG = 'sfwd-lms';

	// ──────────────────────────────────────────────────────────────────────────
	// Batch API — called by LearnDashMigrator
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Count the source items a step still has to copy.
	 *
	 * Rows already in the migration map are left out, so a second run counts only
	 * what is new. Nothing is removed from LearnDash.
	 *
	 * @param string $step Step name.
	 * @return int
	 */
	public static function count_source_items( string $step ): int {
		global $wpdb;

		$activity_table = $wpdb->prefix . 'learndash_user_activity';
		$not_post       = MigrationMap::unmapped_sql( 'ID', self::SLUG, MigrationMap::POST );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		switch ( $step ) {
			case 'users':
				$not_user = MigrationMap::unmapped_sql( 'id', self::SLUG, MigrationMap::USER );
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM (
							SELECT DISTINCT u.ID AS id
							FROM {$wpdb->users} u
							INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
							WHERE um.meta_key = %s AND um.meta_value LIKE %s
							UNION
							SELECT DISTINCT user_id AS id
							FROM {$activity_table}
							WHERE activity_type = %s
						) AS combined
						WHERE 1=1 {$not_user}",
						$wpdb->get_blog_prefix() . 'capabilities',
						'%group_leader%',
						'access'
					)
				);

			case 'courses':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'sfwd-courses' {$not_post}" );

			case 'enrollments':
				$not_enrollment = MigrationMap::unmapped_sql( 'activity_id', self::SLUG, 'enrollments' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$activity_table} WHERE activity_type = 'access' {$not_enrollment}" );

			case 'lesson_progress':
				$not_progress = MigrationMap::unmapped_sql( 'activity_id', self::SLUG, 'lesson_progress' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$activity_table} WHERE activity_type IN ('lesson', 'topic', 'quiz') {$not_progress}" );

			case 'orders':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'sfwd-transactions' {$not_post}" );

			case 'quiz_attempts':
				$ref_table = $wpdb->prefix . 'learndash_pro_quiz_statistic_ref';
				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $ref_table ) ) !== $ref_table ) {
					return 0;
				}
				$not_attempt = MigrationMap::unmapped_sql( 'statistic_ref_id', self::SLUG, 'quiz_attempts' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$ref_table} WHERE 1=1 {$not_attempt}" );

			case 'assignments':
				// Lessons with at least one submission that has not been copied yet.
				$not_submission = MigrationMap::unmapped_sql( 'p.ID', self::SLUG, MigrationMap::POST );
				return (int) $wpdb->get_var(
					"SELECT COUNT(DISTINCT CAST(pm.meta_value AS UNSIGNED))
					 FROM {$wpdb->postmeta} pm
					 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
					 WHERE p.post_type = 'sfwd-assignment' AND pm.meta_key = 'lesson_id' {$not_submission}"
				);
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return 0;
	}

	/**
	 * Return one batch of source IDs starting after $cursor.
	 *
	 * Every step pages by ID and leaves out rows already in the migration map. Nothing
	 * is removed from LearnDash, so nothing is "self-cleaning"; $exclude is unused
	 * because a cursor never revisits a failed ID within a run.
	 *
	 * @param string $step    Step name.
	 * @param int    $limit   Batch size.
	 * @param int    $cursor  Last processed ID (0 = first batch).
	 * @param int[]  $exclude Unused.
	 * @return int[]
	 */
	public static function get_source_ids( string $step, int $limit, int $cursor, array $exclude = array() ): array {
		global $wpdb;

		$activity_table = $wpdb->prefix . 'learndash_user_activity';
		$not_post       = MigrationMap::unmapped_sql( 'ID', self::SLUG, MigrationMap::POST );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		switch ( $step ) {
			case 'users':
				$not_user = MigrationMap::unmapped_sql( 'id', self::SLUG, MigrationMap::USER );
				$ids      = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT id FROM (
							SELECT DISTINCT u.ID AS id
							FROM {$wpdb->users} u
							INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
							WHERE um.meta_key = %s AND um.meta_value LIKE %s
							UNION
							SELECT DISTINCT user_id AS id
							FROM {$activity_table}
							WHERE activity_type = %s
						) AS combined
						WHERE id > %d {$not_user}
						ORDER BY id ASC
						LIMIT %d",
						$wpdb->get_blog_prefix() . 'capabilities',
						'%group_leader%',
						'access',
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'courses':
				return self::post_ids_after( 'sfwd-courses', $cursor, $limit, $not_post );

			case 'enrollments':
				return self::activity_ids_after( "activity_type = 'access'", 'enrollments', $cursor, $limit );

			case 'lesson_progress':
				return self::activity_ids_after( "activity_type IN ('lesson', 'topic', 'quiz')", 'lesson_progress', $cursor, $limit );

			case 'orders':
				return self::post_ids_after( 'sfwd-transactions', $cursor, $limit, $not_post );

			case 'quiz_attempts':
				$ref_table = $wpdb->prefix . 'learndash_pro_quiz_statistic_ref';
				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $ref_table ) ) !== $ref_table ) {
					return array();
				}
				$not_attempt = MigrationMap::unmapped_sql( 'statistic_ref_id', self::SLUG, 'quiz_attempts' );
				$ids         = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT statistic_ref_id FROM {$ref_table}
						 WHERE statistic_ref_id > %d {$not_attempt}
						 ORDER BY statistic_ref_id ASC
						 LIMIT %d",
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'assignments':
				// Lessons with at least one submission that has not been copied yet.
				$not_submission = MigrationMap::unmapped_sql( 'p.ID', self::SLUG, MigrationMap::POST );
				$ids            = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED) AS lesson_id
						 FROM {$wpdb->postmeta} pm
						 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
						 WHERE p.post_type = 'sfwd-assignment' AND pm.meta_key = 'lesson_id'
						   AND CAST(pm.meta_value AS UNSIGNED) > %d {$not_submission}
						 ORDER BY lesson_id ASC
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
	 * Migrate a single item for the given step.
	 *
	 * Must be idempotent: safe to call twice for the same (step, item_id) pair.
	 *
	 * @param string $step    Step name matching a key in get_steps().
	 * @param int    $item_id Source item ID (post ID, user ID or table row ID).
	 * @throws \Exception Triggers ROLLBACK in the job engine; item is added to the failed list.
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
			case 'assignments':
				self::migrate_single_assignment( $item_id );
				break;
		}
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
	 * One page of learndash_user_activity IDs, after the cursor, that are not in the map yet.
	 *
	 * @param string $where  Activity type condition, a literal SQL fragment.
	 * @param string $entity Map entity the step records under.
	 * @param int    $cursor Last processed ID.
	 * @param int    $limit  Batch size.
	 * @return int[]
	 */
	private static function activity_ids_after( string $where, string $entity, int $cursor, int $limit ): array {
		global $wpdb;

		$table   = $wpdb->prefix . 'learndash_user_activity';
		$not_row = MigrationMap::unmapped_sql( 'activity_id', self::SLUG, $entity );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT activity_id FROM {$table}
				 WHERE {$where}
				   AND activity_id > %d
				 {$not_row}
				 ORDER BY activity_id ASC
				 LIMIT %d",
				$cursor,
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( 'intval', $ids ? $ids : array() );
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Per-item migration
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Give a LearnDash group leader or learner the matching Masteriyo role.
	 *
	 * LearnDash's own roles and capabilities stay: LearnDash keeps running until the
	 * site owner deactivates it.
	 *
	 * @param int $user_id WP user ID.
	 * @throws \Exception If the user does not exist.
	 */
	private static function migrate_single_user( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			throw new \Exception( sprintf( 'migrate_single_user: WP user %d not found.', $user_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$caps = is_array( $user->caps ) ? $user->caps : array();
		if ( in_array( 'group_leader', (array) $user->roles, true ) || isset( $caps['group_leader'] ) ) {
			Helper::update_user_role( $user_id, Roles::INSTRUCTOR );
		} else {
			Helper::update_user_role( $user_id, Roles::STUDENT );
		}

		MigrationMap::record( self::SLUG, MigrationMap::USER, $user_id, $user_id );
	}

	/**
	 * Copy a LearnDash course with its sections, lessons, topics, quizzes and questions.
	 *
	 * LearnDash's posts are read, never changed: the course is cloned and the clone
	 * converted; the clone carries the source meta, so the LearnDash meta reads below
	 * keep working on it. Enrollments are owned by the 'enrollments' step.
	 *
	 * @param int $source_course_id sfwd-courses post ID.
	 * @throws \Exception If the post does not exist or cannot be copied.
	 */
	private static function migrate_single_course( int $source_course_id ): void {
		$course = get_post( $source_course_id );
		if ( ! $course ) {
			throw new \Exception( sprintf( 'migrate_single_course: post %d not found.', $source_course_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$course_id = PostCloner::clone_post( self::SLUG, $source_course_id, array( 'post_type' => PostType::COURSE ) );

		self::build_course_curriculum( $source_course_id, $course_id );
		self::update_masteriyo_course_from_ld( $course_id );
		Helper::migrate_course_author( $course_id );
	}

	/**
	 * Build the copied course's curriculum: sections, lessons, topics, and quizzes.
	 *
	 * Steps are read from the LearnDash course; each lesson, topic and quiz is cloned
	 * into a new section of the copied course. LearnDash stores section headings in the
	 * `course_sections` post-meta as a JSON array of `{order, post_title}` objects, where
	 * `order` is the 0-based index of the section heading in the COMBINED flat list of
	 * all course items (section headings + lessons). Because section headings themselves
	 * occupy positions in that list, `order` is NOT a direct lesson index. To convert:
	 * lesson_index = order − idx, where idx is the 0-based position of this section in
	 * the sections-sorted-by-order array (the number of section headings that precede it).
	 *
	 * @param int $source_course_id sfwd-courses post ID.
	 * @param int $course_id        Copied mto-course post ID.
	 */
	private static function build_course_curriculum( int $source_course_id, int $course_id ): void {
		$total_data = self::get_course_steps( $source_course_id );

		if ( empty( $total_data ) ) {
			return;
		}

		$author_id = (int) get_post_field( 'post_author', $course_id );

		// Build a map of lesson-index → section title.
		$raw_headings = get_post_meta( $source_course_id, 'course_sections', true );
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
					$section_id = self::inserted( Helper::insert_post( $sections_at[ $i ], '', $author_id, PostType::SECTION, $menu_order, $course_id ), 'section' );
					self::update_course_item_meta( $section_id, $course_id, $course_id );
					++$menu_order;
				}

				if ( self::copy_course_step( (int) $lesson_key, PostType::LESSON, $menu_order, $section_id, $course_id ) ) {
					++$menu_order;
				}

				// LearnDash topics are sub-lessons → Masteriyo lessons in the same section.
				foreach ( (array) ( isset( $lesson_data['sfwd-topic'] ) ? $lesson_data['sfwd-topic'] : array() ) as $topic_key => $topic_data ) {
					if ( self::copy_course_step( (int) $topic_key, PostType::LESSON, $menu_order, $section_id, $course_id ) ) {
						++$menu_order;
					}

					foreach ( array_keys( (array) ( isset( $topic_data['sfwd-quiz'] ) ? $topic_data['sfwd-quiz'] : array() ) ) as $quiz_key ) {
						$quiz_id = self::copy_course_step( (int) $quiz_key, PostType::QUIZ, $menu_order, $section_id, $course_id );
						if ( $quiz_id ) {
							++$menu_order;
							self::migrate_ld_quiz( $quiz_id, $course_id );
						}
					}
				}

				// Quizzes directly under this lesson (not under a topic).
				foreach ( array_keys( (array) ( isset( $lesson_data['sfwd-quiz'] ) ? $lesson_data['sfwd-quiz'] : array() ) ) as $quiz_key ) {
					$quiz_id = self::copy_course_step( (int) $quiz_key, PostType::QUIZ, $menu_order, $section_id, $course_id );
					if ( $quiz_id ) {
						++$menu_order;
						self::migrate_ld_quiz( $quiz_id, $course_id );
					}
				}

				++$i;
			}

			// Top-level quizzes not nested under any lesson.
			if ( ! empty( $total_data['sfwd-quiz'] ) ) {
				foreach ( array_keys( $total_data['sfwd-quiz'] ) as $quiz_key ) {
					$quiz_id = self::copy_course_step( (int) $quiz_key, PostType::QUIZ, $menu_order, $section_id, $course_id );
					if ( $quiz_id ) {
						++$menu_order;
						self::migrate_ld_quiz( $quiz_id, $course_id );
					}
				}
			}
		}

		// Courses that contain only standalone quizzes (no lessons at all).
		if ( empty( $total_data['sfwd-lessons'] ) && ! empty( $total_data['sfwd-quiz'] ) ) {
			$section_id = self::inserted( Helper::insert_post( __( 'Section', 'learning-management-system' ), '', $author_id, PostType::SECTION, 0, $course_id ), 'section' );
			self::update_course_item_meta( $section_id, $course_id, $course_id );

			foreach ( array_keys( $total_data['sfwd-quiz'] ) as $quiz_key ) {
				$quiz_id = self::copy_course_step( (int) $quiz_key, PostType::QUIZ, $menu_order, $section_id, $course_id );
				if ( $quiz_id ) {
					++$menu_order;
					self::migrate_ld_quiz( $quiz_id, $course_id );
				}
			}
		}
	}

	/**
	 * Copy a LearnDash lesson, topic or quiz into a section of the copied course.
	 *
	 * LearnDash shared course steps: a step in several courses has one copy, kept by
	 * the course that copied it first; later courses skip it with a warning.
	 *
	 * @param int    $source_id  sfwd-lessons, sfwd-topic or sfwd-quiz post ID.
	 * @param string $post_type  Masteriyo post type for the copy.
	 * @param int    $menu_order Position in the section.
	 * @param int    $section_id Copied mto-section post ID.
	 * @param int    $course_id  Copied mto-course post ID.
	 * @return int Copied post ID, or 0 when the copy belongs to another course.
	 */
	private static function copy_course_step( int $source_id, string $post_type, int $menu_order, int $section_id, int $course_id ): int {
		if ( Helper::copy_kept_by_another_course( self::SLUG, 'LearnDash', $post_type, $source_id, $course_id ) ) {
			return 0;
		}

		$copy_id = PostCloner::clone_post(
			self::SLUG,
			$source_id,
			array(
				'post_type'   => $post_type,
				'post_parent' => $section_id,
				'menu_order'  => $menu_order,
			)
		);
		self::update_course_item_meta( $copy_id, $course_id, $section_id );

		return $copy_id;
	}

	/**
	 * The ID Helper::insert_post() returned, or an exception naming what could not be made.
	 *
	 * @param int|\WP_Error $post_id Result of Helper::insert_post().
	 * @param string        $what    What was being inserted, for the message.
	 * @return int
	 * @throws \Exception When the insert failed.
	 */
	private static function inserted( $post_id, string $what ): int {
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			$reason = is_wp_error( $post_id ) ? $post_id->get_error_message() : 'wp_insert_post returned 0';
			throw new \Exception( sprintf( 'Could not insert the %s: %s', $what, $reason ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		return (int) $post_id;
	}

	/**
	 * Copy a LearnDash enrollment (activity_type='access') to masteriyo_user_items.
	 *
	 * The activity row stays. A row whose user is gone or who is already enrolled in
	 * the copied course is recorded as a skip so it is not fetched again.
	 *
	 * @param int $activity_id Primary key of the learndash_user_activity row.
	 * @throws \Exception If the row or its copied course is missing, or the insert fails.
	 */
	private static function migrate_single_enrollment( int $activity_id ): void {
		global $wpdb;

		$ld_table = $wpdb->prefix . 'learndash_user_activity';
		$activity = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$ld_table} WHERE activity_id = %d", $activity_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $activity ) {
			throw new \Exception( sprintf( 'migrate_single_enrollment: activity %d not found.', $activity_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$user_id          = (int) $activity['user_id'];
		$source_course_id = (int) $activity['course_id'];

		// Records a skip so the row is not fetched again.
		$skip = function() use ( $activity_id ): void {
			MigrationMap::record( self::SLUG, 'enrollments', $activity_id, 0 );
		};

		if ( ! get_userdata( $user_id ) ) {
			$skip();
			return;
		}

		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_course_id, 'course' );

		$already = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %d AND item_id = %d AND item_type = 'user_course'",
				$user_id,
				$course_id
			)
		);

		if ( $already ) {
			$skip();
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

		// The LearnDash transaction for this pair; its copy, when the orders step has
		// already run, becomes the order reference. Otherwise the orders step links it.
		$source_order_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = %s
				 INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
				 WHERE CAST(pm1.meta_value AS UNSIGNED) = %d
				   AND CAST(pm2.meta_value AS UNSIGNED) = %d
				   AND p.post_type = 'sfwd-transactions'
				 LIMIT 1",
				'course_id',
				'user_id',
				$source_course_id,
				$user_id
			)
		);

		$order_id = $source_order_id ? MigrationMap::target( self::SLUG, MigrationMap::POST, $source_order_id ) : 0;

		$price = 0.0;
		if ( $source_order_id ) {
			$course_meta = get_post_meta( $source_course_id, '_sfwd-courses', true );
			$price       = isset( $course_meta['sfwd-courses_course_price'] )
				? floatval( $course_meta['sfwd-courses_course_price'] )
				: 0.0;
		}

		$user_item_id = Helper::enroll_user( $user_id, $course_id, $date_start, $date_end, UserCourseStatus::ACTIVE, $order_id ? $order_id : null, $price, $date_modified );
		if ( null === $user_item_id ) {
			throw new \Exception( sprintf( 'migrate_single_enrollment: failed to enroll user %d in course %d.', $user_id, $course_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		MigrationMap::record( self::SLUG, 'enrollments', $activity_id, $user_item_id );
	}

	/**
	 * Copy a LearnDash lesson/topic/quiz activity to masteriyo_user_activities.
	 *
	 * Finds or creates the parent course_progress row on the copied course and records
	 * the item completion against the copied lesson or quiz. The activity row stays;
	 * finalize_step() recalculates the course-progress status once.
	 *
	 * @param int $activity_id Primary key of the learndash_user_activity row.
	 * @throws \Exception If the row, its copied course or its copied item is missing, or a write fails.
	 */
	private static function migrate_single_lesson_progress( int $activity_id ): void {
		global $wpdb;

		$ld_table = $wpdb->prefix . 'learndash_user_activity';
		$activity = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$ld_table} WHERE activity_id = %d", $activity_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $activity ) {
			throw new \Exception( sprintf( 'migrate_single_lesson_progress: activity %d not found.', $activity_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$user_id          = (int) $activity['user_id'];
		$source_course_id = (int) $activity['course_id'];
		$source_post_id   = (int) $activity['post_id'];

		// Records a skip so the row is not fetched again.
		$skip = function() use ( $activity_id ): void {
			MigrationMap::record( self::SLUG, 'lesson_progress', $activity_id, 0 );
		};

		if ( ! $source_post_id || ! $source_course_id || ! get_userdata( $user_id ) ) {
			$skip();
			return;
		}

		$is_quiz   = 'quiz' === $activity['activity_type'];
		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_course_id, 'course' );
		$post_id   = MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_post_id, $is_quiz ? 'quiz' : 'lesson' );
		$item_type = $is_quiz ? CourseProgressItemType::QUIZ : CourseProgressItemType::LESSON;
		$act_table = $wpdb->prefix . 'masteriyo_user_activities';

		$started_at  = $activity['activity_started']
			? gmdate( 'Y-m-d H:i:s', (int) $activity['activity_started'] )
			: current_time( 'mysql', true );
		$progress_id = Helper::get_or_create_course_progress( $user_id, $course_id, $started_at );

		if ( ! $progress_id ) {
			throw new \Exception( sprintf( 'migrate_single_lesson_progress: could not create course_progress for user %d course %d.', $user_id, $course_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$item_exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$act_table} WHERE user_id = %d AND item_id = %d AND parent_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$post_id,
				$progress_id
			)
		);

		if ( $item_exists ) {
			$skip();
			return;
		}

		$item_status  = $activity['activity_status']
			? CourseProgressStatus::COMPLETED
			: CourseProgressStatus::STARTED;
		$completed_at = ( CourseProgressStatus::COMPLETED === $item_status && ! empty( $activity['activity_completed'] ) )
			? gmdate( 'Y-m-d H:i:s', (int) $activity['activity_completed'] )
			: null;

		$inserted = $wpdb->insert(
			$act_table,
			array(
				'user_id'         => $user_id,
				'item_id'         => $post_id,
				'activity_type'   => $item_type,
				'activity_status' => $item_status,
				'parent_id'       => $progress_id,
				'created_at'      => $started_at,
				'modified_at'     => $started_at,
				'completed_at'    => $completed_at,
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			throw new \Exception( sprintf( 'migrate_single_lesson_progress: insert failed for activity %d: %s', $activity_id, $wpdb->last_error ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		MigrationMap::record( self::SLUG, 'lesson_progress', $activity_id, (int) $wpdb->insert_id );
	}

	/**
	 * Copy a LearnDash transaction (sfwd-transactions) to a new mto-order.
	 *
	 * All LD transactions represent successful payments so they map to
	 * OrderStatus::COMPLETED. The transaction post stays; the copy carries its meta.
	 *
	 * @param int $source_order_id sfwd-transactions post ID.
	 * @throws \Exception If the post or its copied course is missing.
	 */
	private static function migrate_single_order( int $source_order_id ): void {
		$post = get_post( $source_order_id );
		if ( ! $post ) {
			throw new \Exception( sprintf( 'migrate_single_order: post %d not found.', $source_order_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// Resolved before the copy so a missing course leaves nothing half-made.
		$source_course_id = (int) get_post_meta( $source_order_id, 'course_id', true );
		$course_id        = $source_course_id
			? MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_course_id, 'course' )
			: 0;

		$order_time = strtotime( $post->post_date );
		$title      = __( 'Order', 'learning-management-system' )
			. ' &ndash; '
			. gmdate( get_option( 'date_format' ), $order_time )
			. ' @ '
			. gmdate( get_option( 'time_format' ), $order_time );

		$order_id = PostCloner::clone_post(
			self::SLUG,
			$source_order_id,
			array(
				'post_type'     => PostType::ORDER,
				'post_status'   => OrderStatus::COMPLETED,
				'post_title'    => $title,
				'post_password' => masteriyo_generate_order_key(),
			)
		);

		self::migrate_order_item_from_ld( $order_id, $course_id );
		self::update_order_meta_from_ld( $order_id, $course_id );
		self::link_order_to_enrollment( $order_id, $course_id, (int) get_post_meta( $order_id, 'user_id', true ) );
	}

	/**
	 * Point an already-copied enrollment at its copied order.
	 *
	 * The enrollments step runs before orders, so the order did not exist when the
	 * enrollment was copied.
	 *
	 * @param int $order_id    Copied mto-order post ID.
	 * @param int $course_id   Copied mto-course post ID.
	 * @param int $customer_id WP user ID.
	 */
	private static function link_order_to_enrollment( int $order_id, int $course_id, int $customer_id ): void {
		global $wpdb;

		if ( ! $course_id || ! $customer_id ) {
			return;
		}

		$user_item_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ui.id FROM {$wpdb->prefix}masteriyo_user_items ui
				 WHERE ui.user_id = %d AND ui.item_id = %d AND ui.item_type = 'user_course'
				   AND NOT EXISTS (
				       SELECT 1 FROM {$wpdb->prefix}masteriyo_user_itemmeta m
				       WHERE m.user_item_id = ui.id AND m.meta_key = '_order_id'
				   )
				 LIMIT 1",
				$customer_id,
				$course_id
			)
		);

		if ( ! $user_item_id ) {
			return;
		}

		$wpdb->insert(
			$wpdb->prefix . 'masteriyo_user_itemmeta',
			array(
				'user_item_id' => $user_item_id,
				'meta_key'     => '_order_id',
				'meta_value'   => $order_id,
			)
		);
	}

	/**
	 * Copy a WPProQuiz attempt (statistic_ref row + its statistic rows) to
	 * masteriyo_quiz_attempts, against the copied quiz and course.
	 *
	 * The statistic rows stay. An attempt already present on the copied quiz is
	 * recorded as a skip so it is not fetched again.
	 *
	 * @param int $ref_id Primary key of the learndash_pro_quiz_statistic_ref row.
	 * @throws \Exception If the row or its copied quiz is missing, or the insert fails.
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
			throw new \Exception( sprintf( 'migrate_single_quiz_attempt: statistic_ref %d not found.', $ref_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// Records a skip so the row is not fetched again.
		$skip = function() use ( $ref_id ): void {
			MigrationMap::record( self::SLUG, 'quiz_attempts', $ref_id, 0 );
		};

		$user_id = (int) $ref['user_id'];
		if ( ! get_userdata( $user_id ) ) {
			$skip();
			return;
		}

		$quiz_id          = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $ref['quiz_post_id'], 'quiz' );
		$source_course_id = (int) $ref['course_post_id'];
		$course_id        = $source_course_id
			? MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_course_id, 'course' )
			: (int) get_post_meta( $quiz_id, '_course_id', true );
		$started_at       = gmdate( 'Y-m-d H:i:s', (int) $ref['create_time'] );
		$attempts_table   = $wpdb->prefix . 'masteriyo_quiz_attempts';

		$already = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$attempts_table} WHERE quiz_id = %d AND user_id = %d AND attempt_started_at = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$quiz_id,
				$user_id,
				$started_at
			)
		);

		if ( $already ) {
			$skip();
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

		// Answers reference the copied questions; a question that was not copied keeps its LearnDash ID.
		$question_map = MigrationMap::targets( self::SLUG, MigrationMap::POST, array_column( $stats, 'question_post_id' ) );

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
				'question_id'   => $question_map[ (int) $stat['question_post_id'] ] ?? (int) $stat['question_post_id'],
				'correct_count' => $correct,
				'points'        => (float) $stat['points'],
				'answer_data'   => maybe_unserialize( $stat['answer_data'] ),
			);

			// Essay type comes from the pro_quiz table, read through the LearnDash question post.
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

		// Total marks come from the pro-quiz question table; the copied quiz carries quiz_pro_id.
		$quiz_pro_id = (int) get_post_meta( $quiz_id, 'quiz_pro_id', true );
		$total_marks = 0.0;
		if ( $quiz_pro_id ) {
			$total_marks = (float) $wpdb->get_var(
				$wpdb->prepare( "SELECT SUM(points) FROM {$qm_table} WHERE quiz_id = %d", $quiz_pro_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
		}

		// _pass_mark was written onto the copied quiz by the courses step.
		$pass_mark     = (float) get_post_meta( $quiz_id, '_pass_mark', true );
		$pass_mark_pts = ( $total_marks > 0 && $pass_mark > 0 ) ? ( $pass_mark / 100 ) * $total_marks : 0;

		if ( $has_essay ) {
			$attempt_status = 'pending';
		} elseif ( $pass_mark > 0 ) {
			$attempt_status = $earned_marks >= $pass_mark_pts ? 'passed' : 'failed';
		} else {
			$attempt_status = 'passed';
		}

		$inserted = $wpdb->insert(
			$attempts_table,
			array(
				'quiz_id'                  => $quiz_id,
				'course_id'                => $course_id,
				'user_id'                  => $user_id,
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

		if ( false === $inserted ) {
			throw new \Exception( sprintf( 'migrate_single_quiz_attempt: insert failed for statistic_ref %d: %s', $ref_id, $wpdb->last_error ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		MigrationMap::record( self::SLUG, 'quiz_attempts', $ref_id, (int) $wpdb->insert_id );
	}

	/**
	 * Copy the sfwd-assignment submissions of one LearnDash lesson into an
	 * mto-assignment on the copied lesson, with one mto-assignment-reply per submission.
	 *
	 * The lesson's assignment is made once and reused; each submission is copied once.
	 * A lesson is listed by the batch queries while it has an uncopied submission, so a
	 * second run copies only what students handed in since.
	 *
	 * @param int $source_lesson_id sfwd-lessons post ID that owns the assignments.
	 * @throws \Exception If the copied lesson is missing or a post insert fails.
	 */
	private static function migrate_single_assignment( int $source_lesson_id ): void {
		$lesson_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_lesson_id, 'lesson' );
		$course_id = (int) get_post_meta( $lesson_id, '_course_id', true );

		$assignment_id = MigrationMap::target( self::SLUG, 'assignments', $source_lesson_id );

		if ( ! $assignment_id || ! get_post( $assignment_id ) ) {
			$lesson_meta    = get_post_meta( $source_lesson_id, '_sfwd-lessons', true );
			$lesson_meta    = is_array( $lesson_meta ) ? $lesson_meta : array();
			$points_enabled = ! empty( $lesson_meta['sfwd-lessons_assignment_points_enabled'] );
			$total_points   = $points_enabled ? (int) ( isset( $lesson_meta['sfwd-lessons_assignment_points_amount'] ) ? $lesson_meta['sfwd-lessons_assignment_points_amount'] : 100 ) : 100;
			$due_date       = isset( $lesson_meta['sfwd-lessons_assignment_due_date'] ) ? $lesson_meta['sfwd-lessons_assignment_due_date'] : '';
			$max_upload     = (int) ( isset( $lesson_meta['sfwd-lessons_max_upload_size'] ) ? $lesson_meta['sfwd-lessons_max_upload_size'] : 5 );

			$assignment_id = wp_insert_post(
				array(
					'post_type'   => 'mto-assignment',
					'post_status' => 'publish',
					'post_title'  => get_the_title( $lesson_id ) . ' Assignment',
					'post_author' => (int) get_post_field( 'post_author', $lesson_id ),
					'post_parent' => $lesson_id,
				)
			);

			if ( ! $assignment_id || is_wp_error( $assignment_id ) ) {
				throw new \Exception( sprintf( 'migrate_single_assignment: failed to create mto-assignment for lesson %d.', $lesson_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}

			update_post_meta( $assignment_id, '_total_points', $total_points );
			update_post_meta( $assignment_id, '_max_file_upload_size', $max_upload );

			if ( ! empty( $due_date ) ) {
				update_post_meta( $assignment_id, '_due_date', sanitize_text_field( $due_date ) );
			}

			if ( $course_id ) {
				update_post_meta( $assignment_id, '_course_id', $course_id );
			}

			MigrationMap::record( self::SLUG, 'assignments', $source_lesson_id, (int) $assignment_id );
		}

		$submissions = get_posts(
			array(
				'post_type'      => 'sfwd-assignment',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => 'lesson_id',
						'value' => $source_lesson_id,
					),
				),
			)
		);

		foreach ( $submissions as $sub ) {
			if ( MigrationMap::is_settled_post( self::SLUG, (int) $sub->ID ) ) {
				continue;
			}

			$reply_id = wp_insert_post(
				array(
					'post_type'    => 'mto-assignment-reply',
					'post_status'  => 'publish',
					'post_author'  => $sub->post_author,
					'post_content' => $sub->post_content,
					'post_parent'  => $assignment_id,
					'post_date'    => $sub->post_date,
				)
			);

			if ( ! $reply_id || is_wp_error( $reply_id ) ) {
				throw new \Exception( sprintf( 'migrate_single_assignment: failed to copy submission %d.', $sub->ID ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}

			$upload_url = get_post_meta( $sub->ID, 'upload', true );
			if ( $upload_url ) {
				update_post_meta( $reply_id, '_upload', esc_url_raw( $upload_url ) );
			}
			if ( $course_id ) {
				update_post_meta( $reply_id, '_course_id', $course_id );
			}

			MigrationMap::record( self::SLUG, MigrationMap::POST, (int) $sub->ID, (int) $reply_id );
		}
	}

	// ──────────────────────────────────────────────────────────────────────────
	// Private helpers
	// ──────────────────────────────────────────────────────────────────────────

	/**
	 * Migrate quiz questions for a given quiz post.
	 *
	 * Handles single-choice, multiple-choice, true/false, text-answer, fill-in-the-blanks,
	 * sortable, and matching types. Assessment and unknown types are skipped and noted on
	 * the copied quiz. Questions are new mto-question posts; the sfwd-question posts stay.
	 *
	 * @since 1.16.0
	 *
	 * @param int $quiz_id   Copied mto-quiz post ID; it carries the LearnDash quiz meta.
	 * @param int $course_id Copied mto-course post ID.
	 * @throws \Exception If a question insert fails.
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

				// Randomise question order when configured in LearnDash.
				if ( '1' === (string) $master['question_random'] ) {
					update_post_meta( $quiz_id, '_randomize', true );
				}
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

			// A reused quiz copy keeps its questions; they follow it to the new course. The
			// lookup is per quiz because LearnDash shares one question between quizzes.
			$existing = self::question_copy_in_quiz( $quiz_id, (int) $question_post_id );
			if ( $existing ) {
				update_post_meta( $existing, '_course_id', $course_id );
				continue;
			}

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
						$sort      = $ao->getSortString();
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
					// assessment and any unknown types — noted on the copied quiz, never on LearnDash's question.
					Helper::store_unmigrated_meta( $quiz_id, 'ld_skipped_question_' . $question_post_id, $row['answer_type'] );
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

			if ( ! $new_id || is_wp_error( $new_id ) ) {
				throw new \Exception( sprintf( 'migrate_ld_quiz: failed to insert question %d for quiz %d.', $question_post_id, $quiz_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}

			update_post_meta( $new_id, '_course_id', $course_id );
			update_post_meta( $new_id, '_type', $question_type );
			update_post_meta( $new_id, '_points', $row['points'] );
			update_post_meta( $new_id, '_parent_id', $quiz_id );
			update_post_meta( $new_id, '_ld_question_id', (int) $question_post_id );

			// For cloze questions, also store answers separately so they can be graded.
			if ( 'fill-in-the-blanks' === $question_type ) {
				update_post_meta( $new_id, '_answers', wp_json_encode( $answers ) );
			}

			// The map keeps the first copy; quiz attempts resolve their question IDs through it.
			if ( ! MigrationMap::is_settled_post( self::SLUG, (int) $question_post_id ) ) {
				MigrationMap::record( self::SLUG, MigrationMap::POST, (int) $question_post_id, (int) $new_id );
			}
		}
	}

	/**
	 * The copy of a LearnDash question under a copied quiz, or 0.
	 *
	 * @param int $quiz_id          Copied mto-quiz post ID.
	 * @param int $question_post_id sfwd-question post ID.
	 * @return int
	 */
	private static function question_copy_in_quiz( int $quiz_id, int $question_post_id ): int {
		$ids = get_posts(
			array(
				'post_type'      => PostType::QUESTION,
				'post_parent'    => $quiz_id,
				'post_status'    => 'any',
				'meta_key'       => '_ld_question_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $question_post_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'         => 'ids',
				'posts_per_page' => 1,
			)
		);

		return $ids ? (int) $ids[0] : 0;
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

		// Prerequisites point at the copies; a prerequisite not copied yet keeps its LearnDash ID.
		$prerequisite_ids = isset( $meta['sfwd-courses_course_prerequisite'] ) ? $meta['sfwd-courses_course_prerequisite'] : array();
		if ( ! empty( $prerequisite_ids ) && is_array( $prerequisite_ids ) ) {
			$copies = MigrationMap::targets( self::SLUG, MigrationMap::POST, $prerequisite_ids );
			update_post_meta(
				$course_id,
				'_prerequisites_courses',
				array_map(
					function( $id ) use ( $copies ) {
						return $copies[ (int) $id ] ?? (int) $id;
					},
					$prerequisite_ids
				)
			);
		}

		// Sequential flow — enforced unless LD explicitly disables progression.
		$disable_progression = isset( $meta['sfwd-courses_course_disable_lesson_progression'] ) ? $meta['sfwd-courses_course_disable_lesson_progression'] : '';
		if ( 'on' !== $disable_progression ) {
			update_post_meta( $course_id, '_flow', 'sequential' );
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
	 * @param int $order_id  Copied mto-order post ID.
	 * @param int $course_id Copied mto-course post ID, or 0 when the transaction names none.
	 */
	private static function migrate_order_item_from_ld( int $order_id, int $course_id ): void {
		global $wpdb;

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
	 * Write Masteriyo order post-meta from the LearnDash transaction's metadata, which
	 * the copied order carries.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id  Copied mto-order post ID.
	 * @param int $course_id Copied mto-course post ID, or 0 when the transaction names none.
	 */
	private static function update_order_meta_from_ld( int $order_id, int $course_id ): void {
		global $wpdb;

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
	 * @param string $step Step name.
	 */
	public static function finalize_step( string $step ): void {
		if ( 'courses' === $step ) {
			Helper::relink_prerequisites( self::SLUG );
			return;
		}

		if ( 'enrollments' === $step ) {
			// Catch group-enrolled users who never visited the course page
			// and therefore have no row in wp_learndash_user_activity (activity_type='access').
			self::migrate_group_enrollments();
			return;
		}

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
			         COALESCE( NULLIF( parent.completed_at, '0000-00-00 00:00:00' ), UTC_TIMESTAMP() ),
			         NULL
			     ),
			     parent.modified_at  = UTC_TIMESTAMP()
			 WHERE parent.activity_type = 'course_progress'
			   AND parent.item_id IN ( {$copied} )"
		);
		// phpcs:enable
	}

	/**
	 * Enroll users who were added to a LearnDash Group but never visited the course page,
	 * so they have no row in wp_learndash_user_activity (activity_type='access').
	 *
	 * Reads group membership from `learndash_group_users_{group_id}` post meta and
	 * course assignments from `ld_auto_enroll_group_course_ids` post meta. Enrolls into
	 * the copied course, only when the pair is not already enrolled; a course not copied
	 * yet is passed over. The group posts stay.
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

			foreach ( $course_ids as $source_course_id ) {
				$course_id = MigrationMap::target( self::SLUG, MigrationMap::POST, (int) $source_course_id );
				if ( ! $course_id ) {
					continue;
				}

				foreach ( $member_ids as $user_id ) {
					$user_id = (int) $user_id;
					if ( ! $user_id || ! get_userdata( $user_id ) ) {
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
