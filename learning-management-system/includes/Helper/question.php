<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Question functions.
 *
 * @since 1.0.0
 */

use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuestionType;

/**
 * Get questions
 *
 * @since 1.0.0
 *
 * @param array $args Query arguments.
 *
 * @return Masteriyo\Models\Question[]
 */
function masteriyo_get_questions( $args = array() ) {
	$questions = masteriyo( 'query.questions' )->set_args( $args )->get_questions();

	/**
	 * Filters queried question objects.
	 *
	 * @since 1.0.0
	 *
	 * @param Masteriyo\Models\Question[] $questions Queried question objects.
	 * @param array $query_args Query args.
	 */
	return apply_filters( 'masteriyo_get_questions', $questions, $args );
}

/**
 * Get question.
 *
 * @since 1.0.0
 *
 * @param int|Masteriyo\Models\Question|WP_Post $question Question id or Question Model or Post.
 *
 * @return Masteriyo\Models\Question\Question|null
 */
function masteriyo_get_question( $question ) {
	if ( is_int( $question ) ) {
		$id = $question;
	} else {
		$id = is_a( $question, '\WP_Post' ) ? $question->ID : $question->get_id();
	}

	if ( is_a( $question, 'Masteriyo\Models\Question' ) ) {
		$id = $question->get_id();
	} elseif ( is_a( $question, 'WP_Post' ) ) {
		$id = $question->ID;
	} else {
		$id = $question;
	}

	try {
		$type           = get_post_meta( $id, '_type', true );
		$question_obj   = masteriyo( empty( $type ) ? 'question' : "question.{$type}" );
		$question_store = masteriyo( 'question.store' );

		$id = absint( $id );
		$question_obj->set_id( $id );
		$question_store->read( $question_obj );
	} catch ( \Exception $e ) {
		return null;
	}

	/**
	 * Filters question object.
	 *
	 * @since 1.0.0
	 *
	 * @param Masteriyo\Models\Question|null $question_obj The question object.
	 * @param int|Masteriyo\Models\Question|WP_Post $question Question id or Question Model or Post.
	 */
	return apply_filters( 'masteriyo_get_question', $question_obj, $question );
}


/**
 * Get the number of questions of a quiz.
 *
 * @since 1.0.0
 * @since 1.5.15 Return zero instead of WP_Error
 *
 * @param int|Question|WP_Post $question Question id or Question Model or Post.
 *
 * @return int
 */
function masteriyo_get_questions_count_by_quiz( $quiz ) {
	$quiz = masteriyo_get_quiz( $quiz );

	// Bail early if there is error.
	if ( is_null( $quiz ) ) {
		return 0;
	}

	$query = new \WP_Query(
		array(
			'post_type'    => 'mto-question',
			'post_status'  => PostStatus::PUBLISH,
			'post_parent'  => $quiz->get_id(),
			'meta_key'     => '_type',
			'meta_value'   => QuestionType::all(),
			'meta_compare' => 'IN',
		)
	);

	return absint( $query->found_posts );
}


if ( ! function_exists( 'masteriyo_get_all_questions_count_by_quiz' ) ) {
	/**
	 * Get the count of all questions associated with a quiz.
	 *
	 * This function retrieves the total number of questions linked to a specific quiz,
	 * either directly through the post parent or via a meta value pattern match.
	 *
	 * @since 1.17.0
	 *
	 * @param int|WP_Post|Masteriyo\Models\Quiz $quiz Quiz ID, WP_Post, or Quiz model.
	 * @return int The number of questions associated with the quiz.
	 */
	function masteriyo_get_all_questions_count_by_quiz( $quiz ) {
		$quiz = masteriyo_get_quiz( $quiz );

		if ( is_null( $quiz ) ) {
			return 0;
		}

		global $wpdb;

		$quiz_id = $quiz->get_id();

		try {
			$query = $wpdb->prepare(
				"SELECT COUNT(*)
					FROM {$wpdb->posts} q
					LEFT JOIN {$wpdb->prefix}masteriyo_quiz_question_rel qr
						ON q.ID = qr.question_id AND qr.quiz_id = %d
					WHERE q.post_type = 'mto-question' AND q.post_status = 'publish'
					AND (qr.quiz_id = %d OR q.post_parent = %d)",
				$quiz_id,
				$quiz_id,
				$quiz_id
			);

			$count = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} catch ( \Exception $e ) {
			$count = 0;
		}

		return $count ? intval( $count ) : 0;
	}
}

if ( ! function_exists( 'masteriyo_get_all_question_ids_by_quiz' ) ) {
	/**
	 * Get all question IDs by quiz, considering `menu_order` from the custom table
	 * and falling back to `wp_posts` if a question doesn't exist in the table.
	 *
	 * @since 1.17.0
	 *
	 * @param int|WP_Post|Masteriyo\Models\Quiz $quiz Quiz ID, WP_Post or Quiz model.
	 *
	 * @return int[]
	 */
	function masteriyo_get_all_question_ids_by_quiz( $quiz ) {
		$quiz = masteriyo_get_quiz( $quiz );

		if ( ! $quiz ) {
			return array();
		}

		global $wpdb;

		$quiz_id = $quiz->get_id();

		try {
			$query = $wpdb->prepare(
				"SELECT q.ID
					FROM {$wpdb->posts} q
					LEFT JOIN {$wpdb->prefix}masteriyo_quiz_question_rel qr
						ON q.ID = qr.question_id AND qr.quiz_id = %d
					WHERE q.post_type = 'mto-question' AND q.post_status = 'publish'
					AND (qr.quiz_id = %d OR q.post_parent = %d)",
				$quiz_id,
				$quiz_id,
				$quiz_id
			);

			$results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		} catch ( Exception $e ) {
			return array();
		}

		if ( is_null( $results ) ) {
			return array();
		}

		return array_map( 'intval', wp_list_pluck( $results, 'ID' ) );
	}
}

if ( ! function_exists( 'masteriyo_add_question_to_quiz' ) ) {
	/**
	 * Adds a question to a quiz with a specified menu order.
	 *
	 * This function inserts or updates a record in the quiz-question relationship table,
	 * associating a question with a quiz and assigning it a menu order.
	 *
	 * @since 1.17.0
	 *
	 * @param int $quiz_id The ID of the quiz.
	 * @param int $question_id The ID of the question to add.
	 * @param int $menu_order Optional. The menu order for the question in the quiz. Default 0.
	 *
	 * @return int|false The number of rows affected, or false on error.
	 */
	function masteriyo_add_question_to_quiz( $quiz_id, $question_id, $menu_order = 0 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'masteriyo_quiz_question_rel';

		try {
			$result = $wpdb->replace(
				$table_name,
				array(
					'quiz_id'     => $quiz_id,
					'question_id' => $question_id,
					'menu_order'  => $menu_order,
				),
				array( '%d', '%d', '%d' )
			);
		} catch ( \Exception $e ) {
			return false;
		}

		return $result;
	}
}

if ( ! function_exists( 'masteriyo_remove_questions_from_bank' ) ) {
	/**
	 * Removes a question from the question bank.
	 *
	 * This function deletes a question from the quiz-question relationship table
	 * using its question ID.
	 *
	 * @since 1.17.0
	 *
	 * @param int $question_id The ID of the question to remove.
	 *
	 * @return int|false The number of rows affected, or false on error.
	 */
	function masteriyo_remove_questions_from_bank( $question_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'masteriyo_quiz_question_rel';

		try {
			$result = $wpdb->delete(
				$table_name,
				array(
					'question_id' => $question_id,
				),
				array( '%d' )
			);
		} catch ( \Exception $e ) {
			return false;
		}

		return $result;
	}
}

if ( ! function_exists( 'masteriyo_remove_question_from_quiz' ) ) {
	/**
	 * Removes a question from a quiz.
	 *
	 * This function deletes a record from the quiz-question relationship table,
	 * disassociating a question from a quiz.
	 *
	 * @since 1.17.0
	 *
	 * @param int $quiz_id The ID of the quiz.
	 * @param int $question_id The ID of the question to remove.
	 *
	 * @return int|false The number of rows affected, or false on error.
	 */
	function masteriyo_remove_question_from_quiz( $quiz_id, $question_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'masteriyo_quiz_question_rel';

		try {
			$result = $wpdb->delete(
				$table_name,
				array(
					'quiz_id'     => $quiz_id,
					'question_id' => $question_id,
				),
				array( '%d', '%d' )
			);
		} catch ( \Exception $e ) {
			return false;
		}

		return $result;
	}
}

if ( ! function_exists( 'masteriyo_get_questions_for_quiz' ) ) {
	/**
	 * Retrieves an array of question IDs associated with a quiz in the correct order.
	 *
	 * @since 1.17.0
	 *
	 * @param int $quiz_id The ID of the quiz.
	 *
	 * @return int[] An array of question IDs.
	 */
	function masteriyo_get_questions_for_quiz( $quiz_id ) {
		global $wpdb;

		try {
			$results = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT question_id
				 FROM {$wpdb->prefix}masteriyo_quiz_question_rel
				 WHERE quiz_id = %d
				 ORDER BY menu_order ASC",
					$quiz_id
				)
			);
		} catch ( \Exception $e ) {
			return array();
		}

		return $results;
	}
}

if ( ! function_exists( 'masteriyo_is_question_linked_to_quiz' ) ) {
	/**
	 * Check if a question is linked to a specific quiz.
	 *
	 * @since 1.17.0
	 *
	 * @param int $quiz_id Quiz ID.
	 * @param int $question_id Question ID.
	 *
	 * @return bool True if the question is linked to the quiz, false otherwise.
	 */
	function masteriyo_is_question_linked_to_quiz( $quiz_id, $question_id ) {
			global $wpdb;

			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
							FROM {$wpdb->prefix}masteriyo_quiz_question_rel
							WHERE quiz_id = %d AND question_id = %d",
					absint( $quiz_id ),
					absint( $question_id )
				)
			);

			return ( $exists > 0 );
	}
}

if ( ! function_exists( 'masteriyo_get_question_menu_order' ) ) {
	/**
	 * Get the menu order of a specific question for a given quiz.
	 *
	 * @since 1.17.0
	 *
	 * @param int $quiz_id Quiz ID.
	 * @param int $question_id Question ID.
	 *
	 * @return int|null Menu order of the question. Returns null if not found.
	 */
	function masteriyo_get_question_menu_order( $quiz_id, $question_id ) {
			global $wpdb;

			$menu_order = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT menu_order
							FROM {$wpdb->prefix}masteriyo_quiz_question_rel
							WHERE quiz_id = %d AND question_id = %d",
					absint( $quiz_id ),
					absint( $question_id )
				)
			);

			return ( null !== $menu_order ) ? absint( $menu_order ) : null;
	}
}

if ( ! function_exists( 'masteriyo_get_questions_by_quiz_with_pagination' ) ) {
	/**
	 * Retrieves an array of question IDs associated with a quiz in the correct order with pagination.
	 *
	 * @since  1.17.0
	 *
	 * @param array $query_args {
	 *     Array of query arguments.
	 *
	 *     @type int $post_parent     The parent post ID (quiz ID).
	 *     @type int $posts_per_page  Number of posts per page.
	 *     @type int $paged           Current page number.
	 * }
	 *
	 * @return array Returns an array with 'objects' and 'meta' keys.
	 */
	function masteriyo_get_questions_by_quiz_with_pagination( $query_args ) {
		global $wpdb;

		$quiz_id  = isset( $query_args['post_parent'] ) ? (int) $query_args['post_parent'] : 0;
		$per_page = isset( $query_args['posts_per_page'] ) ? (int) $query_args['posts_per_page'] : 10;
		$page     = isset( $query_args['paged'] ) ? (int) $query_args['paged'] : 1;
		$total    = 0;
		$offset   = ( $page - 1 ) * $per_page;

		if ( ! $quiz_id ) {
			return array(
				'objects' => array(),
				'meta'    => array(
					'total'        => $total,
					'pages'        => 0,
					'current_page' => $page,
					'per_page'     => $per_page,
				),
			);
		}

		try {
			$total = masteriyo_get_all_questions_count_by_quiz( $quiz_id );

			$sql = "SELECT q.ID,
					IFNULL(qr.menu_order, q.menu_order) AS question_order
				FROM {$wpdb->posts} q
				LEFT JOIN {$wpdb->prefix}masteriyo_quiz_question_rel qr
					ON q.ID = qr.question_id AND qr.quiz_id = %d
				WHERE q.post_type = 'mto-question' AND q.post_status = 'publish'
				AND (qr.quiz_id = %d OR q.post_parent = %d)
				ORDER BY question_order ASC
				LIMIT %d OFFSET %d";

			$results = array();

			if ( $total > 0 ) {
				$results = $wpdb->get_results( $wpdb->prepare( $sql, $quiz_id, $quiz_id, $quiz_id, $per_page, $offset ), ARRAY_A ); // phpcs:ignore
			}
		} catch ( Exception $e ) {
			$results = array();
		}

		return array(
			'objects'      => array_filter(
				array_map(
					function ( $result ) {
						if ( ! isset( $result['ID'] ) ) {
							return null;
						}

						return masteriyo_get_question( absint( $result['ID'] ) );
					},
					$results
				)
			),
			'total'        => (int) $total,
			'pages'        => (int) ceil( $total / (int) $per_page ),
			'current_page' => $page,
			'per_page'     => $per_page,
		);
	}
}
