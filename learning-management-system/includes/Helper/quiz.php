<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}


/**
 * Masteriyo Quiz Functions
 *
 * Functions for quiz specific things.
 *
 * @package Masteriyo\Functions
 * @version 1.0.0
 */

use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuizAttemptStatus;
use Masteriyo\Models\QuizAttempt;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\QuizAttemptQuery;

/**
 * Get quiz question.
 *
 * @since  1.0.0
 *
 * @since 1.5.37 Remove second parameter "$by".
 *
 * @param integer $quiz_id Quiz ID.
 * @return \Masteriyo\Models\Question[]
 */
function masteriyo_get_quiz_questions( $quiz_id ) {

	$query = new \WP_Query(
		array(
			'post_type'      => PostType::QUESTION,
			'posts_per_page' => -1,
			'post_status'    => PostStatus::PUBLISH,
			'post_parent'    => $quiz_id,
		)
	);

	return array_filter( array_map( 'masteriyo_get_question', $query->posts ) );
}

/**
 * Determine if there is any started quiz exists.
 *
 * @since 1.0.0
 *
 * @param int $quiz_id
 *
 * @return array|null|object
 */
function masteriyo_is_quiz_started( $quiz_id = 0 ) {

	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return false;
	}

	$query = new QuizAttemptQuery(
		array(
			'user_id' => $user_id,
			'quiz_id' => $quiz_id,
			'status'  => QuizAttemptStatus::STARTED,
			'limit'   => 1,
		)
	);

	$attempt    = $query->get_quiz_attempts();
	$is_started = empty( $attempt ) ? false : current( $attempt );

	return $is_started;
}

/**
 * Determine if there is any started quiz exists.
 *
 * @since 1.8.0 [free]
 *
 * @param int $course_id
 * @param int $quiz_id
 *
 * @return array|null|object
 */
function masteriyo_is_course_quiz_started( $course_id, $quiz_id = 0 ) {

	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return false;
	}

	$query = new QuizAttemptQuery(
		array(
			'user_id'   => $user_id,
			'quiz_id'   => $quiz_id,
			'course_id' => $course_id,
			'status'    => QuizAttemptStatus::STARTED,
			'limit'     => 1,
		)
	);

	$attempt    = $query->get_quiz_attempts();
	$is_started = empty( $attempt ) ? false : current( $attempt );

	return $is_started;
}

/**
 * Get the ID of the quiz the current user (or guest) currently has an in-progress attempt for, in a
 * given course. Logged-in attempts are read from the database; guest attempts from the session.
 *
 * @since x.x.x
 *
 * @param int $course_id The course ID.
 *
 * @return int The active (started) quiz ID, or 0 if there is no in-progress attempt.
 */
function masteriyo_get_started_quiz_id_for_course( $course_id ) {
	$course_id = (int) $course_id;

	if ( is_user_logged_in() ) {
		$started_attempt = masteriyo_is_course_quiz_started( $course_id );

		return $started_attempt ? (int) $started_attempt->get_quiz_id() : 0;
	}

	// Guests: in-progress attempts are kept in the session, keyed by quiz ID.
	$session = masteriyo( 'session' );

	if ( ! $session ) {
		return 0;
	}

	$all_attempts = $session->get( 'quiz_attempts', array() );

	if ( empty( $all_attempts ) || ! is_array( $all_attempts ) ) {
		return 0;
	}

	foreach ( $all_attempts as $attempts ) {
		if ( ! is_array( $attempts ) ) {
			continue;
		}

		foreach ( $attempts as $attempt ) {
			if (
				isset( $attempt['attempt_status'], $attempt['course_id'], $attempt['quiz_id'] ) &&
				QuizAttemptStatus::STARTED === $attempt['attempt_status'] &&
				(int) $attempt['course_id'] === $course_id
			) {
				return (int) $attempt['quiz_id'];
			}
		}
	}

	return 0;
}

/**
 * Check whether the current user is blocked from reading a piece of course content because the
 * course restricts content during quizzes ("Restrict Content During Quiz" / `disable_course_content`)
 * and the user has a quiz attempt in progress.
 *
 * Only the quiz being attempted (and its own questions) stays accessible; every other content item
 * is blocked. Admins, instructors and users who can edit the course are exempt. This is enforced from
 * the REST permission checks so it applies to every request, including content opened in a new tab.
 * Both logged-in and guest (session-based) quiz attempts are covered.
 *
 * @since x.x.x
 *
 * @param \Masteriyo\Models\Course $course The course the content belongs to.
 * @param int|\WP_Post             $post   The content post (or its ID) being accessed.
 *
 * @return \WP_Error|null WP_Error when access should be denied, null otherwise.
 */
function masteriyo_check_content_restriction_during_quiz( $course, $post ) {
	if ( ! $course || ! $course->get_disable_course_content() ) {
		return null;
	}

	// Admins, instructors and course editors are exempt.
	if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_instructor() ) {
		return null;
	}

	if ( is_user_logged_in() && user_can( get_current_user_id(), 'edit_course', $course->get_id() ) ) {
		return null;
	}

	$active_quiz_id = masteriyo_get_started_quiz_id_for_course( $course->get_id() );

	if ( ! $active_quiz_id ) {
		return null;
	}

	$post = get_post( $post );

	if ( ! $post ) {
		return null;
	}

	// Only the quiz being attempted (and its own questions) stays accessible — the learner must be
	// able to reach the quiz they are taking. Every other content item, including other quizzes and
	// their questions, is blocked, since that is the study material the restriction locks during a
	// quiz. A question's parent quiz is stored as its post_parent.
	if ( PostType::QUIZ === $post->post_type && (int) $post->ID === $active_quiz_id ) {
		return null;
	}

	if ( PostType::QUESTION === $post->post_type && (int) $post->post_parent === $active_quiz_id ) {
		return null;
	}

	return new \WP_Error(
		'masteriyo_rest_content_restricted_during_quiz',
		__( 'Sorry, you cannot access other course content while a quiz attempt is in progress.', 'learning-management-system' ),
		array(
			'status'    => rest_authorization_required_code(),
			'quiz_id'   => $active_quiz_id,
			'course_id' => (int) $course->get_id(),
		)
	);
}

/**
 * Get quiz attempt data according to attempt id and after attempt ended.
 *
 * @since 1.0.0
 * @since 1.4.9 Changed position of required and optional parameters as it will throw deprecated notice in php 8.0.
 *
 * @param int $id User Attempt ID.
 * @param int $quiz Quiz ID.
 *
 * @return array
 */
function masteriyo_get_quiz_attempt_ended_data( $id, $quiz_id = 0 ) {
	global $wpdb;

	$user_id = get_current_user_id();

	$attempt_data = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT *
			FROM 	{$wpdb->prefix}masteriyo_quiz_attempts
			WHERE 	id = %d
					AND user_id =  %d
					AND quiz_id = %d
					AND attempt_status = %s;
			",
			$id,
			$user_id,
			$quiz_id,
			'attempt_ended'
		)
	);

	return (array) $attempt_data;
}

/**
 *
 * Get all of the attempts by an user of a quiz.
 *
 * @since 1.0.0
 *
 * @param int $quiz_id
 * @param int $user_id
 *
 * @return array|bool|null|object
 */

function masteriyo_get_all_quiz_attempts( $quiz_id = 0, $user_id = 0 ) {
	global $wpdb;

	$user_id = $user_id ? $user_id : get_current_user_id();

	$attempts = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT *
			FROM 	{$wpdb->prefix}masteriyo_quiz_attempts
			WHERE 	quiz_id = %d
					AND user_id = %d;
			",
			$quiz_id,
			$user_id
		)
	);

	if ( is_array( $attempts ) && count( $attempts ) ) {
		return $attempts;
	}

	return false;
}

/**
 * Fetch quiz attempts.
 *
 * @since 1.0.0
 *
 * @param array $query_vars Query vars.
 * @return QuizAttempt[]
 */
function masteriyo_get_quiz_attempts( $query_vars ) {
	global $wpdb;

	$search_criteria = array();
	$sql[]           = "SELECT * FROM {$wpdb->prefix}masteriyo_quiz_attempts";

	// Construct where clause part.
	if ( ! empty( $query_vars['id'] ) ) {
		$search_criteria[] = $wpdb->prepare( 'id = %d', $query_vars['id'] );
	}

	if ( ! empty( $query_vars['course_id'] ) ) {
		$search_criteria[] = $wpdb->prepare( 'course_id = %d', $query_vars['course_id'] );
	}

	if ( ! empty( $query_vars['quiz_id'] ) ) {
		$search_criteria[] = $wpdb->prepare( 'quiz_id = %d', $query_vars['quiz_id'] );
	}

	if ( ! empty( $query_vars['user_id'] ) ) {
		$search_criteria[] = $wpdb->prepare( 'user_id = %d', $query_vars['user_id'] );
	}

	if ( ! empty( $query_vars['status'] ) ) {
		$search_criteria[] = $wpdb->prepare( 'attempt_status = %s', $query_vars['status'] );
	}

	if ( 1 <= count( $search_criteria ) ) {
		$criteria = implode( ' AND ', $search_criteria );
		$sql[]    = 'WHERE ' . $criteria;
	}

	// Construct order and order by part.
	$sql[] = 'ORDER BY ' . sanitize_sql_orderby( $query_vars['orderby'] . ' ' . $query_vars['order'] );

	// Construct limit part.
	$per_page = $query_vars['per_page'];

	if ( $query_vars['paged'] > 0 ) {
		$offset = ( $query_vars['paged'] - 1 ) * $per_page;
	}

	$sql[] = $wpdb->prepare( 'LIMIT %d, %d', $offset, $per_page );

	// Generate SQL from the SQL parts.
	$sql = implode( ' ', $sql ) . ';';

	// Fetch the results.
	$quiz_attempt = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return (array) $quiz_attempt;
}

/**
 * Get the quiz attempt by attempt ID.
 *
 * @since 1.0.0
 *
 * @since 1.5.37 Return \Masteriyo\Models\QuizAttempt object.
 *
 * @param \Masteriyo\Models\QuizAttempt|int $quiz_attempt

 * @return \Masteriyo\Models\QuizAttempt
 */
function masteriyo_get_quiz_attempt( $quiz_attempt ) {
	if ( is_a( $quiz_attempt, 'Masteriyo\Database\Model' ) ) {
		$id = $quiz_attempt->get_id();
	} else {
		$id = absint( $quiz_attempt );
	}

	try {
		$quiz_attempt_obj = masteriyo( 'quiz-attempt' );
		$quiz_attempt_obj->set_id( $id );
		$quiz_attempt_obj_repo = masteriyo( 'quiz-attempt.store' );
		$quiz_attempt_obj_repo->read( $quiz_attempt_obj );
	} catch ( \Exception $e ) {
		$quiz_attempt_obj = null;
	}

	/**
	 * Filters quiz attempt object.
	 *
	 * @since 1.5.37
	 *
	 * @param \Masteriyo\Models\QuizAttempt $quiz_attempt_obj
	 * @param int|\Masteriyo\Models\QuizAttempt $quiz_attempt
	 */
	return apply_filters( 'masteriyo_get_quiz_attempt', $quiz_attempt_obj, $quiz_attempt );
}

/**
 * Get quiz attempt count based on quiz id.
 *
 * @since 1.0.0
 *
 * @param int $quiz_id
 * @param int $user_id
 * @return int
 */
function masteriyo_get_quiz_attempt_count( $quiz_id, $user_id ) {
	global $wpdb;

	$attempt_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*)
			FROM 	{$wpdb->prefix}masteriyo_quiz_attempts
			WHERE user_id =  %d
				AND quiz_id = %d
			",
			$user_id,
			$quiz_id
		)
	);

	return absint( $attempt_count );
}

/**
 * Get quiz.
 *
 * @since 1.0.0
 *
 * @param int|Masteriyo\Models\Quiz|WP_Post $quiz Quiz id or Quiz Model or Post.
 * @return Masteriyo\Models\Quiz|null
 */
function masteriyo_get_quiz( $quiz ) {
	$quiz_obj   = masteriyo( 'quiz' );
	$quiz_store = masteriyo( 'quiz.store' );

	if ( is_a( $quiz, 'Masteriyo\Models\Quiz' ) ) {
		$id = $quiz->get_id();
	} elseif ( is_a( $quiz, 'WP_Post' ) ) {
		$id = $quiz->ID;
	} else {
		$id = $quiz;
	}

	try {
		$id = absint( $id );
		$quiz_obj->set_id( $id );
		$quiz_store->read( $quiz_obj );
	} catch ( \Exception $e ) {
		$quiz_obj = null;
	}

	/**
	 * Filters quiz object.
	 *
	 * @since 1.0.0
	 *
	 * @param Masteriyo\Models\Quiz|null $quiz_obj The quiz object.
	 * @param int|Masteriyo\Models\Quiz|WP_Post $quiz Quiz id or quiz Model or Post.
	 */
	return apply_filters( 'masteriyo_get_quiz', $quiz_obj, $quiz );
}

/**
 * Create quiz attempt object.
 *
 * @since 1.5.37
 *
 * @return \Masteriyo\ModelException\QuizAttempt
 */
function masteriyo_create_quiz_attempt_object() {
	return masteriyo( 'quiz-attempt' );
}

if ( ! function_exists( 'masteriyo_get_instructor_quiz_ids' ) ) {
	/**
	 * Retrieves the quiz IDs associated with a given instructor.
	 *
	 * @since 1.11.0
	 *
	 * @param int|null $instructor_id The ID of the instructor. If not provided, the current user's ID will be used.
	 *
	 * @return array An array of quiz IDs associated with the specified instructor.
	 */
	function masteriyo_get_instructor_quiz_ids( $instructor_id = null ) {
		if ( is_null( $instructor_id ) ) {
			$instructor_id = masteriyo_is_current_user_instructor() ? get_current_user_id() : 0;
		}

		if ( ! $instructor_id ) {
			return array();
		}

		$course_ids = masteriyo_get_instructor_course_ids( $instructor_id );

		if ( empty( $course_ids ) ) {
			return array();
		}

		$quiz_ids = get_posts(
			array(
				'post_type'      => PostType::QUIZ,
				'post_status'    => PostStatus::PUBLISH,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_course_id',
						'value'   => $course_ids,
						'compare' => 'IN',
					),
				),
			)
		);

		/**
		 * Filter the list of quiz IDs for an instructor.
		 *
		 * @since 1.11.0
		 *
		 * @param array $quiz_ids The array of quiz IDs.
		 * @param int $instructor_id The instructor ID.
		 */
		$quiz_ids = apply_filters( 'masteriyo_get_instructor_quiz_ids', $quiz_ids, $instructor_id );

		return $quiz_ids;
	}
}

if ( ! function_exists( 'masteriyo_get_questions_bank_data_by_quiz_id' ) ) {
	/**
	 * Gets the questions associated with a quiz.
	 *
	 * @since 1.17.5
	 *
	 * @param int $quiz_id The ID of the quiz.
	 *
	 * @return array An array of question data.
	 */
	function masteriyo_get_questions_bank_data_by_quiz_id( $quiz_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT question_id, menu_order
				FROM {$wpdb->prefix}masteriyo_quiz_question_rel
				WHERE quiz_id = %d",
				absint( $quiz_id )
			),
			ARRAY_A
		);

		if ( empty( $results ) || ! is_array( $results ) ) {
			return array();
		}

		return $results;
	}
}
