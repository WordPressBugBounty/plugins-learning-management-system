<?php
/**
 * LifterLMS migrations.
 *
 * @since 1.16.0
 *
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Addons\WcIntegration\CourseProduct;
use Masteriyo\Addons\WcIntegration\Helper as HelperWoocommerce;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CoursePriceType;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuestionType;
use Masteriyo\PostType\PostType;

/**
 * Class LifterLMS.
 *
 * @since 1.16.0
 */
class LifterLMS {

	/**
	 * Migrate courses from LifterLMS.
	 *
	 * @since 1.16.0
	 *
	 * @return array
	 */
	public static function migrate_lf_courses() {
		$lf_courses = self::get_lf_courses();

		if ( ! $lf_courses || ! is_array( $lf_courses ) ) {
			return rest_ensure_response(
				array(
					'message'         => __( 'No courses found to migrate.', 'learning-management-system' ),
					'remainingOrders' => self::migrate_lf_orders(),
				)
			);
		}

		$lf_course_id = reset( $lf_courses );

		self::migrate_course( absint( $lf_course_id ) );

		self::migrate_course_info( $lf_course_id );

		self::create_woocommerce_product( $lf_course_id );

		Helper::migrate_course_author( $lf_course_id );

		self::migrate_course_enrollment( $lf_course_id );

		$remaining_courses = array_slice( $lf_courses, 1 );

		update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_courses ) );

		$response = array(
			'message' => __( 'Course with ID: ', 'learning-management-system' ) . $lf_course_id . __( ' migrated successfully.', 'learning-management-system' ),
		);

		if ( 1 > count( $remaining_courses ) ) {
			$response['remainingOrders'] = self::get_lf_orders();
		} else {
			$response['remainingCourses'] = $remaining_courses;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Migrate orders from LifterLMS.
	 *
	 * @since 1.16.0
	 *
	 * @return array
	 */
	public static function migrate_lf_orders() {
		$lf_orders = self::get_lf_orders();

		if ( ! $lf_orders || ! is_array( $lf_orders ) ) {
			return rest_ensure_response( array( 'message' => __( 'No orders found to migrate.', 'learning-management-system' ) ) );
		}

		$lf_order_id = reset( $lf_orders );

		self::migrate_order( absint( $lf_order_id ) );

		$remaining_orders = array_slice( $lf_orders, 1 );

		update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $remaining_orders ) );

		$response = array(
			'message' => __( 'Order with ID: ', 'learning-management-system' ) . $lf_order_id . __( ' migrated successfully.', 'learning-management-system' ),
		);

		if ( 1 > count( $remaining_orders ) ) {
			Helper::delete_remaining_migrated_items();
			return rest_ensure_response( array( 'message' => __( 'All the LifterLMS data migrated successfully.', 'learning-management-system' ) ) );
		} else {
			$response['remainingOrders'] = $remaining_orders;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Retrieves LifterLMS reviews.
	 *
	 * @return array|null Array of LifterLMS review IDs or null if not found.
	 */
	private static function get_lf_courses() {
		global $wpdb;

		$ld_courses = get_option( 'masteriyo_remaining_migrated_items', 'not_started' );

		if ( 'not_started' === $ld_courses ) {
			$ld_courses = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'course' AND post_status = 'publish';" );
		} else {
			$ld_courses = is_string( $ld_courses ) ? json_decode( $ld_courses, true ) : $ld_courses;
		}

		return $ld_courses;
	}

	/**
	 * Retrieves LifterLMS orders.
	 *
	 * @since 1.16.0
	 *
	 * @return array|null Array of LifterLMS order IDs or null if not found.
	 */
	private static function get_lf_orders() {
		global $wpdb;
		$lf_orders = get_option( 'masteriyo_remaining_migrated_items' );

		if ( empty( json_decode( $lf_orders, true ) ) || 'not_started' === $lf_orders ) {

			$lf_orders = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'llms_order' " );
		} else {
			$lf_orders = is_string( $lf_orders ) ? json_decode( $lf_orders, true ) : $lf_orders;
		}

		return $lf_orders;
	}

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
				'post_author'  => $course->get_author,
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

					if ( ! $quiz ) {
						continue;
					}

					$lesson_post_type = PostType::QUIZ;

					$mto_section['items'][] = array(
						'ID'           => $quiz->id,
						'post_type'    => $lesson_post_type,
						'post_title'   => $quiz->post->post_title,
						'post_content' => $quiz->post->post_content,
						'post_parent'  => '{section_id}',
						'menu_order'   => $lesson_order,
					);
				} else {
					$mto_section['items'][] = array(
						'ID'           => $lesson->id,
						'post_type'    => $lesson_post_type,
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
					update_post_meta( $item_id, '_attempts_allowed', absint( $quiz->get( 'allowed_attempts' ) ) );
					update_post_meta( $item_id, '_duration', absint( $quiz->get( 'time_limit' ) ) );
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

		wp_set_object_terms( $course_id, $course_type, 'course_visibility', false );
		update_post_meta( $course_id, '_access_mode', $access_mode );
		update_post_meta( $course_id, '_regular_price', $regular_price );

		if ( 'no' !== $is_on_sale ) {
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

			if ( 'no' !== $is_on_sale ) {
				$product->set_sale_price( $sale_price );
				$product->set_date_on_sale_from( $sale_start );
				$product->set_date_on_sale_to( $sale_end );
			}

			$product->set_image_id( $course->get_image_id() );
			$product->get_category_ids( $course->get_category_ids() );
			$product->get_tag_ids( $course->get_tag_ids() );
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

			$order_id   = str_replace( 'order_', '', $order_id );
			$table_name = $wpdb->prefix . 'masteriyo_user_items';

			$user_items_data = array(
				'item_id'    => $course_id,
				'user_id'    => $user_id,
				'item_type'  => 'user_course',
				'date_start' => $lf_enrollment->updated_date,
				'parent_id'  => 0,
				'status'     => 'active',
			);

			if ( masteriyo_is_user_already_enrolled( $user_id, $course_id, 'inactive' ) ) {
				$wpdb->update(
					$table_name,
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
				$wpdb->insert(
					$table_name,
					$user_items_data,
					array( '%d', '%d', '%s', '%s', '%d', '%s' )
				);

				$user_item_id = $wpdb->insert_id;

				Helper::update_user_role( $user_id );

				self::insert_user_item_meta( $user_item_id, $order_id );
			}
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
	private static function insert_user_item_meta( $user_item_id, $order_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'masteriyo_user_itemmeta';

		$user_item_metas = array(
			array(
				'user_item_id' => $user_item_id,
				'meta_key'     => '_order_id',
				'meta_value'   => $order_id,
			),
			array(
				'user_item_id' => $user_item_id,
				'meta_key'     => '_price',
				'meta_value'   => get_post_meta( $order_id, '_llms_total', true ),
			),
		);

		foreach ( $user_item_metas as $item_meta ) {
			$wpdb->insert( $table_name, $item_meta, array( '%d', '%s', '%s' ) );
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
	 * Migrates an order from LifterLMS to Masteriyo.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id LifterLMS order ID.
	 */
	private static function migrate_order( $order_id ) {
		$current_status = get_post_status( $order_id );
		$status         = OrderStatus::PENDING;

		if ( 'llms-completed' === $current_status ) {
			$status = OrderStatus::COMPLETED;
		} elseif ( 'llms-on-hold' === $current_status ) {
			$status = OrderStatus::ON_HOLD;
		} elseif ( 'llms-pending' === $current_status ) {
			$status = OrderStatus::PENDING;
		} elseif ( 'llms-cancelled' === $current_status ) {
			$status = OrderStatus::CANCELLED;
		} elseif ( 'llms-refunded' === $current_status ) {
			$status = OrderStatus::REFUNDED;
		} elseif ( 'llms-failed' === $current_status ) {
			$status = OrderStatus::FAILED;
		}

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
	}
}
