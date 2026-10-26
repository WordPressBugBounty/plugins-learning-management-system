<?php
/**
 * Course content seams.
 *
 * A course's contents are lessons and quizzes in core, and whatever else the
 * addons add — assignments, Zoom meetings, Google Meet meetings. Core owns the
 * curriculum queries, the learn-page URL and the course exporter, so each of
 * those has to know which post statuses a content item may carry and which
 * models count as content at all. Those are questions core cannot answer for a
 * content type it does not ship.
 *
 * These functions are that question. Core asks and answers for what it owns; each
 * addon extends the answer for its own content type through the filter.
 *
 * @package Masteriyo\Helper
 */

// As this file autoloads from composer, bail with `return` and never `exit` —
// `exit` would kill any process that loads the autoloader outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Whether a model is a course content item.
 *
 * Core answers for the two content types it ships. An addon that adds one hooks
 * the filter and answers for its own model.
 *
 * @param mixed $item Model, or anything at all.
 *
 * @return bool
 */
function masteriyo_is_course_content_item( $item ) {
	$is_content_item = $item instanceof \Masteriyo\Models\Lesson || $item instanceof \Masteriyo\Models\Quiz;

	/**
	 * Filters whether a model is a course content item.
	 *
	 * @param bool  $is_content_item Whether the model is a course content item.
	 * @param mixed $item            Model, or anything at all.
	 */
	return (bool) apply_filters( 'masteriyo_is_course_content_item', $is_content_item, $item );
}

/**
 * Whether a course content item is a draft.
 *
 * @param mixed $item Model, or anything at all.
 *
 * @return bool
 */
function masteriyo_is_draft_course_content( $item ): bool {
	return method_exists( $item, 'get_status' ) && \Masteriyo\Enums\PostStatus::DRAFT === $item->get_status();
}

/**
 * Learn page URL that opens a course content item in the player.
 *
 * @param \Masteriyo\Models\Course $course Course the item belongs to.
 * @param \Masteriyo\Database\Model $item   Course content item model.
 *
 * @return string
 */
function masteriyo_get_course_content_learn_url( $course, $item ): string {
	$learn_page_url = masteriyo_get_page_permalink( 'learn' );
	$url            = trailingslashit( $learn_page_url ) . 'course/' . $course->get_slug();

	// The player lists a draft only in preview mode, which reads course_name as an ID, never a slug.
	if ( masteriyo_is_draft_course_content( $item ) ) {
		$url = add_query_arg(
			array(
				'course_name' => $course->get_id(),
				'mto-preview' => 'true',
			),
			$learn_page_url
		);
	} elseif ( '' === get_option( 'permalink_structure' ) ) {
		$url = add_query_arg( array( 'course_name' => $course->get_id() ), $learn_page_url );
	}

	return $url . '#/course/' . $course->get_id() . '/' . $item->get_object_type() . '/' . $item->get_id();
}

/**
 * Post statuses a course content item may carry and still be shown.
 *
 * Core's own content is simply published. Addons whose content types carry their
 * own statuses — a Zoom meeting is `upcoming` or `active`, never `publish` — add
 * them here, so a curriculum query written in core finds them.
 *
 * Given the course being read, drafts join the list for a viewer the player lets preview it: anyone who may edit the course.
 *
 * @param int|WP_Post|\Masteriyo\Models\Course|null $course Course the curriculum is being read for, when there is one.
 *
 * @return string[]
 */
function masteriyo_get_course_content_post_statuses( $course = null ) {
	$statuses = array( \Masteriyo\Enums\PostStatus::PUBLISH );

	$course_model = $course ? masteriyo_get_course( $course ) : null;

	// The player's own preview rule, so a draft is listed only where it can also be opened.
	if ( $course_model && masteriyo_is_course_preview_request(
		array(
			'mto-preview' => true,
			'course_id'   => $course_model->get_id(),
		)
	) ) {
		$statuses[] = \Masteriyo\Enums\PostStatus::DRAFT;
	}

	/**
	 * Filters the post statuses a visible course content item may carry.
	 *
	 * @param string[] $statuses Post statuses.
	 * @param int|WP_Post|\Masteriyo\Models\Course|null $course Course the curriculum is being read for, when there is one.
	 */
	return array_values( array_unique( (array) apply_filters( 'masteriyo_course_content_post_statuses', $statuses, $course ) ) );
}

/**
 * Post statuses the exporter should include for a course content post type.
 *
 * @param string[] $statuses  Statuses the exporter has so far.
 * @param string   $post_type Post type being exported.
 *
 * @return string[]
 */
function masteriyo_get_exportable_post_statuses( $statuses, $post_type ) {
	/**
	 * Filters the post statuses the exporter includes for a post type.
	 *
	 * @param string[] $statuses  Post statuses.
	 * @param string   $post_type Post type being exported.
	 */
	return array_values( array_unique( (array) apply_filters( 'masteriyo_exportable_post_statuses', (array) $statuses, $post_type ) ) );
}

/**
 * Get a Zoom meeting by ID.
 *
 * Core schedules and sends the session reminder emails, so it has to read the
 * session. The Zoom addon owns the model and answers through the filter.
 *
 * @param int $meeting_id Zoom meeting ID.
 *
 * @return object|null The Zoom meeting model, or null when there is none.
 */
function masteriyo_get_zoom_meeting( $meeting_id ) {
	/**
	 * Filters the Zoom meeting resolved from an ID.
	 *
	 * @param object|null $meeting    The Zoom meeting model, or null.
	 * @param int         $meeting_id Zoom meeting ID.
	 */
	return apply_filters( 'masteriyo_zoom_meeting', null, $meeting_id );
}
