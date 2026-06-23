<?php
/**
 * MasterStudy migrations.
 *
 * @since 1.16.0
 *
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool\LMS;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\Helper;
use Masteriyo\Enums\CommentStatus;
use Masteriyo\Enums\CommentType;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CoursePriceType;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuestionType;
use Masteriyo\Enums\CourseProgressItemType;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Roles;
use MasterStudy\Lms\Repositories\CurriculumRepository;

/**
 * Class MasterStudy.
 *
 * @since 1.16.0
 */
class MasterStudy {

	/**
	 * Migrates a single MasterStudy course.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id MasterStudy course ID.
	 */
	private static function migrate_course( $course_id ) {
		$curriculum_repo = new CurriculumRepository();
		$sections        = $curriculum_repo->get_curriculum( $course_id, true );

		if ( empty( $sections ) ) {
			wp_update_post(
				array(
					'ID'        => $course_id,
					'post_type' => PostType::COURSE,
				)
			);
			return;
		}

		$mto_course = array();

		foreach ( $sections as $section_data ) {
			$section_title = $section_data['title'];
			$section_order = $section_data['order'];

			$mto_section = array(
				'post_type'   => PostType::SECTION,
				'post_title'  => $section_title ? $section_title : __( 'Section', 'learning-management-system' ),
				'post_status' => PostStatus::PUBLISH,
				'post_author' => get_post_field( 'post_author', $course_id ),
				'post_parent' => $course_id,
				'menu_order'  => $section_order,
				'items'       => array(),
			);

			if ( empty( $section_data['materials'] ) ) {
				$mto_course[] = $mto_section;
				continue;
			}

			foreach ( $section_data['materials'] as $item ) {
				$item_post_type  = PostType::LESSON;
				$item_menu_order = $item['order'];

				if ( 'stm-quizzes' === $item['post_type'] ) {
					$item_post_type = PostType::QUIZ;
				}

				$mto_section['items'][] = array(
					'ID'          => absint( $item['post_id'] ),
					'post_type'   => $item_post_type,
					'post_parent' => null,
					'menu_order'  => $item_menu_order,
				);
			}

			$mto_course[] = $mto_section;
		}

		if ( empty( $mto_course ) ) {
			wp_update_post(
				array(
					'ID'        => $course_id,
					'post_type' => PostType::COURSE,
				)
			);
			return;
		}

		foreach ( $mto_course as $section ) {
			$items = $section['items'];
			unset( $section['items'] );

			$section_id = wp_insert_post( $section );

			if ( is_wp_error( $section_id ) ) {
				masteriyo_get_logger()->error( 'Migration: Failed to insert MasterStudy section post.', array( 'source' => 'migration-tool' ) );
				continue;
			}

			update_post_meta( $section_id, '_course_id', $course_id );

			if ( empty( $items ) ) {
				continue;
			}

			foreach ( $items as $item ) {
				$item_id = masteriyo_array_get( $item, 'ID', 0 );

				$item['post_parent'] = $section_id;

				wp_update_post( $item );
				update_post_meta( $item_id, '_course_id', $course_id );

				if ( PostType::QUIZ === $item['post_type'] ) {
					$quiz_id = $item_id;

					$question_ids = explode( ',', get_post_meta( $quiz_id, 'questions', true ) );

					if ( empty( $question_ids ) ) {
						continue;
					}

					$k = 0;
					foreach ( $question_ids as $question_id ) {
						self::process_question_migration( absint( $question_id ), $quiz_id, $course_id, $k );
						++$k;
					}

					$duration         = absint( get_post_meta( $quiz_id, 'duration', true ) );
					$duration_measure = get_post_meta( $quiz_id, 'duration_measure', true );

					if ( 'hours' === $duration_measure ) {
						$duration *= 60;
					} elseif ( 'days' === $duration_measure ) {
						$duration *= 60 * 24;
					}

					$passing_grade = get_post_meta( $quiz_id, 'passing_grade', true );

					update_post_meta( $item_id, '_duration', $duration );
					update_post_meta( $item_id, '_pass_mark', $passing_grade );
				} elseif ( PostType::LESSON === $item['post_type'] ) {
					$source       = get_post_meta( $item_id, 'video_type', true );
					$video_poster = get_post_meta( $item_id, 'lesson_video_poster', true );
					$files        = get_post_meta( $item_id, 'lesson_files', true );
					$files        = is_string( $files ) ? json_decode( $files, true ) : $files;
					$url          = '';

					switch ( $source ) {
						case 'embed':
							$source = 'embed-video';
							$url    = htmlspecialchars_decode( get_post_meta( $item_id, 'lesson_embed_ctx', true ) );
							break;
						case 'youtube':
							$url = get_post_meta( $item_id, 'lesson_youtube_url', true );
							break;
						case 'vimeo':
							$url = get_post_meta( $item_id, 'lesson_vimeo_url', true );
							break;
						case 'external':
							$url = get_post_meta( $item_id, 'lesson_ext_link_url', true );
							break;
						case 'html':
							$video_id = absint( get_post_meta( $item_id, 'lesson_video', true ) );
							// Lesson model reads _video_source_url as absint() for self-hosted, so store the
							// attachment ID as a string — not the resolved URL.
							$url    = (string) $video_id;
							$source = 'self-hosted';
							break;
					}

					update_post_meta( $item_id, '_video_source', $source );
					update_post_meta( $item_id, '_video_source_url', $url );

					if ( $video_poster ) {
						update_post_meta( $item_id, '_thumbnail_id', $video_poster );
					}

					if ( ! empty( $files ) ) {
						update_post_meta( $item_id, '_download_materials', maybe_serialize( $files ) );
					}
				}
			}
		}

		$mto_course = array(
			'ID'        => $course_id,
			'post_type' => PostType::COURSE,
		);

		wp_update_post( $mto_course );
		update_post_meta( $course_id, '_was_ms_course', true );
	}

	/**
	 * Migrate course info from MasterStudy.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id MasterStudy course ID.
	 */
	private static function migrate_course_info( $course_id ) {
		$regular_price = get_post_meta( $course_id, 'price', true );
		$regular_price = $regular_price ? $regular_price : 0;
		$sale_price    = get_post_meta( $course_id, 'sale_price', true );
		$single_sale   = get_post_meta( $course_id, 'single_sale', true );

		$course_type = CoursePriceType::FREE;
		$access_mode = CourseAccessMode::OPEN;

		if ( 'on' === $single_sale ) {
			$course_type = CoursePriceType::PAID;
			$access_mode = CourseAccessMode::ONE_TIME;
		}

		wp_set_object_terms( $course_id, $course_type, 'course_visibility', false );
		update_post_meta( $course_id, '_access_mode', $access_mode );
		update_post_meta( $course_id, '_regular_price', $regular_price );

		if ( $sale_price ) {
			update_post_meta( $course_id, '_price', $sale_price );
			update_post_meta( $course_id, '_sale_price', $sale_price );
		} else {
			update_post_meta( $course_id, '_price', $regular_price );
		}

		$level = get_post_meta( $course_id, 'level', true );
		Helper::set_course_difficulty( $course_id, $level );

		$retake = get_post_meta( $course_id, 'retake', true );
		update_post_meta( $course_id, '_enable_course_retake', masteriyo_string_to_bool( $retake ) );

		$reviews_allowed = true;

		if ( class_exists( 'STM_LMS_Options' ) ) {
			$reviews_allowed = \STM_LMS_Options::get_option( 'course_tab_reviews', true );
		}

		update_post_meta( $course_id, '_reviews_allowed', masteriyo_string_to_bool( $reviews_allowed ) );

		$duration = absint( get_post_meta( $course_id, 'duration', true ) );
		if ( $duration ) {
			update_post_meta( $course_id, '_duration', $duration );
		}

		$highlights = get_post_meta( $course_id, 'basic_info', true );
		if ( $highlights ) {
			update_post_meta( $course_id, '_highlights', wp_kses_post( $highlights ) );
		}

		// Assign a starter certificate template if the source course had one.
		$course_certificate = get_post_meta( $course_id, 'course_certificate', true );
		if ( $course_certificate ) {
			Helper::assign_certificate_template(
				$course_id,
				(int) get_post_field( 'post_author', $course_id )
			);
		}
	}

	/**
	 * Inserts meta information for a user item in Masteriyo.
	 *
	 * @since 1.16.0
	 *
	 * @param int $user_item_id The ID of the user item.
	 * @param int $order_id     The order ID associated with the user enrollment.
	 *
	 * @return void
	 */
	private static function insert_user_item_meta( $user_id, $course_id, $order_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'masteriyo_user_itemmeta';

		$user_item_id = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %d AND item_id = %d AND item_type = 'user_course'",
				$user_id,
				$course_id
			)
		);

		if ( empty( $user_item_id ) ) {
			return;
		}

		$user_item_id = $user_item_id[0];

		$user_item_metas = array(
			array(
				'user_item_id' => $user_item_id,
				'meta_key'     => '_order_id',
				'meta_value'   => $order_id,
			),
			array(
				'user_item_id' => $user_item_id,
				'meta_key'     => '_price',
				'meta_value'   => get_post_meta( $order_id, '_order_total', true ),
			),
		);

		foreach ( $user_item_metas as $item_meta ) {
			$wpdb->insert( $table_name, $item_meta, array( '%d', '%s', '%s' ) );
		}
	}

	/**
	 * Processes migration for a single MasterStudy quiz question.
	 *
	 * @since 1.16.0
	 *
	 * @param int $question_id Question ID.
	 * @param int $quiz_id Masteriyo quiz ID.
	 * @param int $course_id Masteriyo course ID.
	 *
	 * @return void
	 */
	private static function process_question_migration( $question_id, $quiz_id, $course_id, $menu_order = 0 ) {
		$ms_type       = get_post_meta( $question_id, 'type', true );
		$question_type = self::determine_question_type( $ms_type );

		if ( ! $question_type ) {
			return;
		}

		$formatted_answers = self::format_answers( get_post_meta( $question_id, 'answers', true ), $ms_type );

		// fill_the_gap and keywords may produce single-item arrays — only skip truly empty results.
		if ( empty( $formatted_answers )
			&& ! in_array( $ms_type, array( 'fill_the_gap', 'keywords' ), true ) ) {
			return;
		}

		$question_data = array(
			'ID'           => $question_id,
			'post_type'    => PostType::QUESTION,
			'post_content' => wp_json_encode( $formatted_answers ),
			'post_parent'  => $quiz_id,
			'menu_order'   => $menu_order,
		);

		// Normalize fill_the_gap blank markers in the question title to {{blank}}.
		if ( 'fill_the_gap' === $ms_type ) {
			$raw_title                   = get_the_title( $question_id );
			$question_data['post_title'] = preg_replace( '/\[[^\]]*\]/', '{{blank}}', $raw_title );
		}

		$question_id = wp_update_post( $question_data );

		if ( is_wp_error( $question_id ) ) {
			masteriyo_get_logger()->error( 'Migration: Failed to update MasterStudy question post.', array( 'source' => 'migration-tool' ) );
			return;
		}

		update_post_meta( $question_id, '_course_id', $course_id );
		update_post_meta( $question_id, '_type', $question_type );
		update_post_meta( $question_id, '_parent_id', $quiz_id );
	}

	/**
	 * Determines the question type for Masteriyo based on MasterStudy data.
	 *
	 * @since 1.16.0
	 *
	 * @param string $ques_type The question type from MasterStudy.
	 *
	 * @return string|null The mapped question type for Masteriyo, or null if unsupported.
	 */
	private static function determine_question_type( $ques_type ) {
		switch ( $ques_type ) {
			case 'true_false':
				return QuestionType::TRUE_FALSE;
			case 'multi_choice':
				return QuestionType::MULTIPLE_CHOICE;
			case 'single_choice':
				return QuestionType::SINGLE_CHOICE;
			case 'fill_the_gap':
				return 'fill-in-the-blanks';
			case 'sortable':
				return 'sortable';
			case 'item_match':
			case 'image_match':
				return 'matching';
			case 'keywords':
				return 'text-answer';
			case 'question_bank':
			default:
				return null;
		}
	}

	/**
	 * Formats the answers for Masteriyo by sanitizing and structuring them.
	 *
	 * @since 1.16.0
	 *
	 * @param array  $answers  The serialized answers from MasterStudy.
	 * @param string $ms_type  Original MasterStudy question type slug.
	 *
	 * @return array The formatted answers array.
	 */
	private static function format_answers( $answers, $ms_type = '' ) {
		$answers = maybe_unserialize( $answers );

		if ( empty( $answers ) ) {
			return array();
		}

		$formatted_answers = array();

		switch ( $ms_type ) {
			case 'fill_the_gap':
				foreach ( $answers as $answer ) {
					$value = sanitize_text_field( $answer['text'] ?? '' );
					if ( '' !== $value ) {
						$formatted_answers[] = array( 'name' => $value );
					}
				}
				return $formatted_answers;

			case 'sortable':
				$i = 0;
				foreach ( $answers as $answer ) {
					$text = sanitize_text_field( $answer['text'] ?? '' );
					if ( '' !== $text ) {
						$formatted_answers[] = array(
							'name'  => $text,
							'order' => $i,
						);
						++$i;
					}
				}
				return $formatted_answers;

			case 'item_match':
				foreach ( $answers as $answer ) {
					$left  = sanitize_text_field( $answer['text'] ?? '' );
					$right = sanitize_text_field(
						$answer['question_value'] ?? $answer['match_value'] ?? $answer['answer'] ?? ''
					);
					if ( '' !== $left ) {
						$formatted_answers[] = array(
							'name'         => $left,
							'match_answer' => $right,
						);
					}
				}
				return $formatted_answers;

			case 'image_match':
				foreach ( $answers as $answer ) {
					$attachment_id = absint( $answer['attachment_id'] ?? $answer['image'] ?? 0 );
					$left          = $attachment_id
						? get_the_title( $attachment_id )
						: sanitize_text_field( $answer['text'] ?? '' );
					$right         = sanitize_text_field(
						$answer['question_value'] ?? $answer['match_value'] ?? $answer['answer'] ?? ''
					);
					if ( '' !== $left ) {
						$formatted_answers[] = array(
							'name'         => $left,
							'match_answer' => $right,
						);
					}
				}
				return $formatted_answers;

			case 'keywords':
				foreach ( $answers as $answer ) {
					$keyword = sanitize_text_field( $answer['text'] ?? '' );
					if ( '' !== $keyword ) {
						$formatted_answers[] = array( 'name' => $keyword );
					}
				}
				return $formatted_answers;

			default:
				// true_false, single_choice, multi_choice and any unknown type.
				foreach ( $answers as $answer ) {
					$choice = sanitize_text_field( $answer['text'] ?? '' );
					if ( '' !== $choice ) {
						$formatted_answers[] = array(
							'name'    => $choice,
							'correct' => ! empty( $answer['isTrue'] ),
						);
					}
				}
				return $formatted_answers;
		}
	}

	/**
	 * Migrates an order from MasterStudy to Masteriyo.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id MasterStudy order ID.
	 */
	private static function migrate_order( $order_id ) {
		$status     = get_post_meta( $order_id, 'status', true );
		$date       = get_post_meta( $order_id, 'date', true );
		$order_date = gmdate( 'Y-m-d H:i:s', absint( $date ) );
		$title      = 'Order - ' . $order_date;

		$order_status_map = array(
			'pending'   => OrderStatus::PENDING,
			'completed' => OrderStatus::COMPLETED,
			'cancelled' => OrderStatus::CANCELLED,
		);
		$post_status      = $order_status_map[ $status ] ?? OrderStatus::PENDING;

		$order = array(
			'ID'            => $order_id,
			'post_type'     => PostType::ORDER,
			'post_title'    => $title,
			'post_status'   => $post_status,
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
	 * @param int $order_id MasterStudy order ID.
	 */
	private static function update_order_items( $order_id ) {
		global $wpdb;

		$items = maybe_unserialize( get_post_meta( $order_id, 'items', true ) );

		if ( empty( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			$item_id = absint( $item['item_id'] );

			if ( ! $item_id ) {
				continue;
			}

			$item_name = get_post_field( 'post_title', $item_id );

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

			self::update_order_items_meta( $order_item_id, $order_id, $item_id );
		}
	}

	/**
	 * Updates order item meta for a given order item and order.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_item_id Order item ID.
	 * @param int $order_id      Order ID.
	 * @param int $course_id     Course ID.
	 */
	private static function update_order_items_meta( $order_item_id, $order_id, $course_id ) {
		global $wpdb;

		$stm_order_items_table = $wpdb->prefix . 'stm_lms_order_items';
		$quantity              = array();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $stm_order_items_table ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$quantity = $wpdb->get_col( $wpdb->prepare( "SELECT quantity FROM {$stm_order_items_table} WHERE order_id = %d AND object_id = %d", $order_id, $course_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		if ( ! empty( $quantity ) ) {
			$quantity = $quantity[0];
		} else {
			$quantity = 1;
		}

		$total   = get_post_meta( $order_id, '_order_total', true );
		$user_id = absint( get_post_meta( $order_id, 'user_id', true ) );

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
				'meta_value'    => $total,
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

		self::insert_user_item_meta( $user_id, $course_id, $order_id );
	}

	/**
	 * Updates the order meta data.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id Masteriyo order ID.
	 */
	private static function update_order_meta( $order_id ) {
		$user_id         = absint( get_post_meta( $order_id, 'user_id', true ) );
		$currency        = get_post_meta( $order_id, '_order_currency', true );
		$payment_gateway = get_post_meta( $order_id, 'payment_code', true );

		if ( 'cash' === $payment_gateway ) {
			$payment_gateway = 'offline';
		}

		$user = get_user_by( 'id', $user_id );

		update_post_meta( $order_id, '_payment_method', $payment_gateway );
		update_post_meta( $order_id, '_version', MASTERIYO_VERSION );
		update_post_meta( $order_id, '_customer_id', $user_id );
		update_post_meta( $order_id, '_total', get_post_meta( $order_id, '_order_total', true ) );
		update_post_meta( $order_id, '_currency', $currency );

		if ( $user ) {
			update_post_meta( $order_id, '_billing_first_name', $user->first_name );
			update_post_meta( $order_id, '_billing_last_name', $user->last_name );
			update_post_meta( $order_id, '_billing_address_index', $user->user_email );
			update_post_meta( $order_id, '_billing_email', $user->user_email );
		}

		update_post_meta( $order_id, '_was_ms_order', true );

		// Subtotal, tax, and transaction ID were missing from the original mapping.
		$subtotal       = get_post_meta( $order_id, '_order_subtotal', true );
		$tax_total      = get_post_meta( $order_id, '_order_taxes', true );
		$transaction_id = get_post_meta( $order_id, 'transaction_id', true );

		if ( $subtotal ) {
			update_post_meta( $order_id, '_subtotal', $subtotal );
		}
		if ( $tax_total ) {
			update_post_meta( $order_id, '_tax_total', $tax_total );
		}
		if ( $transaction_id ) {
			update_post_meta( $order_id, '_transaction_id', $transaction_id );
		}
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
				// Count all instructors (stm_lms_instructor WP role) UNION all enrolled students.
				$capabilities_key = $wpdb->get_blog_prefix() . 'capabilities';
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM (
							SELECT DISTINCT u.ID
							FROM {$wpdb->users} u
							INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
							WHERE um.meta_key = %s AND um.meta_value LIKE %s
							UNION
							SELECT DISTINCT user_id FROM {$wpdb->prefix}stm_lms_user_courses
						) AS ms_users",
						$capabilities_key,
						'%stm_lms_instructor%'
					)
				);

			case 'courses':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'stm-courses'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'enrollments':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}stm_lms_user_courses" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'lesson_progress':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}stm_lms_user_lessons" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'orders':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'stm-orders'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'reviews':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'stm-reviews'" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'quiz_attempts':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}stm_lms_user_quizzes" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				);

			case 'wishlists':
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s",
						'stm_lms_wishlist'
					)
				);
		}

		return 0;
	}

	/**
	 * Return paginated source IDs for a given migration step using LIMIT/OFFSET.
	 *
	 * Courses, orders, and reviews use no OFFSET because their migration mutates the source
	 * record (CPT rename or deletion), making migrated items self-remove from the result set.
	 * Enrollments use OFFSET because the stm_lms_user_courses row persists after migration.
	 *
	 * @since x.x.x
	 * @param string $step   Step name.
	 * @param int    $limit  Batch size.
	 * @param int    $offset Number of records already processed (used only for enrollments).
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
							WHERE um.meta_key = %s AND um.meta_value LIKE %s
							UNION
							SELECT DISTINCT user_id FROM {$wpdb->prefix}stm_lms_user_courses
						) AS ms_users
						WHERE user_id > %d
						ORDER BY user_id ASC
						LIMIT %d",
						$capabilities_key,
						'%stm_lms_instructor%',
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'courses':
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts}
						 WHERE post_type = 'stm-courses'
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
					$enr_not_in      = "WHERE user_course_id NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$enr_not_in_args = array_map( 'intval', $exclude );
				}
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_course_id FROM {$wpdb->prefix}stm_lms_user_courses
						 {$enr_not_in}
						 ORDER BY user_course_id ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $enr_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'lesson_progress':
				$lp_not_in      = '';
				$lp_not_in_args = array();
				if ( ! empty( $exclude ) ) {
					$placeholders   = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
					$lp_not_in      = "WHERE user_lesson_id NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$lp_not_in_args = array_map( 'intval', $exclude );
				}
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_lesson_id FROM {$wpdb->prefix}stm_lms_user_lessons
						 {$lp_not_in}
						 ORDER BY user_lesson_id ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $lp_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'orders':
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts}
						 WHERE post_type = 'stm-orders'
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
						 WHERE post_type = 'stm-reviews'
						 {$not_in}
						 ORDER BY ID ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'quiz_attempts':
				$qa_not_in      = '';
				$qa_not_in_args = array();
				if ( ! empty( $exclude ) ) {
					$placeholders   = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
					$qa_not_in      = "WHERE user_quiz_id NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$qa_not_in_args = array_map( 'intval', $exclude );
				}
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_quiz_id FROM {$wpdb->prefix}stm_lms_user_quizzes
						 {$qa_not_in}
						 ORDER BY user_quiz_id ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $qa_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ? $ids : array() );

			case 'wishlists':
				$wl_not_in      = '';
				$wl_not_in_args = array();
				if ( ! empty( $exclude ) ) {
					$placeholders   = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
					$wl_not_in      = "AND user_id NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wl_not_in_args = array_map( 'intval', $exclude );
				}
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT user_id FROM {$wpdb->usermeta}
						 WHERE meta_key = %s
						 {$wl_not_in}
						 ORDER BY user_id ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( array( 'stm_lms_wishlist' ), $wl_not_in_args, array( $limit ) )
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
	 * @param string $step    Step name matching a key in MasterStudyMigrator::get_steps().
	 * @param int    $item_id Source item ID (post ID or stm_lms_user_courses.user_course_id).
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
			case 'wishlists':
				static::register_wishlist_service_provider();
				static::migrate_single_wishlist( $item_id );
				break;
		}
	}

	/**
	 * Assign Masteriyo roles to a single MasterStudy instructor.
	 *
	 * Handles instructors only — identified by the stm_lms_instructor WP role.
	 * Students receive the Masteriyo student role automatically via Helper::update_user_role()
	 * when their enrollment is processed in the 'enrollments' step.
	 *
	 * Idempotent: if the user already has the Masteriyo instructor role the stm_lms_instructor
	 * role is still cleaned up and the method returns without re-adding.
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

		// Migrate instructor: stm_lms_instructor WP role → Masteriyo instructor.
		if ( in_array( 'stm_lms_instructor', $roles, true ) || isset( $caps['stm_lms_instructor'] ) ) {
			if ( ! $is_admin ) {
				$user->add_role( Roles::INSTRUCTOR );
			}
			$user->remove_role( 'stm_lms_instructor' );
			$user->remove_cap( 'stm_lms_instructor' );
			// Refresh roles after mutation so the student check below sees the updated state.
			$roles = (array) $user->roles;
			$caps  = is_array( $user->caps ) ? $user->caps : array();
		}

		// Migrate student: any user enrolled in stm_lms_user_courses who is not an admin or instructor.
		if ( ! $is_admin
			&& ! in_array( Roles::INSTRUCTOR, $roles, true )
			&& ! isset( $caps[ Roles::INSTRUCTOR ] )
			&& ! in_array( Roles::STUDENT, $roles, true )
			&& ! isset( $caps[ Roles::STUDENT ] )
		) {
			$has_enrollment = (bool) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT 1 FROM {$wpdb->prefix}stm_lms_user_courses WHERE user_id = %d LIMIT 1",
					$user_id
				)
			);

			if ( $has_enrollment ) {
				$user->add_role( Roles::STUDENT );
			}
		}
	}

	/**
	 * Migrate a single MasterStudy course to a Masteriyo mto-course.
	 *
	 * Extracted from migrate_ms_courses() bulk loop. Does NOT migrate enrollments —
	 * enrollments are exclusively owned by the 'enrollments' step via migrate_single_enrollment().
	 * Idempotent: the inner migrate_course() sets _was_ms_course meta after completing.
	 *
	 * @since x.x.x
	 * @param int $course_id MasterStudy stm-courses post ID.
	 * @throws \Exception If the post does not exist or is not an stm-courses post.
	 */
	public static function migrate_single_course( int $course_id ): void {
		$post = get_post( $course_id );

		if ( ! $post ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d not found.', $course_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		// Idempotency: already migrated if post_type is now mto-course.
		if ( PostType::COURSE === $post->post_type ) {
			return;
		}

		if ( 'stm-courses' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d is not an stm-courses post (got %s).', $course_id, $post->post_type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		static::migrate_course( $course_id );

		Helper::migrate_course_categories_from_to_masteriyo( $course_id, 'stm_lms_course_taxonomy' );

		static::migrate_course_info( $course_id );

		static::migrate_course_announcement( $course_id );

		Helper::migrate_course_author( $course_id );
	}

	/**
	 * Migrate the MasterStudy course announcement meta to a Masteriyo mto-announcement post.
	 *
	 * MasterStudy stores one announcement per course as plain text in the `announcement`
	 * post meta key. Creates one `mto-announcement` post if the meta is non-empty.
	 *
	 * @since x.x.x
	 * @param int $course_id Masteriyo mto-course post ID.
	 */
	private static function migrate_course_announcement( int $course_id ): void {
		$announcement_text = get_post_meta( $course_id, 'announcement', true );

		if ( empty( $announcement_text ) ) {
			return;
		}

		$stripped = mb_substr( wp_strip_all_tags( $announcement_text ), 0, 80 );
		$title    = $stripped ? $stripped : __( 'Course Announcement', 'learning-management-system' );
		$post_id  = wp_insert_post(
			array(
				'post_type'    => PostType::COURSEANNOUNCEMENT,
				'post_title'   => sanitize_text_field( $title ),
				'post_content' => wp_kses_post( $announcement_text ),
				'post_status'  => PostStatus::PUBLISH,
				'post_author'  => (int) get_post_field( 'post_author', $course_id ),
				'post_parent'  => 0,
			)
		);

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_course_id', $course_id );
		}
	}

	/**
	 * Migrate a single MasterStudy enrollment record to a Masteriyo user_course item.
	 *
	 * Operates on one stm_lms_user_courses row by its user_course_id primary key.
	 * Idempotent: skips silently if the user is already enrolled in Masteriyo.
	 *
	 * @since x.x.x
	 * @param int $user_course_id Primary key of the stm_lms_user_courses row.
	 * @throws \Exception If the row does not exist, is not enrolled, or the DB insert fails.
	 */
	public static function migrate_single_enrollment( int $user_course_id ): void {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, course_id, status, start_time, end_time
				 FROM {$wpdb->prefix}stm_lms_user_courses
				 WHERE user_course_id = %d",
				$user_course_id
			)
		);

		if ( ! $row ) {
			throw new \Exception(
				sprintf( 'migrate_single_enrollment: stm_lms_user_courses row %d not found.', $user_course_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$user_id   = (int) $row->user_id;
		$course_id = (int) $row->course_id;

		if ( masteriyo_is_user_already_enrolled( $user_id, $course_id ) ) {
			$wpdb->delete(
				$wpdb->prefix . 'stm_lms_user_courses',
				array( 'user_course_id' => $user_course_id ),
				array( '%d' )
			);
			return;
		}

		$status_map = array(
			'enrolled'    => UserCourseStatus::ACTIVE,
			'in-progress' => UserCourseStatus::ACTIVE,
			'completed'   => UserCourseStatus::ACTIVE,
		);
		$status     = $status_map[ $row->status ] ?? UserCourseStatus::ACTIVE;
		$date_end   = ( 'completed' === $row->status && $row->end_time )
			? gmdate( 'Y-m-d H:i:s', (int) $row->end_time )
			: '0000-00-00 00:00:00';

		$result = $wpdb->insert(
			$wpdb->prefix . 'masteriyo_user_items',
			array(
				'item_id'       => $course_id,
				'user_id'       => $user_id,
				'item_type'     => 'user_course',
				'date_start'    => gmdate( 'Y-m-d H:i:s', (int) $row->start_time ),
				'date_end'      => $date_end,
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

		$wpdb->delete(
			$wpdb->prefix . 'stm_lms_user_courses',
			array( 'user_course_id' => $user_course_id ),
			array( '%d' )
		);
	}

	/**
	 * Migrate a single stm_lms_user_lessons row to masteriyo_user_activities, then delete source.
	 *
	 * Writes a course_progress activity (if missing) and a lesson activity to
	 * masteriyo_user_activities, mapping progress=1 → 'completed' and progress=0 → 'started'.
	 * Deletes the source row after migrating so it self-removes from paginated queries.
	 *
	 * @since x.x.x
	 * @param int $user_lesson_id Primary key of the stm_lms_user_lessons row.
	 * @throws \Exception If the row does not exist.
	 */
	public static function migrate_single_lesson_progress( int $user_lesson_id ): void {
		global $wpdb;

		$now            = gmdate( 'Y-m-d H:i:s' );
		$activities_tbl = $wpdb->prefix . 'masteriyo_user_activities';

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, course_id, lesson_id, progress, start_time, end_time
				 FROM {$wpdb->prefix}stm_lms_user_lessons
				 WHERE user_lesson_id = %d",
				$user_lesson_id
			)
		);

		if ( ! $row ) {
			throw new \Exception(
				sprintf( 'migrate_single_lesson_progress: stm_lms_user_lessons row %d not found.', $user_lesson_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$user_id   = (int) $row->user_id;
		$course_id = (int) $row->course_id;
		$lesson_id = (int) $row->lesson_id;
		$completed = 1 === (int) $row->progress;
		$date      = $row->start_time ? gmdate( 'Y-m-d H:i:s', (int) $row->start_time ) : $now;

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
			// Delete source row so the job engine doesn't retry indefinitely.
			$wpdb->delete( $wpdb->prefix . 'stm_lms_user_lessons', array( 'user_lesson_id' => $user_lesson_id ), array( '%d' ) );
			return;
		}

		// Idempotency: skip inserting the lesson activity if it already exists.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$activities_tbl}
				 WHERE user_id = %d AND item_id = %d AND activity_type = %s AND parent_id = %d",
				$user_id,
				$lesson_id,
				CourseProgressItemType::LESSON,
				$progress_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $existing ) {
			$activity_status = $completed ? CourseProgressStatus::COMPLETED : CourseProgressStatus::STARTED;
			$completed_at    = $completed
				? ( $row->end_time ? gmdate( 'Y-m-d H:i:s', (int) $row->end_time ) : $now )
				: '0000-00-00 00:00:00';

			$wpdb->insert(
				$activities_tbl,
				array(
					'user_id'         => $user_id,
					'item_id'         => $lesson_id,
					'activity_type'   => CourseProgressItemType::LESSON,
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
		$wpdb->delete( $wpdb->prefix . 'stm_lms_user_lessons', array( 'user_lesson_id' => $user_lesson_id ), array( '%d' ) );
	}

	/**
	 * Migrate a single MasterStudy order to a Masteriyo mto-order.
	 *
	 * Thin public wrapper around the private migrate_order(). Idempotent: returns early
	 * if the post_type is already mto-order.
	 *
	 * @since x.x.x
	 * @param int $order_id MasterStudy stm-orders post ID.
	 * @throws \Exception If the post does not exist or is not an stm-orders post.
	 */
	public static function migrate_single_order( int $order_id ): void {
		$post = get_post( $order_id );

		if ( ! $post ) {
			throw new \Exception(
				sprintf( 'migrate_single_order: post %d not found.', $order_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		// Idempotency: already migrated if post_type is now mto-order.
		if ( PostType::ORDER === $post->post_type ) {
			return;
		}

		if ( 'stm-orders' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_order: post %d is not an stm-orders post (got %s).', $order_id, $post->post_type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		static::migrate_order( $order_id );
	}

	/**
	 * Migrate a single MasterStudy review post to a Masteriyo course_review comment.
	 *
	 * Fixes the variable-reuse bug in the private migrate_review() where $review_id was
	 * overwritten with the new comment ID before wp_trash_post() was called, causing the
	 * wrong post to be trashed. This method uses a separate $new_comment_id variable and
	 * calls wp_delete_post() on the original source post instead.
	 *
	 * Idempotent: throws if the source post is already gone (not 'stm-reviews' post_type).
	 *
	 * @since x.x.x
	 * @param int $review_id MasterStudy stm-reviews post ID.
	 * @throws \Exception If the post does not exist or is not an stm-reviews post.
	 */
	public static function migrate_single_review( int $review_id ): void {
		$ms_review = get_post( $review_id );

		if ( ! $ms_review || 'stm-reviews' !== $ms_review->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_review: stm-reviews post %d not found.', $review_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$course_id            = get_post_meta( $review_id, 'review_course', true );
		$comment_karma        = get_post_meta( $review_id, 'review_mark', true );
		$user_id              = get_post_meta( $review_id, 'review_user', true );
		$comment_author       = '';
		$comment_author_email = '';
		$comment_author_url   = '';

		$user = get_user_by( 'id', $user_id );

		if ( $user ) {
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;
			$comment_author_url   = $user->user_url;
		}

		$comment_status = 'publish' === $ms_review->post_status ? CommentStatus::APPROVE : CommentStatus::HOLD;

		$review_data = array(
			'comment_post_ID'      => $course_id,
			'comment_author'       => $comment_author,
			'comment_author_email' => $comment_author_email,
			'comment_author_url'   => $comment_author_url,
			'comment_date'         => $ms_review->post_date,
			'comment_date_gmt'     => $ms_review->post_date_gmt,
			'comment_content'      => wp_kses_post( $ms_review->post_content ),
			'comment_karma'        => $comment_karma,
			'comment_parent'       => $ms_review->post_parent,
			'user_id'              => $user_id,
			'comment_approved'     => $comment_status,
			'comment_type'         => CommentType::COURSE_REVIEW,
			'comment_agent'        => 'Masteriyo',
		);

		$new_comment_id = wp_insert_comment( $review_data );

		if ( $new_comment_id ) {
			update_comment_meta( $new_comment_id, '_rating', intval( $comment_karma ) );
			update_comment_meta( $new_comment_id, '_title', sanitize_text_field( $ms_review->post_title ) );
		}

		// Delete the original source post so it no longer appears in source queries.
		wp_delete_post( $review_id, true );
	}

	/**
	 * Migrate a single MasterStudy quiz attempt to masteriyo_quiz_attempts.
	 *
	 * Reads one stm_lms_user_quizzes row and its related stm_lms_user_answers rows,
	 * inserts a row into masteriyo_quiz_attempts, then deletes the source rows so the
	 * step is self-cleaning (LIMIT-only pagination, no OFFSET needed).
	 *
	 * @since x.x.x
	 * @param int $user_quiz_id stm_lms_user_quizzes.user_quiz_id primary key.
	 * @throws \Exception If the row does not exist or the DB insert fails.
	 */
	public static function migrate_single_quiz_attempt( int $user_quiz_id ): void {
		global $wpdb;

		$now          = gmdate( 'Y-m-d H:i:s' );
		$attempts_tbl = $wpdb->prefix . 'masteriyo_quiz_attempts';

		$attempt = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, course_id, quiz_id, progress, status, created_at
				 FROM {$wpdb->prefix}stm_lms_user_quizzes
				 WHERE user_quiz_id = %d",
				$user_quiz_id
			)
		);

		if ( ! $attempt ) {
			throw new \Exception(
				sprintf( 'migrate_single_quiz_attempt: stm_lms_user_quizzes row %d not found.', $user_quiz_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$user_id   = (int) $attempt->user_id;
		$quiz_id   = (int) $attempt->quiz_id;
		$course_id = (int) $attempt->course_id;

		$started_at = $attempt->created_at ? $attempt->created_at : $now;

		// Idempotency — skip if this exact attempt was already migrated.
		$already_exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$attempts_tbl} WHERE quiz_id = %d AND user_id = %d AND attempt_started_at = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$quiz_id,
				$user_id,
				$started_at
			)
		);

		if ( $already_exists ) {
			$wpdb->delete( $wpdb->prefix . 'stm_lms_user_quizzes', array( 'user_quiz_id' => $user_quiz_id ), array( '%d' ) );
			return;
		}

		// Fetch per-question answers for this user+quiz combination.
		$raw_answers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT question_id, user_answer, correct_answer
				 FROM {$wpdb->prefix}stm_lms_user_answers
				 WHERE user_id = %d AND quiz_id = %d",
				$user_id,
				$quiz_id
			)
		);

		$total_questions          = count( $raw_answers );
		$total_answered_questions = 0;
		$total_correct            = 0;
		$answers_php              = array();

		foreach ( $raw_answers as $raw ) {
			if ( '' !== (string) $raw->user_answer ) {
				++$total_answered_questions;
			}
			if ( '1' === (string) $raw->correct_answer ) {
				++$total_correct;
			}
			$answers_php[] = array(
				'id'           => (int) $raw->question_id,
				'given_answer' => $raw->user_answer,
				'is_correct'   => '1' === (string) $raw->correct_answer,
			);
		}

		$total_incorrect = $total_questions - $total_correct;
		$total_marks     = (float) get_post_meta( $quiz_id, '_pass_mark', true );
		$progress        = absint( $attempt->progress );
		$earned_marks    = $total_marks > 0 ? round( ( $progress / 100 ) * $total_marks, 2 ) : 0.0;

		$status_map     = array(
			'passed' => 'passed',
			'failed' => 'failed',
		);
		$attempt_status = $status_map[ $attempt->status ] ?? 'attempt';

		$total_attempts_seq = 1 + (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$attempts_tbl} WHERE quiz_id = %d AND user_id = %d",
				$quiz_id,
				$user_id
			)
		);

		$inserted = $wpdb->insert(
			$attempts_tbl,
			array(
				'course_id'                => $course_id,
				'quiz_id'                  => $quiz_id,
				'user_id'                  => $user_id,
				'total_questions'          => $total_questions,
				'total_answered_questions' => $total_answered_questions,
				'total_marks'              => $total_marks,
				'total_attempts'           => $total_attempts_seq,
				'total_correct_answers'    => $total_correct,
				'total_incorrect_answers'  => $total_incorrect,
				'earned_marks'             => $earned_marks,
				'answers'                  => maybe_serialize( $answers_php ),
				'attempt_status'           => $attempt_status,
				'attempt_started_at'       => $started_at,
				'attempt_ended_at'         => $started_at,
			),
			array( '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception(
				sprintf(
					'migrate_single_quiz_attempt: DB insert failed for user %d quiz %d: %s',
					$user_id,
					$quiz_id,
					$wpdb->last_error
				)
			);
			// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		// Delete source rows — makes this step self-cleaning for LIMIT-only pagination.
		$wpdb->delete( $wpdb->prefix . 'stm_lms_user_quizzes', array( 'user_quiz_id' => $user_quiz_id ), array( '%d' ) );
		$wpdb->delete(
			$wpdb->prefix . 'stm_lms_user_answers',
			array(
				'user_id' => $user_id,
				'quiz_id' => $quiz_id,
			),
			array( '%d', '%d' )
		);
	}

	/**
	 * Register the WishList service provider in the DI container on-demand.
	 *
	 * Cannot use AbstractLMSMigrator::ensure_service_provider() here because MasterStudy
	 * is a static helper class, not a subclass of AbstractLMSMigrator. Inlines the same
	 * logic so the container key is available before migrate_single_wishlist() is called.
	 *
	 * @since x.x.x
	 * @throws \Exception If the WishList addon is not installed.
	 */
	private static function register_wishlist_service_provider(): void {
		global $masteriyo;

		$service_key    = 'wishlist-item';
		$provider_class = \Masteriyo\Addons\WishList\Providers\WishListServiceProvider::class;

		if ( $masteriyo->has( $service_key ) ) {
			return;
		}

		if ( ! class_exists( $provider_class ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( 'Addon class WishListServiceProvider not found — install the WishList addon to migrate wishlist data.' );
		}

		$masteriyo->addServiceProvider( new $provider_class() );
	}

	/**
	 * Migrate all MasterStudy wishlist entries for a single user to Masteriyo wishlist items.
	 *
	 * Reads the stm_lms_wishlist user meta (serialized array of course IDs) and creates
	 * an mto-wishlist-item post for each entry not yet migrated. Deletes the user meta
	 * afterwards so the step self-cleans (LIMIT-only pagination, no OFFSET needed).
	 *
	 * @since x.x.x
	 * @param int $user_id WordPress user ID.
	 */
	public static function migrate_single_wishlist( int $user_id ): void {
		global $wpdb;

		$wishlist = get_user_meta( $user_id, 'stm_lms_wishlist', true );

		if ( empty( $wishlist ) || ! is_array( $wishlist ) ) {
			delete_user_meta( $user_id, 'stm_lms_wishlist' );
			return;
		}

		// Build a set of already-migrated course IDs for this user (idempotency).
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

		foreach ( array_map( 'intval', $wishlist ) as $course_id ) {
			if ( ! $course_id || in_array( $course_id, $already_migrated, true ) ) {
				continue;
			}

			$item = masteriyo( 'wishlist-item' );
			$item->set_course_id( $course_id );
			$item->set_author_id( $user_id );

			$mto_course = masteriyo_get_course( $course_id );
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
		}

		// Remove the source meta — makes this step self-cleaning.
		delete_user_meta( $user_id, 'stm_lms_wishlist' );
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
			     WHERE  p.post_type = 'mto-lesson'
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
