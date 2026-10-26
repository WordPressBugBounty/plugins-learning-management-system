<?php
/**
 * Content tracking information.
 *
 * @package Masteriyo\Tracking
 */

namespace Masteriyo\Tracking;

use Masteriyo\Enums\CommentType;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\QuestionType;
use Masteriyo\PostType\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Published content counts, split by lesson and question type.
 *
 * @phpstan-type ContentData array{section_count: int, lesson_count: int, quiz_count: int, question_count: int, qa_count: int, course_review_count: int, lesson_types: array<array-key, positive-int>, lesson_video_sources: array<array-key, positive-int>, question_types: array<array-key, positive-int>}
 */
class ContentTrackingInfo {

	/**
	 * Lesson types the course builder can create.
	 *
	 * @var string[]
	 */
	const LESSON_TYPES = array( 'video-lesson', 'text-lesson', 'pdf-lesson', 'audio-lesson', 'live-stream-lesson' );

	/**
	 * Build the content group.
	 *
	 * @return ContentData
	 */
	public static function all(): array {
		return array(
			'section_count'        => TrackingCounts::count_published( PostType::SECTION ),
			'lesson_count'         => TrackingCounts::count_published( PostType::LESSON ),
			'quiz_count'           => TrackingCounts::count_published( PostType::QUIZ ),
			'question_count'       => TrackingCounts::count_published( PostType::QUESTION ),
			'qa_count'             => MasteriyoTrackingInfo::count_comments( CommentType::COURSE_QA ),
			'course_review_count'  => MasteriyoTrackingInfo::count_comments( CommentType::COURSE_REVIEW, true ),
			'lesson_types'         => TrackingCounts::normalize( self::lesson_type_rows(), self::LESSON_TYPES ),
			'lesson_video_sources' => TrackingCounts::normalize( self::video_source_rows(), array_keys( masteriyo_get_lesson_video_sources() ) ),
			'question_types'       => TrackingCounts::by_meta_value( PostType::QUESTION, '_type', QuestionType::all() ),
		);
	}

	/**
	 * Published lessons grouped by type. Lessons saved without `_lesson_type`
	 * (pre-2.12.0, imported, migrated) get the type Lesson::get_icon() shows for them.
	 *
	 * @return list<array{value: string|null, total: string}> Raw rows.
	 */
	private static function lesson_type_rows(): array {
		global $wpdb;

		if ( ! $wpdb ) {
			return array();
		}

		// Same order as Lesson::get_icon().
		return TrackingCounts::rows(
			"SELECT CASE
				WHEN COALESCE(lt.meta_value, '') <> '' THEN lt.meta_value
				WHEN COALESCE(vu.meta_value, '') NOT IN ('', '0') AND vs.meta_value = 'live-stream' THEN 'live-stream-lesson'
				WHEN COALESCE(vu.meta_value, '') NOT IN ('', '0') THEN 'video-lesson'
				WHEN COALESCE(pdf.meta_value, '') NOT IN ('', 'a:0:{}') THEN 'pdf-lesson'
				WHEN COALESCE(au.meta_value, '') <> '' OR COALESCE(af.meta_value, '') NOT IN ('', 'a:0:{}') THEN 'audio-lesson'
				ELSE 'text-lesson'
			END AS value, COUNT(DISTINCT p.ID) AS total
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} lt ON lt.post_id = p.ID AND lt.meta_key = '_lesson_type'
			LEFT JOIN {$wpdb->postmeta} vs ON vs.post_id = p.ID AND vs.meta_key = '_video_source'
			LEFT JOIN {$wpdb->postmeta} vu ON vu.post_id = p.ID AND vu.meta_key = '_video_source_url'
			LEFT JOIN {$wpdb->postmeta} pdf ON pdf.post_id = p.ID AND pdf.meta_key = '_pdf'
			LEFT JOIN {$wpdb->postmeta} au ON au.post_id = p.ID AND au.meta_key = '_audio_source_url'
			LEFT JOIN {$wpdb->postmeta} af ON af.post_id = p.ID AND af.meta_key = '_audio_source_files'
			WHERE p.post_type = %s AND p.post_status = %s
			GROUP BY value",
			PostType::LESSON,
			PostStatus::PUBLISH
		);
	}

	/**
	 * Published lessons with a video, grouped by video source. A source left
	 * behind without a URL (e.g. the editor's `self-hosted` default) is skipped.
	 *
	 * @return list<array{value: string|null, total: string}> Raw rows.
	 */
	private static function video_source_rows(): array {
		global $wpdb;

		if ( ! $wpdb ) {
			return array();
		}

		return TrackingCounts::rows(
			"SELECT vs.meta_value AS value, COUNT(DISTINCT p.ID) AS total
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} vs ON vs.post_id = p.ID AND vs.meta_key = '_video_source'
			INNER JOIN {$wpdb->postmeta} vu ON vu.post_id = p.ID AND vu.meta_key = '_video_source_url'
			WHERE p.post_type = %s AND p.post_status = %s AND vu.meta_value NOT IN ('', '0')
			GROUP BY vs.meta_value",
			PostType::LESSON,
			PostStatus::PUBLISH
		);
	}
}
