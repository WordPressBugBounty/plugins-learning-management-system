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
 * Post statuses a course content item may carry and still be shown.
 *
 * Core's own content is simply published. Addons whose content types carry their
 * own statuses — a Zoom meeting is `upcoming` or `active`, never `publish` — add
 * them here, so a curriculum query written in core finds them.
 *
 * @return string[]
 */
function masteriyo_get_course_content_post_statuses() {
	/**
	 * Filters the post statuses a visible course content item may carry.
	 *
	 * @param string[] $statuses Post statuses.
	 */
	return array_values( array_unique( (array) apply_filters( 'masteriyo_course_content_post_statuses', array( \Masteriyo\Enums\PostStatus::PUBLISH ) ) ) );
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
