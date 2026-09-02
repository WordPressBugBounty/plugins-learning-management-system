<?php
/**
 * Prepopulate newly created courses with a starter curriculum.
 *
 * @package Masteriyo
 */

namespace Masteriyo;

use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * CourseStarterCurriculum class.
 *
 * Creates a small editable outline (sections with draft lessons) when a course
 * is created through the admin course builder, so users do not start from an
 * empty screen.
 */
class CourseStarterCurriculum {

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'masteriyo_rest_insert_course_object', array( $this, 'maybe_create_starter_curriculum' ), 10, 3 );
	}

	/**
	 * Create the starter curriculum for a new course when requested.
	 *
	 * Runs only on REST course creation and only when the request explicitly
	 * asks for it (the admin course builder does), so imports, duplication and
	 * third-party API consumers are unaffected.
	 *
	 * @param \Masteriyo\Models\Course $course   Course object.
	 * @param \WP_REST_Request         $request  Request object.
	 * @param boolean                  $creating True when creating object, false when updating.
	 */
	public function maybe_create_starter_curriculum( $course, $request, $creating ) {
		if ( ! $creating || ! $course || ! $course->get_id() ) {
			return;
		}

		// Not declared in the course REST schema, so it arrives unsanitized: the
		// string "false" is non-empty and would opt the caller in.
		if ( ! rest_sanitize_boolean( $request['with_starter_curriculum'] ?? false ) ) {
			return;
		}

		// Everything below runs inside the caller's create_item try/catch,
		// where an escaping ModelException deletes the just-created course —
		// so the whole body, including third-party filter callbacks, must be
		// contained here.
		try {
			// An earlier listener (e.g. on masteriyo_new_course) may already
			// have added sections; never stack the starter on top of those.
			$existing_sections = get_posts(
				array(
					'post_type'      => PostType::SECTION,
					'post_parent'    => $course->get_id(),
					'post_status'    => PostStatus::ANY,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);

			if ( ! empty( $existing_sections ) ) {
				return;
			}

			/**
			 * Filters the starter curriculum blueprint for new courses.
			 *
			 * Each item is a section: `name` (string) and `lessons` (string[] of
			 * lesson names, created as drafts). Return an empty array to disable
			 * the starter curriculum entirely.
			 *
			 * @param array                    $blueprint Sections with their lesson names.
			 * @param \Masteriyo\Models\Course $course    The just-created course.
			 */
			$blueprint = apply_filters(
				'masteriyo_course_starter_curriculum',
				array(
					array(
						'name'    => __( 'Getting started', 'learning-management-system' ),
						'lessons' => array(
							__( 'Welcome to your course', 'learning-management-system' ),
							__( 'Your first lesson', 'learning-management-system' ),
						),
					),
					array(
						'name'    => __( 'Section 2', 'learning-management-system' ),
						'lessons' => array(
							__( 'Another lesson', 'learning-management-system' ),
						),
					),
				),
				$course
			);

			if ( empty( $blueprint ) || ! is_array( $blueprint ) ) {
				return;
			}

			$this->create_curriculum( $course, $blueprint );
		} catch ( \Throwable $e ) {
			// A failed prefill must never fail (or delete) the new course.
			masteriyo_get_logger()->error( 'Starter curriculum creation failed: ' . $e->getMessage(), array( 'source' => 'course-starter-curriculum' ) );
		}
	}

	/**
	 * Create sections and draft lessons from the blueprint.
	 *
	 * @param \Masteriyo\Models\Course $course    Course object.
	 * @param array                    $blueprint Sections with their lesson names.
	 */
	protected function create_curriculum( $course, $blueprint ) {
		$section_order = 0;

		foreach ( $blueprint as $section_data ) {
			if ( empty( $section_data['name'] ) || ! is_string( $section_data['name'] ) ) {
				continue;
			}

			// Status and author are omitted on purpose: SectionRepository::create()
			// hardcodes publish status and forces the course author.
			/** @var \Masteriyo\Models\Section $section */
			$section = masteriyo( 'section' );
			$section->set_name( $section_data['name'] );
			$section->set_parent_id( $course->get_id() );
			$section->set_course_id( $course->get_id() );
			$section->set_menu_order( $section_order );
			$section->save();

			if ( ! $section->get_id() ) {
				continue;
			}

			++$section_order;
			$lesson_order = 0;

			foreach ( (array) ( $section_data['lessons'] ?? array() ) as $lesson_name ) {
				if ( empty( $lesson_name ) || ! is_string( $lesson_name ) ) {
					continue;
				}

				/** @var \Masteriyo\Models\Lesson $lesson */
				$lesson = masteriyo( 'lesson' );
				$lesson->set_name( $lesson_name );
				$lesson->set_parent_id( $section->get_id() );
				$lesson->set_course_id( $course->get_id() );
				$lesson->set_menu_order( $lesson_order );
				$lesson->set_status( PostStatus::DRAFT );
				$lesson->set_author_id( $course->get_author_id() );
				$lesson->save();

				++$lesson_order;
			}
		}
	}
}
