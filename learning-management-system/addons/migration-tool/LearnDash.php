<?php
/**
 * LearnDash migrations.
 *
 * @since 1.16.0
 *
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuestionType;
use Masteriyo\PostType\PostType;
use WpProQuiz_Model_AnswerTypes;
use LDLMS_Factory_Post;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Roles;

/**
 * Class LearnDash.
 *
 * @since 1.16.0
 */
class LearnDash {
	/**
	 * Handles the migration of LearnDash courses.
	 *
	 * Retrieves LearnDash courses and migrates them to Masteriyo, including sections, items (lessons, quizzes),
	 * questions, and user enrollments.
	 *
	 * @since 1.8.0
	 *
	 * @param array $remaining_ids Array of remaining IDs to be migrated.
	 * @return WP_REST_Response|null Returns WP_REST_Response on success or null on failure.
	 */
	public static function migrate_ld_courses() {
		$ld_courses = self::get_learndash_courses();

		if ( ! is_array( $ld_courses ) || empty( $ld_courses ) ) {
			return null;
		}

		$ld_course = reset( $ld_courses );
		$course_id = isset( $ld_course['ID'] ) ? $ld_course['ID'] : 0;

		if ( $course_id ) {
			$total_data = LDLMS_Factory_Post::course_steps( $course_id );
			$total_data = $total_data->get_steps();

			self::migrate_ld_course( $course_id );

			self::update_masteriyo_course_from_ld( $course_id );

			// Insert the course enrollments.
			self::insert_ld_course_enrollment( $course_id );
		}

		$remaining_courses = array_slice( $ld_courses, 1 );
		update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_courses ) );

		$response = self::generate_migration_response_from_ld( $ld_course, $remaining_courses );

		return rest_ensure_response( $response );
	}

	/**
	 * Retrieves LearnDash courses.
	 *
	 * @since 1.12.2
	 *
	 * @return array|null Array of LearnDash course IDs or null if not found.
	 */
	private static function get_learndash_courses() {
		global $wpdb;

		$ld_courses = get_option( 'masteriyo_remaining_migrated_items' );

		if ( 'not_started' === $ld_courses ) {
			$ld_courses = $wpdb->get_results( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'sfwd-courses' AND post_status = 'publish';", ARRAY_A );
		} else {
			$ld_courses = is_string( $ld_courses ) ? json_decode( $ld_courses, true ) : $ld_courses;
		}

		return $ld_courses;
	}

	/**
	 * Migrates the course ID.
	 *
	 * @since 1.8.0
	 *
	 * @param int $course_id Masteriyo course ID.
	 */
	private static function migrate_ld_course( $course_id ) {
		$new_course_id = self::update_course_post_type( $course_id );

		if ( is_wp_error( $new_course_id ) ) {
			return;
		}

		$section_heading = self::get_section_headings( $course_id );
		$total_data      = self::get_course_steps( $course_id );

		if ( empty( $total_data ) ) {
			return;
		}

		$section_id = 0;
		$menu_order = 0;

		if ( ! empty( $total_data['sfwd-lessons'] ) ) {
			$i             = 0;
			$section_count = 0;
			foreach ( $total_data['sfwd-lessons'] as $lesson_key => $lesson_data ) {
				$author_id = get_post_field( 'post_author', $course_id );

				$check = 0 === $i ? 0 : $i + 1;
				++$menu_order;

				if ( isset( $section_heading[ $section_count ]['order'] ) ) {
					if ( $section_heading[ $section_count ]['order'] === $check ) {
						$section_id = Helper::insert_post( $section_heading[ $section_count ]['post_title'], '', $author_id, PostType::SECTION, $i, $course_id );
						self::update_course_item_meta( $section_id, $course_id, $course_id );
						++$section_count;
					}
				}

				if ( $section_id ) {
					$lesson_id = Helper::update_post( $lesson_key, PostType::LESSON, $menu_order, $section_id );

					self::update_course_item_meta( $lesson_id, $course_id, $section_id );

					foreach ( $lesson_data['sfwd-topic'] as $lesson_inner_key => $lesson_inner ) {
						++$menu_order;

						$lesson_id = Helper::update_post( $lesson_inner_key, PostType::LESSON, $menu_order, $section_id );

						self::update_course_item_meta( $lesson_id, $course_id, $section_id );

						foreach ( $lesson_inner['sfwd-quiz'] as $quiz_key => $quiz_data ) {
							++$menu_order;

							$quiz_id = Helper::update_post( $quiz_key, PostType::QUIZ, $menu_order, $section_id );

							self::update_course_item_meta( $quiz_id, $course_id, $section_id );

							if ( $quiz_id ) {
								self::migrate_ld_quiz( $quiz_id, $course_id );
							}
						}
					}

					foreach ( $lesson_data['sfwd-quiz'] as $quiz_key => $quiz_data ) {
						++$menu_order;
						$quiz_id = Helper::update_post( $quiz_key, PostType::QUIZ, $menu_order, $section_id );

						self::update_course_item_meta( $quiz_id, $course_id, $section_id );

						if ( $quiz_id ) {
							self::migrate_ld_quiz( $quiz_id, $course_id );
						}
					}
				}
				++$i;
			}

			if ( ! empty( $total_data['sfwd-quiz'] ) ) {
				foreach ( $total_data['sfwd-quiz'] as $quiz_key => $quiz_data ) {
					++$menu_order;
					$quiz_id = Helper::update_post( $quiz_key, PostType::QUIZ, $menu_order, $section_id );

					self::update_course_item_meta( $quiz_id, $course_id, $section_id );

					if ( $quiz_id ) {
						self::migrate_ld_quiz( $quiz_id, $course_id );
					}
				}
			}
		}

		// Handle Standalone Quizzes if there are no lessons.
		if ( empty( $total_data['sfwd-lessons'] ) && ! empty( $total_data['sfwd-quiz'] ) ) {
			$author_id  = get_post_field( 'post_author', $course_id );
			$section_id = Helper::insert_post( __( 'Section', 'learning-management-system' ), '', $author_id, PostType::SECTION, 0, $course_id );
			update_post_meta( $section_id, '_course_id', $course_id );
			update_post_meta( $section_id, '_parent_id', $section_id );

			foreach ( $total_data['sfwd-quiz'] as $quiz_key => $quiz_data ) {
				++$menu_order;

				if ( $section_id ) {
					$quiz_id = Helper::update_post( $quiz_key, PostType::QUIZ, $menu_order, $section_id );

					update_post_meta( $quiz_id, '_course_id', $course_id );
					update_post_meta( $quiz_id, '_parent_id', $section_id );

					if ( $quiz_id ) {
						self::migrate_ld_quiz( $quiz_id, $course_id );
					}
				}
			}
		}
	}

	/**
	 * Updates the course post type.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id Course ID
	 *
	 * @return int|WP_Error Updated post ID
	 */
	private static function update_course_post_type( $course_id ) {
		return wp_update_post(
			array(
				'ID'        => $course_id,
				'post_type' => PostType::COURSE,
			)
		);
	}

	/**
	 * Gets section headings for the course.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id Course ID
	 *
	 * @return array Section headings
	 */
	private static function get_section_headings( $course_id ) {
		$section_heading = get_post_meta( $course_id, 'course_sections', true );

		return $section_heading ? json_decode( $section_heading, true ) : array(
			array(
				'order'      => 0,
				'post_title' => __( 'Section', 'learning-management-system' ),
			),
		);
	}

	/**
	 * Retrieves the steps of a LearnDash course.
	 *
	 * @since 1.16.0
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return array The steps of the course.
	 */
	private static function get_course_steps( $course_id ) {
		$total_data = LDLMS_Factory_Post::course_steps( $course_id );

		return $total_data->get_steps();
	}

	/**
	 * Updates the course and parent IDs for a course item.
	 *
	 * @since 1.16.0
	 *
	 * @param int $item_id Course item ID.
	 * @param int $course_id Course ID.
	 * @param int $parent_id Parent ID.
	 */
	private static function update_course_item_meta( $item_id, $course_id, $parent_id ) {
		update_post_meta( $item_id, '_course_id', $course_id );
		update_post_meta( $item_id, '_parent_id', $parent_id );
	}

	/**
	 * Updates Masteriyo course information.
	 *
	 * @since 1.8.0
	 *
	 * @param int $course_id Masteriyo course ID.
	 */
	private static function update_masteriyo_course_from_ld( $course_id ) {
		$course_meta_data = get_post_meta( $course_id, '_sfwd-courses', true );

		$_ld_price                = floatval( isset( $course_meta_data['sfwd-courses_course_price'] ) ? $course_meta_data['sfwd-courses_course_price'] : 0 );
		$_ld_max_students         = isset( $course_meta_data['sfwd-courses_course_seats_limit'] ) ? absint( $course_meta_data['sfwd-courses_course_seats_limit'] ) : 0;
		$_ld_courses_course_price = isset( $course_meta_data['sfwd-courses_course_price_type'] ) ? $course_meta_data['sfwd-courses_course_price_type'] : 'open';
		$_ld_show_curriculum      = isset( $course_meta_data['sfwd-courses_course_disable_content_table'] ) ? $course_meta_data['sfwd-courses_course_disable_content_table'] : 'on';
		$price_type               = ( 'paynow' === $_ld_courses_course_price || 'subscribe' === $_ld_courses_course_price ) ? 'paid' : 'free'; // Determine if the course is free or paid.
		// Set the term in 'course_visibility' taxonomy.
		wp_set_object_terms( $course_id, $price_type, 'course_visibility', false );

		update_post_meta( $course_id, '_was_ld_course', true );
		update_post_meta( $course_id, '_price', $_ld_price );
		update_post_meta( $course_id, '_regular_price', $_ld_price );
		update_post_meta( $course_id, '_enrollment_limit', $_ld_max_students );
		update_post_meta( $course_id, '_show_curriculum', 'on' === $_ld_show_curriculum ? true : false );

		if ( 'open' === $_ld_courses_course_price ) {
			update_post_meta( $course_id, '_access_mode', CourseAccessMode::OPEN );
		} elseif ( 'paynow' === $_ld_courses_course_price ) {
			update_post_meta( $course_id, '_access_mode', CourseAccessMode::ONE_TIME );
		} elseif ( 'free' === $_ld_courses_course_price ) {
			update_post_meta( $course_id, '_access_mode', CourseAccessMode::NEED_REGISTRATION );
		} elseif ( 'subscribe' === $_ld_courses_course_price ) {
			update_post_meta( $course_id, '_access_mode', CourseAccessMode::RECURRING );
		}

		// Migrate course categories.
		Helper::migrate_course_categories_from_to_masteriyo( $course_id, 'ld_course_category' );

		Helper::migrate_course_author( $course_id );
	}

	/**
	 * Generates migration response.
	 *
	 * @since 1.8.0
	 *
	 * @param object $ld_course LearnPress course data.
	 * @param array $remaining_courses Updated LearnPress course data.
	 *
	 * @return array Migration response.
	 */
	private static function generate_migration_response_from_ld( $ld_course, $remaining_courses ) {
		global $wpdb;

		$type          = 'courses';
		$remaining_ids = wp_list_pluck( $remaining_courses, 'ID' );
		$course_id     = $ld_course['ID'] ?? 0;

		if ( 1 > count( $remaining_courses ) ) {
			$type          = 'orders';
			$remaining_ids = $wpdb->get_results( "SELECT ID, post_date FROM {$wpdb->posts} WHERE post_type = 'sfwd-transactions' AND post_status = 'publish';" );

			update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_ids ) );

			if ( is_wp_error( $remaining_ids ) || empty( $remaining_ids ) ) {
				Helper::delete_remaining_migrated_items();
				return rest_ensure_response( array( 'message' => __( 'All the LearnDash data migrated successfully.', 'learning-management-system' ) ) );
			}
		}

		$response = array(
			'message' => __( 'Course with ID: ', 'learning-management-system' ) . $course_id . __( ' migrated successfully.', 'learning-management-system' ),
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
	 * Migrates the quiz for a given Masteriyo course ID.
	 *
	 * @since 1.8.0
	 *
	 * @param int $quiz_id Quiz ID.
	 * @param int $course_id Masteriyo course ID.
	 */
	private static function migrate_ld_quiz( $quiz_id, $course_id ) {
		global $wpdb;
		$question_ids = get_post_meta( $quiz_id, 'ld_quiz_questions', true );
		$is_table     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', "{$wpdb->prefix}learndash_pro_quiz_question" ) );

		if ( ! empty( $question_ids ) ) {
			$question_ids = array_keys( $question_ids );
			$menu_order   = 0;

			foreach ( $question_ids as $question_single ) {
				++$menu_order;

				$question_id = get_post_meta( $question_single, 'question_pro_id', true );

				$table_name = $is_table ? "{$wpdb->prefix}learndash_pro_quiz_question" : "{$wpdb->prefix}wp_pro_quiz_question";

				$query = $wpdb->prepare(
					"SELECT id, title, question, points, answer_type, answer_data FROM $table_name WHERE id = %d",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$question_id
				);

				$result = $wpdb->get_row( $query, ARRAY_A ); // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared

				$question_type = null;

				if ( 'single' === $result['answer_type'] ) {
					$question_type = QuestionType::SINGLE_CHOICE;
				} elseif ( 'multiple' === $result['answer_type'] ) {
					$question_type = QuestionType::MULTIPLE_CHOICE;
				}

				if ( null === $question_type ) {
					return;
				}

				$serialized_answers = maybe_unserialize( $result['answer_data'] );

				$answers = array();

				foreach ( (array) $serialized_answers as $answer_object ) {
					$answer_data = array();

					if ( ! $answer_object instanceof WpProQuiz_Model_AnswerTypes ) {
						continue;
					}

					$i = 0;
					foreach ( (array) $answer_object as $answer_value ) {

						if ( 0 === $i ) {
							$answer_data['name'] = $answer_value;
						} elseif ( 3 === $i ) {
							$answer_data['correct'] = (bool) $answer_value;
						}

						++$i;
					}

					$answers[] = $answer_data;
				}

				$question_array = array(
					'post_type'    => PostType::QUESTION,
					'post_title'   => sanitize_text_field( $result['question'] ),
					'post_content' => wp_json_encode( $answers ),
					'post_status'  => PostStatus::PUBLISH,
					'post_author'  => get_post_field( 'post_author', $quiz_id ),
					'post_parent'  => $quiz_id,
					'menu_order'   => $menu_order,
				);

				$question_id = wp_insert_post( $question_array );

				if ( is_wp_error( $question_id ) ) {
					return;
				}

				update_post_meta( $question_id, '_course_id', $course_id );
				update_post_meta( $question_id, '_type', $question_type );
				update_post_meta( $question_id, '_points', $result['points'] );
				update_post_meta( $question_id, '_parent_id', $quiz_id );
			}
		}
	}

	/**
	 * Migrates user enrollments for a given Masteriyo course ID.
	 *
	 * @since 1.8.0
	 *
	 * @param int $course_id Masteriyo course ID.
	 */
	private static function insert_ld_course_enrollment( $course_id ) {
		global $wpdb;
		$ld_course_user_activities = $wpdb->get_results( $wpdb->prepare( "SELECT * from {$wpdb->prefix}learndash_user_activity WHERE course_id = %d AND activity_type = 'access'", absint( $course_id ) ) );

		if ( 1 > count( $ld_course_user_activities ) ) {
			return;
		}

		foreach ( $ld_course_user_activities as $data ) {
			$user_id            = $data->user_id;
			$complete_course_id = $data->course_id;
			$order_id           = 0;

			$args = array(
				'post_type'      => 'sfwd-transactions',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => 'course_id',
						'value' => $complete_course_id,
					),
					array(
						'key'   => 'user_id',
						'value' => $user_id,
					),
				),
			);

			$query = new \WP_Query( $args );

			if ( ! empty( $query->posts ) ) {
				foreach ( $query->posts as $post_id ) {
					$order_id = $post_id;
				}
			}

			$is_enrolled = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %d AND item_id = %d AND item_type = 'user_course'",
					$user_id,
					$complete_course_id
				)
			);

			if ( $is_enrolled ) {
				continue; // Skip if the user is already enrolled.
			}

			$table_name = $wpdb->prefix . 'masteriyo_user_items';

			$user_items_data = array(
				'item_id'       => $course_id,
				'user_id'       => $user_id,
				'item_type'     => 'user_course',
				'date_start'    => gmdate( 'Y-m-d H:i:s', $data->activity_started ),
				'date_modified' => gmdate( 'Y-m-d H:i:s', $data->activity_updated ),
				'date_end'      => gmdate( 'Y-m-d H:i:s', $data->activity_completed ),
				'status'        => 'active',
			);

			$result = $wpdb->insert(
				$table_name,
				$user_items_data,
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
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
	 * Migrate LearnPress orders to Masteriyo.
	 *
	 * Retrieves LearnPress orders and migrates them to Masteriyo, updating order status,
	 * creating corresponding Masteriyo order items, and updating order meta.
	 *
	 * @since 1.8.0
	 *
	 * @return WP_REST_Response|null Returns WP_REST_Response on success or null on failure.
	 */
	public static function migrate_ld_orders() {
		global $wpdb;

		$ld_orders = self::get_learndash_orders();

		if ( ! is_array( $ld_orders ) || empty( $ld_orders ) ) {
			return null;
		}

		$ld_order = reset( $ld_orders );
		$order_id = isset( $ld_order['ID'] ) ? $ld_order['ID'] : 0;

		if ( $order_id ) {
			$order_time = strtotime( $ld_order['post_date'] );
			$title      = __( 'Order', 'learning-management-system' ) . ' &ndash; ' . gmdate( get_option( 'date_format' ), $order_time ) . ' @ ' . gmdate( get_option( 'time_format' ), $order_time );

			$migrate_order_data = array(
				'ID'            => $order_id,
				'post_status'   => OrderStatus::COMPLETED,
				'post_type'     => PostType::ORDER,
				'post_title'    => $title,
				'post_password' => masteriyo_generate_order_key(),
			);

			wp_update_post( $migrate_order_data );

			self::migrate_order_item_from_ld( $order_id );

			self::update_order_meta_from_ld( $order_id, $ld_order );
		}

		$remaining_orders = array_slice( $ld_orders, 1 );

		update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_orders ) );

		$type = 'orders';

		if ( 1 > count( $remaining_orders ) ) {
			Helper::delete_remaining_migrated_items();
			return rest_ensure_response( array( 'message' => __( 'All the LearnDash data migrated successfully.', 'learning-management-system' ) ) );
		}

		$response = array(
			'message' => __( 'Order with ID: ', 'learning-management-system' ) . $order_id . __( ' migrated successfully.', 'learning-management-system' ),
		);

		if ( 'orders' === $type ) {
			$response['remainingOrders'] = wp_list_pluck( $remaining_orders, 'ID' );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Retrieves LearnDash orders.
	 *
	 * @since 1.12.2
	 *
	 * @return array|null Array of LearnDash order IDs or null if not found.
	 */
	private static function get_learndash_orders() {
		global $wpdb;

		$ld_orders = get_option( 'masteriyo_remaining_migrated_items', null );

		if ( ! $ld_orders ) {
			$ld_orders = $wpdb->get_results( "SELECT ID, post_date  FROM {$wpdb->posts} WHERE post_type = 'sfwd-transactions' AND post_status = 'publish';", ARRAY_A );
		} else {
			$ld_orders = is_string( $ld_orders ) ? json_decode( $ld_orders, true ) : $ld_orders;
		}

		return $ld_orders;
	}

	/**
	 * Migrate order item.
	 *
	 * @since 1.8.0
	 *
	 * @param int    $order_id      Order ID.
	 */
	private static function migrate_order_item_from_ld( $order_id ) {
		global $wpdb;

		$course_id = get_post_meta( $order_id, 'course_id', true );

		$item_data = array(
			'order_item_name' => get_the_title( $course_id ),
			'order_item_type' => 'course',
			'order_id'        => $order_id,
		);

		$wpdb->insert( $wpdb->prefix . 'masteriyo_order_items', $item_data );
		$order_item_id = absint( $wpdb->insert_id );

		if ( ! $order_item_id ) {
			return;
		}

		$_ld_price = get_post_meta( $course_id, '_sfwd-courses', true );

		$_course_id = $course_id;
		$_quantity  = 1;
		$_subtotal  = isset( $_ld_price['sfwd-courses_course_price'] ) ? $_ld_price['sfwd-courses_course_price'] : 0;
		$_total     = isset( $_ld_price['sfwd-courses_course_price'] ) ? $_ld_price['sfwd-courses_course_price'] : 0;

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
	private static function update_order_meta_from_ld( $order_id, $lp_order ) {
		global $wpdb;

		$course_id = get_post_meta( $order_id, 'course_id', true );
		$_ld_price = get_post_meta( $course_id, '_sfwd-courses', true );

		$customer_id = get_post_meta( $order_id, 'user_id', true );
		$total       = isset( $_ld_price['sfwd-courses_course_price'] ) ? $_ld_price['sfwd-courses_course_price'] : 0;

		$all_meta = get_post_meta( $order_id );

		$pattern = '/_currency$/';

		$currency = '';

		foreach ( $all_meta as $key => $value ) {
			if ( preg_match( $pattern, $key ) ) {
				$currency = $value[0];
				break;
			}
		}

		$version = get_post_meta( $order_id, 'learndash_version', true );

		update_post_meta( $order_id, '_customer_id', $customer_id );
		update_post_meta( $order_id, '_total', $total );
		update_post_meta( $order_id, '_currency', $currency );
		update_post_meta( $order_id, '_version', $version );

		$user_email = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM {$wpdb->users} WHERE ID = %d", $customer_id ) );

		update_post_meta( $order_id, '_billing_address_index', $user_email );
		update_post_meta( $order_id, '_billing_email', $user_email );

		update_post_meta( $order_id, '_was_ld_order', true );
	}
}
