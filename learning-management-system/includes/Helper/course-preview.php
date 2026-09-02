<?php
/**
 * Course preview seams.
 *
 * Letting a visitor read part of a course before buying it is the course-preview
 * addon's feature, and that addon is pro. But core decides what an unenrolled
 * visitor may see — the learn page redirect, the lesson video restriction and
 * three REST controllers all turn on it.
 *
 * Those call sites used `is_callable()` feature detection, which the shipped free
 * release does too and which is safe. They now ask through a filter instead, so
 * the question has one spelling and free's answer is a plain `false` rather than a
 * guard around a function that is not there.
 *
 * @package Masteriyo\Helper
 */

// As this file autoloads from composer, bail with `return` and never `exit` —
// `exit` would kill any process that loads the autoloader outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Whether a course has any lesson a non-enrolled visitor may preview.
 *
 * @param int $course_id Course ID.
 *
 * @return bool False unless pro answers otherwise.
 */
function masteriyo_course_has_previewable_lessons( $course_id ) {
	/**
	 * Filters whether a course has previewable lessons.
	 *
	 * @param bool $has_previewable_lessons Whether the course has previewable lessons.
	 * @param int  $course_id               Course ID.
	 */
	return (bool) apply_filters( 'masteriyo_course_has_previewable_lessons', false, $course_id );
}

/**
 * Whether a request is a course preview by a user allowed to preview (admin or course author).
 *
 * @param \WP_REST_Request|array $request Request carrying `mto-preview` and `course_id`.
 *
 * @return bool
 */
function masteriyo_is_course_preview_request( $request ) {
	$preview = masteriyo_string_to_bool( isset( $request['mto-preview'] ) ? $request['mto-preview'] : false );

	if ( ! $preview ) {
		return false;
	}

	$course_id = isset( $request['course_id'] ) ? absint( $request['course_id'] ) : 0;

	return masteriyo_is_current_user_admin() || ( $course_id && masteriyo_is_current_user_post_author( $course_id ) );
}

/**
 * Whether a lesson may be previewed by a non-enrolled visitor.
 *
 * @param \Masteriyo\Models\Lesson|int $lesson Lesson model or ID.
 *
 * @return bool False unless pro answers otherwise.
 */
function masteriyo_is_lesson_previewable( $lesson ) {
	/**
	 * Filters whether a lesson may be previewed.
	 *
	 * @param bool                         $is_previewable Whether the lesson may be previewed.
	 * @param \Masteriyo\Models\Lesson|int $lesson         Lesson model or ID.
	 */
	return (bool) apply_filters( 'masteriyo_is_lesson_previewable', false, $lesson );
}
