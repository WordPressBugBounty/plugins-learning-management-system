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
use Masteriyo\Addons\MigrationTool\MigrationMap;
use Masteriyo\Addons\MigrationTool\PostCloner;
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
 * MasterStudy's records are read, never changed: every post is cloned and the clone
 * converted, every table row is copied into Masteriyo's own tables, and the migration
 * map remembers what has been copied so a second run picks up only what is new.
 *
 * @since 1.16.0
 */
class MasterStudy {

	/**
	 * Migrator slug, the key every migration map row is written under.
	 */
	const SLUG = 'masterstudy';

	/**
	 * Register an addon service provider in the DI container on-demand.
	 *
	 * Cannot use AbstractLMSMigrator::ensure_service_provider() here because MasterStudy
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
	 * Copy a MasterStudy course's curriculum onto the copied course.
	 *
	 * Sections are MasterStudy table rows, so each becomes a new section post. Lessons,
	 * quizzes, assignments and questions are posts, so each is cloned under its copied
	 * section and the clone converted; the source posts are not touched.
	 *
	 * @since 1.16.0
	 *
	 * @param int $source_course_id MasterStudy course ID.
	 * @param int $course_id        Masteriyo course ID (the copy).
	 */
	private static function migrate_course( $source_course_id, $course_id ) {
		$curriculum_repo = new CurriculumRepository();
		$sections        = $curriculum_repo->get_curriculum( $source_course_id, true );

		if ( empty( $sections ) ) {
			return;
		}

		$author = get_post_field( 'post_author', $source_course_id );

		foreach ( $sections as $section_data ) {
			$source_section_id = absint( $section_data['id'] ?? 0 );
			$section_title     = $section_data['title'];

			$section_id = $source_section_id ? MigrationMap::target( self::SLUG, 'sections', $source_section_id ) : 0;

			if ( ! $section_id || ! get_post( $section_id ) ) {
				$section_id = wp_insert_post(
					array(
						'post_type'   => PostType::SECTION,
						'post_title'  => $section_title ? $section_title : __( 'Section', 'learning-management-system' ),
						'post_status' => PostStatus::PUBLISH,
						'post_author' => $author,
						'post_parent' => $course_id,
						'menu_order'  => $section_data['order'],
					)
				);

				if ( is_wp_error( $section_id ) || ! $section_id ) {
					masteriyo_get_logger()->error( 'Migration: Failed to insert MasterStudy section post.', array( 'source' => 'migration-tool' ) );
					continue;
				}

				if ( $source_section_id ) {
					MigrationMap::record( self::SLUG, 'sections', $source_section_id, (int) $section_id );
				}
			} else {
				// A section kept from a deleted course copy follows the course's new copy.
				PostCloner::place(
					(int) $section_id,
					array(
						'post_parent' => $course_id,
						'menu_order'  => (int) $section_data['order'],
					)
				);
			}
			update_post_meta( $section_id, '_course_id', $course_id );

			if ( empty( $section_data['materials'] ) ) {
				continue;
			}

			foreach ( $section_data['materials'] as $item ) {
				$source_item_id = absint( $item['post_id'] );
				$item_post_type = PostType::LESSON;

				if ( 'stm-quizzes' === $item['post_type'] ) {
					$item_post_type = PostType::QUIZ;
				} elseif ( 'stm-assignments' === $item['post_type'] ) {
					$item_post_type = PostType::ASSIGNMENT;
				}

				if ( ! $source_item_id || ! get_post( $source_item_id ) ) {
					continue;
				}

				// An item reused in several curricula has one copy, kept by the first course.
				if ( Helper::copy_kept_by_another_course( self::SLUG, 'MasterStudy', 'post', $source_item_id, $course_id ) ) {
					continue;
				}

				$item_id = PostCloner::clone_post(
					self::SLUG,
					$source_item_id,
					array(
						'post_type'   => $item_post_type,
						'post_parent' => $section_id,
						'menu_order'  => (int) $item['order'],
					)
				);

				update_post_meta( $item_id, '_course_id', $course_id );

				if ( PostType::QUIZ === $item_post_type ) {
					self::migrate_quiz( $source_item_id, $item_id, $course_id );
				} elseif ( PostType::LESSON === $item_post_type ) {
					self::migrate_lesson( $source_item_id, $item_id );
				} elseif ( PostType::ASSIGNMENT === $item_post_type ) {
					self::migrate_assignment_meta( $source_item_id, $item_id, $section_id );
				}
			}
		}
	}

	/**
	 * Convert a copied quiz: clone its questions under it and write the Masteriyo meta.
	 *
	 * @param int $source_quiz_id MasterStudy quiz ID.
	 * @param int $quiz_id        Masteriyo quiz ID (the copy).
	 * @param int $course_id      Masteriyo course ID (the copy).
	 */
	private static function migrate_quiz( $source_quiz_id, $quiz_id, $course_id ) {
		$question_ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $source_quiz_id, 'questions', true ) ) ) );

		$k = 0;
		foreach ( $question_ids as $question_id ) {
			self::process_question_migration( $question_id, $quiz_id, $course_id, $k );
			++$k;
		}

		$duration         = absint( get_post_meta( $source_quiz_id, 'duration', true ) );
		$duration_measure = get_post_meta( $source_quiz_id, 'duration_measure', true );

		if ( 'hours' === $duration_measure ) {
			$duration *= 60;
		} elseif ( 'days' === $duration_measure ) {
			$duration *= 60 * 24;
		}

		update_post_meta( $quiz_id, '_duration', $duration );
		update_post_meta( $quiz_id, '_pass_mark', get_post_meta( $source_quiz_id, 'passing_grade', true ) );
	}

	/**
	 * Convert a copied lesson: video, poster, materials and preview go on the copy.
	 *
	 * @param int $source_lesson_id MasterStudy lesson ID.
	 * @param int $lesson_id        Masteriyo lesson ID (the copy).
	 */
	private static function migrate_lesson( $source_lesson_id, $lesson_id ) {
		$source       = get_post_meta( $source_lesson_id, 'video_type', true );
		$video_poster = get_post_meta( $source_lesson_id, 'lesson_video_poster', true );
		$files        = get_post_meta( $source_lesson_id, 'lesson_files', true );
		$files        = is_string( $files ) ? json_decode( $files, true ) : $files;
		$url          = '';

		switch ( $source ) {
			case 'embed':
				$source = 'embed-video';
				$url    = htmlspecialchars_decode( get_post_meta( $source_lesson_id, 'lesson_embed_ctx', true ) );
				break;
			case 'youtube':
				$url = get_post_meta( $source_lesson_id, 'lesson_youtube_url', true );
				break;
			case 'vimeo':
				$url = get_post_meta( $source_lesson_id, 'lesson_vimeo_url', true );
				break;
			case 'external':
				$url = get_post_meta( $source_lesson_id, 'lesson_ext_link_url', true );
				break;
			case 'html':
				$video_id = absint( get_post_meta( $source_lesson_id, 'lesson_video', true ) );
				// Lesson model reads _video_source_url as absint() for self-hosted, so store the
				// attachment ID as a string — not the resolved URL.
				$url    = (string) $video_id;
				$source = 'self-hosted';
				break;
		}

		update_post_meta( $lesson_id, '_video_source', $source );
		update_post_meta( $lesson_id, '_video_source_url', $url );

		if ( $video_poster ) {
			update_post_meta( $lesson_id, '_thumbnail_id', $video_poster );
		}

		if ( ! empty( $files ) ) {
			update_post_meta( $lesson_id, '_download_materials', maybe_serialize( $files ) );
		}

		if ( get_post_meta( $source_lesson_id, 'preview', true ) ) {
			update_post_meta( $lesson_id, '_enable_preview', true );
		}
	}

	/**
	 * Write the Masteriyo assignment meta on a copied assignment.
	 *
	 * The pro 'assignments' step finds this copy through the map, so it is not copied twice.
	 *
	 * @param int $source_assignment_id MasterStudy assignment ID.
	 * @param int $assignment_id        Masteriyo assignment ID (the copy).
	 * @param int $section_id           Masteriyo section ID (the copy).
	 */
	private static function migrate_assignment_meta( $source_assignment_id, $assignment_id, $section_id ) {
		update_post_meta( $assignment_id, '_parent_id', $section_id );

		$max_points = get_post_meta( $source_assignment_id, 'assignment_tries', true );
		if ( $max_points ) {
			update_post_meta( $assignment_id, '_total_points', (int) $max_points );
		}

		if ( get_post_meta( $source_assignment_id, 'assignment_time_limit', true ) ) {
			masteriyo_get_logger()->info(
				sprintf( 'Migration: Assignment %d time limit cannot be converted to absolute date — skipped.', $source_assignment_id ),
				array( 'source' => 'migration-tool' )
			);
		}
	}

	/**
	 * Migrate course info from MasterStudy.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id Masteriyo course ID (the copy, carrying MasterStudy's meta).
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
	 * @param int $user_id   User ID.
	 * @param int $course_id Masteriyo course ID (the copy).
	 * @param int $order_id  Masteriyo order ID (the copy).
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
	 * Copy a MasterStudy quiz question under the copied quiz.
	 *
	 * The clone gets the Masteriyo answers JSON as its content; the source question
	 * keeps MasterStudy's. An unsupported type is left out, not copied.
	 *
	 * @since 1.16.0
	 *
	 * @param int $question_id MasterStudy question ID.
	 * @param int $quiz_id     Masteriyo quiz ID (the copy).
	 * @param int $course_id   Masteriyo course ID (the copy).
	 * @param int $menu_order  Position within the quiz.
	 *
	 * @return void
	 */
	private static function process_question_migration( $question_id, $quiz_id, $course_id, $menu_order = 0 ) {
		if ( ! get_post( $question_id ) ) {
			return;
		}

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

		$overrides = array(
			'post_type'    => PostType::QUESTION,
			'post_content' => wp_json_encode( $formatted_answers ),
			'post_parent'  => $quiz_id,
			'menu_order'   => $menu_order,
		);

		// Normalize fill_the_gap blank markers in the question title to {{blank}}.
		if ( 'fill_the_gap' === $ms_type ) {
			$overrides['post_title'] = preg_replace( '/\[[^\]]*\]/', '{{blank}}', get_the_title( $question_id ) );
		}

		// A library question used by several quizzes has one copy, kept by the first course.
		if ( Helper::copy_kept_by_another_course( self::SLUG, 'MasterStudy', 'post', $question_id, $course_id ) ) {
			return;
		}

		$copy_id = PostCloner::clone_post( self::SLUG, $question_id, $overrides );

		update_post_meta( $copy_id, '_course_id', $course_id );
		update_post_meta( $copy_id, '_type', $question_type );
		update_post_meta( $copy_id, '_parent_id', $quiz_id );
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
				return QuestionType::FILL_IN_THE_BLANKS;
			case 'sortable':
				return QuestionType::SORTABLE;
			case 'item_match':
			case 'image_match':
				return QuestionType::MATCHING;
			case 'keywords':
				return QuestionType::TEXT_ANSWER;
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
	 * @param array $answers The serialized answers from MasterStudy.
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
	 * Copy a MasterStudy order to a new Masteriyo order.
	 *
	 * The order's courses are resolved to their copies before anything is written, so
	 * an order for an uncopied course fails whole and is retried once the course exists.
	 *
	 * @since 1.16.0
	 *
	 * @param int $source_order_id MasterStudy order ID.
	 * @throws \Exception If a course on the order has not been copied.
	 */
	private static function migrate_order( $source_order_id ) {
		$status     = get_post_meta( $source_order_id, 'status', true );
		$date       = get_post_meta( $source_order_id, 'date', true );
		$order_date = gmdate( 'Y-m-d H:i:s', absint( $date ) );
		$title      = 'Order - ' . $order_date;

		$order_status_map = array(
			'pending'   => OrderStatus::PENDING,
			'completed' => OrderStatus::COMPLETED,
			'cancelled' => OrderStatus::CANCELLED,
		);
		$post_status      = $order_status_map[ $status ] ?? OrderStatus::PENDING;

		// Source course ID => copied course ID, for every item on the order.
		$items   = maybe_unserialize( get_post_meta( $source_order_id, 'items', true ) );
		$courses = array();
		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$source_course_id = absint( $item['item_id'] ?? 0 );
			if ( $source_course_id ) {
				$courses[ $source_course_id ] = MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_course_id, 'course' );
			}
		}

		$order_id = PostCloner::clone_post(
			self::SLUG,
			$source_order_id,
			array(
				'post_type'     => PostType::ORDER,
				'post_title'    => $title,
				'post_status'   => $post_status,
				'post_password' => masteriyo_generate_order_key(),
			)
		);

		self::update_order_items( $order_id, $source_order_id, $courses );
		self::update_order_meta( $order_id );
	}

	/**
	 * Write the order items on the copied order.
	 *
	 * @since 1.16.0
	 *
	 * @param int             $order_id        Masteriyo order ID (the copy).
	 * @param int             $source_order_id MasterStudy order ID.
	 * @param array<int, int> $courses         Source course ID => copied course ID.
	 */
	private static function update_order_items( $order_id, $source_order_id, array $courses ) {
		global $wpdb;

		foreach ( $courses as $source_course_id => $course_id ) {
			$item_data = array(
				'order_item_name' => get_post_field( 'post_title', $course_id ),
				'order_item_type' => 'course',
				'order_id'        => $order_id,
			);

			$wpdb->insert( $wpdb->prefix . 'masteriyo_order_items', $item_data );
			$order_item_id = absint( $wpdb->insert_id );

			if ( ! $order_item_id ) {
				throw new \Exception( sprintf( 'Could not write the order item for course %d on order %d: %s', $course_id, $order_id, $wpdb->last_error ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}

			self::update_order_items_meta( $order_item_id, $order_id, $course_id, $source_order_id, $source_course_id );
		}
	}

	/**
	 * Updates order item meta for a given order item and order.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_item_id    Order item ID.
	 * @param int $order_id         Masteriyo order ID (the copy).
	 * @param int $course_id        Masteriyo course ID (the copy).
	 * @param int $source_order_id  MasterStudy order ID, which its order items table is keyed by.
	 * @param int $source_course_id MasterStudy course ID.
	 */
	private static function update_order_items_meta( $order_item_id, $order_id, $course_id, $source_order_id, $source_course_id ) {
		global $wpdb;

		$stm_order_items_table = $wpdb->prefix . 'stm_lms_order_items';
		$quantity              = array();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $stm_order_items_table ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$quantity = $wpdb->get_col( $wpdb->prepare( "SELECT quantity FROM {$stm_order_items_table} WHERE order_id = %d AND object_id = %d", $source_order_id, $source_course_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
	 * @param int $order_id Masteriyo order ID (the copy, carrying MasterStudy's meta).
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
	 * Count the source items a step still has to copy. Fast COUNT queries, no records loaded.
	 *
	 * Rows already in the migration map are left out, so a second run counts only
	 * what is new. Wishlists are re-read every run: their source is per-user state
	 * that grows, and copying them is idempotent.
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
				// Instructors (stm_lms_instructor WP role) and every enrolled student.
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
							SELECT DISTINCT user_id FROM {$wpdb->prefix}stm_lms_user_courses
						) AS ms_users
						WHERE 1=1 {$not_user}",
						$capabilities_key,
						'%stm_lms_instructor%'
					)
				);

			case 'courses':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'stm-courses' {$not_post}" );

			case 'enrollments':
				$not_enrollment = MigrationMap::unmapped_sql( 'user_course_id', self::SLUG, 'enrollments' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}stm_lms_user_courses WHERE 1=1 {$not_enrollment}" );

			case 'lesson_progress':
				$not_lesson = MigrationMap::unmapped_sql( 'user_lesson_id', self::SLUG, 'lesson_progress' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}stm_lms_user_lessons WHERE 1=1 {$not_lesson}" );

			case 'orders':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'stm-orders' {$not_post}" );

			case 'reviews':
				$not_review = MigrationMap::unmapped_sql( 'ID', self::SLUG, 'reviews' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'stm-reviews' {$not_review}" );

			case 'quiz_attempts':
				$not_attempt = MigrationMap::unmapped_sql( 'user_quiz_id', self::SLUG, 'quiz_attempts' );
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}stm_lms_user_quizzes WHERE 1=1 {$not_attempt}" );

			case 'wishlists':
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = %s",
						'stm_lms_wishlist'
					)
				);
		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return 0;
	}

	/**
	 * Return one batch of source IDs starting after $cursor.
	 *
	 * Every step pages by its own ID column and leaves out rows already in the
	 * migration map. Nothing is removed from the source, so nothing is "self-cleaning";
	 * $exclude is unused because a cursor never revisits a failed ID within a run.
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
							SELECT DISTINCT user_id FROM {$wpdb->prefix}stm_lms_user_courses
						) AS ms_users
						WHERE user_id > %d
						{$not_user}
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
				return self::post_ids_after( 'stm-courses', $cursor, $limit, $not_post );

			case 'enrollments':
				return self::row_ids_after( 'stm_lms_user_courses', 'user_course_id', 'enrollments', $cursor, $limit );

			case 'lesson_progress':
				return self::row_ids_after( 'stm_lms_user_lessons', 'user_lesson_id', 'lesson_progress', $cursor, $limit );

			case 'orders':
				return self::post_ids_after( 'stm-orders', $cursor, $limit, $not_post );

			case 'reviews':
				return self::post_ids_after( 'stm-reviews', $cursor, $limit, MigrationMap::unmapped_sql( 'ID', self::SLUG, 'reviews' ) );

			case 'quiz_attempts':
				return self::row_ids_after( 'stm_lms_user_quizzes', 'user_quiz_id', 'quiz_attempts', $cursor, $limit );

			case 'wishlists':
				// Re-read every run: wishlists grow, and copying an entry twice is a no-op.
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT user_id FROM {$wpdb->usermeta}
						 WHERE meta_key = %s
						   AND user_id > %d
						 ORDER BY user_id ASC
						 LIMIT %d",
						'stm_lms_wishlist',
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
	 * One page of row IDs of a MasterStudy table, after the cursor, that are not in the map yet.
	 *
	 * @param string $table  Table name without the site prefix.
	 * @param string $column Primary key column.
	 * @param string $entity Map entity the step records under.
	 * @param int    $cursor Last processed ID.
	 * @param int    $limit  Batch size.
	 * @return int[]
	 */
	private static function row_ids_after( string $table, string $column, string $entity, int $cursor, int $limit ): array {
		global $wpdb;

		$not_row = MigrationMap::unmapped_sql( $column, self::SLUG, $entity );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT {$column} FROM {$wpdb->prefix}{$table}
				 WHERE {$column} > %d
				 {$not_row}
				 ORDER BY {$column} ASC
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
				static::register_service_provider(
					'wishlist-item',
					\Masteriyo\Addons\WishList\Providers\WishListServiceProvider::class
				);
				static::migrate_single_wishlist( $item_id );
				break;
		}
	}

	/**
	 * Give a MasterStudy instructor or enrolled student the matching Masteriyo role.
	 *
	 * MasterStudy's own roles and capabilities stay: MasterStudy keeps running until
	 * the site owner deactivates it.
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

		if ( ! $is_admin && ( in_array( 'stm_lms_instructor', $roles, true ) || isset( $caps['stm_lms_instructor'] ) ) ) {
			$user->add_role( Roles::INSTRUCTOR );
			$roles = (array) $user->roles;
			$caps  = is_array( $user->caps ) ? $user->caps : array();
		}

		// A student is any user enrolled in stm_lms_user_courses who is not an admin or instructor.
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

		MigrationMap::record( self::SLUG, MigrationMap::USER, $user_id, $user_id );
	}

	/**
	 * Copy a MasterStudy course with its curriculum into a new Masteriyo course.
	 *
	 * The stm-courses post is read, never changed: it is cloned and the clone converted.
	 * Enrollments are owned by the 'enrollments' step.
	 *
	 * @param int $source_course_id MasterStudy stm-courses post ID.
	 * @throws \Exception If the post does not exist or is not an stm-courses post.
	 */
	public static function migrate_single_course( int $source_course_id ): void {
		$post = get_post( $source_course_id );

		if ( ! $post ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d not found.', $source_course_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		if ( 'stm-courses' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_course: post %d is not an stm-courses post (got %s).', $source_course_id, $post->post_type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$course_id = PostCloner::clone_post( self::SLUG, $source_course_id, array( 'post_type' => PostType::COURSE ) );

		static::migrate_course( $source_course_id, $course_id );

		// The copy carries the source's term relationships.
		Helper::migrate_course_categories_from_to_masteriyo( $course_id, 'stm_lms_course_taxonomy' );

		static::migrate_course_info( $course_id );
		static::migrate_course_announcement( $source_course_id, $course_id );

		Helper::migrate_course_author( $course_id );
	}

	/**
	 * Copy the course announcement meta field to a new mto-announcement post.
	 *
	 * Recorded under 'announcements' by the source course ID, since the announcement
	 * has no post of its own in MasterStudy.
	 *
	 * @param int $source_course_id MasterStudy course ID.
	 * @param int $course_id        Masteriyo course ID (the copy).
	 */
	private static function migrate_course_announcement( int $source_course_id, int $course_id ): void {
		$announcement_text = get_post_meta( $source_course_id, 'announcement', true );
		if ( empty( $announcement_text ) ) {
			return;
		}

		$existing = MigrationMap::target( self::SLUG, 'announcements', $source_course_id );
		if ( $existing && get_post( $existing ) ) {
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
			MigrationMap::record( self::SLUG, 'announcements', $source_course_id, (int) $post_id );
		}
	}

	/**
	 * Copy a single MasterStudy enrollment row to a Masteriyo user_course item.
	 *
	 * The stm_lms_user_courses row stays. A user already enrolled in the copied course
	 * is recorded as a skip so the row is not fetched again.
	 *
	 * @param int $user_course_id Primary key of the stm_lms_user_courses row.
	 * @throws \Exception If the row does not exist, its course has not been copied, or the insert fails.
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
		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $row->course_id, 'course' );

		if ( masteriyo_is_user_already_enrolled( $user_id, $course_id ) ) {
			MigrationMap::record( self::SLUG, 'enrollments', $user_course_id, 0 );
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

		MigrationMap::record( self::SLUG, 'enrollments', $user_course_id, (int) $wpdb->insert_id );
	}

	/**
	 * Copy a single stm_lms_user_lessons row to masteriyo_user_activities.
	 *
	 * Writes a course_progress activity (if missing) and a lesson activity against the
	 * copied course and lesson, mapping progress=1 → 'completed' and progress=0 →
	 * 'started'. The source row stays.
	 *
	 * @param int $user_lesson_id Primary key of the stm_lms_user_lessons row.
	 * @throws \Exception If the row does not exist, its course has not been copied, or the progress row cannot be made.
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
		$course_id = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $row->course_id, 'course' );
		$lesson_id = MigrationMap::target( self::SLUG, MigrationMap::POST, (int) $row->lesson_id );
		$completed = 1 === (int) $row->progress;
		$date      = $row->start_time ? gmdate( 'Y-m-d H:i:s', (int) $row->start_time ) : $now;

		// A lesson outside the curriculum was never copied; there is nothing to record progress on.
		if ( ! $lesson_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Lesson progress %d skipped: lesson %d has no copy.', $user_lesson_id, (int) $row->lesson_id ),
				array( 'source' => 'migration-tool' )
			);
			MigrationMap::record( self::SLUG, 'lesson_progress', $user_lesson_id, 0 );
			return;
		}

		$progress_id = Helper::get_or_create_course_progress( $user_id, $course_id, $date );

		if ( ! $progress_id ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'migrate_single_lesson_progress: could not create the course_progress activity for user %d course %d.', $user_id, $course_id ) );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$activities_tbl}
				 WHERE user_id = %d AND item_id = %d AND activity_type = %s AND parent_id = %d
				 LIMIT 1",
				$user_id,
				$lesson_id,
				CourseProgressItemType::LESSON,
				$progress_id
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $existing ) {
			MigrationMap::record( self::SLUG, 'lesson_progress', $user_lesson_id, 0 );
			return;
		}

		$activity_status = $completed ? CourseProgressStatus::COMPLETED : CourseProgressStatus::STARTED;
		$completed_at    = $completed
			? ( $row->end_time ? gmdate( 'Y-m-d H:i:s', (int) $row->end_time ) : $now )
			: '0000-00-00 00:00:00';

		$inserted = $wpdb->insert(
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

		if ( false === $inserted ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'migrate_single_lesson_progress: DB insert failed for row %d: %s', $user_lesson_id, $wpdb->last_error ) );
		}

		MigrationMap::record( self::SLUG, 'lesson_progress', $user_lesson_id, (int) $wpdb->insert_id );
	}

	/**
	 * Copy a single MasterStudy order to a new Masteriyo mto-order.
	 *
	 * The stm-orders post stays.
	 *
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

		if ( 'stm-orders' !== $post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_order: post %d is not an stm-orders post (got %s).', $order_id, $post->post_type ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		static::migrate_order( $order_id );
	}

	/**
	 * Copy a single MasterStudy review post to a new Masteriyo course_review comment.
	 *
	 * The stm-reviews post stays; the new comment is recorded against it.
	 *
	 * @param int $review_id MasterStudy stm-reviews post ID.
	 * @throws \Exception If the post does not exist, is not an stm-reviews post, its course has not been copied, or the insert fails.
	 */
	public static function migrate_single_review( int $review_id ): void {
		$ms_review = get_post( $review_id );

		if ( ! $ms_review || 'stm-reviews' !== $ms_review->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_review: stm-reviews post %d not found.', $review_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$existing = MigrationMap::target( self::SLUG, 'reviews', $review_id );
		if ( $existing && get_comment( $existing ) ) {
			return;
		}

		$course_id            = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) get_post_meta( $review_id, 'review_course', true ), 'course' );
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

		if ( ! $new_comment_id ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'migrate_single_review: could not insert the review comment for post %d.', $review_id ) );
		}

		update_comment_meta( $new_comment_id, '_rating', intval( $comment_karma ) );
		update_comment_meta( $new_comment_id, '_title', sanitize_text_field( $ms_review->post_title ) );

		// Not the post entity: the target is a comment, not a post.
		MigrationMap::record( self::SLUG, 'reviews', $review_id, (int) $new_comment_id );
	}

	/**
	 * Copy a single MasterStudy quiz attempt to a Masteriyo quiz attempt row.
	 *
	 * Reads the stm_lms_user_quizzes row and the user's stm_lms_user_answers for the
	 * quiz, and inserts a masteriyo_quiz_attempts row against the copied quiz and
	 * course. Both source tables stay, so a later attempt by the same user still finds
	 * its answers.
	 *
	 * @param int $user_quiz_id stm_lms_user_quizzes.user_quiz_id primary key.
	 * @throws \Exception If the row does not exist, its course or quiz has not been copied, or the insert fails.
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

		$user_id        = (int) $attempt->user_id;
		$source_quiz_id = (int) $attempt->quiz_id;
		$course_id      = MigrationMap::require_target( self::SLUG, MigrationMap::POST, (int) $attempt->course_id, 'course' );
		$quiz_id        = MigrationMap::require_target( self::SLUG, MigrationMap::POST, $source_quiz_id, 'quiz' );

		$started_at = $attempt->created_at ? $attempt->created_at : $now;

		$already_exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$attempts_tbl} WHERE quiz_id = %d AND user_id = %d AND attempt_started_at = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$quiz_id,
				$user_id,
				$started_at
			)
		);

		if ( $already_exists ) {
			MigrationMap::record( self::SLUG, 'quiz_attempts', $user_quiz_id, 0 );
			return;
		}

		// MasterStudy keys answers by user and quiz, not by attempt.
		$raw_answers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT question_id, user_answer, correct_answer
				 FROM {$wpdb->prefix}stm_lms_user_answers
				 WHERE user_id = %d AND quiz_id = %d",
				$user_id,
				$source_quiz_id
			)
		);

		// Answers name MasterStudy's question IDs; the attempt needs the copies'.
		$copied_questions = MigrationMap::targets( self::SLUG, MigrationMap::POST, wp_list_pluck( $raw_answers, 'question_id' ) );

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
				'id'           => $copied_questions[ (int) $raw->question_id ] ?? 0,
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

		MigrationMap::record( self::SLUG, 'quiz_attempts', $user_quiz_id, (int) $wpdb->insert_id );
	}

	/**
	 * Copy all MasterStudy wishlist entries for a single user to Masteriyo wishlist items.
	 *
	 * Reads the stm_lms_wishlist user meta (serialized array of course IDs) and creates
	 * an mto-wishlist-item post for each wishlisted course whose copy exists and has no
	 * item yet. The user meta stays.
	 *
	 * @param int $user_id WordPress user ID.
	 */
	public static function migrate_single_wishlist( int $user_id ): void {
		global $wpdb;

		$wishlist = get_user_meta( $user_id, 'stm_lms_wishlist', true );

		if ( empty( $wishlist ) || ! is_array( $wishlist ) ) {
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
		// A course already completed keeps the date it was first completed on a rerun.
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
