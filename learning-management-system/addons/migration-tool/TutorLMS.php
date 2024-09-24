<?php
/**
 * Migration Tool helper class.
 *
 * @since 1.13.0
 */
namespace Masteriyo\Addons\MigrationTool;

use Countable;
use Masteriyo\DateTime;
use Masteriyo\Enums\CommentType;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CoursePriceType;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuestionType;
use Masteriyo\PostType\PostType;
use Masteriyo\Roles;
use WP_Query;

class TutorLMS {
	/**
	 * Migrate courses from Tutor.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	public static function migrate_tutor_courses( $remaining_ids ) {
		return static::get_tutor_courses_and_update_masteriyo( $remaining_ids );
	}

	/**
	 * Migrate course orders from Tutor.
	 *
	 * @since 1.13.0
	 *
	 * @return boolean
	 */
	public static function migrate_tutor_order() {
		global $wpdb;
		$monetize_by = \tutor_utils()->get_option( 'monetize_by' );

		if ( 'wc' !== $monetize_by ) {
			update_option( 'masteriyo_remaining_courses_for_orders', array() );
			return null;
		}

		$orders_json = get_option( 'masteriyo_remaining_courses_for_orders' );
		$orders      = json_decode( $orders_json, true );

		if ( empty( $orders ) ) {
			return static::generate_migration_response_from_tutor( 'reviews', $orders, $orders );
		}

		foreach ( $orders as $index => $order ) {
			$order_id     = $order['order_id'];
			$course_id    = $order['course_id'];
			$user_item_id = $order['user_item_id'];

			if ( 'wc' === $monetize_by ) {
				$order_obj = \wc_get_order( $order_id );
				if ( ! empty( $order_obj ) && 'completed' !== static::convert_wc_status( $order_obj->get_status() ) ) {
					$table_name = $wpdb->prefix . 'masteriyo_user_items';

					$wpdb->delete(
						$table_name,
						array( 'id' => $user_item_id ),
						array( '%d' )
					);
				}
			} else {
				return null;
			}

			if ( empty( $order_obj ) ) {
				continue;
			}

			static::insert_custom_order( $order_obj, $course_id );

			// // Filter out the processed order
			$orders = array_filter(
				$orders,
				function ( $order_item ) use ( $order ) {
						return $order_item['order_id'] !== $order['order_id'];
				}
			);

			$type = 'orders';

			if ( empty( $orders ) ) {
				$type = 'reviews';

				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->posts}
				                WHERE post_author > 0
				                    AND post_parent > 0
				                    AND post_type = %s",
						'tutor_enrolled'
					)
				);
			}

			$response = static::generate_migration_response_from_tutor( $type, $order, $orders );
			update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $orders ) );
			update_option( 'masteriyo_remaining_courses_for_orders', wp_json_encode( $orders ) );

			return rest_ensure_response( $response );
		}
	}


	/**
	 * Migrate course reviews from Tutor.
	 *
	 * @since 1.13.0
	 *
	 * @return boolean
	 */
	public static function migrate_tutor_reviews() {
		global $wpdb;
		$tutor_reviews = static::get_tutor_course_reviews();

		if ( is_wp_error( $tutor_reviews ) ) {
			return null;
		}

		if ( 1 > count( $tutor_reviews ) ) {
					return static::generate_migration_response_from_tutor( 'announcement', $tutor_reviews, $tutor_reviews );
		}

		foreach ( $tutor_reviews as $review ) {

			$review_id = $review->comment_ID ?? 0;
			if ( ! $review_id ) {
				return null;
			}

			$comment = isset( $review->comment_content ) ? $review->comment_content : '';

			if ( strlen( $comment ) > 200 ) {
				$content_title = substr( $comment, 0, 198 ) . '...';
			} else {
				$content_title = $comment;
			}

			$review_migrate_data = array(
				'comment_content'  => $comment,
				'comment_approved' => 'approved' === $review->comment_status ? 1 : 0,
				'comment_type'     => CommentType::COURSE_REVIEW,
				'comment_agent'    => 'Masteriyo',
				'comment_karma'    => $review->rating,
				'comment_parent'   => $review->comment_parent,
			);
			update_comment_meta( $review_id, '_title', $content_title );
			$result = $wpdb->update( $wpdb->comments, $review_migrate_data, array( 'comment_ID' => $review_id ) );

			delete_comment_meta( $review_id, 'tutor_rating' );

			$updated_tutor_review = array_filter(
				$tutor_reviews,
				function ( $current_review ) use ( $review_id ) {
					return $current_review->comment_ID !== $review_id;
				}
			);

				$type = 'reviews';

			if ( 1 > count( $updated_tutor_review ) ) {
					$type = 'announcement';
			}
			$response = static::generate_migration_response_from_tutor( $type, $review, $updated_tutor_review );
			update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $updated_tutor_review ) );

			return rest_ensure_response( $response );
		}
	}

	/**
	 * Retrieves Tutor courses.
	 *
	 * @since 1.13.0
	 *
	 * @return array|null Array of Tutor course IDs or null if not found.
	 */
	public static function get_tutor_courses_and_update_masteriyo() {
		global $wpdb;
		$remaining_courses_for_quiz_attempts = get_option( 'masteriyo_remaining_courses_for_quiz_attempts' );

		$tutor_courses = $wpdb->get_results(
			"
						SELECT ID ,post_author ,post_status
						FROM {$wpdb->posts} 
						WHERE post_type = 'courses' 
						AND (post_status = 'publish' OR post_status = 'draft' OR post_status = 'pending' OR post_status = 'private' )
						;"
		);

		if ( empty( $remaining_courses_for_quiz_attempts ) ) {
			update_option( 'masteriyo_remaining_courses_for_quiz_attempts', $tutor_courses );
		}

		if ( 1 > count( $tutor_courses ) ) {
			return static::generate_migration_response_from_tutor( 'orders', $tutor_courses, $tutor_courses );
		}

		foreach ( $tutor_courses as $course ) {
			$course_id = $course->ID ?? 0;

			if ( ! $course_id ) {
				continue;
			}

			$course->post_type = 'mto-course';

			if ( 'pending' === $course->post_status ) {
				$course->post_status = 'draft';
			}

			if ( 'private' === $course->post_status ) {
				$course->post_status = 'draft';
			}

			wp_update_post( $course );

			if ( 0 !== $course->post_author ) {
				$user_id = $course->post_author;
				$user    = new \WP_User( $user_id );

				if ( ! empty( $user ) ) {
					$user->remove_role( 'subscriber' );
					$user->remove_role( 'tutor_instructor' );
					$user->remove_role( Roles::STUDENT );
					delete_user_meta( $user_id, '_tutor_instructor_status' );
					delete_user_meta( $user_id, '_is_tutor_instructor' );

					if ( ! in_array( 'administrator', (array) $user->roles ) ) {
						$user->add_role( Roles::INSTRUCTOR );
					}
				}
			}

			static::update_masteriyo_course_from_tutor( $course->ID );

			$enrolled_user_by_course_id = static::get_enrolled_users_by_course_id( $course_id );

			if ( ! empty( $enrolled_user_by_course_id ) ) {
				foreach ( $enrolled_user_by_course_id as $enrolled_user ) {

					$order_id = get_post_meta( $enrolled_user->ID, '_tutor_enrolled_by_order_id', true );

					$email = sanitize_email( $enrolled_user->user_email );

					$result = static::update_tutor_enrolled_user_to_masteriyo_enrolled_user( $course_id, $email, $enrolled_user );

					if ( false === $result ) {
						static::update_edd_order_data_and_order_info( $course_id, $order_id );
					} else {
						static::update_edd_order_data_and_order_info( $course_id, $order_id, $result );
					}
				}
			}

			//sections listing data.
			$sections = get_posts(
				array(
					'post_type'      => 'topics',
					'post_parent'    => $course_id,
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
				)
			);

			// Fetch sections associated with the course
			if ( empty( $sections ) ) {
				continue;
			}

			foreach ( $sections as $section ) {
				$section_id = $section->ID;

				$section->post_type   = 'mto-section';
				$section->post_parent = $course_id;

				update_post_meta( $section_id, '_course_id', $course_id );
				wp_update_post( $section );

				$lessons_and_quizzes = get_posts(
					array(
						'post_type'      => array( 'lesson', 'tutor_quiz' ),
						'post_parent'    => $section_id,
						'posts_per_page' => -1,
						'orderby'        => 'menu_order',
						'order'          => 'ASC',
					)
				);

				if ( empty( $lessons_and_quizzes ) ) {
					continue;
				}

				// Process the fetched lessons and quizzes
				foreach ( $lessons_and_quizzes as $item ) {
					// 'mto-lesson',
					static::update_tutor_lesson_to_masteriyo( $item, $section_id, $course_id );

					static::update_tutor_course_quiz_to_masteriyo( $item, $section_id, $course_id );

				}
			}

			$updated_courses = array_filter(
				$tutor_courses,
				function ( $current_course ) use ( $course ) {
					return $current_course !== $course;
				}
			);
			$type            = 'courses';

			if ( 1 > count( $updated_courses ) ) {

				$type = 'orders';
			}

			$response = static::generate_migration_response_from_tutor( $type, $course, $updated_courses );

			update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $updated_courses ) );

			return rest_ensure_response( $response );
		}

	}



	/**
	 * update tutor course quiz to masteriyo
	 *
	 * @param [type] $item
	 * @param [type] $section_id
	 * @param [type] $course_id
	 * @return void
	 */
	public static function update_tutor_course_quiz_to_masteriyo( $item, $section_id, $course_id ) {
		if ( 'tutor_quiz' === $item->post_type ) {
			$item->post_type   = 'mto-quiz';
			$item->post_parent = $section_id;

			if ( $item->ID && $item->ID > 0 ) {
				update_post_meta( $item->ID, '_course_id', $course_id );
			}

			$questions = ( $item->ID && $item->ID > 0 ) ? \tutor_utils()->get_questions_by_quiz( $item->ID ) : array();

			if ( ! empty( $questions ) ) {
				$total_marks = 0;
				foreach ( $questions as $question ) {
					static::process_question_migration_from_tutor( $question, $item->ID, $course_id, $total_marks );
					$total_marks = $total_marks + intval( $question->question_mark );
				}
				update_post_meta( $item->ID, '_full_mark', $total_marks );

			}

			wp_update_post( $item );

			//tutor_quiz_option
			$quiz_options    = get_post_meta( $item->ID, 'tutor_quiz_option', true );
			$pass_percentage = $quiz_options['passing_grade'];
			$passing_mark    = ( $pass_percentage / 100 ) * $total_marks;

			update_post_meta( $item->ID, '_pass_mark', floor( $passing_mark ) );
			update_post_meta( $item->ID, '_mto_quiz_options', $quiz_options );
			update_post_meta( $item->ID, '_pass_mark_type', 'percentage' );
			update_post_meta( $item->ID, '_questions_display_per_page', $quiz_options['max_questions_for_answer'] );
			update_post_meta( $item->ID, '_attempts_allowed', $quiz_options['attempts_allowed'] );

			$time_in_minutes = static::convert_time_limit_to_minutes( $quiz_options['time_limit'] );
			update_post_meta( $item->ID, '_duration', $time_in_minutes );
		}
	}
	/**
	 * update tutor course lesson to masteriyo course lesson
	 *
	 * @since 1.13.0
	 */
	public static function update_tutor_lesson_to_masteriyo( $item, $section_id, $course_id ) {
		if ( 'lesson' === $item->post_type ) {
			$item->post_type   = 'mto-lesson';
			$item->post_parent = $section_id;

			if ( $item->ID && $item->ID > 0 ) {
				update_post_meta( $item->ID, '_course_id', $course_id );
			}
			static::extract_video_source( $item->ID );
			//attachments
			$attachments = \tutor_utils()->get_attachments( $item->ID );
			if ( ! empty( $attachments ) ) {
				$attachment_data = array();
				foreach ( $attachments as $attachment ) {
					$attachment_data[] = $attachment->post_id;
				}
				update_post_meta( $item->ID, '_download_materials', $attachment_data );
			}

			wp_update_post( $item );
		}
	}

	/**
	 * update tutor enrolled user to masteriyo enrolled user.
	 *
	 * @since 1.13.0
	 */
	public static function update_tutor_enrolled_user_to_masteriyo_enrolled_user( $course_id, $email, $enrolled_user ) {
		global $wpdb;
		$user = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->users} WHERE user_email = %s",
				$email
			)
		);

		$is_enrolled = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}masteriyo_user_items WHERE user_id = %d AND item_id = %d AND item_type = 'user_course'",
				$user->ID,
				$course_id
			)
		);

		$user = new \WP_User( $user->ID );

		if ( isset( $user->ID ) ) {
			$user->add_role( Roles::STUDENT );
			delete_user_meta( $user->get_id(), '_is_tutor_student' );
			$user->remove_role( 'subscriber' );
			$user->remove_role( 'tutor_instructor' );
			if ( ! in_array( 'administrator', (array) $user->roles ) && ! in_array( 'masteriyo_instructor', (array) $user->roles ) ) {
				$user->add_role( Roles::STUDENT );
			}
		}

		if ( ! $is_enrolled ) {

			$table_name = $wpdb->prefix . 'masteriyo_user_items';

			$user_items_data = array(
				'item_id'       => $course_id,
				'user_id'       => $user->ID,
				'item_type'     => 'user_course',
				'date_start'    => gmdate( 'Y-m-d H:i:s', strtotime( $enrolled_user->post_date ) ),
				'date_modified' => isset( $enrolled_user->post_modified ) ? gmdate( 'Y-m-d H:i:s', $enrolled_user->post_modified ) : null,
				'date_end'      => null,
				'status'        => 'active',
			);

			$wpdb->insert(
				$table_name,
				$user_items_data,
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
			);

			return $wpdb->insert_id;

		} else {
			return false;
		}

	}


	/**
	 * add order data to array and change edd orders information.
	 *
	 * @since 1.13.0
	 */
	public static function update_edd_order_data_and_order_info( $course_id, $order_id, $user_item_id = false ) {
		if ( ! empty( $order_id ) ) {
			$remaining_courses_for_orders = get_option( 'masteriyo_remaining_courses_for_orders', '[]' );
			$remaining_courses_for_orders = json_decode( $remaining_courses_for_orders );

			if ( ! is_array( $remaining_courses_for_orders ) ) {
				$remaining_courses_for_orders = array();
			}

			$remaining_courses_for_orders[] = array(
				'order_id'     => $order_id,
				'course_id'    => $course_id,
				'user_item_id' => $user_item_id ?? '',
			);

			update_option( 'masteriyo_remaining_courses_for_orders', wp_json_encode( $remaining_courses_for_orders ) );

			$monetize_by = \tutor_utils()->get_option( 'monetize_by' );
			if ( 'edd' === $monetize_by ) {
				$product_id = (int) get_post_meta( $course_id, '_tutor_course_product_id', true );
				$download   = \edd_get_download( $product_id );

				if ( $download ) {
					update_post_meta( $course_id, '_edd_download_id', $product_id );
					update_post_meta( $product_id, '_is_masteriyo_course', 'yes' );
					update_post_meta( $product_id, '_masteriyo_course_id', $course_id );
					delete_post_meta( $course_id, '_tutor_course_product_id', true );
				}
			}
		}
	}


	/**
	 * Generates migration response.
	 *
	 * @since 1.13.0
	 *
	 * @param object $course Tutor course data.
	 * @param array $updated_courses Updated Tutor course data.
	 *
	 * @return array Migration response.
	 */
	public static function generate_migration_response_from_tutor( $type, $course, $updated_courses ) {
		if ( empty( $type ) ) {
			$type = 'courses';
		}

		$response = array();

		if ( 'courses' === $type ) {
			$remaining_ids = wp_list_pluck( $updated_courses, 'ID' );
			if ( ! empty( $remaining_ids ) ) {
				$course_id                    = $course->ID ?? 0;
				$response['remainingCourses'] = $remaining_ids;
				$response['message']          = __( 'Course with ID: ', 'learning-management-system' ) . $course_id . __( ' migrated successfully.', 'learning-management-system' );
				$response['type']             = 'courses';
				return $response;
			}
			$type = 'orders';
		}
		if ( 'orders' === $type ) {
			$orders_json = get_option( 'masteriyo_remaining_courses_for_orders', '[]' );
			$orders      = json_decode( $orders_json, true );

			if ( ! empty( $orders ) ) {
				$order_ids                   = wp_list_pluck( $orders, 'order_id' );
				$response['remainingOrders'] = array_combine( array_keys( $order_ids ), array_values( $order_ids ) );
				$response['message']         = __( 'Order', 'learning-management-system' ) . __( ' migrated successfully.', 'learning-management-system' );
				$response['type']            = 'orders';
				return $response;
			}

			$type = 'reviews';
		}
		if ( 'reviews' === $type ) {
			$tutor_reviews = static::get_tutor_course_reviews();

			if ( ! empty( $tutor_reviews ) ) {
				$reviews                      = wp_list_pluck( $tutor_reviews, 'comment_ID' );
				$response['remainingReviews'] = array_combine( array_keys( $reviews ), array_values( $reviews ) );
				$response['message']          = __( 'Review', 'learning-management-system' ) . __( ' migrated successfully.', 'learning-management-system' );
				$response['type']             = 'reviews';
				return $response;
			}
			$type = 'announcement';
		}
		if ( 'announcement' === $type ) {
			$announcements = static::get_tutor_announcements_data();

			if ( ! empty( $announcements ) ) {
				$announcements_ids                 = wp_list_pluck( $announcements, 'ID' );
				$response['remainingAnnouncement'] = array_combine( array_keys( $announcements_ids ), array_values( $announcements_ids ) );
				$response['message']               = __( 'Announcement', 'learning-management-system' ) . __( ' migrated successfully.', 'learning-management-system' );
				$response['type']                  = 'announcement';
				return $response;
			}
			$type = 'questions_n_answers';
		}

		if ( 'questions_n_answers' === $type ) {
			$questions = \tutor_utils()->get_qa_questions( 0, 100000, '', null, null, null, null );

			if ( ! empty( $questions ) ) {
				$questions_ids                         = wp_list_pluck( $questions, 'comment_ID' );
				$response['remainingQuestionsAnswers'] = array_combine( array_keys( $questions_ids ), array_values( $questions_ids ) );
				$response['message']                   = __( 'Question and Answer', 'learning-management-system' ) . $course->comment_ID . __( ' migrated successfully.', 'learning-management-system' );
				$response['type']                      = 'questions_n_answers';
				return $response;
			}
		}

		// static::remove_student_data_from_tutor();
		// static::remove_tutor_instructor_related_data();

		$response['message'] = __( 'Migrated successfully.', 'learning-management-system' );
		return $response;

	}


	/**
	 * Update quiz attempt from tutor to masteriyo
	 *
	 * @since 1.13.0
	 */
	public static function update_quiz_attempts_from_tutor_to_masteriyo() {
		global $wpdb;

		$remaining_courses_for_quiz_attempts = get_option( 'masteriyo_remaining_courses_for_quiz_attempts' );

		// foreach ( $remaining_courses_for_quiz_attempts as $course ) {
		// 			$query = "
		// 					INSERT INTO {$wpdb->prefix}masteriyo_quiz_attempts (
		// 							course_id,
		// 							quiz_id,
		// 							user_id,
		// 							total_questions,
		// 							total_answered_questions,
		// 							total_marks,
		// 							total_attempts,
		// 							total_correct_answers,
		// 							total_incorrect_answers,
		// 							earned_marks,
		// 							answers,
		// 							attempt_status,
		// 							attempt_started_at,
		// 							attempt_ended_at
		// 					)
		//                  SELECT
		// 							a.course_id,
		// 							q.quiz_id,
		// 							a.user_id,
		// 							COUNT(q.question_id) AS total_questions,
		// 							SUM(CASE WHEN aa.given_answer IS NOT NULL THEN 1 ELSE 0 END) AS total_answered_questions,
		// 							SUM(q.question_mark) AS total_marks,
		// 							COUNT(DISTINCT a.attempt_id) AS total_attempts,
		// 							SUM(CASE WHEN aa.is_correct = 1 THEN 1 ELSE 0 END) AS total_correct_answers,
		// 							SUM(CASE WHEN aa.is_correct = 0 THEN 1 ELSE 0 END) AS total_incorrect_answers,
		// 							SUM(aa.achieved_mark) AS earned_marks,
		// 							JSON_ARRAYAGG(
		// 									JSON_OBJECT(
		// 											'id', q.question_id,
		// 											'name', q.question_title,
		// 											'description', q.question_description,
		// 											'type', q.question_type,
		// 											'parent_id', q.quiz_id,
		// 											'course_id', a.course_id,
		// 											'menu_order', q.question_order,
		// 											'answer_explanation', q.answer_explanation,
		// 											'answers', JSON_ARRAYAGG(
		// 													JSON_OBJECT(
		// 															'name', qa.answer_title,
		// 															'correct', qa.is_correct
		// 													)
		// 											)
		// 									)
		// 							) AS answers,
		// 							a.attempt_status,
		// 							a.attempt_started_at,
		// 							a.attempt_ended_at
		//                  FROM
		// 							{$wpdb->prefix}tutor_quiz_questions q
		//                  JOIN
		// 							{$wpdb->prefix}tutor_quiz_attempts a ON q.quiz_id = a.quiz_id
		//                  LEFT JOIN
		// 							{$wpdb->prefix}tutor_quiz_question_answers qa ON q.question_id = qa.belongs_question_id
		//                  LEFT JOIN
		// 							{$wpdb->prefix}tutor_quiz_attempt_answers aa ON q.question_id = aa.question_id
		//                  WHERE
		// 							a.course_id = %d
		//                  GROUP BY
		// 							a.attempt_id
		//                  ORDER BY
		// 							q.question_order;
		// 					";

		// 	$wpdb->query( $wpdb->prepare( $query, $course->ID ) );

		// 	$updated_courses = array_filter(
		// 		$remaining_courses_for_quiz_attempts,
		// 		function ( $current_course ) use ( $course ) {
		// 			return $current_course !== $course;
		// 		}
		// 	);

		// 	if ( 1 > count( $updated_courses ) ) {
		// 		return rest_ensure_response( array( 'message' => __( 'All the Tutor LMS data migrated successfully.', 'learning-management-system' ) ) );
		// 	}

		// 	update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $updated_courses ) );
		// 	update_option( 'masteriyo_remaining_courses_for_quiz_attempts', wp_json_encode( $updated_courses ) );

		// 	$key      = 'quiz_attempts';
		// 	$response = static::generate_migration_response_from_tutor( $key, $course, $updated_courses );
		// 	return $response;
		// }

	}

	/**
	 * calculate correct answers
	 *
	 * @param [type] $answers
	 * @return void
	 */
	public static function calculate_correct_answers( $answers ) {
		$correct = 0;
		foreach ( $answers as $answer ) {
			if ( $answer->is_correct ) {
				$correct++;
			}
		}
		return $correct;
	}

	/**
	 * calculate incorrect answers
	 *
	 * @param [type] $answers
	 * @return void
	 */
	public static function calculate_incorrect_answers( $answers ) {
		$incorrect = 0;
		foreach ( $answers as $answer ) {
			if ( ! $answer->is_correct ) {
				$incorrect++;
			}
		}
		return $incorrect;
	}

	/**
	 * Update questions and answers from tutor to masteriyo
	 *
	 * @return void
	 */
	public static function update_questions_and_answers_from_tutor() {
		$questions = \tutor_utils()->get_qa_questions( 0, 100000, '', null, null, null, null );

		foreach ( $questions as $question ) {
			$question_data_tutors = \tutor_utils()->get_qa_answer_by_question( $question->comment_ID );
			if ( ! $question_data_tutors ) {
				return static::generate_migration_response_from_tutor( 'questions_n_answers', $question, $question );
			}

			foreach ( $question_data_tutors as $question_data_tutor ) {
				$question_data = array(
					'comment_ID'           => $question_data_tutor->comment_ID,
					'comment_post_ID'      => $question_data_tutor->comment_post_ID,
					'comment_author'       => $question_data_tutor->comment_author,
					'comment_author_email' => $question_data_tutor->comment_author_email,
					'comment_date'         => $question_data_tutor->comment_date,
					'comment_date_gmt'     => $question_data_tutor->comment_date_gmt,
					'comment_content'      => html_entity_decode(
						$question_data_tutor->comment_content
					),
					'comment_parent'       => $question_data_tutor->comment_parent,
					'user_id'              => $question_data_tutor->user_id,
					'comment_approved'     => 1,
					'comment_agent'        => 'Masteriyo',
					'comment_type'         => CommentType::COURSE_QA,
				);

				$result = wp_update_comment( $question_data );

				if ( is_wp_error( $result ) ) {
					error_log( 'Failed to update comment ID ' . $question_data_tutor->comment_ID );
				} else {
					error_log( 'Successfully updated comment ID ' . $question_data_tutor->comment_ID );
				}
			}
			$updated_questions = array_filter(
				$questions,
				function ( $current_question ) use ( $question ) {
					return $current_question->comment_ID !== $question->comment_ID;
				}
			);
			$type              = 'questions_n_answers';

			if ( 1 > count( $updated_questions ) ) {
				$type = 'quiz_attempts';
			}

			$response = static::generate_migration_response_from_tutor( $type, $question, $updated_questions );
			update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $updated_questions ) );

			return rest_ensure_response( $response );
		}
	}

	/**
	 * Updates Masteriyo course information.
	 *
	 * @since 1.13.0
	 *
	 * @param int $course_id Masteriyo course ID.
	 */
	public static function update_masteriyo_course_from_tutor( $course_id ) {
		$regular_price          = '';
		$sale_price             = '';
		$course_maximum_student = \tutor_utils()->get_course_settings( $course_id, 'maximum_students', 0 );
		$course_level           = get_post_meta( $course_id, '_tutor_course_level', true ) ?? '';

		$product_id  = \tutor_utils()->get_course_product_id( $course_id );
		$monetize_by = \tutor_utils()->get_option( 'monetize_by' );

		if ( 'wc' === $monetize_by ) {
			$wc_product = \wc_get_product( $product_id );

			if ( $wc_product ) {
					$regular_price = \wc_get_price_to_display( $wc_product, array( 'price' => $wc_product->get_regular_price() ) );
					$sale_price    = \wc_get_price_to_display( $wc_product, array( 'price' => $wc_product->get_sale_price() ) );

					$new_type = 'mto_course';
				if ( $wc_product->is_type( 'subscription' ) || $wc_product->is_type( 'variable-subscription' ) ) {
						$new_type = 'mto_course_recurring';
				}

					// Update the product type in the database
					wp_set_object_terms( $product_id, $new_type, 'product_type' );

					// Update metadata as needed
					update_post_meta( $course_id, '_wc_product_id', $product_id );
					update_post_meta( $product_id, '_masteriyo_course_id', $course_id );

					// Reinitialize the product object after changing its type
					$wc_product = wc_get_product( $product_id );
			}
		}

		if ( 'edd' === $monetize_by ) {
			if ( \tutils()->has_edd() ) {
				$regular_price = \edd_get_download_price( $product_id );
				// $sale_price    = \edd_get_download_price( $product_id );
			}
		}

		$public_course     = get_post_meta( $course_id, '_tutor_is_public_course', true ) === 'yes' ? 1 : 0;
		$highlights        = get_post_meta( $course_id, '_tutor_course_benefits', true );
		$requirements      = get_post_meta( $course_id, '_tutor_course_requirements', true );
		$target_audience   = get_post_meta( $course_id, '_tutor_course_target_audience', true );
		$material_includes = get_post_meta( $course_id, '_tutor_course_material_includes', true );
		$duration_in_tutor = get_post_meta( $course_id, '_course_duration', true );
		$duration          = static::convert_time_limit_to_minutes( $duration_in_tutor );
		$video             = maybe_unserialize( get_post_meta( $course_id, '_video', true ) );
		$course_retake     = \tutor_utils()->get_option( 'course_retake_feature' );
		//////////////////////////////
		update_post_meta( $course_id, '_enable_course_retake', $course_retake );
		update_post_meta( $course_id, '_migration_course_requirements', $requirements );
		update_post_meta( $course_id, '_migration_course_target_audience', $target_audience );
		update_post_meta( $course_id, '_migration_course_material_includes', $material_includes );
		update_post_meta( $course_id, '_was_tutor_course', true );
		update_post_meta(
			$course_id,
			'_price',
			'' !== $sale_price ? $sale_price : ( '' !== $regular_price ? $regular_price : '0' )
		);
		update_post_meta( $course_id, '_regular_price', '' !== $regular_price ? $regular_price : '0' );
		update_post_meta( $course_id, '_sale_price', '' !== $sale_price ? $sale_price : '0' );
		update_post_meta( $course_id, '_duration', $duration );
		update_post_meta( $course_id, '_enrollment_limit', $course_maximum_student );

		// Set the course difficulty.
		static::set_course_difficulty_from_tutor_to_masteriyo( $course_id, $course_level );
		update_post_meta( $course_id, '_thumbnail_id', get_post_thumbnail_id( $course_id ) );
		update_post_meta( $course_id, '_show_curriculum', true );
		update_post_meta( $course_id, '_reviews_allowed', true );
		update_post_meta( $course_id, '_review_after_course_completion', true );

		$purchasable = \tutor_utils()->is_course_purchasable( $course_id );
		if ( ! $public_course && ! $purchasable ) {
			update_post_meta( $course_id, '_price_type', CoursePriceType::FREE );
			update_post_meta( $course_id, '_access_mode', CourseAccessMode::NEED_REGISTRATION );
			wp_set_object_terms( $course_id, CoursePriceType::FREE, 'course_visibility', false );
		} elseif ( $public_course ) {
			update_post_meta( $course_id, '_access_mode', CourseAccessMode::OPEN );
			update_post_meta( $course_id, '_price_type', CoursePriceType::FREE );
			wp_set_object_terms( $course_id, CoursePriceType::FREE, 'course_visibility', false );
		} elseif ( $purchasable ) {
			update_post_meta( $course_id, '_price_type', CoursePriceType::PAID );
			update_post_meta( $course_id, '_access_mode', CourseAccessMode::ONE_TIME );
			wp_set_object_terms( $course_id, CoursePriceType::PAID, 'course_visibility', false );
		}

				// Set the term in 'course_visibility' taxonomy.

		update_post_meta( $course_id, '_highlights', $highlights );
		static::extract_video_source( $course_id );
		// Migrate course categories.
		static::migrate_course_categories_from_tutor_to_masteriyo( $course_id );

	}

	/**
	 * course difficulty from tutor to masteriyo
	 *
	 * @since 1.13.0
	 *
	 * @param [type] $course_id
	 * @param [type] $_tutor_level
	 * @return void
	 */
	public static function set_course_difficulty_from_tutor_to_masteriyo( $course_id, $_tutor_level ) {
		if ( $_tutor_level ) {
			$difficulty_term = get_term_by( 'slug', $_tutor_level, 'course_difficulty' );

			if ( ! $difficulty_term || is_wp_error( $difficulty_term ) ) {
				$difficulty_term = wp_insert_term(
					ucfirst( $_tutor_level ),
					'course_difficulty',
					array( 'slug' => $_tutor_level )
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
	 * Migrates course categories from Tutor LMS to Masteriyo.
	 *
	 * This public static function retrieves the course categories associated with a given course from Tutor LMS
	 * and assigns them to the same course in Masteriyo.
	 *
	 * @since 1.13.0
	 *
	 * @param int $course_id The ID of the course for which categories are to be migrated.
	 *                      This should be the Masteriyo course ID which corresponds to the Tutor LMS course.
	 *
	 * @return void This public static function does not return anything. It operates by side effect, updating the course taxonomy.
	 */
	public static function migrate_course_categories_from_tutor_to_masteriyo( $course_id, $taxonomy = 'course-category' ) {
		$categories = wp_get_post_terms( $course_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$masteriyo_categories = array();
			$category_hierarchy   = array();

			// Step 1: Build category hierarchy
			foreach ( $categories as $cat_id ) {
				$cat = get_term( $cat_id, $taxonomy );

				if ( is_object( $cat ) && ! is_wp_error( $cat ) ) {
					$category_hierarchy[ $cat->term_id ] = array(
						'name'   => $cat->name,
						'parent' => $cat->parent,
					);
				}
			}

			// Handle top-level categories
			foreach ( $category_hierarchy as $cat_id => $cat_info ) {
				if ( 0 == $cat_info['parent'] ) {
					$masteriyo_cat_id = term_exists( $cat_info['name'], 'course_cat' );

					if ( 0 === $masteriyo_cat_id || null === $masteriyo_cat_id ) {
						$masteriyo_cat = wp_insert_term(
							$cat_info['name'],
							'course_cat'
						);

						if ( ! is_wp_error( $masteriyo_cat ) ) {
									$masteriyo_cat_id = $masteriyo_cat['term_id'];
									$image_id         = get_term_meta( $cat_id, 'thumbnail_id', true );
							if ( $image_id ) {
								add_term_meta( $masteriyo_cat_id, '_featured_image', $image_id );
							}
						}
					} elseif ( is_array( $masteriyo_cat_id ) ) {
						$masteriyo_cat_id = $masteriyo_cat_id['term_id'];
					}

					$masteriyo_categories[ $cat_id ] = (int) $masteriyo_cat_id;
				}
			}

			// Handle child categories
			foreach ( $category_hierarchy as $cat_id => $cat_info ) {
				if ( 0 != $cat_info['parent'] ) {
					$parent_id        = isset( $masteriyo_categories[ $cat_info['parent'] ] ) ? $masteriyo_categories[ $cat_info['parent'] ] : 0;
					$masteriyo_cat_id = term_exists( $cat_info['name'], 'course_cat' );

					if ( 0 === $masteriyo_cat_id || null === $masteriyo_cat_id ) {
						$masteriyo_cat = wp_insert_term(
							$cat_info['name'],
							'course_cat',
							array( 'parent' => $parent_id )
						);

						if ( ! is_wp_error( $masteriyo_cat ) ) {
							$masteriyo_cat_id = $masteriyo_cat['term_id'];
							$image_id         = get_term_meta( $cat_id, 'thumbnail_id', true );
							if ( $image_id ) {
								add_term_meta( $masteriyo_cat_id, '_featured_image', $image_id );
							}
						}
					} elseif ( is_array( $masteriyo_cat_id ) ) {
						$masteriyo_cat_id = $masteriyo_cat_id['term_id'];
					}

					$masteriyo_categories[ $cat_id ] = (int) $masteriyo_cat_id;
				}
			}

			// Set migrated categories to the course
			if ( ! empty( $masteriyo_categories ) ) {
				wp_set_object_terms( $course_id, array_values( $masteriyo_categories ), 'course_cat', false );
			}
		}
	}



	/**
	 * get course id by tutor course id
	 *
	 * @since 1.13.0
	 *
	 * @param number $course_id
	 * @return object|null
	 */
	public static function get_enrolled_users_by_course_id( $course_id ) {
		global $wpdb;

		$posts_table = $wpdb->prefix . 'posts';
		$users_table = $wpdb->prefix . 'users';

		// Prepare the SQL query with aliases for readability
		$sql = "SELECT p.ID, p.post_title,p.post_date, u.user_login, u.user_email
            FROM {$posts_table} AS p
            INNER JOIN {$users_table} AS u ON p.post_author = u.ID
            WHERE p.post_parent = %d
            AND p.post_type = 'tutor_enrolled'";

		$prepared_sql = $wpdb->prepare( $sql, $course_id );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results      = $wpdb->get_results( $prepared_sql );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $results;
	}

	/**
	 * get tutor course reviews
	 *
	 * @since 1.13.0
	 *
	 * @return array|object|null
	 */
	public static function get_tutor_course_reviews() {
		global $wpdb;

		$select_columns =
		'_reviews.comment_ID,
		_reviews.comment_post_ID,
		_reviews.comment_author,
		_reviews.comment_author_email,
		_reviews.comment_date,
		_reviews.comment_content,
		_reviews.comment_approved AS comment_status,
		_reviews.user_id,
		_reviews.comment_parent,
		_rev_meta.meta_value AS rating,
		_reviewer.display_name';

		$query = $wpdb->prepare(
			"SELECT {$select_columns}
		FROM 	{$wpdb->comments} _reviews
				INNER JOIN {$wpdb->commentmeta} _rev_meta
					ON _reviews.comment_ID = _rev_meta.comment_id
				LEFT JOIN {$wpdb->users} _reviewer
					ON _reviews.user_id = _reviewer.ID
		WHERE  _reviews.comment_type = 'tutor_course_rating'
 				AND _rev_meta.meta_key = 'tutor_rating'
		ORDER BY _reviews.comment_ID"
		);

		return $wpdb->get_results( $query );
	}

	/**
	 * Processes migration for a single Tutor quiz question.
	 *
	 * @since 1.13.0
	 *
	 * @param object $question Tutor quiz question data.
	 * @param int $quiz_id Tutor quiz ID.
	 * @param int $course_id Masteriyo course ID.
	 */
	public static function process_question_migration_from_tutor( $question, $quiz_id, $course_id, $total_mark ) {
		$question_type = null;
		if ( 'true_false' === $question->question_type ) {
			$question_type = QuestionType::TRUE_FALSE;
		} elseif ( 'single_choice' === $question->question_type ) {
			$question_type = QuestionType::SINGLE_CHOICE;
		} elseif ( 'multiple_choice' === $question->question_type ) {
			$question_type = QuestionType::MULTIPLE_CHOICE;
		} elseif ( 'fill_in_the_blank' === $question->question_type ) {
			$question_type = 'fill-in-the-blanks';
		} elseif ( 'fill_in_the_blank' === $question->question_type ) {
			$question_type = 'fill-in-the-blanks';
		} elseif ( 'open_ended' === $question->question_type || 'short_answer' === $question_type ) {
			$question_type = 'text-answer';
		}

		if ( $question_type ) {
			$answers_tutor        = static::answer_list_by_question( $question->question_id, $question->question_type );
			$question_description = sanitize_text_field( $question->question_description ) ?? '';
			$answers              = array();
			if ( $question_type ) {
				$answers_tutor        = static::answer_list_by_question( $question->question_id, $question->question_type );
				$question_description = sanitize_text_field( $question->question_description ) ?? '';
				$answers              = array();

				if ( ! empty( $answers_tutor ) ) {
					foreach ( $answers_tutor as $answer_tutor ) {

						if ( 'true_false' === $answer_tutor->belongs_question_type ) {
							$answers[] = array(
								'name'    => $answer_tutor->answer_title,
								'correct' => masteriyo_string_to_bool( $answer_tutor->is_correct ),
							);
						}

						if ( 'single_choice' === $answer_tutor->belongs_question_type ) {

							$answers[] = array(
								'name'               => $answer_tutor->answer_title,
								'correct'            => masteriyo_string_to_bool( $answer_tutor->is_correct ),
								'answer_view_format' => $answer_tutor->answer_view_format ?? '',
								'image_id'           => $answer_tutor->image_id ?? '',
							);
						}
						if ( 'multiple_choice' === $answer_tutor->belongs_question_type ) {
							$answers[] = array(
								'name'               => $answer_tutor->answer_title,
								'correct'            => masteriyo_string_to_bool( $answer_tutor->is_correct ),
								'answer_view_format' => $answer_tutor->answer_view_format ?? '',
								'image_id'           => $answer_tutor->image_id ?? '',
							);
						}
						if ( 'fill_in_the_blank' === $answer_tutor->belongs_question_type ) {
							$answers = '{{' . $answer_tutor->answer_two_gap_match . '}}';
						}
						if ( 'open_ended' === $question_type || 'short_answer' === $question_type ) {
							$answers[] = array(
								'name' => $answer_tutor->answer_title,
							);
						}
					}
				}

				$question_array = array(
					'post_type'    => PostType::QUESTION,
					'post_title'   => $question->question_title,
					'post_content' => wp_json_encode( $answers ),
					'post_excerpt' => $question_description,
					'post_status'  => PostStatus::PUBLISH,
					'post_author'  => $question->post_author ?? '',
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
	}

	/**
	 * gets the list of answers by question id.
	 *
	 * @since 1.13.0
	 *
	 * @param object $question_id Tutor quiz question id.
	 * @param int $question_type Tutor question type.
	 */
	public static function answer_list_by_question( int $question_id, string $question_type ): array {
		global $wpdb;
		$answers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}tutor_quiz_question_answers
			where belongs_question_id = %d
				AND belongs_question_type = %s
			order by answer_order asc ;",
				$question_id,
				$question_type
			)
		);
		return is_array( $answers ) && count( $answers ) ? $answers : array();
	}



	/**
	 * Extract video source from video data from Tutor LMS.
	 *
	 * @since 1.13.0
	 *
	 * @param [type] $post_id
	 * @return void
	 */
	public static function extract_video_source( $post_id ) {
		$video_data       = maybe_unserialize( get_post_meta( $post_id, '_video', true ) );
		$video_source     = '';
		$video_source_url = '';
		$video_id         = 0;

		// Check if the $video_data is actually an array and contains the necessary data.
		if ( is_array( $video_data ) ) {
			// Check for YouTube source.
			if ( isset( $video_data['source_youtube'] ) && '' !== $video_data['source_youtube'] ) {
				$video_source     = 'youtube';
				$video_source_url = $video_data['source_youtube'];
			} elseif ( isset( $video_data['source_vimeo'] ) && '' !== $video_data['source_vimeo'] ) {
				$video_source     = 'vimeo';
				$video_source_url = $video_data['source_vimeo'];
			} elseif ( isset( $video_data['source_embedded'] ) && '' !== $video_data['source_embedded'] ) {
				$video_source     = 'embed-video';
				$video_source_url = $video_data['source_embedded'];
			} elseif ( isset( $video_data['source_external_url'] ) && '' !== $video_data['source_external_url'] ) {
				$video_source     = 'external';
				$video_source_url = $video_data['source_external_url'];
			} elseif ( isset( $video_data['source'] ) && 'html5' === $video_data['source'] ) {
				$video_source = 'self-hosted';
			}

			if ( isset( $video_data['source_video_id'] ) && '' !== $video_data['source_video_id'] ) {
				$video_source_url = $video_data['source_video_id'];
			}
		}

		// Update post meta correctly.
		update_post_meta( $post_id, '_video_source', $video_source );
		update_post_meta( $post_id, '_video_source_url', $video_source_url );
		update_post_meta( $post_id, '_video_source_id', $video_id );
	}
	// embed-video
	/**
	 * Add the masteriyo_student user role.
	 *
	 * @since 1.13.0
	 *
	 * @param int $user_id User ID.
	 */
	public static function update_user_role_to_masteriyo_student( $user_id ) {
		$user = new \WP_User( $user_id );

		if ( ! $user || ! isset( $user->ID ) || 0 === $user->ID || ! $user->exists() ) {
			return;
		}

		if (
		! in_array( Roles::ADMIN, (array) $user->roles, true ) &&
		! in_array( Roles::MANAGER, (array) $user->roles, true ) &&
		! in_array( Roles::STUDENT, (array) $user->roles, true ) &&
		! in_array( Roles::INSTRUCTOR, (array) $user->roles, true )
		) {
			$user->add_role( Roles::STUDENT );
			delete_user_meta( $user->get_id(), '_is_tutor_student' );
		}
	}


	// /**
	//  * Add the masteriyo_instructor user role.
	//  *
	//  * @since 1.13.0
	//  *
	//  * @param int $user_id User ID.
	//  * @return void
	//  */
	// public static function update_tutor_instructor_to_masteriyo( $user_id ) {

	// 	if ( ! $user || ! isset( $user->ID ) || 0 === $user->ID || ! $user->exists() ) {
	// 		return;
	// 	}

	// 	if (
	// 	! in_array( Roles::ADMIN, (array) $user->roles, true ) &&
	// 	! in_array( Roles::MANAGER, (array) $user->roles, true ) &&
	// 	! in_array( Roles::INSTRUCTOR, (array) $user->roles, true )
	// 	) {

	// 	}
	// }


	/**
	 * get tutor announcements data
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	public static function get_tutor_announcements_data() {
		$args = array(
			'post_type'      => 'tutor_announcements',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderBy'        => 'ID',
			'order'          => sanitize_text_field( 'DESC' ),
		);

		$the_query     = new WP_Query( $args );
		$announcements = $the_query->have_posts() ? $the_query->posts : array();
		return $announcements;
	}


	/**
	 * Gets the tutor lms course announcements.
	 *
	 * @since 1.13.0
	 *
	 * @return void
	 */
	public static function get_and_update_tutor_announcements() {
		$announcements = static::get_tutor_announcements_data();

		if ( 1 > count( $announcements ) ) {
			return static::generate_migration_response_from_tutor( 'questions_n_answers', $announcements, $announcements );
		}

		foreach ( $announcements as $announcement ) {
			$masteriyo_announcement = array(
				'ID'          => $announcement->ID,
				'post_type'   => PostType::COURSEANNOUNCEMENT,
				'post_status' => PostStatus::PUBLISH,
				'post_author' => $announcement->post_author,
				'ping_status' => 'closed',
			);

			wp_update_post( $masteriyo_announcement );
			update_post_meta( $announcement->ID, '_course_id', $announcement->post_parent );

			$updated_announcements = array_filter(
				$announcements,
				function ( $current_announcement ) use ( $announcement ) {
					return $current_announcement->ID !== $announcement->ID;
				}
			);

			$type = 'announcement';

			if ( 1 > count( $updated_announcements ) ) {
				$type = 'questions_n_answers';
			}

			$response = static::generate_migration_response_from_tutor( $type, $announcement, $updated_announcements );

			update_option( 'masteriyo_remaining_migrated_items', wp_json_encode( $updated_announcements ) );

			return rest_ensure_response( $response );
		}
	}

	public static function convert_wc_status( $status ) {
		$map = array(
			'processing'    => OrderStatus::PROCESSING,
			'pending'       => OrderStatus::PENDING,
			'cancelled'     => OrderStatus::CANCELLED,
			'on-hold'       => OrderStatus::ON_HOLD,
			'completed'     => OrderStatus::COMPLETED,
			'refunded'      => OrderStatus::REFUNDED,
			'failed'        => OrderStatus::FAILED,
			'wc-processing' => OrderStatus::PROCESSING,
			'wc-pending'    => OrderStatus::PENDING,
			'wc-cancelled'  => OrderStatus::CANCELLED,
			'wc-on-hold'    => OrderStatus::ON_HOLD,
			'wc-completed'  => OrderStatus::COMPLETED,
			'wc-refunded'   => OrderStatus::REFUNDED,
			'wc-failed'     => OrderStatus::FAILED,
		);

		$new_status = isset( $map[ $status ] ) ? $map[ $status ] : 'pending';

		return OrderStatus::PROCESSING === $new_status ? 'pending' : $new_status;
	}

	/**
	 * Order searched based on the user id and
	 * insert into masteriyo order.
	 *
	 * @since 1.13.0
	 */
	public static function insert_custom_order( $order_datas, $course_id ) {
		global $wpdb;
		$monetize_by = \tutor_utils()->get_option( 'monetize_by' );
		if ( 'wc' === $monetize_by ) {
			$order_data = $order_datas;

			$order_date     = new DateTime( $order_data->get_date_created() );
			$formatted_date = $order_date->format( _x( 'M d, Y @ h:i A', 'Order date parsed by DateTime::format', 'learning-management-system' ) );
			$title          = sprintf( __( 'Order - %s', 'learning-management-system' ), $formatted_date );

			$post_id = wp_insert_post(
				array(
					'post_title'    => $title,
					'post_status'   => static::convert_wc_status( $order_datas->get_status() ),
					'post_type'     => PostType::ORDER,
					'post_date'     => $order_data->get_date_created(),
					'post_password' => masteriyo_generate_order_key(),
					'post_content'  => 'Order details for customer ID ' . $order_data->customer_id,
				)
			);

			$item_data = array(
				'order_item_name' => get_the_title( $course_id ),
				'order_item_type' => 'course',
				'order_id'        => $post_id,
			);
			$wpdb->insert( $wpdb->prefix . 'masteriyo_order_items', $item_data );
			$order_item_id = absint( $wpdb->insert_id );

			$mto_item_metas = array(
				array(
					'order_item_id' => $order_item_id,
					'meta_key'      => 'course_id',
					'meta_value'    => $course_id,
				),
				array(
					'order_item_id' => $order_item_id,
					'meta_key'      => 'quantity',
					'meta_value'    => $order_datas->get_item_count(),
				),
				array(
					'order_item_id' => $order_item_id,
					'meta_key'      => 'subtotal',
					'meta_value'    => $order_datas->get_subtotal(),
				),
				array(
					'order_item_id' => $order_item_id,
					'meta_key'      => 'total',
					'meta_value'    => $order_datas->get_total(),
				),
			);

			$table_name = $wpdb->prefix . 'masteriyo_order_itemmeta';

			foreach ( $mto_item_metas as $item_meta ) {
				$wpdb->insert( $table_name, $item_meta );
			}

			$meta_keys = array(
				'_total'                => $order_data->get_total(),
				'_currency'             => $order_data->get_currency(),
				'_transaction_id'       => $order_data->get_order_key(),
				'_date_paid'            => $order_data->get_date_created(),
				'_customer_id'          => $order_data->get_customer_id(),
				'_customer_ip_address'  => $order_data->get_customer_ip_address(),
				'_customer_user_agent'  => $order_data->get_customer_user_agent(),
				'_payment_method'       => $order_data->get_payment_method(),
				'_payment_method_title' => $order_data->get_payment_method_title(),
				'_billing_email'        => $order_data->get_billing_email(),
				'_quantity'             => $order_data->get_item_count(),
				'_prices_include_tax'   => $order_data->get_prices_include_tax(),
				'_billing_first_name'   => $order_data->get_billing_first_name(),
				'_billing_last_name'    => $order_data->get_billing_last_name(),
				'_billing_company'      => $order_data->get_billing_company(),
				'_billing_address_1'    => $order_data->get_billing_address_1(),
				'_billing_address_2'    => $order_data->get_billing_address_2(),
				'_billing_city'         => $order_data->get_billing_city(),
				'_billing_postcode'     => $order_data->get_billing_postcode(),
				'_billing_country'      => $order_data->get_billing_country(),
				'_billing_state'        => $order_data->get_shipping_state(),
				'_billing_email'        => $order_data->get_billing_email(),
				'_billing_phone'        => $order_data->get_billing_phone(),
			);
			if ( ! empty( $order_data->get_customer_note() ) ) {
				$meta_keys['_customer_note'] = $order_data->get_customer_note();
			}
		}

		/**
		 * Update Masteriyo order meta.
		 *
		 * @since 1.13.0
		 */
		foreach ( $meta_keys as $key => $value ) {
			if ( ! empty( $value ) ) {
				update_post_meta( $post_id, $key, $value );
			}
		}

		$wpdb->update(
			"{$wpdb->prefix}wc_orders",
			array( 'status' => 'trash' ),
			array( 'id' => $order_datas->get_id() )
		);
	}

	/**
	 * Get the duration of a Tutor post in minutes.
	 *
	 * Parses the duration meta field of a Tutor post and converts it to minutes.
	 *
	 * @since 1.13.0
	 *
	 * @param int $post_id The ID of the Tutor post.
	 * @return int Returns the duration in minutes. Returns 0 if the duration is not valid or not set.
	 */
	public static function convert_time_limit_to_minutes( $time_limit ) {
		$time_value = isset( $time_limit['time_value'] ) ? (int) $time_limit['time_value'] : 0;
		$time_type  = isset( $time_limit['time_type'] ) ? $time_limit['time_type'] : '';

		switch ( $time_type ) {
			case 'hours':
				return $time_value * 60;

			case 'days':
				return $time_value * 24 * 60;

			case 'time_limit_seconds':
				return $time_value / 60;

			case 'minutes':
			default:
				return $time_value;
		}
	}

	/**
	 * remove students data related to tutor
	 *
	 * @return void
	 */
	public static function remove_student_data_from_tutor() {
		global $wpdb;

		// Step 1: Identify all post IDs of type 'tutor_enrolled'
		$post_ids_to_delete = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
						WHERE post_type = %s",
				'tutor_enrolled'
			)
		);

		if ( ! empty( $post_ids_to_delete ) ) {
			// Convert the post IDs into a comma-separated list
			$post_ids_placeholder = implode( ',', array_fill( 0, count( $post_ids_to_delete ), '%d' ) );

			// Step 2: Delete all posts of type 'tutor_enrolled'
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->posts}
									WHERE ID IN ($post_ids_placeholder)",
					$post_ids_to_delete
				)
			);

			// Step 3: Optionally delete related data from postmeta table
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta}
							WHERE post_id IN ($post_ids_placeholder)",
					$post_ids_to_delete
				)
			);

		}

	}

	public static function remove_tutor_instructor_related_data() {
		global $wpdb;

		// Get all instructor user IDs based on static criteria
		$wpdb->query(
			"DELETE FROM {$wpdb->usermeta}
			 WHERE meta_key = '_tutor_instructor_status'
				 AND meta_value IN ('approved', 'pending', 'blocked')"
		);

	}

}
