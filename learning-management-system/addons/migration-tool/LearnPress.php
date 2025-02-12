<?php
/**
 * LearnPress migrations.
 *
 * @since 1.16.0
 *
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool;

use Masteriyo\Enums\CommentType;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuestionType;
use Masteriyo\PostType\PostType;
use Masteriyo\Roles;

/**
 * Class LearnPress.
 *
 * @since 1.16.0
 */
class LearnPress {
	/**
		 * Handles the migration of LearnPress courses.
		 *
		 * Retrieves LearnPress courses and migrates them to Masteriyo, including sections, items (lessons, quizzes),
		 * questions, and user enrollments.
		 *
		 * @since 1.8.0
		 *
		 * @param array $remaining_ids Array of remaining IDs to be migrated.
		 * @return WP_REST_Response|null Returns WP_REST_Response on success or null on failure.
		 */
	public static function migrate_lp_courses( $remaining_ids ) {
		$lp_courses = self::get_learnpress_courses();

		if ( ! is_array( $lp_courses ) || empty( $lp_courses ) ) {
			return null;
		}

		$lp_course = reset( $lp_courses );
		self::process_course_migration_from_lp( $lp_course );

		$remaining_courses = array_slice( $lp_courses, 1 );
		update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_courses ) );

		$response = self::generate_migration_response_from_lp( $lp_course, $remaining_courses );

		return rest_ensure_response( $response );
	}

	/**
	 * Retrieves LearnPress courses.
	 *
	 * @since 1.8.0
	 *
	 * @return array|null Array of LearnPress course IDs or null if not found.
	 */
	private static function get_learnpress_courses() {
		global $wpdb;

		$lp_courses = get_option( 'masteriyo_remaining_migrated_items' );

		if ( 'not_started' === $lp_courses ) {
			$lp_courses = $wpdb->get_results( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'lp_course';", ARRAY_A );
		} else {
			$lp_courses = is_string( $lp_courses ) ? json_decode( $lp_courses, true ) : $lp_courses;
		}

		return $lp_courses;
	}

	/**
	 * Processes migration for a single LearnPress course.
	 *
	 * @since 1.8.0
	 *
	 * @param array $lp_course LearnPress course data.
	 */
	private static function process_course_migration_from_lp( $lp_course ) {
		$course_id = $lp_course['ID'] ?? 0;
		$course    = \learn_press_get_course( $course_id );

		if ( ! $course ) {
			return;
		}

		$curriculum = $course->get_curriculum();
		$mto_course = array();

		if ( $curriculum ) {
			$i = 0;

			foreach ( $curriculum as $section ) {
				++$i;

				$mto_section = array(
					'post_type'    => PostType::SECTION,
					'post_title'   => $section->get_title(),
					'post_content' => $section->get_description(),
					'post_status'  => PostStatus::PUBLISH,
					'post_author'  => $course->get_author( 'id' ),
					'post_parent'  => $course_id,
					'menu_order'   => $i,
					'items'        => array(),
				);

				$items = $section->get_items();

				$j = 0;
				foreach ( $items as $item ) {
					++$j;

					$item_post_type = \learn_press_get_post_type( $item->get_id() );

					if ( 'lp_quiz' === $item_post_type ) {
						$item_post_type = PostType::QUIZ;
					} elseif ( 'lp_lesson' === $item_post_type ) {
						$item_post_type = PostType::LESSON;
					}

					$mto_items = array(
						'ID'          => $item->get_id(),
						'post_type'   => $item_post_type,
						'post_parent' => '{section_id}',
						'menu_order'  => $j,
					);

					$mto_section['items'][] = $mto_items;
				}

				$mto_course[] = $mto_section;
			}
		}

		if ( count( $mto_course ) > 0 ) {
			foreach ( $mto_course as $section ) {
				$items = $section['items'];
				unset( $section['items'] );

				$section_id = wp_insert_post( $section );

				if ( is_wp_error( $section_id ) ) {
					continue;
				}

				update_post_meta( $section_id, '_course_id', $course_id );

				foreach ( $items as $item ) {
					if ( PostType::QUIZ === $item['post_type'] ) {
						$quiz_id = masteriyo_array_get( $item, 'ID', 0 );

						$questions = self::get_quiz_questions_from_lp( $quiz_id );

						if ( count( $questions ) > 0 ) {
							foreach ( $questions as $question ) {
								self::process_question_migration_from_lp( $question, $quiz_id, $course_id );
							}
						}
					}

					$item['post_parent'] = $section_id;
					$item_id             = masteriyo_array_get( $item, 'ID', 0 );
					wp_update_post( $item );
					update_post_meta( $item_id, '_course_id', $course_id );
				}
			}
		}

		self::update_masteriyo_course_from_lp( $course_id );
	}

	/**
	 * Retrieves quiz questions for a given LearnPress quiz ID.
	 *
	 * @since 1.8.0
	 *
	 * @param int $quiz_id LearnPress quiz ID.
	 * @return array Array of quiz questions.
	 */
	private static function get_quiz_questions_from_lp( $quiz_id ) {
		global $wpdb;

		$questions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT question_id, question_order, questions.ID, questions.post_content, questions.post_author, questions.post_status, questions.post_title, question_type_meta.meta_value as question_type, question_mark_meta.meta_value as question_mark
					FROM {$wpdb->prefix}learnpress_quiz_questions
					LEFT JOIN {$wpdb->posts} questions on question_id = questions.ID
					LEFT JOIN {$wpdb->postmeta} question_type_meta on question_id = question_type_meta.post_id AND question_type_meta.meta_key = '_lp_type'
					LEFT JOIN {$wpdb->postmeta} question_mark_meta on question_id = question_mark_meta.post_id AND question_mark_meta.meta_key = '_lp_mark'
					WHERE quiz_id = %d",
				$quiz_id
			)
		);

		return $questions;
	}

	/**
	 * Processes migration for a single LearnPress quiz question.
	 *
	 * @since 1.8.0
	 *
	 * @param object $question LearnPress quiz question data.
	 * @param int $quiz_id LearnPress quiz ID.
	 * @param int $course_id Masteriyo course ID.
	 */
	private static function process_question_migration_from_lp( $question, $quiz_id, $course_id ) {
		$question_type = null;

		if ( 'true_or_false' === $question->question_type ) {
			$question_type = QuestionType::TRUE_FALSE;
		} elseif ( 'single_choice' === $question->question_type ) {
			$question_type = QuestionType::SINGLE_CHOICE;
		} elseif ( 'multi_choice' === $question->question_type ) {
			$question_type = QuestionType::MULTIPLE_CHOICE;
		}

		if ( $question_type ) {
			$answers = self::get_question_answers_from_lp( $question->question_id );

			$question_description = sanitize_text_field( $question->post_content );

			$question_array = array(
				'post_type'    => PostType::QUESTION,
				'post_title'   => $question->post_title,
				'post_content' => wp_json_encode( $answers ),
				'post_excerpt' => $question_description,
				'post_status'  => PostStatus::PUBLISH,
				'post_author'  => $question->post_author,
				'post_parent'  => $quiz_id,
			);

			$question_id = wp_insert_post( $question_array );

			if ( is_wp_error( $question_id ) ) {
				return;
			}

			update_post_meta( $question_id, '_course_id', $course_id );
			update_post_meta( $question_id, '_type', $question_type );
			update_post_meta( $question_id, '_points', $question->question_mark );
			update_post_meta( $question_id, '_parent_id', $quiz_id );
			if ( $question_description ) {
				update_post_meta( $question_id, '_enable_description', true );
			}
		}
	}

	/**
	 * Retrieves answers for a given LearnPress question ID.
	 *
	 * @since 1.8.0
	 *
	 * @param int $question_id LearnPress question ID.
	 * @return array Array of question answers.
	 */
	private static function get_question_answers_from_lp( $question_id ) {
		global $wpdb;

		$answer_items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}learnpress_question_answers WHERE question_id = %d",
				$question_id
			),
			ARRAY_A
		);

		$answers = array();

		if ( count( $answer_items ) > 0 ) {
			foreach ( $answer_items as $answer_item ) {
				$answers[] = array(
					'name'    => masteriyo_array_get( $answer_item, 'title', '' ),
					'correct' => 'yes' === masteriyo_array_get( $answer_item, 'is_true', '' ) ? true : false,
				);
			}
		}

		return $answers;
	}

	/**
	 * Updates Masteriyo course information.
	 *
	 * @since 1.8.0
	 *
	 * @param int $course_id Masteriyo course ID.
	 */
	private static function update_masteriyo_course_from_lp( $course_id ) {
		$_lp_price              = floatval( get_post_meta( $course_id, '_lp_regular_price', true ) );
		$_lp_max_students       = get_post_meta( $course_id, '_lp_max_students', true ) ?? 0;
		$_lp_thumbnail_id       = get_post_meta( $course_id, '_thumbnail_id', true ) ?? 0;
		$_lp_no_required_enroll = get_post_meta( $course_id, '_lp_no_required_enroll', true ) ?? 'no';
		$_lp_sale_start         = get_post_meta( $course_id, '_lp_sale_start', true ) ?? '';
		$_lp_sale_end           = get_post_meta( $course_id, '_lp_sale_end', true ) ?? '';
		$_lp_level              = get_post_meta( $course_id, '_lp_level', true ) ?? '';
		$_lp_retake_count       = absint( get_post_meta( $course_id, '_lp_retake_count', true ) ) ?? 0;

		$price_type = ( $_lp_price > 0 ) ? 'paid' : 'free'; // Determine if the course is free or paid.

		// Set the term in 'course_visibility' taxonomy.
		wp_set_object_terms( $course_id, $price_type, 'course_visibility', false );

		$mto_course = array(
			'ID'        => $course_id,
			'post_type' => PostType::COURSE,
		);

		wp_update_post( $mto_course );
		update_post_meta( $course_id, '_was_lp_course', true );
		update_post_meta( $course_id, '_price', $_lp_price );
		update_post_meta( $course_id, '_regular_price', $_lp_price );
		update_post_meta( $course_id, '_duration', self::learn_press_get_duration_in_minutes( $course_id ) );
		update_post_meta( $course_id, '_enrollment_limit', $_lp_max_students );
		update_post_meta( $course_id, '_thumbnail_id', $_lp_thumbnail_id );
		update_post_meta( $course_id, '_show_curriculum', true );

		if ( ! empty( $_lp_sale_start ) ) {
			update_post_meta( $course_id, '_date_on_sale_from', $_lp_sale_start );
		}

		if ( ! empty( $_lp_sale_end ) ) {
			update_post_meta( $course_id, '_date_on_sale_from', $_lp_sale_end );
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

		// Set the course difficulty.
		self::set_course_difficulty_from_lp_to_masteriyo( $course_id, $_lp_level );

		// Migrate course categories.
		Helper::migrate_course_categories_from_to_masteriyo( $course_id );

		Helper::migrate_course_author( $course_id );

		$_lp_key_features = maybe_unserialize( get_post_meta( $course_id, '_lp_key_features', true ) );
		$_highlights      = '';

		if ( is_array( $_lp_key_features ) && ! empty( $_lp_key_features ) ) {
			foreach ( $_lp_key_features as $feature ) {
				$_highlights .= "<li>{$feature}</li>";
			}
		}

		if ( $_highlights ) {
			update_post_meta( $course_id, '_highlights', $_highlights );
		}

		// Enrollment migration.
		self::migrate_enrollments_from_lp( $course_id );
	}

	/**
	 * Migrates user enrollments for a given Masteriyo course ID.
	 *
	 * @since 1.8.0
	 *
	 * @param int $course_id Masteriyo course ID.
	 */
	private static function migrate_enrollments_from_lp( $course_id ) {
		global $wpdb;

		$lp_enrollments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT lp_user_items.*,
					lp_order.ID as order_id,
					lp_order.post_date as order_time
					FROM {$wpdb->prefix}learnpress_user_items lp_user_items
					LEFT JOIN {$wpdb->posts} lp_order ON lp_user_items.ref_id = lp_order.ID
					WHERE item_id = %d AND ref_type = 'lp_order'",
				$course_id
			)
		);

		foreach ( $lp_enrollments as $lp_enrollment ) {

			if ( ! isset( $lp_enrollment->user_id, $lp_enrollment->order_id, $lp_enrollment->start_time, $lp_enrollment->parent_id ) ) {
				continue;
			}

			$user_id  = $lp_enrollment->user_id;
			$order_id = $lp_enrollment->order_id;

			$is_enrolled = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %d AND item_id = %d AND item_type = 'user_course'",
					$user_id,
					$course_id
				)
			);

			if ( $is_enrolled ) {
				continue; // Skip if the user is already enrolled.
			}

			$table_name = $wpdb->prefix . 'masteriyo_user_items';

			$user_items_data = array(
				'item_id'    => $course_id,
				'user_id'    => $user_id,
				'item_type'  => 'user_course',
				'date_start' => $lp_enrollment->start_time,
				'parent_id'  => $lp_enrollment->parent_id,
				'status'     => 'active',
			);

			$result = $wpdb->insert(
				$table_name,
				$user_items_data,
				array( '%d', '%d', '%s', '%s', '%s', '%s' )
			);

			if ( false === $result ) {
				continue;
			}

			$user_item_id = $wpdb->insert_id;

			Helper::update_user_role( $user_id, Roles::STUDENT );

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

			$table_name = $wpdb->prefix . 'masteriyo_user_itemmeta';

			foreach ( $user_item_metas as $item_meta ) {
				$wpdb->insert( $table_name, $item_meta );
			}
		}
	}

	/**
	 * Generates migration response.
	 *
	 * @since 1.8.0
	 *
	 * @param array $lp_course LearnPress course data.
	 * @param array $remaining_courses Updated LearnPress course data.
	 *
	 * @return array Migration response.
	 */
	private static function generate_migration_response_from_lp( $lp_course, $remaining_courses ) {
		$type          = 'courses';
		$remaining_ids = wp_list_pluck( $remaining_courses, 'ID' );

		if ( empty( $remaining_courses ) ) {
			$type          = 'orders';
			$remaining_ids = self::get_remaining_order_ids_from_lp();

			if ( is_wp_error( $remaining_ids ) || empty( $remaining_ids ) ) {
				$type          = 'reviews';
				$remaining_ids = self::fetch_lp_review_ids();
			}
			update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_ids ) );
		}

		$response = array(
			'message' => __( 'Course with ID: ', 'learning-management-system' ) . $lp_course['ID'] . __( ' migrated successfully.', 'learning-management-system' ),
		);

		if ( 'courses' === $type ) {
			$response['remainingCourses'] = $remaining_ids;
		} elseif ( 'orders' === $type ) {
			$response['remainingOrders'] = wp_list_pluck( $remaining_ids, 'ID' );
		} else {
			$response['remainingReviews'] = $remaining_ids;
		}

		return $response;
	}

	/**
	 * Retrieves remaining order IDs.
	 *
	 * @since 1.8.0
	 *
	 * @return array Array of remaining order IDs.
	 */
	private static function get_remaining_order_ids_from_lp() {
		global $wpdb;

		return $wpdb->get_results( "SELECT ID, post_date, post_author FROM {$wpdb->posts} WHERE post_type = 'lp_order' AND post_status = 'lp-completed';", ARRAY_A );
	}

	/**
	 * Migrate LearnPress orders to Masteriyo.
	 *
	 * Retrieves LearnPress orders and migrates them to Masteriyo, updating order status,
	 * creating corresponding Masteriyo order items, and updating order meta.
	 *
	 * @since 1.8.0
	 *
	 * @return WP_REST_Response|null Returns WP_REST_Response on success or null on failure.
	 */
	public static function migrate_lp_orders() {
		$lp_orders = self::get_lp_orders_to_migrate();

		if ( ! is_array( $lp_orders ) || count( $lp_orders ) < 1 ) {
			return null;
		}

		$lp_order = reset( $lp_orders );
		$order_id = isset( $lp_order['ID'] ) ? $lp_order['ID'] : 0;

		if ( $order_id ) {
			$order_time = strtotime( $lp_order['post_date'] );
			$title      = __( 'Order', 'learning-management-system' ) . ' &ndash; ' . gmdate( get_option( 'date_format' ), $order_time ) . ' @ ' . gmdate( get_option( 'time_format' ), $order_time );

			$migrate_order_data = array(
				'ID'            => $order_id,
				'post_status'   => 'completed',
				'post_type'     => PostType::ORDER,
				'post_title'    => $title,
				'post_password' => masteriyo_generate_order_key(),
			);

			wp_update_post( $migrate_order_data );

			$lp_order_items = self::get_lp_order_items( $order_id );

			if ( count( $lp_order_items ) > 0 ) {
				foreach ( $lp_order_items as $lp_order_item ) {
					self::migrate_order_item_from_lp( $lp_order_item, $order_id );
				}
			}

			self::update_order_meta_from_lp( $order_id, $lp_order );
		}

		$remaining_orders = array_slice( $lp_orders, 1 );
		update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_orders ) );

		$type = 'orders';

		if ( 1 > count( $remaining_orders ) ) {
			$type          = 'reviews';
			$remaining_ids = self::fetch_lp_review_ids();
			update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_ids ) );
		}

		$response = array(
			'message' => __( 'Order with ID: ', 'learning-management-system' ) . $order_id . __( ' migrated successfully.', 'learning-management-system' ),
		);

		if ( 'orders' === $type ) {
			$response['remainingOrders'] = wp_list_pluck( $remaining_orders, 'ID' );
		} else {
			$response['remainingReviews'] = $remaining_ids;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Retrieves LP orders to migrate.
	 *
	 * @since 1.8.0
	 *
	 * @return array LP orders to migrate.
	 */
	private static function get_lp_orders_to_migrate() {
		$lp_orders = get_option( 'masteriyo_remaining_migrated_items', null );

		if ( empty( $lp_orders ) || 'not_started' === $lp_orders ) {
			$lp_orders = self::get_remaining_order_ids_from_lp();
		} else {
			$lp_orders = is_string( $lp_orders ) ? json_decode( $lp_orders, true ) : $lp_orders;
		}

		return $lp_orders;
	}

	/**
	 * Get LearnPress order items.
	 *
	 * @since 1.8.0
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array
	 */
	private static function get_lp_order_items( $order_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT order_item_id as id, order_item_name as name
					, oim.meta_value as `course_id`
			FROM {$wpdb->learnpress_order_items} oi
					INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oi.order_item_id = oim.learnpress_order_item_id AND oim.meta_key='_course_id'
			WHERE order_id = %d ",
				$order_id
			)
		);
	}

	/**
	 * Migrate order item.
	 *
	 * @since 1.8.0
	 *
	 * @param object $lp_order_item LearnPress order item object.
	 * @param int    $order_id      Order ID.
	 */
	private static function migrate_order_item_from_lp( $lp_order_item, $order_id ) {
		global $wpdb;

		$item_data = array(
			'order_item_name' => $lp_order_item->name,
			'order_item_type' => 'course',
			'order_id'        => $order_id,
		);

		$wpdb->insert( $wpdb->prefix . 'masteriyo_order_items', $item_data );
		$order_item_id = absint( $wpdb->insert_id );

		$lp_item_metas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value
			FROM {$wpdb->prefix}learnpress_order_itemmeta
			WHERE learnpress_order_item_id = %d",
				$lp_order_item->id
			)
		);

		$lp_formatted_metas = array();
		foreach ( $lp_item_metas as $item_meta ) {
			$lp_formatted_metas[ $item_meta->meta_key ] = $item_meta->meta_value;
		}

		$_course_id = masteriyo_array_get( $lp_formatted_metas, '_course_id', 0 );
		$_quantity  = masteriyo_array_get( $lp_formatted_metas, '_quantity', 0 );
		$_subtotal  = masteriyo_array_get( $lp_formatted_metas, '_subtotal', 0 );
		$_total     = masteriyo_array_get( $lp_formatted_metas, '_total', 0 );

		$mto_item_metas = array(
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'course_id',
				'meta_value'    => $_course_id,
			),
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'quantity',
				'meta_value'    => $_quantity,
			),
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'subtotal',
				'meta_value'    => $_subtotal,
			),
			array(
				'order_item_id' => $order_item_id,
				'meta_key'      => 'total',
				'meta_value'    => $_total,
			),
		);

		$table_name = $wpdb->prefix . 'masteriyo_order_itemmeta';

		foreach ( $mto_item_metas as $item_meta ) {
			$wpdb->insert( $table_name, $item_meta );
		}
	}

	/**
	 * Update Masteriyo order meta.
	 *
	 * @since 1.8.0
	 *
	 * @param int   $order_id  Order ID.
	 * @param array $lp_order   LearnPress order data.
	 */
	private static function update_order_meta_from_lp( $order_id, $lp_order ) {
		global $wpdb;

		$customer_id         = get_post_meta( $order_id, '_user_id', true );
		$customer_ip_address = get_post_meta( $order_id, '_user_ip_address', true );
		$customer_user_agent = get_post_meta( $order_id, '_user_agent', true );
		$total               = get_post_meta( $order_id, '_order_total', true );
		$currency            = get_post_meta( $order_id, '_order_currency', true );
		$version             = get_post_meta( $order_id, '_order_version', true );

		update_post_meta( $order_id, '_customer_id', $customer_id );
		update_post_meta( $order_id, '_customer_ip_address', $customer_ip_address );
		update_post_meta( $order_id, '_customer_user_agent', $customer_user_agent );
		update_post_meta( $order_id, '_total', $total );
		update_post_meta( $order_id, '_currency', $currency );
		update_post_meta( $order_id, '_version', $version );

		$user_email = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM {$wpdb->users} WHERE ID = %d", $customer_id ) );

		update_post_meta( $order_id, '_billing_address_index', $user_email );
		update_post_meta( $order_id, '_billing_email', $user_email );
		update_post_meta( $order_id, '_was_lp_order', true );
	}

	/**
	 * Migrate LearnPress reviews to Masteriyo.
	 *
	 * @since 1.8.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return WP_REST_Response|null Returns WP_REST_Response on success or null on failure.
	 */
	public static function migrate_lp_reviews() {
		global $wpdb;

		$lp_review_ids = self::fetch_lp_review_ids();

		if ( is_wp_error( $lp_review_ids ) || empty( $lp_review_ids ) ) {
			return null;
		}

		foreach ( $lp_review_ids as $lp_review_id ) {
			$review_migrate_data = array(
				'comment_approved' => 1,
				'comment_type'     => CommentType::COURSE_REVIEW,
				'comment_agent'    => 'Masteriyo',
				'comment_karma'    => 0,
			);

			$result = $wpdb->update( $wpdb->comments, $review_migrate_data, array( 'comment_ID' => $lp_review_id ) );

			if ( false === $result ) {
				continue;
			}
		}

		Helper::delete_remaining_migrated_items();

		return rest_ensure_response( array( 'message' => __( 'All the LearnPress data migrated successfully.', 'learning-management-system' ) ) );
	}

	/**
	 * Fetches the IDs of reviews from LearnPress.
	 *
	 * @since 1.8.0
	 *
	 * @return array Array of review IDs.
	 */
	private static function fetch_lp_review_ids() {
		global $wpdb;

		return $wpdb->get_col(
			$wpdb->prepare(
				"
					SELECT comments.comment_ID
					FROM {$wpdb->comments} AS comments
					JOIN {$wpdb->posts} AS posts ON comments.comment_post_ID = posts.ID
					WHERE comments.comment_type = %s
					AND posts.post_type = %s
					AND EXISTS (
							SELECT 1 FROM {$wpdb->postmeta}
							WHERE post_id = comments.comment_post_ID
							AND meta_key = '_was_lp_course'
					)",
				'comment',
				PostType::COURSE
			)
		);
	}

	/**
	 * Get the duration of a LearnPress post in minutes.
	 *
	 * Parses the duration meta field of a LearnPress post and converts it to minutes.
	 *
	 * @since 1.8.0
	 *
	 * @param int $post_id The ID of the LearnPress post.
	 * @return int Returns the duration in minutes. Returns 0 if the duration is not valid or not set.
	 */
	private static function learn_press_get_duration_in_minutes( $post_id ) {
		$duration = get_post_meta( $post_id, '_lp_duration', true );

		$duration_arr = explode( ' ', $duration );

		if ( count( $duration_arr ) > 1 ) {
			$duration_number = absint( $duration_arr[0] );
			$duration_unit   = strtolower( $duration_arr[1] );

			switch ( $duration_unit ) {
				case 'minute':
				case 'minutes':
					return $duration_number;
				case 'hour':
				case 'hours':
					return $duration_number * 60;
				case 'day':
				case 'days':
					return $duration_number * 1440;
				case 'week':
				case 'weeks':
					return $duration_number * 10080;
				default:
					return 0;
			}
		}

		return 0;
	}

	/**
	 * Sets or creates and sets the course difficulty level based on the specified level slug.
	 *
	 * @since 1.8.0
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
}
