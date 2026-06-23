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
 * @since 1.16.0
 */
class LifterLMS {

	/**
	 * Migrates a single LifterLMS course.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id LifterLMS course ID.
	 */
	private static function migrate_course( $course_id ) {
		$course   = new \LLMS_Course( $course_id );
		$sections = $course->get_sections();

		if ( empty( $sections ) ) {
			wp_update_post(
				array(
					'ID'        => $course_id,
					'post_type' => PostType::COURSE,
				)
			);
			return;
		}

		$mto_course       = array();
		$lesson_post_type = PostType::LESSON;
		$section_order    = 0;

		foreach ( $sections as $section ) {
			$mto_section = array(
				'post_type'    => PostType::SECTION,
				'post_title'   => $section->post->post_title,
				'post_content' => $section->post->post_content,
				'post_status'  => PostStatus::PUBLISH,
				'post_author'  => $course->post->post_author,
				'post_parent'  => $course_id,
				'menu_order'   => $section_order,
				'items'        => array(),
			);

			++$section_order;

			$lessons = $section->get_lessons();

			if ( empty( $lessons ) ) {
				continue;
			}

			$lesson_order = 0;

			foreach ( $lessons as $lesson ) {

				if ( $lesson->has_quiz() ) {
					$quiz = $lesson->get_quiz();

					if ( $quiz ) {
						$mto_section['items'][] = array(
							'ID'           => $quiz->id,
							'post_type'    => PostType::QUIZ,
							'post_title'   => $quiz->post->post_title,
							'post_content' => $quiz->post->post_content,
							'post_parent'  => '{section_id}',
							'menu_order'   => $lesson_order,
						);
					} else {
						$mto_section['items'][] = array(
							'ID'           => $lesson->id,
							'post_type'    => PostType::LESSON,
							'post_title'   => $lesson->post->post_title,
							'post_content' => $lesson->post->post_content,
							'post_parent'  => '{section_id}',
							'menu_order'   => $lesson_order,
						);
					}
				} else {
					$mto_section['items'][] = array(
						'ID'           => $lesson->id,
						'post_type'    => PostType::LESSON,
						'post_title'   => $lesson->post->post_title,
						'post_content' => $lesson->post->post_content,
						'post_parent'  => '{section_id}',
						'menu_order'   => $lesson_order,
					);
				}

				++$lesson_order;
			}

			$mto_course[] = $mto_section;
		}

		if ( empty( $mto_course ) ) {
			return;
		}

		foreach ( $mto_course as $section ) {
			$items = $section['items'];
			unset( $section['items'] );

			$section_id = wp_insert_post( $section );

			if ( is_wp_error( $section_id ) ) {
				masteriyo_get_logger()->error( 'Migration: Failed to insert LifterLMS section post.', array( 'source' => 'migration-tool' ) );
				continue;
			}

			update_post_meta( $section_id, '_course_id', $course_id );

			foreach ( $items as $item ) {
				if ( PostType::QUIZ === $item['post_type'] ) {
					$quiz_id = masteriyo_array_get( $item, 'ID', 0 );

					$quiz      = new \LLMS_Quiz( $quiz_id );
					$questions = $quiz->get_questions();

					if ( empty( $questions ) ) {
						continue;
					}

					$k = 0;
					foreach ( $questions as $question ) {
						self::process_question_migration( $question, $quiz_id, $course_id, $k );
						++$k;
					}
				}

				$item['post_parent'] = $section_id;
				$item_id             = masteriyo_array_get( $item, 'ID', 0 );
				wp_update_post( $item );
				update_post_meta( $item_id, '_course_id', $course_id );

				if ( PostType::QUIZ === $item['post_type'] ) {
					if ( 'no' === $quiz->get( 'limit_attempts' ) ) {
						update_post_meta( $item_id, '_attempts_allowed', 0 );
					} else {
						update_post_meta( $item_id, '_attempts_allowed', absint( $quiz->get( 'allowed_attempts' ) ) );
					}

					if ( 'no' === $quiz->get( 'limit_time' ) ) {
						update_post_meta( $item_id, '_duration', 0 );
					} else {
						update_post_meta( $item_id, '_duration', absint( $quiz->get( 'time_limit' ) ) * 60 );
					}

					update_post_meta( $item_id, '_pass_mark', floatval( $quiz->get( 'passing_percent' ) ) );
				} elseif ( PostType::LESSON === $item['post_type'] ) {
					$url = get_post_meta( $item_id, '_llms_video_embed', true );

					$source = Helper::determine_video_source_from_url( $url );

					if ( is_array( $source ) ) {
						$source = $source[0];
					}

					update_post_meta( $item_id, '_video_source', $source );
					update_post_meta( $item_id, '_video_source_url', $url );
				}
			}
		}

		$mto_course = array(
			'ID'        => $course_id,
			'post_type' => PostType::COURSE,
		);

		wp_update_post( $mto_course );
		update_post_meta( $course_id, '_was_lf_course', true );
	}

	/**
	 * Migrate course info from LifterLMS.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id LifterLMS course ID.
	 */
	private static function migrate_course_info( $course_id ) {
		$max_student = absint( get_post_meta( $course_id, '_llms_capacity', true ) );
		update_post_meta( $course_id, '_enrollment_limit', $max_student );

		$review_enabled = get_post_meta( $course_id, '_llms_reviews_enabled', true );
		update_post_meta( $course_id, '_reviews_allowed', masteriyo_string_to_bool( $review_enabled ) );

		$product_ids = self::get_access_plan_ids( $course_id );
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
					$course_id
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
	 * Creates a WooCommerce product for a migrated LifterLMS course.
	 *
	 * @param int $course_id LifterLMS course ID.
	 *
	 * @return void
	 */
	private static function create_woocommerce_product( $course_id ) {
		if ( ! HelperWoocommerce::is_wc_active() ) {
			return;
		}

		$product_ids = self::get_access_plan_ids( $course_id );
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
	 * Migrates user enrollments for a given LifterLMS course ID.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id LifterLMS course ID.
	 */
	private static function migrate_course_enrollment( $course_id ) {
		global $wpdb;

		$lf_enrollments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}lifterlms_user_postmeta lifuer
				WHERE lifuer.post_id = %d AND lifuer.meta_key='_status' AND lifuer.meta_value='enrolled';",
				$course_id
			)
		);

		if ( ! $lf_enrollments ) {
			return;
		}

		foreach ( $lf_enrollments as $lf_enrollment ) {
			$user_id = absint( $lf_enrollment->user_id );

			if ( masteriyo_is_user_already_enrolled( $user_id, $course_id, 'active' ) ) {
				continue;
			}

			$order_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_value FROM {$wpdb->prefix}lifterlms_user_postmeta
					WHERE user_id = %d AND post_id = %d AND meta_key = '_enrollment_trigger';",
					$user_id,
					$course_id
				)
			);

			if ( ! $order_id ) {
				continue;
			}

			$order_id = str_replace( 'order_', '', $order_id );

			if ( masteriyo_is_user_already_enrolled( $user_id, $course_id, 'inactive' ) ) {
				$wpdb->update(
					$wpdb->prefix . 'masteriyo_user_items',
					array(
						'status' => 'active',
					),
					array(
						'user_id' => $user_id,
						'item_id' => $course_id,
						'status'  => 'inactive',
					),
					array( '%s' ),
					array( '%d', '%d', '%s' )
				);
			} else {
				$price        = (float) get_post_meta( $order_id, '_llms_total', true );
				$order_id_int = (int) $order_id;
				Helper::enroll_user( $user_id, $course_id, $lf_enrollment->updated_date, null, 'active', $order_id_int ? $order_id_int : null, $price );
			}
		}
	}

	/**
	 * Processes migration for a single LifterLMS quiz question.
	 *
	 * @since 1.16.0
	 *
	 * @param object $question LifterLMS quiz question data.
	 * @param int $quiz_id Masteriyo quiz ID.
	 * @param int $course_id Masteriyo course ID.
	 *
	 * @return void
	 */
	private static function process_question_migration( $question, $quiz_id, $course_id, $menu_order = 0 ) {
		$ques_id       = $question->id;
		$meta_key      = '_llms_question_type';
		$ques_type     = get_post_meta( $ques_id, $meta_key, true );
		$question_type = self::determine_question_type( $ques_type, $question );

		// Flatten group: recurse into children as top-level questions.
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

		// Skip content-type display blocks (no Masteriyo equivalent) and unknown types.
		if ( ! $question_type ) {
			return;
		}

		$formatted_answers = self::format_answers( $question->get_choices() );

		if ( empty( $formatted_answers ) ) {
			return;
		}

		$question_data = array(
			'ID'           => $question->post->ID,
			'post_type'    => PostType::QUESTION,
			'post_title'   => sanitize_text_field( $question->post->post_title ),
			'post_content' => wp_json_encode( $formatted_answers ),
			'post_excerpt' => sanitize_text_field( $question->post_content ),
			'post_status'  => PostStatus::PUBLISH,
			'post_parent'  => $quiz_id,
			'menu_order'   => $menu_order,
		);

		$question_id = wp_update_post( $question_data );

		if ( is_wp_error( $question_id ) ) {
			masteriyo_get_logger()->error( 'Migration: Failed to update LifterLMS question post.', array( 'source' => 'migration-tool' ) );
			return;
		}

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
	 * Count total source items for a given migration step. Fast COUNT query — no records loaded.
	 *
	 * @since x.x.x
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
							WHERE um.meta_key = %s
							  AND ( um.meta_value LIKE %s OR um.meta_value LIKE %s )
							UNION
							SELECT DISTINCT user_id
							FROM {$wpdb->prefix}lifterlms_user_postmeta
							WHERE meta_key = '_status'
						) AS llms_users",
						$capabilities_key,
						'%"instructor"%',
						'%"lms_manager"%'
					)
				);

			case 'courses':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'course'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'enrollments':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}lifterlms_user_postmeta WHERE meta_key = '_status'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'lesson_progress':
				// LifterLMS stores _is_complete only for lessons, never for quizzes.
				// Lessons without quizzes are renamed to mto-lesson by the courses step.
				// Lessons that had quizzes are NOT renamed — their quiz becomes mto-quiz but
				// the original lesson post keeps post_type = 'lesson'. Both must be counted.
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}lifterlms_user_postmeta upm
					 INNER JOIN {$wpdb->posts} p ON p.ID = upm.post_id
					 WHERE upm.meta_key = '_is_complete'
					   AND p.post_type IN ('mto-lesson', 'lesson')" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'orders':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'llms_order'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'reviews':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'llms_review'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'quiz_attempts':
				$attempts_table = $wpdb->prefix . 'lifterlms_quiz_attempts';
				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $attempts_table ) ) !== $attempts_table ) {
					return 0;
				}
				$mto_attempts_table = $wpdb->prefix . 'masteriyo_quiz_attempts';
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$attempts_table} la
					 LEFT JOIN {$mto_attempts_table} ma
					     ON ma.quiz_id = la.quiz_id
					    AND ma.user_id = la.student_id
					    AND ma.attempt_started_at = la.start_date
					 WHERE ma.id IS NULL"
				);
				// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			case 'earned_certificates':
				return (int) $wpdb->get_var(
					"SELECT COUNT(DISTINCT pm.meta_value)
					 FROM {$wpdb->postmeta} pm
					 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
					 WHERE p.post_type = 'llms_my_certificate'
					   AND pm.meta_key = '_llms_related'
					   AND pm.meta_value > 0" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

		}

		return 0;
	}

	/**
	 * Return paginated source IDs for a given migration step.
	 *
	 * Courses, orders, and reviews use no OFFSET because their migration mutates the source
	 * record (CPT rename or deletion), making migrated items self-remove from the result set.
	 * Enrollments and lesson_progress rows are deleted after migration, so they also self-remove.
	 * Users use OFFSET because user rows persist after role assignment.
	 *
	 * @since x.x.x
	 * @param string $step   Step name.
	 * @param int    $limit  Batch size.
	 * @param int    $offset Number of records already processed (used only for users).
	 * @return int[]
	 */
	public static function get_source_ids( string $step, int $limit, int $cursor, array $exclude = array() ): array {
		global $wpdb;

		// NOT IN for ID-column self-cleaning steps.
		$not_in      = '';
		$not_in_args = array();
		if ( ! empty( $exclude ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
			$not_in       = "AND ID NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$not_in_args  = array_map( 'intval', $exclude );
		}

		// NOT IN for meta_id-column self-cleaning steps.
		$meta_not_in      = '';
		$meta_not_in_args = array();
		if ( ! empty( $exclude ) ) {
			$placeholders     = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
			$meta_not_in      = "AND upm.meta_id NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$meta_not_in_args = array_map( 'intval', $exclude );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		switch ( $step ) {
			case 'users':
				// Cursor-based — user rows persist after role change; $exclude ignored.
				$capabilities_key = $wpdb->get_blog_prefix() . 'capabilities';
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
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts}
						 WHERE post_type = 'course'
						 {$not_in}
						 ORDER BY ID ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'enrollments':
				$enr_not_in      = '';
				$enr_not_in_args = array();
				if ( ! empty( $exclude ) ) {
					$placeholders    = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
					$enr_not_in      = "AND meta_id NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$enr_not_in_args = array_map( 'intval', $exclude );
				}
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT meta_id FROM {$wpdb->prefix}lifterlms_user_postmeta
						 WHERE meta_key = '_status'
						 {$enr_not_in}
						 ORDER BY meta_id ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $enr_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'lesson_progress':
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT upm.meta_id FROM {$wpdb->prefix}lifterlms_user_postmeta upm
						 INNER JOIN {$wpdb->posts} p ON p.ID = upm.post_id
						 WHERE upm.meta_key = '_is_complete'
						   AND p.post_type IN ('mto-lesson', 'lesson')
						   {$meta_not_in}
						 ORDER BY upm.meta_id ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $meta_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'orders':
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts}
						 WHERE post_type = 'llms_order'
						 {$not_in}
						 ORDER BY ID ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'reviews':
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts}
						 WHERE post_type = 'llms_review'
						 {$not_in}
						 ORDER BY ID ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'quiz_attempts':
				// Cursor-based — source rows are not deleted after migration; already-migrated rows
				// are excluded via LEFT JOIN so re-running shows the correct remaining count.
				$attempts_table     = $wpdb->prefix . 'lifterlms_quiz_attempts';
				$mto_attempts_table = $wpdb->prefix . 'masteriyo_quiz_attempts';
				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $attempts_table ) ) !== $attempts_table ) {
					return array();
				}
				return array_map(
					'intval',
					$wpdb->get_col(
						$wpdb->prepare(
							"SELECT la.id FROM {$attempts_table} la
							 LEFT JOIN {$mto_attempts_table} ma
							     ON ma.quiz_id = la.quiz_id
							    AND ma.user_id = la.student_id
							    AND ma.attempt_started_at = la.start_date
							 WHERE la.id > %d
							   AND ma.id IS NULL
							 ORDER BY la.id ASC
							 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							$cursor,
							$limit
						)
					)
				);

			case 'earned_certificates':
				// Cursor-based — source posts are not deleted; $exclude ignored.
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED) AS course_id
						 FROM {$wpdb->postmeta} pm
						 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
						 WHERE p.post_type = 'llms_my_certificate'
						   AND pm.meta_key = '_llms_related'
						   AND CAST(pm.meta_value AS UNSIGNED) > %d
						 ORDER BY course_id ASC
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
	 * Dispatch a single-item migration to the appropriate migrate_single_*() method.
	 *
	 * Called by MigrationProcessJob inside a START TRANSACTION / COMMIT wrapper.
	 * Must be idempotent — safe to call twice for the same (step, item_id) pair.
	 *
	 * @since x.x.x
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
			case 'earned_certificates':
				static::migrate_single_earned_certificate( $item_id );
				break;
		}
	}

	/**
	 * Assign Masteriyo roles to a single LifterLMS user.
	 *
	 * Maps LifterLMS instructor/lms_manager roles to Masteriyo instructor.
	 * Students receive the Masteriyo student role via Helper::update_user_role()
	 * when their enrollment is processed in the 'enrollments' step.
	 *
	 * Idempotent: LifterLMS roles are removed and Masteriyo roles added; safe to call twice.
	 *
	 * @since x.x.x
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

		$llms_instructor_roles = array( 'instructor', 'lms_manager' );
		$has_llms_instructor   = ! empty( array_intersect( $llms_instructor_roles, $roles ) );

		if ( $has_llms_instructor ) {
			if ( ! $is_admin ) {
				$user->add_role( Roles::INSTRUCTOR );
			}
			foreach ( $llms_instructor_roles as $llms_role ) {
				$user->remove_role( $llms_role );
			}
			// Refresh after mutation so the student check below sees updated roles.
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
	}

	/**
	 * Migrate a single LifterLMS course to a Masteriyo mto-course.
	 *
	 * Idempotent: returns early if post_type is already mto-course.
	 *
	 * @since x.x.x
	 * @param int $course_id LifterLMS course post ID.
	 * @throws \Exception If the post does not exist or is not a LifterLMS course.
	 */
	public static function migrate_single_course( int $course_id ): void {
		$post = get_post( $course_id );

		if ( ! $post ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d not found.', $course_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		if ( PostType::COURSE === $post->post_type ) {
			return;
		}

		if ( 'course' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d is not a LifterLMS course (got %s).', $course_id, $post->post_type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		static::migrate_course( $course_id );
		static::migrate_course_info( $course_id );
		static::create_woocommerce_product( $course_id );
		Helper::migrate_course_author( $course_id );
		Helper::migrate_course_categories_from_to_masteriyo( $course_id, 'course_cat' );
		Helper::migrate_course_categories_from_to_masteriyo( $course_id, 'course_difficulty', 'course_difficulty' );
		Helper::migrate_course_categories_from_to_masteriyo( $course_id, 'course_tag', 'course_tag' );
	}

	/**
	 * Migrate a single LifterLMS enrollment record to a Masteriyo user_course item.
	 *
	 * Operates on one lifterlms_user_postmeta row by its meta_id primary key.
	 * Deletes the source row after migration so it self-removes from paginated queries.
	 *
	 * @since x.x.x
	 * @param int $meta_id Primary key of the lifterlms_user_postmeta row (meta_key = '_status').
	 * @throws \Exception If the row does not exist or the DB insert fails.
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

		$user_id   = (int) $row->user_id;
		$course_id = (int) $row->post_id;

		if ( masteriyo_is_user_already_enrolled( $user_id, $course_id ) ) {
			$wpdb->delete(
				$wpdb->prefix . 'lifterlms_user_postmeta',
				array( 'meta_id' => $meta_id ),
				array( '%d' )
			);
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

		Helper::update_user_role( $user_id );

		// Link to the Masteriyo order if an enrollment trigger exists (format: 'order_123').
		$user_item_id = (int) $wpdb->insert_id;
		if ( $user_item_id ) {
			$trigger = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_value FROM {$wpdb->prefix}lifterlms_user_postmeta
					 WHERE user_id = %d AND post_id = %d AND meta_key = '_enrollment_trigger'
					 LIMIT 1",
					$user_id,
					$course_id
				)
			);

			if ( $trigger && 0 === strpos( $trigger, 'order_' ) ) {
				$order_id = (int) str_replace( 'order_', '', $trigger );

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
							'meta_value'   => get_post_meta( $order_id, '_llms_total', true ),
						),
						array( '%d', '%s', '%s' )
					);
				}
			}
		}

		$wpdb->delete(
			$wpdb->prefix . 'lifterlms_user_postmeta',
			array( 'meta_id' => $meta_id ),
			array( '%d' )
		);
	}

	/**
	 * Migrate a single lifterlms_user_postmeta lesson/quiz progress row to masteriyo_user_activities.
	 *
	 * Writes a course_progress activity (if missing) and a lesson or quiz activity.
	 * Deletes the source row after migrating so it self-removes from paginated queries.
	 *
	 * @since x.x.x
	 * @param int $meta_id Primary key of the lifterlms_user_postmeta row (meta_key = '_is_complete').
	 * @throws \Exception If the row does not exist.
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

		$user_id   = (int) $row->user_id;
		$lesson_id = (int) $row->post_id;
		$completed = ( 'yes' === $row->meta_value );
		$date      = $row->updated_date ? $row->updated_date : $now;

		// Default: plain lesson renamed to mto-lesson by migrate_course().
		$activity_type    = CourseProgressItemType::LESSON;
		$activity_item_id = $lesson_id;
		$course_id        = (int) get_post_meta( $lesson_id, '_course_id', true );

		// If the lesson still has post_type = 'lesson', it had a quiz. migrate_course() renamed
		// the QUIZ to mto-quiz and left the lesson post behind. LifterLMS writes _is_complete on
		// the lesson when its quiz passes — we must map this to a QUIZ activity on the quiz post.
		if ( 'lesson' === get_post_type( $lesson_id ) ) {
			$quiz_id = (int) get_post_meta( $lesson_id, '_llms_quiz', true );

			if ( ! $quiz_id ) {
				masteriyo_get_logger()->warning(
					sprintf( 'Migration: Lesson %d (post_type=lesson) has no _llms_quiz meta — skipping progress row %d.', $lesson_id, $meta_id ),
					array( 'source' => 'migration-tool' )
				);
				$wpdb->delete( $wpdb->prefix . 'lifterlms_user_postmeta', array( 'meta_id' => $meta_id ), array( '%d' ) );
				return;
			}

			$activity_type    = CourseProgressItemType::QUIZ;
			$activity_item_id = $quiz_id;
			$course_id        = (int) get_post_meta( $quiz_id, '_course_id', true );
		}

		if ( ! $course_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: No course found for lesson %d — skipping progress row %d.', $lesson_id, $meta_id ),
				array( 'source' => 'migration-tool' )
			);
			$wpdb->delete( $wpdb->prefix . 'lifterlms_user_postmeta', array( 'meta_id' => $meta_id ), array( '%d' ) );
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
			masteriyo_get_logger()->warning(
				sprintf( 'Migration: Failed to get/create course_progress activity for user %d course %d.', $user_id, $course_id ),
				array( 'source' => 'migration-tool' )
			);
			$wpdb->delete( $wpdb->prefix . 'lifterlms_user_postmeta', array( 'meta_id' => $meta_id ), array( '%d' ) );
			return;
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

		// Delete source row — makes it self-removing from paginated queries.
		$wpdb->delete( $wpdb->prefix . 'lifterlms_user_postmeta', array( 'meta_id' => $meta_id ), array( '%d' ) );
	}

	/**
	 * Migrate a single LifterLMS order to a Masteriyo mto-order.
	 *
	 * Idempotent: returns early if post_type is already mto-order.
	 *
	 * @since x.x.x
	 * @param int $order_id LifterLMS llms_order post ID.
	 * @throws \Exception If the post does not exist or is not an llms_order post.
	 */
	public static function migrate_single_order( int $order_id ): void {
		$post = get_post( $order_id );

		if ( ! $post ) {
			throw new \Exception(
				sprintf( 'migrate_single_order: post %d not found.', $order_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		if ( PostType::ORDER === $post->post_type ) {
			return;
		}

		if ( 'llms_order' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_order: post %d is not an llms_order post (got %s).', $order_id, $post->post_type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		static::migrate_order( $order_id );
	}

	/**
	 * Migrate a single LifterLMS review (llms_review CPT) to a Masteriyo course_review comment.
	 *
	 * Deletes the source post after inserting the WP comment so it self-removes from paginated queries.
	 *
	 * @since x.x.x
	 * @param int $review_id LifterLMS llms_review post ID.
	 * @throws \Exception If the post does not exist or is not an llms_review post.
	 */
	public static function migrate_single_review( int $review_id ): void {
		$llms_review = get_post( $review_id );

		if ( ! $llms_review || 'llms_review' !== $llms_review->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_review: llms_review post %d not found.', $review_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$course_id = (int) $llms_review->post_parent;
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

		$review_data = array(
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
		);

		$new_comment_id = wp_insert_comment( $review_data );

		if ( $new_comment_id ) {
			update_comment_meta( $new_comment_id, '_title', sanitize_text_field( $llms_review->post_title ) );
		}

		// Delete the original source post so it no longer appears in source queries.
		wp_delete_post( $review_id, true );
	}

	/**
	 * Migrates an order from LifterLMS to Masteriyo.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id LifterLMS order ID.
	 */
	private static function migrate_order( $order_id ) {
		$current_status = get_post_status( $order_id );

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
		$status           = $order_status_map[ $current_status ] ?? OrderStatus::PENDING;

		$order = array(
			'ID'            => $order_id,
			'post_type'     => PostType::ORDER,
			'post_status'   => $status,
			'post_password' => masteriyo_generate_order_key(),
		);

		wp_update_post( $order );

		self::update_order_items( $order_id );
		self::update_order_meta( $order_id );
	}

	/**
	 * Updates the order items.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id LifterLMS order ID.
	 */
	private static function update_order_items( $order_id ) {
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

		self::update_order_items_meta( $order_item_id, $order_id );
	}

	/**
	 * Updates order item meta for a given order item and order.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_item_id Order item ID.
	 * @param int $order_id      Order ID.
	 */
	private static function update_order_items_meta( $order_item_id, $order_id ) {
		global $wpdb;

		$quantity  = 1;
		$course_id = absint( get_post_meta( $order_id, '_llms_product_id', true ) );
		$subtotal  = get_post_meta( $order_id, '_llms_original_total', true );
		$total     = get_post_meta( $order_id, '_llms_total', true );

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
	 * Updates the order meta data.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id Masteriyo order ID.
	 */
	private static function update_order_meta( $order_id ) {
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

		// Map gateway transaction reference from llms_transaction child post.
		$transactions = get_posts(
			array(
				'post_type'      => 'llms_transaction',
				'post_parent'    => $order_id,
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
	 * Migrate a single LifterLMS quiz attempt row to masteriyo_quiz_attempts.
	 *
	 * Cursor-based — source rows are not deleted (historical records are preserved).
	 *
	 * @since x.x.x
	 * @param int $attempt_id Primary key of the lifterlms_quiz_attempts row.
	 */
	private static function migrate_single_quiz_attempt( int $attempt_id ): void {
		global $wpdb;

		$attempts_table = $wpdb->prefix . 'lifterlms_quiz_attempts';
		$row            = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$attempts_table} WHERE id = %d", $attempt_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		if ( ! $row ) {
			return;
		}

		$quiz_id   = (int) $row['quiz_id'];
		$course_id = (int) get_post_meta( $quiz_id, '_course_id', true );

		$raw_questions   = maybe_unserialize( $row['questions'] );
		$answers_json    = array();
		$total_correct   = 0;
		$total_incorrect = 0;
		$total_questions = is_array( $raw_questions ) ? count( $raw_questions ) : 0;

		if ( is_array( $raw_questions ) ) {
			foreach ( $raw_questions as $q ) {
				$q                = (array) $q;
				$is_correct       = ! empty( $q['correct'] ) ? 1 : 0;
				$total_correct   += $is_correct;
				$total_incorrect += ( 1 - $is_correct );
				$answers_json[]   = array(
					'question_id' => isset( $q['id'] ) ? (int) $q['id'] : 0,
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

		// Idempotency — skip if this attempt was already migrated (source rows are not deleted).
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
			return;
		}

		$wpdb->insert(
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
	}

	/**
	 * Assign a Masteriyo certificate template to a course that had earned certificates in LifterLMS.
	 *
	 * LifterLMS certificate designs cannot be converted — a starter template is auto-assigned.
	 * Students who completed the course will be able to download the certificate immediately.
	 *
	 * @since x.x.x
	 * @param int $course_id Masteriyo course post ID.
	 */
	private static function migrate_single_earned_certificate( int $course_id ): void {
		$author_id = (int) get_post_field( 'post_author', $course_id );
		Helper::assign_certificate_template( $course_id, $author_id );
		masteriyo_get_logger()->info(
			sprintf(
				'Migration: Certificate template assigned to course %d (original LifterLMS design cannot be converted).',
				$course_id
			),
			array( 'source' => 'migration-tool' )
		);
	}

	/**
	 * Assign starter certificate templates to all migrated LifterLMS courses that had
	 * earned certificates. Runs once as a single batch after the 'courses' step completes —
	 * avoids one extra DB query per course during migration.
	 *
	 * @since x.x.x
	 */
	private static function assign_certificate_templates_for_lifterlms_courses(): void {
		global $wpdb;

		// One JOIN query finds every migrated mto-course linked to at least one llms_my_certificate.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$course_ids = $wpdb->get_col(
			"SELECT DISTINCT CAST(pm.meta_value AS UNSIGNED) AS course_id
			 FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} cert   ON cert.ID   = pm.post_id
			 INNER JOIN {$wpdb->posts} course ON course.ID = CAST(pm.meta_value AS UNSIGNED)
			 WHERE cert.post_type   = 'llms_my_certificate'
			   AND pm.meta_key      = '_llms_related'
			   AND pm.meta_value    > 0
			   AND course.post_type = 'mto-course'
			 ORDER BY course_id ASC"
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
	 * Bulk-update course_progress status once all lesson_progress items are migrated.
	 * Replaces the per-item recount queries — runs once after the step completes.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 */
	public static function finalize_step( string $step ): void {
		if ( 'courses' === $step ) {
			self::assign_certificate_templates_for_lifterlms_courses();
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
			         UTC_TIMESTAMP(),
			         '0000-00-00 00:00:00'
			     ),
			     parent.modified_at  = UTC_TIMESTAMP()
			 WHERE parent.activity_type = 'course_progress'
			   AND completed.done       > 0"
		);
		// phpcs:enable
	}
}
