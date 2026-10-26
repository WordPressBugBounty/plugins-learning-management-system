<?php
/**
 * LifterLMS migrations.
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
use Masteriyo\Addons\WcIntegration\CourseProduct;
use Masteriyo\Addons\WcIntegration\Helper as HelperWoocommerce;
use Masteriyo\Enums\CommentStatus;
use Masteriyo\Enums\CommentType;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CoursePriceType;
use Masteriyo\Enums\CourseProgressItemType;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuestionType;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Roles;

/**
 * Class LifterLMS.
 *
 * LifterLMS's posts, tables and roles are read, never changed: each record is copied
 * into Masteriyo and the copy recorded in the migration map, so the site can keep
 * running LifterLMS while the copy is checked and a second run picks up only what is new.
 *
 * @since 1.16.0
 */
class LifterLMS {

	/**
	 * Migrator slug, the key every migration map row is written under.
	 */
	const SLUG = 'lifterlms';

	/**
	 * Copy a LifterLMS course with its sections, lessons, quizzes and questions.
	 *
	 * @since 1.16.0
	 *
	 * @param int $source_course_id LifterLMS course ID.
	 * @return int Masteriyo course ID (the copy).
	 */
	private static function migrate_course( int $source_course_id ): int {
		$course   = new \LLMS_Course( $source_course_id );
		$sections = $course->get_sections();

		$course_id = PostCloner::clone_post( self::SLUG, $source_course_id, array( 'post_type' => PostType::COURSE ) );
		update_post_meta( $course_id, '_was_lf_course', true );

		$section_order = 0;

		foreach ( (array) $sections as $section ) {
			$section_id = PostCloner::clone_post(
				self::SLUG,
				(int) $section->id,
				array(
					'post_type'   => PostType::SECTION,
					'post_status' => PostStatus::PUBLISH,
					'post_author' => $course->post->post_author,
					'post_parent' => $course_id,
					'menu_order'  => $section_order,
				)
			);
			++$section_order;

			update_post_meta( $section_id, '_course_id', $course_id );

			$item_order = 0;

			foreach ( (array) $section->get_lessons() as $lesson ) {
				$quiz = $lesson->has_quiz() ? $lesson->get_quiz() : false;

				// A lesson that ends in a quiz is copied as that quiz alone, as it always was.
				if ( $quiz ) {
					self::copy_quiz( $quiz, $section_id, $course_id, $item_order );
				} else {
					self::copy_lesson( $lesson, $section_id, $course_id, $item_order );
				}

				++$item_order;
			}
		}

		return $course_id;
	}

	/**
	 * Copy a LifterLMS lesson into a section of the copied course.
	 *
	 * @param object $lesson     LifterLMS lesson (LLMS_Lesson).
	 * @param int    $section_id Masteriyo section ID (the copy).
	 * @param int    $course_id  Masteriyo course ID (the copy).
	 * @param int    $menu_order Position in the section.
	 */
	private static function copy_lesson( $lesson, int $section_id, int $course_id, int $menu_order ): void {
		$lesson_id = PostCloner::clone_post(
			self::SLUG,
			(int) $lesson->id,
			array(
				'post_type'   => PostType::LESSON,
				'post_parent' => $section_id,
				'menu_order'  => $menu_order,
			)
		);

		update_post_meta( $lesson_id, '_course_id', $course_id );

		// The copy carries the LifterLMS meta.
		$url    = get_post_meta( $lesson_id, '_llms_video_embed', true );
		$source = Helper::determine_video_source_from_url( $url );

		if ( is_array( $source ) ) {
			$source = $source[0];
		}

		update_post_meta( $lesson_id, '_video_source', $source );
		update_post_meta( $lesson_id, '_video_source_url', $url );

		if ( 'yes' === get_post_meta( $lesson_id, '_llms_free_lesson', true ) ) {
			update_post_meta( $lesson_id, '_enable_preview', 1 );
		}

		self::migrate_single_content_drip( $lesson_id );
	}

	/**
	 * Copy a LifterLMS quiz and its questions into a section of the copied course.
	 *
	 * @param object $quiz       LifterLMS quiz (LLMS_Quiz).
	 * @param int    $section_id Masteriyo section ID (the copy).
	 * @param int    $course_id  Masteriyo course ID (the copy).
	 * @param int    $menu_order Position in the section.
	 */
	private static function copy_quiz( $quiz, int $section_id, int $course_id, int $menu_order ): void {
		$questions = $quiz->get_questions();

		// A quiz with no questions has nothing to copy and is left out, as it always was.
		if ( empty( $questions ) ) {
			masteriyo_get_logger()->debug(
				sprintf( 'Migration: Skipping LifterLMS quiz %d (no questions).', $quiz->id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$quiz_id = PostCloner::clone_post(
			self::SLUG,
			(int) $quiz->id,
			array(
				'post_type'   => PostType::QUIZ,
				'post_parent' => $section_id,
				'menu_order'  => $menu_order,
			)
		);

		update_post_meta( $quiz_id, '_course_id', $course_id );

		$k = 0;
		foreach ( $questions as $question ) {
			self::process_question_migration( $question, $quiz_id, $course_id, $k );
			++$k;
		}

		if ( 'no' === $quiz->get( 'limit_attempts' ) ) {
			update_post_meta( $quiz_id, '_attempts_allowed', 0 );
		} else {
			update_post_meta( $quiz_id, '_attempts_allowed', absint( $quiz->get( 'allowed_attempts' ) ) );
		}

		if ( 'no' === $quiz->get( 'limit_time' ) ) {
			update_post_meta( $quiz_id, '_duration', 0 );
		} else {
			update_post_meta( $quiz_id, '_duration', absint( $quiz->get( 'time_limit' ) ) * 60 );
		}

		update_post_meta( $quiz_id, '_pass_mark', floatval( $quiz->get( 'passing_percent' ) ) );
		update_post_meta( $quiz_id, '_randomize', $quiz->get( 'random_questions' ) );
		update_post_meta( $quiz_id, '_reveal_mode', ( 'yes' === $quiz->get( 'show_correct_answer' ) ) ? 'reveal' : 'no_reveal' );
	}

	/**
	 * Migrate course info from LifterLMS onto the copied course.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id        Masteriyo course ID (the copy, which carries the LifterLMS meta).
	 * @param int $source_course_id LifterLMS course ID, which the access plans point at.
	 */
	private static function migrate_course_info( int $course_id, int $source_course_id ) {
		$max_student = absint( get_post_meta( $course_id, '_llms_capacity', true ) );
		update_post_meta( $course_id, '_enrollment_limit', $max_student );

		$review_enabled = get_post_meta( $course_id, '_llms_reviews_enabled', true );
		update_post_meta( $course_id, '_reviews_allowed', masteriyo_string_to_bool( $review_enabled ) );

		$product_ids = self::get_access_plan_ids( $source_course_id );
		$product_id  = reset( $product_ids );

		if ( ! $product_id ) {
			return;
		}

		$regular_price = get_post_meta( $product_id, '_llms_price', true );
		$sale_price    = get_post_meta( $product_id, '_llms_sale_price', true );
		$is_on_sale    = get_post_meta( $product_id, '_llms_on_sale', true );
		$sale_start    = get_post_meta( $product_id, '_llms_sale_start', true );
		$sale_end      = get_post_meta( $product_id, '_llms_sale_end', true );

		$course_type = CoursePriceType::FREE;
		$access_mode = CourseAccessMode::OPEN;

		if ( $regular_price ) {
			$course_type = CoursePriceType::PAID;
			$access_mode = CourseAccessMode::ONE_TIME;
		}

		// Detect recurring billing across all access plans.
		foreach ( $product_ids as $plan_id ) {
			$billing_period = get_post_meta( $plan_id, '_llms_billing_period', true );
			$is_free_plan   = get_post_meta( $plan_id, '_llms_is_free', true );

			if ( $billing_period && 'yes' !== $is_free_plan ) {
				$course_type = CoursePriceType::PAID;
				$access_mode = CourseAccessMode::RECURRING;
				break;
			}
		}

		// Warn about limited-period expiry — end date cannot be computed at migration time.
		$access_expiration = get_post_meta( $product_id, '_llms_access_expiration', true );
		if ( 'limited-period' === $access_expiration ) {
			masteriyo_get_logger()->warning(
				sprintf(
					'Migration: Course %d has a limited-period access plan — expiry date cannot be computed at migration time and was not migrated.',
					$source_course_id
				),
				array( 'source' => 'migration-tool' )
			);
		}

		wp_set_object_terms( $course_id, $course_type, 'course_visibility', false );
		update_post_meta( $course_id, '_access_mode', $access_mode );
		update_post_meta( $course_id, '_regular_price', $regular_price );

		if ( 'yes' === $is_on_sale ) {
			update_post_meta( $course_id, '_price', $sale_price );
			update_post_meta( $course_id, '_sale_price', $sale_price );
			update_post_meta( $course_id, '_date_on_sale_from', $sale_start );
			update_post_meta( $course_id, '_date_on_sale_to', $sale_end );
		} else {
			update_post_meta( $course_id, '_price', $regular_price );
		}
	}

	/**
	 * Creates a WooCommerce product for a copied LifterLMS course.
	 *
	 * @param int $course_id        Masteriyo course ID (the copy).
	 * @param int $source_course_id LifterLMS course ID, which the access plans point at.
	 *
	 * @return void
	 */
	private static function create_woocommerce_product( int $course_id, int $source_course_id ) {
		if ( ! HelperWoocommerce::is_wc_active() ) {
			return;
		}

		$product_ids = self::get_access_plan_ids( $source_course_id );
		$course      = masteriyo_get_course( $course_id );

		if ( empty( $product_ids ) || ! $course ) {
			return;
		}

		foreach ( $product_ids as $product_id ) {
			$regular_price = get_post_meta( $product_id, '_llms_price', true );
			$sale_price    = get_post_meta( $product_id, '_llms_sale_price', true );
			$is_on_sale    = get_post_meta( $product_id, '_llms_on_sale', true );
			$sale_start    = get_post_meta( $product_id, '_llms_sale_start', true );
			$sale_end      = get_post_meta( $product_id, '_llms_sale_end', true );

			$product = new CourseProduct();

			$product->set_name( $course->get_title() );
			$product->set_description( $course->get_description() );
			$product->set_short_description( $course->get_short_description() );
			$product->set_featured( $course->get_featured() );
			$product->set_price( $course->get_price() );
			$product->set_regular_price( $regular_price );

			if ( 'yes' === $is_on_sale ) {
				$product->set_sale_price( $sale_price );
				$product->set_date_on_sale_from( $sale_start );
				$product->set_date_on_sale_to( $sale_end );
			}

			$product->set_image_id( $course->get_image_id() );
			$product->set_category_ids( $course->get_category_ids() );
			$product->set_tag_ids( $course->get_tag_ids() );
			$product->set_reviews_allowed( $course->get_reviews_allowed() );
			$product->set_catalog_visibility( $course->get_catalog_visibility() );
			$product->set_post_password( $course->get_post_password() );

			$product_id = $product->save();

			if ( $product_id ) {
				update_post_meta( $course_id, '_wc_product_id', $product_id );
				update_post_meta( $product_id, '_masteriyo_course_id', $course_id );
			}
		}
	}

	/**
	 * Get all access plan IDs for a given LifterLMS course ID.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id LifterLMS course ID.
	 *
	 * @return array<int> Array of access plan IDs.
	 */
	private static function get_access_plan_ids( $course_id ) {
		global $wpdb;

		$product_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d ORDER BY post_id ASC",
				'_llms_product_id',
				$course_id
			)
		);

		return $product_ids;
	}

	/**
	 * Copy a single LifterLMS quiz question into the copied quiz.
	 *
	 * Group questions are flattened: the children are copied, the group itself is not.
	 *
	 * @since 1.16.0
	 *
	 * @param object $question   LifterLMS quiz question.
	 * @param int    $quiz_id    Masteriyo quiz ID (the copy).
	 * @param int    $course_id  Masteriyo course ID (the copy).
	 * @param int    $menu_order Position in the quiz.
	 *
	 * @return void
	 */
	private static function process_question_migration( $question, $quiz_id, $course_id, $menu_order = 0 ) {
		$ques_id       = $question->id;
		$ques_type     = get_post_meta( $ques_id, '_llms_question_type', true );
		$question_type = self::determine_question_type( $ques_type, $question );

		if ( 'group' === $ques_type ) {
			$children = $question->get_questions();
			if ( ! empty( $children ) ) {
				$child_order = $menu_order;
				foreach ( $children as $child ) {
					self::process_question_migration( $child, $quiz_id, $course_id, $child_order );
					++$child_order;
				}
			}
			return;
		}

		// Content-type display blocks and unknown types have no Masteriyo equivalent.
		if ( ! $question_type ) {
			return;
		}

		$formatted_answers = self::format_answers( $question->get_choices() );

		if ( empty( $formatted_answers ) ) {
			return;
		}

		// The answers go on the copy's post_content; the LifterLMS question keeps its own.
		$question_id = PostCloner::clone_post(
			self::SLUG,
			(int) $question->post->ID,
			array(
				'post_type'    => PostType::QUESTION,
				'post_title'   => sanitize_text_field( $question->post->post_title ),
				'post_content' => wp_json_encode( $formatted_answers ),
				'post_excerpt' => sanitize_text_field( $question->post_content ),
				'post_status'  => PostStatus::PUBLISH,
				'post_parent'  => $quiz_id,
				'menu_order'   => $menu_order,
			)
		);

		update_post_meta( $question_id, '_course_id', $course_id );
		update_post_meta( $question_id, '_type', $question_type );
		update_post_meta( $question_id, '_points', get_post_meta( $ques_id, '_llms_points', true ) );
		update_post_meta( $question_id, '_parent_id', $quiz_id );

		if ( ! empty( $question->post_content ) ) {
			update_post_meta( $question_id, '_enable_description', true );
		}
	}

	/**
	 * Determines the question type for Masteriyo based on LifterLMS data.
	 *
	 * @since 1.16.0
	 *
	 * @param string $ques_type The question type from LifterLMS.
	 * @param object $question The LifterLMS question object.
	 *
	 * @return string|null The mapped question type for Masteriyo, or null if unsupported.
	 */
	private static function determine_question_type( $ques_type, $question ) {
		if ( 'true_false' === $ques_type ) {
			return QuestionType::TRUE_FALSE;
		} elseif ( 'choice' === $ques_type ) {
			return ( 'no' === $question->get( 'multi_choices' ) ) ? QuestionType::SINGLE_CHOICE : QuestionType::MULTIPLE_CHOICE;
		} elseif ( 'content' === $ques_type ) {
			masteriyo_get_logger()->debug(
				sprintf( 'Migration: Skipping LifterLMS content-type question %d (display-only, no Masteriyo equivalent).', $question->id ),
				array( 'source' => 'migration-tool' )
			);
			return null;
		}

		return null;
	}

	/**
	 * Formats the answers for Masteriyo by sanitizing and structuring them.
	 *
	 * @since 1.16.0
	 *
	 * @param array $answers The array of answer choices from LifterLMS.
	 *
	 * @return array The formatted answers array.
	 */
	private static function format_answers( $answers ) {
		$formatted_answers = array();

		foreach ( $answers as $answer ) {
			$choice = sanitize_text_field( $answer->get( 'choice' ) );

			if ( ! empty( $choice ) ) {
				$formatted_answers[] = array(
					'name'    => $choice,
					'correct' => (bool) $answer->is_correct(),
				);
			}
		}

		return $formatted_answers;
	}

	/**
	 * Count the source items a step still has to copy. Fast COUNT queries, no records loaded.
	 *
	 * Rows already in the migration map are left out, so a second run counts only what is new.
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
							WHERE um.meta_key = %s
							  AND ( um.meta_value LIKE %s OR um.meta_value LIKE %s )
							UNION
							SELECT DISTINCT user_id
							FROM {$wpdb->prefix}lifterlms_user_postmeta
							WHERE meta_key = '_status'
						) AS llms_users
						WHERE 1=1 {$not_user}",
						$capabilities_key,
						'%"instructor"%',
						'%"lms_manager"%'
					)
				);

			case 'courses':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'course' {$not_post}" );

			case 'enrollments':
				$not_row = MigrationMap::unmapped_sql( 'upm.meta_id', self::SLUG, 'enrollments' );
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}lifterlms_user_postmeta upm
					 INNER JOIN {$wpdb->posts} p ON p.ID = upm.post_id
					 WHERE upm.meta_key = '_status'
					   AND p.post_type = 'course'
					   {$not_row}"
				);

			case 'lesson_progress':
				// LifterLMS stores _is_complete on lessons only, never on quizzes.
				$not_row = MigrationMap::unmapped_sql( 'upm.meta_id', self::SLUG, 'lesson_progress' );
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}lifterlms_user_postmeta upm
					 INNER JOIN {$wpdb->posts} p ON p.ID = upm.post_id
					 WHERE upm.meta_key = '_is_complete'
					   AND p.post_type = 'lesson'
					   {$not_row}"
				);

			case 'orders':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'llms_order' {$not_post}" );

			case 'reviews':
				$not_review = MigrationMap::unmapped_sql( 'ID', self::SLUG, 'reviews' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'llms_review' {$not_review}" );

			case 'quiz_attempts':
				$attempts_table = $wpdb->prefix . 'lifterlms_quiz_attempts';
				if ( ! self::table_exists( $attempts_table ) ) {
					return 0;
				}
				$not_attempt = MigrationMap::unmapped_sql( 'id', self::SLUG, 'quiz_attempts' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$attempts_table} WHERE 1=1 {$not_attempt}" );

		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return 0;
	}

	/**
	 * Return one batch of source IDs starting after $cursor.
	 *
	 * Every step pages by its own ID column and leaves out rows already in the
	 * migration map. Nothing is removed from the source, so $exclude is unused: a
	 * cursor never revisits a failed ID within a run.
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
							WHERE um.meta_key = %s
							  AND ( um.meta_value LIKE %s OR um.meta_value LIKE %s )
							UNION
							SELECT DISTINCT user_id
							FROM {$wpdb->prefix}lifterlms_user_postmeta
							WHERE meta_key = '_status'
						) AS llms_users
						WHERE user_id > %d
						{$not_user}
						ORDER BY user_id ASC
						LIMIT %d",
						$capabilities_key,
						'%"instructor"%',
						'%"lms_manager"%',
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'courses':
				return self::post_ids_after( 'course', $cursor, $limit, $not_post );

			case 'enrollments':
				$not_row = MigrationMap::unmapped_sql( 'upm.meta_id', self::SLUG, 'enrollments' );
				$ids     = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT upm.meta_id FROM {$wpdb->prefix}lifterlms_user_postmeta upm
						 INNER JOIN {$wpdb->posts} p ON p.ID = upm.post_id
						 WHERE upm.meta_key = '_status'
						   AND p.post_type = 'course'
						   AND upm.meta_id > %d
						   {$not_row}
						 ORDER BY upm.meta_id ASC
						 LIMIT %d",
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'lesson_progress':
				$not_row = MigrationMap::unmapped_sql( 'upm.meta_id', self::SLUG, 'lesson_progress' );
				$ids     = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT upm.meta_id FROM {$wpdb->prefix}lifterlms_user_postmeta upm
						 INNER JOIN {$wpdb->posts} p ON p.ID = upm.post_id
						 WHERE upm.meta_key = '_is_complete'
						   AND p.post_type = 'lesson'
						   AND upm.meta_id > %d
						   {$not_row}
						 ORDER BY upm.meta_id ASC
						 LIMIT %d",
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'orders':
				return self::post_ids_after( 'llms_order', $cursor, $limit, $not_post );

			case 'reviews':
				return self::post_ids_after( 'llms_review', $cursor, $limit, MigrationMap::unmapped_sql( 'ID', self::SLUG, 'reviews' ) );

			case 'quiz_attempts':
				$attempts_table = $wpdb->prefix . 'lifterlms_quiz_attempts';
				if ( ! self::table_exists( $attempts_table ) ) {
					return array();
				}
				$not_attempt = MigrationMap::unmapped_sql( 'id', self::SLUG, 'quiz_attempts' );
				$ids         = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT id FROM {$attempts_table}
						 WHERE id > %d
						 {$not_attempt}
						 ORDER BY id ASC
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
	 * Whether a table exists. A probe rather than SHOW TABLES, which cannot see the
	 * temporary tables the WordPress test library creates.
	 *
	 * @param string $table Prefixed table name.
	 * @return bool
	 */
	private static function table_exists( string $table ): bool {
		global $wpdb;

		$suppressed = $wpdb->suppress_errors();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = false !== $wpdb->query( "SELECT 1 FROM {$table} LIMIT 1" );
		$wpdb->suppress_errors( $suppressed );

		return $exists;
	}

	/**
	 * Dispatch a single-item migration to the appropriate migrate_single_*() method.
	 *
	 * Called by MigrationProcessJob inside a START TRANSACTION / COMMIT wrapper.
	 * Must be idempotent — safe to call twice for the same (step, item_id) pair.
	 *
	 * @param string $step    Step name matching a key in LifterLMSMigrator::get_steps().
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
			case 'quiz_attempts':
				static::migrate_single_quiz_attempt( $item_id );
				break;
		}
	}

	/**
	 * Give a LifterLMS instructor, manager or student the matching Masteriyo role.
	 *
	 * LifterLMS's own roles and capabilities stay: LifterLMS keeps running until the
	 * site owner deactivates it. Students who have an enrollment also get the student
	 * role from Helper::update_user_role() in the 'enrollments' step.
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

		if ( ! $is_admin && array_intersect( array( 'instructor', 'lms_manager' ), $roles ) ) {
			$user->add_role( Roles::INSTRUCTOR );
			$roles = (array) $user->roles;
			$caps  = is_array( $user->caps ) ? $user->caps : array();
		}

		// Any enrolled user who is not an admin or instructor becomes a student.
		if ( ! $is_admin
			&& ! in_array( Roles::INSTRUCTOR, $roles, true )
			&& ! isset( $caps[ Roles::INSTRUCTOR ] )
			&& ! in_array( Roles::STUDENT, $roles, true )
			&& ! isset( $caps[ Roles::STUDENT ] )
		) {
			$has_enrollment = (bool) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT 1 FROM {$wpdb->prefix}lifterlms_user_postmeta WHERE user_id = %d AND meta_key = '_status' LIMIT 1",
					$user_id
				)
			);

			if ( $has_enrollment ) {
				$user->add_role( Roles::STUDENT );
			}
		}

		MigrationMap::record( self::SLUG, MigrationMap::USER, $user_id, $user_id );
	}

	/**
	 * Copy a LifterLMS course into a Masteriyo mto-course.
	 *
	 * The LifterLMS course keeps its post type, meta and terms; the conversion runs on
	 * the copy. The copy shares LifterLMS's course_cat, course_tag and course_difficulty
	 * relationships, so the taxonomy helpers below only ever touch the copy.
	 *
	 * @param int $source_course_id LifterLMS course post ID.
	 * @throws \Exception If the post does not exist or is not a LifterLMS course.
	 */
	public static function migrate_single_course( int $source_course_id ): void {
		$post = get_post( $source_course_id );

		if ( ! $post ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d not found.', $source_course_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		if ( 'course' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d is not a LifterLMS course (got %s).', $source_course_id, $post->post_type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$course_id = static::migrate_course( $source_course_id );
		static::migrate_course_info( $course_id, $source_course_id );
		static::create_woocommerce_product( $course_id, $source_course_id );
		Helper::migrate_course_author( $course_id );
		Helper::migrate_course_categories_from_to_masteriyo( $course_id, 'course_cat' );
		Helper::migrate_course_categories_from_to_masteriyo( $course_id, 'course_difficulty', 'course_difficulty' );
		Helper::migrate_course_categories_from_to_masteriyo( $course_id, 'course_tag', 'course_tag' );
		self::link_prerequisite( $course_id );
	}

	/**
	 * Point a copied course's prerequisite at the copy of the LifterLMS prerequisite.
	 *
	 * Left alone when that course has not been copied yet; finalize_step('courses')
	 * comes back for it. Track-level prerequisites have no Masteriyo equivalent.
	 *
	 * @param int $course_id Masteriyo course ID (the copy, which carries the LifterLMS meta).
	 */
	private static function link_prerequisite( int $course_id ): void {
		if ( 'yes' !== get_post_meta( $course_id, '_llms_has_prerequisite', true ) ) {
			return;
		}

		$source_prereq_id = (int) get_post_meta( $course_id, '_llms_prerequisite', true );
		$prereq_id        = $source_prereq_id ? MigrationMap::target( self::SLUG, MigrationMap::POST, $source_prereq_id ) : 0;

		if ( $prereq_id ) {
			update_post_meta( $course_id, '_prerequisites_courses', array( $prereq_id ) );
		}
	}

	/**
	 * Copy a single LifterLMS enrollment row to a Masteriyo user_course item on the copied course.
	 *
	 * Operates on one lifterlms_user_postmeta row by its meta_id primary key. The row stays;
	 * the map remembers it, with a target of 0 when it was skipped on purpose.
	 *
	 * @param int $meta_id Primary key of the lifterlms_user_postmeta row (meta_key = '_status').
	 * @throws \Exception If the row does not exist, its course has no copy, or the DB insert fails.
	 */
	public static function migrate_single_enrollment( int $meta_id ): void {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, post_id, meta_value, updated_date
				 FROM {$wpdb->prefix}lifterlms_user_postmeta
				 WHERE meta_id = %d AND meta_key = '_status'",
				$meta_id
			)
		);

		if ( ! $row ) {
			throw new \Exception(
				sprintf( 'migrate_single_enrollment: lifterlms_user_postmeta row %d not found.', $meta_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$user_id          = (int) $row->user_id;
		$source_course_id = (int) $row->post_id;

		$skip = function() use ( $meta_id ): void {
			MigrationMap::record( self::SLUG, 'enrollments', $meta_id, 0 );
		};

		// Membership enrollments have no Masteriyo counterpart.
		if ( 'course' !== get_post_type( $source_course_id ) ) {
			$skip();
			return;
		}

		if ( ! get_userdata( $user_id ) ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: Enrollment row %d skipped: user %d does not exist.', $meta_id, $user_id ),
				array( 'source' => 'migration-tool' )
			);
			$skip();
			return;
		}

		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_course_id, 'course' );

		if ( masteriyo_is_user_already_enrolled( $user_id, $course_id ) ) {
			$skip();
			return;
		}

		$status_map = array(
			'enrolled'   => UserCourseStatus::ACTIVE,
			'unenrolled' => UserCourseStatus::INACTIVE,
			'expired'    => UserCourseStatus::INACTIVE,
			'cancelled'  => UserCourseStatus::INACTIVE,
		);
		$status     = $status_map[ $row->meta_value ] ?? UserCourseStatus::ACTIVE;

		$result = $wpdb->insert(
			$wpdb->prefix . 'masteriyo_user_items',
			array(
				'item_id'       => $course_id,
				'user_id'       => $user_id,
				'item_type'     => 'user_course',
				'date_start'    => $row->updated_date,
				'date_end'      => '0000-00-00 00:00:00',
				'date_modified' => current_time( 'mysql', true ),
				'parent_id'     => 0,
				'status'        => $status,
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( false === $result ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception(
				sprintf(
					'migrate_single_enrollment: DB insert failed for user %d course %d: %s',
					$user_id,
					$course_id,
					$wpdb->last_error
				)
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$user_item_id = (int) $wpdb->insert_id;

		Helper::update_user_role( $user_id );

		// Link to the Masteriyo order if an enrollment trigger exists (format: 'order_123').
		$trigger = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->prefix}lifterlms_user_postmeta
				 WHERE user_id = %d AND post_id = %d AND meta_key = '_enrollment_trigger'
				 LIMIT 1",
				$user_id,
				$source_course_id
			)
		);

		if ( $trigger && 0 === strpos( $trigger, 'order_' ) ) {
			$source_order_id = (int) str_replace( 'order_', '', $trigger );

			// The copied order's ID is new, so the order is copied here if the orders step has not reached it.
			$order_id = ( $source_order_id && 'llms_order' === get_post_type( $source_order_id ) )
				? self::migrate_order( $source_order_id )
				: 0;

			if ( $order_id ) {
				$itemmeta_tbl = $wpdb->prefix . 'masteriyo_user_itemmeta';
				$wpdb->insert(
					$itemmeta_tbl,
					array(
						'user_item_id' => $user_item_id,
						'meta_key'     => '_order_id',
						'meta_value'   => $order_id,
					),
					array( '%d', '%s', '%s' )
				);
				$wpdb->insert(
					$itemmeta_tbl,
					array(
						'user_item_id' => $user_item_id,
						'meta_key'     => '_price',
						'meta_value'   => get_post_meta( $source_order_id, '_llms_total', true ),
					),
					array( '%d', '%s', '%s' )
				);
			}
		}

		MigrationMap::record( self::SLUG, 'enrollments', $meta_id, $user_item_id );
	}

	/**
	 * The copied quiz a LifterLMS quiz maps to, or 0 when the courses step left it out.
	 *
	 * Throws when the quiz's course has not been copied, so the item is retried later
	 * rather than skipped for good.
	 *
	 * @param int $source_quiz_id LifterLMS quiz post ID.
	 * @return int
	 * @throws \Exception When the course has no copy yet.
	 */
	private static function copied_quiz( int $source_quiz_id ): int {
		$quiz_id = MigrationMap::target( self::SLUG, MigrationMap::POST, $source_quiz_id );

		if ( $quiz_id ) {
			return $quiz_id;
		}

		$lesson_id = (int) get_post_meta( $source_quiz_id, '_llms_lesson_id', true );
		self::require_copied_course_of_lesson( $lesson_id );

		return 0;
	}

	/**
	 * Throw unless the course a LifterLMS lesson belongs to has been copied.
	 *
	 * A lesson with no course cannot be waited for, so it passes.
	 *
	 * @param int $source_lesson_id LifterLMS lesson post ID.
	 * @throws \Exception When the course has no copy yet.
	 */
	private static function require_copied_course_of_lesson( int $source_lesson_id ): void {
		$source_course_id = (int) get_post_meta( $source_lesson_id, '_llms_parent_course', true );

		if ( $source_course_id ) {
			MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_course_id, 'course' );
		}
	}

	/**
	 * Copy a single lifterlms_user_postmeta completion row to masteriyo_user_activities.
	 *
	 * Writes a course_progress activity (if missing) and a lesson or quiz activity on
	 * the copied item. The row stays; the map remembers it.
	 *
	 * @param int $meta_id Primary key of the lifterlms_user_postmeta row (meta_key = '_is_complete').
	 * @throws \Exception If the row does not exist or its course has no copy.
	 */
	public static function migrate_single_lesson_progress( int $meta_id ): void {
		global $wpdb;

		$now            = gmdate( 'Y-m-d H:i:s' );
		$activities_tbl = $wpdb->prefix . 'masteriyo_user_activities';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, post_id, meta_value, updated_date
				 FROM {$wpdb->prefix}lifterlms_user_postmeta
				 WHERE meta_id = %d AND meta_key = '_is_complete'",
				$meta_id
			)
		);

		if ( ! $row ) {
			throw new \Exception(
				sprintf( 'migrate_single_lesson_progress: lifterlms_user_postmeta row %d not found.', $meta_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$user_id          = (int) $row->user_id;
		$source_lesson_id = (int) $row->post_id;
		$completed        = ( 'yes' === $row->meta_value );
		$date             = $row->updated_date ? $row->updated_date : $now;

		$skip = function() use ( $meta_id ): void {
			MigrationMap::record( self::SLUG, 'lesson_progress', $meta_id, 0 );
		};

		// A plain lesson has a copy; a lesson that ended in a quiz was copied as that quiz,
		// and LifterLMS writes _is_complete on the lesson when its quiz passes.
		$activity_type    = CourseProgressItemType::LESSON;
		$activity_item_id = MigrationMap::target( self::SLUG, MigrationMap::POST, $source_lesson_id );

		if ( ! $activity_item_id ) {
			$source_quiz_id = (int) get_post_meta( $source_lesson_id, '_llms_quiz', true );

			if ( $source_quiz_id ) {
				$activity_type    = CourseProgressItemType::QUIZ;
				$activity_item_id = self::copied_quiz( $source_quiz_id );
			} else {
				self::require_copied_course_of_lesson( $source_lesson_id );
			}
		}

		// Not a skip: the lesson may be copied later, and the row must still be picked up then.
		if ( ! $activity_item_id ) {
			throw new \Exception( sprintf( 'migrate_single_lesson_progress: lesson %d has no copy.', $source_lesson_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$course_id = (int) get_post_meta( $activity_item_id, '_course_id', true );

		if ( ! $course_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: No course found for lesson %d — skipping progress row %d.', $source_lesson_id, $meta_id ),
				array( 'source' => 'migration-tool' )
			);
			$skip();
			return;
		}

		// Ensure a course_progress activity record exists for this user+course.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$progress_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$activities_tbl}
				 WHERE item_id = %d AND user_id = %d AND activity_type = 'course_progress'
				 LIMIT 1",
				$course_id,
				$user_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $progress_id ) {
			$wpdb->insert(
				$activities_tbl,
				array(
					'user_id'         => $user_id,
					'item_id'         => $course_id,
					'activity_type'   => 'course_progress',
					'activity_status' => CourseProgressStatus::STARTED,
					'parent_id'       => 0,
					'created_at'      => $date,
					'modified_at'     => $now,
					'completed_at'    => '0000-00-00 00:00:00',
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
			);
			$progress_id = (int) $wpdb->insert_id;
		}

		if ( ! $progress_id ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception(
				sprintf( 'migrate_single_lesson_progress: could not create course_progress for user %d course %d.', $user_id, $course_id )
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// Idempotency: skip inserting the activity if it already exists.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$activities_tbl}
				 WHERE user_id = %d AND item_id = %d AND activity_type = %s AND parent_id = %d",
				$user_id,
				$activity_item_id,
				$activity_type,
				$progress_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $existing ) {
			$activity_status = $completed ? CourseProgressStatus::COMPLETED : CourseProgressStatus::STARTED;
			$completed_at    = $completed ? $date : '0000-00-00 00:00:00';

			$wpdb->insert(
				$activities_tbl,
				array(
					'user_id'         => $user_id,
					'item_id'         => $activity_item_id,
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

		MigrationMap::record( self::SLUG, 'lesson_progress', $meta_id, $progress_id );
	}

	/**
	 * Copy a single LifterLMS order to a Masteriyo mto-order.
	 *
	 * @param int $order_id LifterLMS llms_order post ID.
	 * @throws \Exception If the post does not exist or is not an llms_order post.
	 */
	public static function migrate_single_order( int $order_id ): void {
		$post = get_post( $order_id );

		if ( ! $post || 'llms_order' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_order: llms_order post %d not found.', $order_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		self::migrate_order( $order_id );
	}

	/**
	 * Copy a single LifterLMS review (llms_review CPT) to a new Masteriyo course_review comment.
	 *
	 * The llms_review post stays; the new comment is recorded against it.
	 *
	 * @param int $review_id LifterLMS llms_review post ID.
	 * @throws \Exception If the post does not exist, is not an llms_review, or its course has no copy.
	 */
	public static function migrate_single_review( int $review_id ): void {
		$llms_review = get_post( $review_id );

		if ( ! $llms_review || 'llms_review' !== $llms_review->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_review: llms_review post %d not found.', $review_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $llms_review->post_parent, 'course' );
		$user_id   = (int) $llms_review->post_author;
		$rating    = get_post_meta( $review_id, 'rating', true );

		$comment_author       = '';
		$comment_author_email = '';
		$comment_author_url   = '';

		$user = get_userdata( $user_id );
		if ( $user ) {
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;
			$comment_author_url   = $user->user_url;
		}

		$comment_status = 'publish' === $llms_review->post_status
			? CommentStatus::APPROVE
			: CommentStatus::HOLD;

		$new_comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $course_id,
				'comment_author'       => $comment_author,
				'comment_author_email' => $comment_author_email,
				'comment_author_url'   => $comment_author_url,
				'comment_date'         => $llms_review->post_date,
				'comment_date_gmt'     => $llms_review->post_date_gmt,
				'comment_content'      => $llms_review->post_content,
				'comment_karma'        => (int) $rating,
				'comment_parent'       => 0,
				'user_id'              => $user_id,
				'comment_approved'     => $comment_status,
				'comment_type'         => CommentType::COURSE_REVIEW,
				'comment_agent'        => 'Masteriyo',
			)
		);

		if ( ! $new_comment_id ) {
			throw new \Exception(
				sprintf( 'migrate_single_review: could not insert a comment for review %d.', $review_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		update_comment_meta( $new_comment_id, '_title', sanitize_text_field( $llms_review->post_title ) );

		// Not the post entity: the target is a comment, not a post.
		MigrationMap::record( self::SLUG, 'reviews', $review_id, (int) $new_comment_id );
	}

	/**
	 * Copy an order from LifterLMS to Masteriyo, once.
	 *
	 * Called from the orders step and from an enrollment that points at the order.
	 * An order for a membership has no Masteriyo counterpart and is skipped.
	 *
	 * @since 1.16.0
	 *
	 * @param int $source_order_id LifterLMS order ID.
	 * @return int Masteriyo order ID (the copy), or 0 when skipped.
	 * @throws \Exception When the order's course has no copy yet.
	 */
	private static function migrate_order( int $source_order_id ): int {
		if ( MigrationMap::is_settled_post( self::SLUG, $source_order_id ) ) {
			return MigrationMap::target( self::SLUG, MigrationMap::POST, $source_order_id );
		}

		$source_course_id = absint( get_post_meta( $source_order_id, '_llms_product_id', true ) );

		if ( 'course' !== get_post_type( $source_course_id ) ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: Order %d skipped: its product %d is not a LifterLMS course.', $source_order_id, $source_course_id ),
				array( 'source' => 'migration-tool' )
			);
			MigrationMap::record( self::SLUG, MigrationMap::POST, $source_order_id, 0 );
			return 0;
		}

		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_course_id, 'course' );

		$order_status_map = array(
			'llms-completed'      => OrderStatus::COMPLETED,
			'llms-active'         => OrderStatus::COMPLETED,
			'llms-expired'        => OrderStatus::COMPLETED,
			'llms-on-hold'        => OrderStatus::ON_HOLD,
			'llms-pending-cancel' => OrderStatus::CANCELLED,
			'llms-pending'        => OrderStatus::PENDING,
			'llms-cancelled'      => OrderStatus::CANCELLED,
			'llms-refunded'       => OrderStatus::REFUNDED,
			'llms-failed'         => OrderStatus::FAILED,
		);
		$status           = $order_status_map[ get_post_status( $source_order_id ) ] ?? OrderStatus::PENDING;

		$order_id = PostCloner::clone_post(
			self::SLUG,
			$source_order_id,
			array(
				'post_type'     => PostType::ORDER,
				'post_status'   => $status,
				'post_password' => masteriyo_generate_order_key(),
			)
		);

		self::update_order_items( $order_id, $course_id );
		self::update_order_meta( $order_id, $source_order_id );

		return $order_id;
	}

	/**
	 * Insert the order item for a copied order.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id  Masteriyo order ID (the copy, which carries the LifterLMS meta).
	 * @param int $course_id Masteriyo course ID (the copy).
	 */
	private static function update_order_items( int $order_id, int $course_id ) {
		global $wpdb;

		$item_name = get_post_meta( $order_id, '_llms_product_title', true );

		$item_data = array(
			'order_item_name' => $item_name,
			'order_item_type' => 'course',
			'order_id'        => $order_id,
		);

		$wpdb->insert( $wpdb->prefix . 'masteriyo_order_items', $item_data );
		$order_item_id = absint( $wpdb->insert_id );

		if ( ! $order_item_id ) {
			return;
		}

		self::update_order_items_meta( $order_item_id, $order_id, $course_id );
	}

	/**
	 * Insert order item meta for a copied order.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_item_id Order item ID.
	 * @param int $order_id      Masteriyo order ID (the copy, which carries the LifterLMS meta).
	 * @param int $course_id     Masteriyo course ID (the copy).
	 */
	private static function update_order_items_meta( int $order_item_id, int $order_id, int $course_id ) {
		global $wpdb;

		$quantity = 1;
		$subtotal = get_post_meta( $order_id, '_llms_original_total', true );
		$total    = get_post_meta( $order_id, '_llms_total', true );

		$item_metas = array(
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'course_id',
				'meta_value'    => $course_id,
			),
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'quantity',
				'meta_value'    => $quantity,
			),
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'subtotal',
				'meta_value'    => $subtotal,
			),
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'total',
				'meta_value'    => $total,
			),
		);

		$table_name = $wpdb->prefix . 'masteriyo_order_itemmeta';

		foreach ( $item_metas as $item_meta ) {
			$wpdb->insert( $table_name, $item_meta );
		}
	}

	/**
	 * Write the Masteriyo order meta on a copied order from the LifterLMS meta it carries.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id        Masteriyo order ID (the copy).
	 * @param int $source_order_id LifterLMS order ID, which the transactions hang off.
	 */
	private static function update_order_meta( int $order_id, int $source_order_id ) {
		$billing_email = get_post_meta( $order_id, '_llms_billing_email', true );

		$payment_gateway = get_post_meta( $order_id, '_llms_payment_gateway', true );

		if ( 'manual' === $payment_gateway ) {
			$payment_gateway = 'offline';
		}

		update_post_meta( $order_id, '_payment_method', $payment_gateway );
		update_post_meta( $order_id, '_version', MASTERIYO_VERSION );
		update_post_meta( $order_id, '_customer_id', get_post_meta( $order_id, '_llms_user_id', true ) );
		update_post_meta( $order_id, '_customer_ip_address', get_post_meta( $order_id, '_llms_user_ip_address', true ) );
		update_post_meta( $order_id, '_total', get_post_meta( $order_id, '_llms_total', true ) );
		update_post_meta( $order_id, '_currency', get_post_meta( $order_id, '_llms_currency', true ) );
		update_post_meta( $order_id, '_billing_address_index', $billing_email );
		update_post_meta( $order_id, '_billing_email', $billing_email );
		update_post_meta( $order_id, '_billing_first_name', get_post_meta( $order_id, '_llms_billing_first_name', true ) );
		update_post_meta( $order_id, '_billing_last_name', get_post_meta( $order_id, '_llms_billing_last_name', true ) );
		update_post_meta( $order_id, '_billing_address_1', get_post_meta( $order_id, '_llms_billing_address_1', true ) );
		update_post_meta( $order_id, '_billing_address_2', get_post_meta( $order_id, '_llms_billing_address_2', true ) );
		update_post_meta( $order_id, '_billing_city', get_post_meta( $order_id, '_llms_billing_city', true ) );
		update_post_meta( $order_id, '_billing_postcode', get_post_meta( $order_id, '_llms_billing_zip', true ) );
		update_post_meta( $order_id, '_billing_country', get_post_meta( $order_id, '_llms_billing_country', true ) );
		update_post_meta( $order_id, '_billing_state', get_post_meta( $order_id, '_llms_billing_state', true ) );
		update_post_meta( $order_id, '_billing_phone', get_post_meta( $order_id, '_llms_billing_phone', true ) );
		update_post_meta( $order_id, '_was_lf_order', true );

		// Map gateway transaction reference from the llms_transaction child of the LifterLMS order.
		$transactions = get_posts(
			array(
				'post_type'      => 'llms_transaction',
				'post_parent'    => $source_order_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'post_status'    => 'any',
			)
		);
		if ( ! empty( $transactions ) ) {
			$txn_source_id = get_post_meta( reset( $transactions ), '_llms_gateway_source_id', true );
			if ( $txn_source_id ) {
				update_post_meta( $order_id, '_transaction_id', $txn_source_id );
			}
		}
	}

	/**
	 * Copy a single LifterLMS quiz attempt row to masteriyo_quiz_attempts. The LifterLMS row stays.
	 *
	 * @param int $attempt_id Primary key of the lifterlms_quiz_attempts row.
	 * @throws \Exception If the row does not exist, its course has no copy, or the insert fails.
	 */
	private static function migrate_single_quiz_attempt( int $attempt_id ): void {
		global $wpdb;

		$attempts_table = $wpdb->prefix . 'lifterlms_quiz_attempts';
		$row            = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$attempts_table} WHERE id = %d", $attempt_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $row ) {
			throw new \Exception(
				sprintf( 'migrate_single_quiz_attempt: attempt %d not found.', $attempt_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$skip = function() use ( $attempt_id ): void {
			MigrationMap::record( self::SLUG, 'quiz_attempts', $attempt_id, 0 );
		};

		$quiz_id = self::copied_quiz( (int) $row['quiz_id'] );

		if ( ! $quiz_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: Quiz %d has no copy — skipping attempt %d.', (int) $row['quiz_id'], $attempt_id ),
				array( 'source' => 'migration-tool' )
			);
			$skip();
			return;
		}

		$course_id = (int) get_post_meta( $quiz_id, '_course_id', true );

		$raw_questions   = maybe_unserialize( $row['questions'] );
		$answers_json    = array();
		$total_correct   = 0;
		$total_incorrect = 0;
		$total_questions = is_array( $raw_questions ) ? count( $raw_questions ) : 0;

		if ( is_array( $raw_questions ) ) {
			// Answers reference the copied questions; one that was not copied keeps its LifterLMS ID.
			$question_map = MigrationMap::targets(
				self::SLUG,
				MigrationMap::POST,
				array_map(
					static function ( $q ): int {
						return (int) ( ( (array) $q )['id'] ?? 0 );
					},
					$raw_questions
				)
			);
			foreach ( $raw_questions as $q ) {
				$q                = (array) $q;
				$is_correct       = ! empty( $q['correct'] ) ? 1 : 0;
				$total_correct   += $is_correct;
				$total_incorrect += ( 1 - $is_correct );
				$question_id      = isset( $q['id'] ) ? (int) $q['id'] : 0;
				$answers_json[]   = array(
					'question_id' => $question_map[ $question_id ] ?? $question_id,
					'correct'     => (bool) $is_correct,
					'points'      => isset( $q['points'] ) ? (float) $q['points'] : 0.0,
					'answer_data' => isset( $q['answer'] ) ? $q['answer'] : null,
				);
			}
		}

		$full_mark    = (float) get_post_meta( $quiz_id, '_full_mark', true );
		$grade        = (float) $row['grade']; // percentage 0-100
		$earned_marks = ( $full_mark > 0 ) ? ( $grade / 100 ) * $full_mark : $grade;

		$status_map     = array(
			'pass'       => 'passed',
			'fail'       => 'failed',
			'complete'   => 'passed',
			'incomplete' => 'attempt',
		);
		$attempt_status = isset( $status_map[ $row['status'] ] ) ? $status_map[ $row['status'] ] : 'attempt';

		$started_at = ! empty( $row['start_date'] ) ? $row['start_date'] : current_time( 'mysql', true );
		$ended_at   = ! empty( $row['end_date'] ) ? $row['end_date'] : $started_at;

		$already_exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_quiz_attempts
				 WHERE quiz_id = %d AND user_id = %d AND attempt_started_at = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$quiz_id,
				(int) $row['student_id'],
				$started_at
			)
		);
		if ( $already_exists ) {
			$skip();
			return;
		}

		$inserted = $wpdb->insert(
			$wpdb->prefix . 'masteriyo_quiz_attempts',
			array(
				'quiz_id'                  => $quiz_id,
				'course_id'                => $course_id,
				'user_id'                  => (int) $row['student_id'],
				'total_questions'          => $total_questions,
				'total_answered_questions' => $total_questions,
				'total_marks'              => $full_mark,
				'total_attempts'           => (int) $row['attempt'],
				'total_correct_answers'    => $total_correct,
				'total_incorrect_answers'  => $total_incorrect,
				'earned_marks'             => $earned_marks,
				'answers'                  => maybe_serialize( $answers_json ),
				'attempt_status'           => $attempt_status,
				'attempt_started_at'       => $started_at,
				'attempt_ended_at'         => $ended_at,
			),
			array( '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			throw new \Exception(
				sprintf( 'migrate_single_quiz_attempt: failed to insert attempt %d.', $attempt_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		MigrationMap::record( self::SLUG, 'quiz_attempts', $attempt_id, (int) $wpdb->insert_id );
	}

	/**
	 * Migrate content drip settings for a single copied lesson from LifterLMS to Masteriyo Pro.
	 *
	 * The copy carries the LifterLMS drip meta and already has _course_id.
	 *
	 * @param int $lesson_id Masteriyo mto-lesson post ID (the copy).
	 */
	private static function migrate_single_content_drip( int $lesson_id ): void {
		$drip_method = get_post_meta( $lesson_id, '_llms_drip_method', true );

		if ( empty( $drip_method ) || 'prerequisite' === $drip_method ) {
			// No drip or no Masteriyo equivalent for prerequisite-based drip.
			return;
		}

		$course_id = (int) get_post_meta( $lesson_id, '_course_id', true );

		if ( 'enrollment' === $drip_method || 'start' === $drip_method ) {
			$days = absint( get_post_meta( $lesson_id, '_llms_days_before_available', true ) );
			update_post_meta( $lesson_id, '_content_drip_days', $days );
			if ( $course_id ) {
				update_post_meta( $course_id, '_flow', 'days' );
			}
		} elseif ( 'date' === $drip_method ) {
			$date = sanitize_text_field( get_post_meta( $lesson_id, '_llms_date_available', true ) );
			$time = sanitize_text_field( get_post_meta( $lesson_id, '_llms_time_available', true ) );
			if ( $date ) {
				$drip_date = $time ? "{$date} {$time}:00" : "{$date} 00:00:00";
				update_post_meta( $lesson_id, '_content_drip_date', $drip_date );
				if ( $course_id ) {
					update_post_meta( $course_id, '_flow', 'date' );
				}
			}
		}
	}

	/**
	 * Assign starter certificate templates to the copies of every LifterLMS course that
	 * had earned certificates. Runs once after the 'courses' step completes.
	 */
	private static function assign_certificate_templates_for_lifterlms_courses(): void {
		global $wpdb;

		// The certificates name LifterLMS's course IDs; the map gives the copies'. _llms_related
		// can also name the lesson or quiz that earned it, so only course copies are kept.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		$course_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT m.target_id
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} cert ON cert.ID = pm.post_id
				 INNER JOIN " . MigrationMap::table() . " m
				         ON m.source_id = CAST(pm.meta_value AS UNSIGNED)
				        AND m.lms = %s AND m.entity = %s AND m.target_id > 0
				 INNER JOIN {$wpdb->posts} course ON course.ID = m.target_id AND course.post_type = %s
				 WHERE cert.post_type = %s
				   AND pm.meta_key = %s
				 ORDER BY m.target_id ASC",
				self::SLUG,
				MigrationMap::POST,
				PostType::COURSE,
				'llms_my_certificate',
				'_llms_related'
			)
		);
		// phpcs:enable

		foreach ( $course_ids as $course_id ) {
			$course_id = (int) $course_id;
			Helper::assign_certificate_template(
				$course_id,
				(int) get_post_field( 'post_author', $course_id )
			);
		}
	}

	/**
	 * Point every copied course with a LifterLMS prerequisite at the prerequisite's copy.
	 *
	 * Per-course linking only works when the prerequisite was copied first; this pass
	 * covers the rest.
	 */
	private static function link_prerequisites(): void {
		$course_ids = get_posts(
			array(
				'post_type'      => PostType::COURSE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_llms_has_prerequisite',
				'meta_value'     => 'yes',
			)
		);

		foreach ( $course_ids as $course_id ) {
			self::link_prerequisite( (int) $course_id );
		}
	}

	/**
	 * Bulk post-step operations: link prerequisites and assign certificate templates
	 * after 'courses', recalculate course_progress status after 'lesson_progress'.
	 *
	 * @param string $step Step name.
	 */
	public static function finalize_step( string $step ): void {
		if ( 'courses' === $step ) {
			self::link_prerequisites();
			self::assign_certificate_templates_for_lifterlms_courses();
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
			            SUM( CASE WHEN activity_status = 'completed' THEN 1 ELSE 0 END ) AS done
			     FROM   {$tbl}
			     WHERE  activity_type IN ( 'lesson', 'quiz' )
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
}
