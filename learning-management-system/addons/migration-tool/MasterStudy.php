<?php
/**
 * MasterStudy migrations.
 *
 * @since 1.16.0
 *
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Enums\CommentStatus;
use Masteriyo\Enums\CommentType;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CoursePriceType;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuestionType;
use Masteriyo\PostType\PostType;
use MasterStudy\Lms\Repositories\CurriculumRepository;

/**
 * Class MasterStudy.
 *
 * @since 1.16.0
 */
class MasterStudy {

	/**
	 * Migrate courses from MasterStudy.
	 *
	 * @since 1.16.0
	 *
	 * @return array
	 */
	public static function migrate_ms_courses() {
		$ms_courses = self::get_ms_courses();

		if ( ! $ms_courses || ! is_array( $ms_courses ) ) {
			return self::migrate_ms_orders();
		}

		$ms_course_id = reset( $ms_courses );

		self::migrate_course( absint( $ms_course_id ) );

		Helper::migrate_course_categories_from_to_masteriyo( $ms_course_id, 'stm_lms_course_taxonomy' );

		self::migrate_course_info( $ms_course_id );

		Helper::migrate_course_author( $ms_course_id );

		self::migrate_course_enrollment( $ms_course_id );

		$remaining_courses = array_slice( $ms_courses, 1 );

		update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_courses ) );

		$response = array(
			'message' => __( 'Course with ID: ', 'learning-management-system' ) . $ms_course_id . __( ' migrated successfully.', 'learning-management-system' ),
		);

		if ( 1 > count( $remaining_courses ) ) {
			$orders = self::get_ms_orders();

			if ( ! $orders ) {
				$response['remainingReviews'] = self::get_ms_reviews();
			} else {
				$response['remainingOrders'] = $orders;
			}
		} else {
			$response['remainingCourses'] = $remaining_courses;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Migrate orders from MasterStudy.
	 *
	 * @since 1.16.0
	 *
	 * @return array
	 */
	public static function migrate_ms_orders() {
		$ms_orders = self::get_ms_orders();

		if ( ! $ms_orders || ! is_array( $ms_orders ) ) {
			return self::migrate_ms_reviews();
		}

		$ms_order_id = reset( $ms_orders );

		self::migrate_order( absint( $ms_order_id ) );

		$remaining_orders = array_slice( $ms_orders, 1 );

		update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_orders ) );

		$response = array(
			'message' => __( 'Order with ID: ', 'learning-management-system' ) . $ms_order_id . __( ' migrated successfully.', 'learning-management-system' ),
		);

		if ( 1 > count( $remaining_orders ) ) {
			$response['remainingReviews'] = self::get_ms_reviews();
		} else {
			$response['remainingOrders'] = $remaining_orders;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Migrate reviews from MasterStudy.
	 *
	 * @since 1.16.0
	 *
	 * @return WP_REST_Response Returns WP_REST_Response indicating success or failure.
	 */
	public static function migrate_ms_reviews() {

		$ms_reviews = self::get_ms_reviews();

		if ( ! $ms_reviews || ! is_array( $ms_reviews ) ) {
			return rest_ensure_response( array( 'message' => __( 'No reviews found to migrate.', 'learning-management-system' ) ) );
		}

		$ms_review_id = reset( $ms_reviews );

		self::migrate_review( absint( $ms_review_id ) );

		$remaining_reviews = array_slice( $ms_reviews, 1 );

		update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_reviews ) );

		$response = array(
			'message' => __( 'Review with ID: ', 'learning-management-system' ) . $ms_review_id . __( ' migrated successfully.', 'learning-management-system' ),
		);

		if ( 1 > count( $remaining_reviews ) ) {
			Helper::delete_remaining_migrated_items();
			return rest_ensure_response( array( 'message' => __( 'All the MasterStudy data migrated successfully.', 'learning-management-system' ) ) );
		} else {
			$response['remainingReviews'] = $remaining_reviews;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Retrieves MasterStudy reviews.
	 *
	 * @return array|null Array of MasterStudy review IDs or null if not found.
	 */
	private static function get_ms_courses() {
		global $wpdb;

		$ms_courses = get_option( 'masteriyo_remaining_migrated_items', 'not_started' );

		if ( 'not_started' === $ms_courses ) {
			$ms_courses = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'stm-courses'" );
		} else {
			$ms_courses = is_string( $ms_courses ) ? json_decode( $ms_courses, true ) : $ms_courses;
		}

		return $ms_courses;
	}

	/**
	 * Retrieves MasterStudy orders.
	 *
	 * @since 1.16.0
	 *
	 * @return array|null Array of MasterStudy order IDs or null if not found.
	 */
	private static function get_ms_orders() {
		global $wpdb;
		$ms_orders = get_option( 'masteriyo_remaining_migrated_items' );

		if ( empty( json_decode( $ms_orders, true ) ) || 'not_started' === $ms_orders ) {

			$ms_orders = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'stm-orders' AND post_status = 'publish' " );
		} else {
			$ms_orders = is_string( $ms_orders ) ? json_decode( $ms_orders, true ) : $ms_orders;
		}

		return $ms_orders;
	}

	/**
	 * Retrieves MasterStudy reviews.
	 *
	 * @return array|null Array of MasterStudy review IDs or null if not found.
	 */

	private static function get_ms_reviews() {
		global $wpdb;

		$ms_reviews = get_option( 'masteriyo_remaining_migrated_items' );

		if ( empty( json_decode( $ms_reviews, true ) ) || 'not_started' === $ms_reviews ) {
			$ms_reviews = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'stm-reviews'" );
		} else {
			$ms_reviews = is_string( $ms_reviews ) ? json_decode( $ms_reviews, true ) : $ms_reviews;
		}

		return $ms_reviews;
	}

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
							$url      = wp_get_attachment_url( $video_id );
							$source   = 'self-hosted';
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
		self::set_course_difficulty_from_lp_to_masteriyo( $course_id, $level );

		$retake = get_post_meta( $course_id, 'retake', true );
		update_post_meta( $course_id, 'enable_course_retake', masteriyo_string_to_bool( $retake ) );

		$reviews_allowed = true;

		if ( class_exists( 'STM_LMS_Options' ) ) {
			$reviews_allowed = \STM_LMS_Options::get_option( 'course_tab_reviews', true );
		}

		update_post_meta( $course_id, '_reviews_allowed', masteriyo_string_to_bool( $reviews_allowed ) );
	}

	/**
	 * Sets or creates and sets the course difficulty level based on the specified level slug.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id The ID of the course for which the difficulty level is being set.
	 * @param string $_lp_level The slug representing the difficulty level.
	 */
	private static function set_course_difficulty_from_lp_to_masteriyo( $course_id, $_lp_level ) {
		if ( $_lp_level ) {
			$difficulty_term = get_term_by( 'slug', $_lp_level, 'course_difficulty' );

			if ( ! $difficulty_term || is_wp_error( $difficulty_term ) ) {
				$difficulty_term = wp_insert_term(
					ucfirst( $_lp_level ),
					'course_difficulty',
					array( 'slug' => $_lp_level )
				);

				if ( is_wp_error( $difficulty_term ) ) {
					update_post_meta( $course_id, '_difficulty_id', 0 );
					return;
				}

				$term_id = $difficulty_term['term_id'];
			} else {
				$term_id = $difficulty_term->term_id;
			}

			update_post_meta( $course_id, '_difficulty_id', $term_id );

			wp_set_object_terms( $course_id, $term_id, 'course_difficulty', false );
		} else {
			update_post_meta( $course_id, '_difficulty_id', 0 );
		}
	}

	/**
	 * Migrates user enrollments for a given MasterStudy course ID.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id MasterStudy course ID.
	 */
	private static function migrate_course_enrollment( $course_id ) {
		global $wpdb;

		$ms_enrollments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}stm_lms_user_courses
				WHERE course_id = %d AND status = %s ",
				$course_id,
				'enrolled'
			)
		);

		if ( ! $ms_enrollments ) {
			return;
		}

		foreach ( $ms_enrollments as $ms_enrollment ) {
			$user_id    = absint( $ms_enrollment->user_id );
			$start_time = $ms_enrollment->start_time;

			if ( masteriyo_is_user_already_enrolled( $user_id, $course_id ) ) {
				continue;
			}

			$table_name = $wpdb->prefix . 'masteriyo_user_items';

			$user_items_data = array(
				'item_id'    => $course_id,
				'user_id'    => $user_id,
				'item_type'  => 'user_course',
				'date_start' => gmdate( 'Y-m-d H:i:s', intval( $start_time ) ),
				'parent_id'  => 0,
				'status'     => 'active',
			);

			$wpdb->insert(
				$table_name,
				$user_items_data,
				array( '%d', '%d', '%s', '%s', '%d', '%s' )
			);

			Helper::update_user_role( $user_id );
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
		$question_type = self::determine_question_type( get_post_meta( $question_id, 'type', true ) );

		if ( ! $question_type ) {
			return;
		}

		$formatted_answers = self::format_answers( get_post_meta( $question_id, 'answers', true ) );

		if ( empty( $formatted_answers ) ) {
			return;
		}

		$question_data = array(
			'ID'           => $question_id,
			'post_type'    => PostType::QUESTION,
			'post_content' => wp_json_encode( $formatted_answers ),
			'post_parent'  => $quiz_id,
			'menu_order'   => $menu_order,
		);

		$question_id = wp_update_post( $question_data );

		if ( is_wp_error( $question_id ) ) {
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
		if ( 'true_false' === $ques_type ) {
			return QuestionType::TRUE_FALSE;
		} elseif ( 'multi_choice' === $ques_type ) {
			return QuestionType::MULTIPLE_CHOICE;
		} elseif ( 'single_choice' === $ques_type ) {
			return QuestionType::SINGLE_CHOICE;
		}

		return null;
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
	private static function format_answers( $answers ) {
		$answers = maybe_unserialize( $answers );

		if ( empty( $answers ) ) {
			return array();
		}

		$formatted_answers = array();

		foreach ( $answers as $answer ) {
			$choice = sanitize_text_field( $answer['text'] );

			if ( ! empty( $choice ) ) {
				$formatted_answers[] = array(
					'name'    => $choice,
					'correct' => $answer['isTrue'] ? true : false,
				);
			}
		}

		return $formatted_answers;
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

		$order = array(
			'ID'            => $order_id,
			'post_type'     => PostType::ORDER,
			'post_title'    => $title,
			'post_status'   => $status ? $status : OrderStatus::PENDING,
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

		$quantity = $wpdb->get_col( $wpdb->prepare( "SELECT quantity FROM {$wpdb->prefix}stm_lms_order_items WHERE order_id = %d AND object_id = %d", $order_id, $course_id ) );

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
	}

	/**
	 * Migrate a single MasterStudy review.
	 *
	 * @since 1.16.0
	 *
	 * @param int $review_id MasterStudy review ID.
	 *
	 * @return void
	 */
	private static function migrate_review( $review_id ) {
		$ms_review = get_post( $review_id );

		if ( ! $ms_review ) {
			return;
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

		$post_status = $ms_review->post_status;

		$comment_status = CommentStatus::HOLD;

		if ( 'publish' === $post_status ) {
			$comment_status = CommentStatus::APPROVE;
		}

		$review_data = array(
			'comment_post_ID'      => $course_id,
			'comment_author'       => $comment_author,
			'comment_author_email' => $comment_author_email,
			'comment_author_url'   => $comment_author_url,
			'comment_date'         => $ms_review->post_date,
			'comment_date_gmt'     => $ms_review->post_date_gmt,
			'comment_karma'        => $comment_karma,
			'comment_parent'       => $ms_review->post_parent,
			'user_id'              => $user_id,
			'comment_approved'     => $comment_status,
			'comment_type'         => CommentType::COURSE_REVIEW,
			'comment_agent'        => 'Masteriyo',
		);

		$review_id = wp_insert_comment( $review_data );

		$review_title = sanitize_text_field( $ms_review->post_content );

		update_comment_meta( $review_id, '_title', $review_title );

		wp_trash_post( $review_id );
	}
}
