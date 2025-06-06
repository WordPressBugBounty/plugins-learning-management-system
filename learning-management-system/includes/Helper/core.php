<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Core functions.
 *
 * @since 1.0.0
 */

use Masteriyo\Addons\GoogleMeet\Enums\GoogleMeetStatus;
use Masteriyo\Constants;
use Masteriyo\Enums\CommentStatus;
use Masteriyo\Models\Faq;
use Masteriyo\Geolocation;
use Masteriyo\Models\User;
use Masteriyo\Models\Course;
use Masteriyo\ModelException;
use Masteriyo\Models\Section;
use Masteriyo\Models\CourseReview;
use Masteriyo\Models\QuizReview;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\SectionChildrenPostType;
use Masteriyo\Enums\UserStatus;
use Masteriyo\Logger;
use Masteriyo\PostType\PostType;
use Masteriyo\Pro\Addons;
use Masteriyo\Taxonomy\Taxonomy;
use Masteriyo\LogHandlers\LogHandlerFile;
use Masteriyo\Query\WPUserQuery;

/**
 * Get course.
 *
 * @since 1.0.0
 *
 * @param int|\Masteriyo\Models\Course|\WP_Post $course Course id or Course Model or Post.
 *
 * @return \Masteriyo\Models\Course|null
 */
function masteriyo_get_course( $course ) {
	$course_obj   = masteriyo( 'course' );
	$course_store = masteriyo( 'course.store' );

	if ( is_a( $course, 'Masteriyo\Models\Course' ) ) {
		$id = $course->get_id();
	} elseif ( is_a( $course, 'WP_Post' ) ) {
		$id = $course->ID;
	} else {
		$id = absint( $course );
	}

	try {
		$id = absint( $id );
		$course_obj->set_id( $id );
		$course_store->read( $course_obj );
	} catch ( \Exception $e ) {
		return null;
	}

	/**
	 * Filters course object.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Course $course_obj Course object.
	 * @param int|\Masteriyo\Models\Course|WP_Post $course Course id or Course Model or Post.
	 */
	return apply_filters( 'masteriyo_get_course', $course_obj, $course );
}

/**
 * Get lesson.
 *
 * @since 1.0.0
 *
 * @param int|\Masteriyo\Models\Lesson|\WP_Post $lesson Lesson id or Lesson Model or Post.
 *
 * @return \Masteriyo\Models\Lesson|null
 */
function masteriyo_get_lesson( $lesson ) {
	$lesson_obj   = masteriyo( 'lesson' );
	$lesson_store = masteriyo( 'lesson.store' );

	if ( is_a( $lesson, 'Masteriyo\Models\Lesson' ) ) {
		$id = $lesson->get_id();
	} elseif ( is_a( $lesson, 'WP_Post' ) ) {
		$id = $lesson->ID;
	} else {
		$id = $lesson;
	}

	try {
		$id = absint( $id );
		$lesson_obj->set_id( $id );
		$lesson_store->read( $lesson_obj );
	} catch ( \Exception $e ) {
		return null;
	}

	/**
	 * Filters lesson object.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Lesson $lesson_obj lesson object.
	 * @param int|\Masteriyo\Models\Lesson|WP_Post $lesson lesson id or lesson Model or Post.
	 */
	return apply_filters( 'masteriyo_get_lesson', $lesson_obj, $lesson );
}

/**
 * Get section.
 *
 * @since 1.0.0
 *
 * @param int|Section|WP_Post $section Section id or Section Model or Post.
 * @return Section|null
 */
function masteriyo_get_section( $section ) {
	$section_obj   = masteriyo( 'section' );
	$section_store = masteriyo( 'section.store' );

	if ( is_a( $section, 'Masteriyo\Models\Section' ) ) {
		$id = $section->get_id();
	} elseif ( is_a( $section, 'WP_Post' ) ) {
		$id = $section->ID;
	} else {
		$id = $section;
	}

	try {
		$id = absint( $id );
		$section_obj->set_id( $id );
		$section_store->read( $section_obj );
	} catch ( \Exception $e ) {
		return null;
	}

	/**
	 * Filters section object.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Section $section_obj section object.
	 * @param int|\Masteriyo\Models\Section|WP_Post $section section id or section Model or Post.
	 */
	return apply_filters( 'masteriyo_get_section', $section_obj, $section );
}

/**
 * Get sections.
 *
 * @since 1.0.0
 *
 * @param array $args Query arguments.
 *
 * @return object|array[Section]
 */
function masteriyo_get_sections( $args = array() ) {
	$sections = masteriyo( 'query.sections' )->set_args( $args )->get_sections();

	/**
	 * Filters queried section objects.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Section|\Masteriyo\Models\Section[] $sections Queried sections.
	 * @param array $args Query args.
	 */
	return apply_filters( 'masteriyo_get_sections', $sections, $args );
}

/**
 * Get lessons.
 *
 * @since 1.0.0
 *
 * @param array $args Query arguments.
 *
 * @return object|array[Lesson]
 */
function masteriyo_get_lessons( $args = array() ) {
	$lessons = masteriyo( 'query.lessons' )->set_args( $args )->get_lessons();

	/**
	 * Filters queried lesson objects.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Lesson|\Masteriyo\Models\Lesson[] $lessons Queried lessons.
	 * @param array $args Query args.
	 */
	return apply_filters( 'masteriyo_get_lessons', $lessons, $args );
}

/**
 * Get quizzes.
 *
 * @since 1.0.0
 *
 * @param array $args Query arguments.
 *
 * @return object|array[Quiz]
 */
function masteriyo_get_quizes( $args = array() ) {
	$quizes = masteriyo( 'query.quizes' )->set_args( $args )->get_quizes();

	/**
	 * Filters queried quiz objects.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Quiz|\Masteriyo\Models\Quiz[] $quizzes Queried quizzes.
	 * @param array $args Query args.
	 */
	return apply_filters( 'masteriyo_get_quizes', $quizes, $args );
}

/**
 * Get course reviews.
 *
 * @since 1.0.0
 *
 * @param array $args Query arguments.
 *
 * @return object|array[CourseReview]
 */
function masteriyo_get_course_reviews( $args = array() ) {
	$course_reviews = masteriyo( 'query.course-reviews' )->set_args( $args )->get_courses_reviews();

	/**
	 * Filters queried course review objects.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\CourseReview|\Masteriyo\Models\CourseReview[] $course_reviews Queried course reviews.
	 * @param array $args Query args.
	 */
	return apply_filters( 'masteriyo_get_course_reviews', $course_reviews, $args );
}

/**
 * Get quizes reviews.
 *
 * @since 1.7.0
 *
 * @param array $args Query arguments.
 *
 * @return object|array[QuizesReview]
 */
function masteriyo_get_quiz_reviews( $args = array() ) {
	$quiz_reviews = masteriyo( 'query.quiz-reviews' )->set_args( $args )->get_quizes_reviews();

	/**
	 * Filters queried quiz review objects.
	 *
	 * @since 1.7.0
	 *
	 * @param \Masteriyo\Models\QuizReview|\Masteriyo\Models\QuizReview[] $quiz_reviews Queried quiz reviews.
	 * @param array $args Query args.
	 */
	return apply_filters( 'masteriyo_get_quiz_reviews', $quiz_reviews, $args );
}

/**
 * Get course question-answer.
 *
 * @since 1.0.0
 *
 * @param  int|WP_Comment|\Masteriyo\Models\CourseQuestionAnswer $course_qa Object ID or WP_Comment or Model.
 *
 * @return \Masteriyo\Models\CourseQuestionAnswer|null
 */
function masteriyo_get_course_qa( $course_qa ) {
	$course_qa_obj   = masteriyo( 'course-qa' );
	$course_qa_store = masteriyo( 'course-qa.store' );

	if ( is_a( $course_qa, Masteriyo\Models\CourseQuestionAnswer::class ) ) {
		$id = $course_qa->get_id();
	} elseif ( is_a( $course_qa, WP_Comment::class ) ) {
		$id = $course_qa->comment_ID;
	} else {
		$id = $course_qa;
	}

	try {
		$id = absint( $id );
		$course_qa_obj->set_id( $id );
		$course_qa_store->read( $course_qa_obj );
	} catch ( \Exception $e ) {
		return null;
	}

	/**
	 * Filters course qa object.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\CourseQuestionAnswer $course_qa_obj Course qa object.
	 * @param int|\Masteriyo\Models\CourseQuestionAnswer|WP_Post $course qa course, qa id or course qa Model or Post.
	 */
	return apply_filters( 'masteriyo_get_course_qa', $course_qa_obj, $course_qa );
}

/**
 * Get course question-answers.
 *
 * @since 1.0.0
 *
 * @param array $args Query arguments.
 *
 * @return object|array[CourseQuestionAnswer]
 */
function masteriyo_get_course_qas( $args = array() ) {
	$course_qas = masteriyo( 'query.course-qas' )->set_args( $args )->get_course_qas();

	/**
	 * Filters queried course qa objects.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\CourseQuestionAnswer|\Masteriyo\Models\CourseQuestionAnswer[] $course_qas Queried course qas.
	 * @param array $args Query args.
	 */
	return apply_filters( 'masteriyo_get_course_qas', $course_qas, $args );
}

/**
 * Determine if curriculum should be displayed for the course.
 *
 * @since 1.14.0
 *
 * @param \Masteriyo\Models\Course $course Course object.
 *
 * @return boolean false if course is a scorm or google classroom course, true otherwise.
 */
function masteriyo_should_show_curriculum( $course ) {

	$curriculum = masteriyo_is_standard_course_type( $course );

	/**
	 * Filters curriculum visibility.
	 *
	 * @since 1.14.0
	 *
	 * @param boolean $curriculum Indicates if curriculum should be shown.
	* @param \Masteriyo\Models\Course $course course object.
	 *
	 */
	return apply_filters( 'masteriyo_should_show_curriculum', $curriculum, $course );
}

/**
 * Check if the course is a standard type (not SCORM or Google Classroom).
 *
 * @since 1.14.0
 *
 * @param \Masteriyo\Models\Course $course course object.
 *
 * @return boolean False if course is SCORM or Google Classroom, true otherwise.
 */
function masteriyo_is_standard_course_type( $course ) {

	if ( get_post_meta( $course->get_id(), '_google_classroom_course_id', true ) ) {
		return false;
	}

	$scorm_package_meta = get_post_meta( $course->get_id(), '_scorm_package', true );

	if ( $scorm_package_meta ) {
		return false;
	}

	return true;
}

/**
 * Get course category.
 *
 * @since 1.0.0
 *
 * @param int|CourseCategory|WP_Term $course_cat Course Category id or Course Category Model or Term.
 * @return CourseCategory|null
 */
function masteriyo_get_course_cat( $course_cat ) {
	$course_cat_obj   = masteriyo( 'course_cat' );
	$course_cat_store = masteriyo( 'course_cat.store' );

	if ( is_a( $course_cat, 'Masteriyo\Models\CourseCategory' ) ) {
		$id = $course_cat->get_id();
	} elseif ( is_a( $course_cat, 'WP_Term' ) ) {
		$id = $course_cat->term_id;
	} else {
		$id = $course_cat;
	}

	try {
		$id = absint( $id );
		$course_cat_obj->set_id( $id );
		$course_cat_store->read( $course_cat_obj );
	} catch ( \Exception $e ) {
		return null;
	}

	/**
	 * Filters queried course category objects.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\CourseCategory|\Masteriyo\Models\CourseCategory[] $course_categories Queried course categories.
	 * @param array $args Query args.
	 */
	return apply_filters( 'masteriyo_get_course_cat', $course_cat_obj, $course_cat );
}

/**
 * Get course tag.
 *
 * @since 1.0.0
 *
 * @param int|CourseTag|WP_Term $course_tag Course Tag id or Course Tag Model or Term.
 * @return CourseTag|null
 */
function masteriyo_get_course_tag( $course_tag ) {
	$course_tag_obj   = masteriyo( 'course_tag' );
	$course_tag_store = masteriyo( 'course_tag.store' );

	if ( is_a( $course_tag, 'Masteriyo\Models\CourseTag' ) ) {
		$id = $course_tag->get_id();
	} elseif ( is_a( $course_tag, 'WP_Term' ) ) {
		$id = $course_tag->term_id;
	} else {
		$id = $course_tag;
	}

	try {
		$id = absint( $id );
		$course_tag_obj->set_id( $id );
		$course_tag_store->read( $course_tag_obj );
	} catch ( \Exception $e ) {
		return null;
	}

	/**
	 * Filters queried course tag objects.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\CourseTag|\Masteriyo\Models\CourseTag[] $course_tags Queried course tags.
	 * @param array $args Query args.
	 */
	return apply_filters( 'masteriyo_get_course_tag', $course_tag_obj, $course_tag );
}

/**
 * Get course difficulty.
 *
 * @since 1.0.0
 *
 * @param int|CourseDifficulty|WP_Term $course_difficulty Course Difficulty id or Course Difficulty Model or Term.
 * @return CourseDifficulty|null
 */
function masteriyo_get_course_difficulty( $course_difficulty ) {
	$course_difficulty_obj   = masteriyo( 'course_difficulty' );
	$course_difficulty_store = masteriyo( 'course_difficulty.store' );

	if ( is_a( $course_difficulty, 'Masteriyo\Models\CourseDifficulty' ) ) {
		$id = $course_difficulty->get_id();
	} elseif ( is_a( $course_difficulty, 'WP_Term' ) ) {
		$id = $course_difficulty->term_id;
	} else {
		$id = $course_difficulty;
	}

	try {
		$id = absint( $id );
		$course_difficulty_obj->set_id( $id );
		$course_difficulty_store->read( $course_difficulty_obj );
	} catch ( \Exception $e ) {
		return null;
	}

	/**
	 * Filters queried course difficulty objects.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\CourseDifficulty|\Masteriyo\Models\CourseDifficulty[] $course_difficulties Queried course difficulties.
	 * @param array $args Query args.
	 */
	return apply_filters( 'masteriyo_get_course_difficulty', $course_difficulty_obj, $course_difficulty );
}

/**
 * Get user.
 *
 * @since 1.0.0
 *
 * @param int|User|WP_User $user User  id or User Model or WP+User.
 * @return User|WP_Error
 */
function masteriyo_get_user( $user ) {
	$user_obj   = masteriyo( 'user' );
	$user_store = masteriyo( 'user.store' );

	if ( is_a( $user, 'Masteriyo\Database\Model' ) ) {
		$id = $user->get_id();
	} elseif ( is_a( $user, 'WP_User' ) ) {
		$id = $user->ID;
	} else {
		$id = $user;
	}

	try {
		$id = absint( $id );
		$user_obj->set_id( $id );
		$user_store->read( $user_obj );
	} catch ( ModelException $e ) {
		$user_obj = new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
	}

	/**
	 * Filters user object.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\User $user_obj user object.
	 * @param int|\Masteriyo\Models\User|WP_Post $user user id or user Model or Post.
	 */
	return apply_filters( 'masteriyo_get_user', $user_obj, $user );
}

/**
 * Get template part.
 *
 * MASTERIYO_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
 *
 * @since 1.0.0
 *
 * @param mixed  $slug Template slug.
 * @param string $name Template name (default: '').
 */
function masteriyo_get_template_part( $slug, $name = '' ) {
	return masteriyo( 'template' )->get_part( $slug, $name );
}

/**
 * Get other templates and include the file.
 *
 * @since 1.0.0
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 */
function masteriyo_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	return masteriyo( 'template' )->get( $template_name, $args, $template_path, $default_path );
}

/**
 * Like get_template, but returns the HTML instead of outputting.
 *
 * @since 1.0.0
 *
 * @see get_template
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 *
 * @return string
 */
function masteriyo_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	return masteriyo( 'template' )->get_html( $template_name, $args, $template_path, $default_path );
}

/**
 * Add a template to the template cache.
 *
 * @since 1.0.0
 *
 * @param string $cache_key Object cache key.
 * @param string $template Located template.
 */
function masteriyo_set_template_cache( $cache_key, $template ) {
	return masteriyo( 'template' )->set_cache( $cache_key, $template );
}

/**
 * Get template cache.
 *
 * @since 1.0.0
 *
 * @param string $cache_key Object cache key.
 *
 * @return string
 */
function masteriyo_get_template_cache( $cache_key ) {
	return masteriyo( 'template' )->get_cache( $cache_key );
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @since 1.0.0
 *
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 *
 * @return string
 */
function masteriyo_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	return masteriyo( 'template' )->locate( $template_name, $template_path, $default_path );
}

/**
 * Retrieve page ids - used for pages like courses. returns -1 if no page is found.
 *
 * @since 1.0.0
 *
 * @param string $page Page slug.
 *
 * @return int
 */
function masteriyo_get_page_id( $page ) {
	$page    = str_replace( '-', '_', $page );
	$page_id = masteriyo_get_setting( "general.pages.{$page}_page_id" );

	/**
	 * Filters page id - used for pages like courses, account etc.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $page_id Page id - used for pages like courses, account etc. Value should be -1 if no page is found.
	 */
	$page_id = apply_filters( 'masteriyo_get_' . $page . '_page_id', $page_id );
	if ( has_filter( 'wpml_object_id' ) ) {
		$page_id = apply_filters( 'wpml_object_id', $page_id, 'page', true );
	}
	return $page_id ? absint( $page_id ) : -1;
}

/**
 * Retrieve page permalink.
 *
 * @since 1.0.0
 *
 * @param string      $page page slug.
 * @param string|bool $fallback Fallback URL if page is not set. Defaults to home URL.
 *
 * @return string
 */
function masteriyo_get_page_permalink( $page, $fallback = null ) {
	$page_id   = masteriyo_get_page_id( $page );
	$permalink = 0 < $page_id ? get_permalink( $page_id ) : '';

	if ( ! $permalink ) {
		$permalink = is_null( $fallback ) ? get_home_url() : $fallback;
	}

	/**
	 * Filters page permalink.
	 *
	 * @since 1.0.0
	 *
	 * @param string $permalink Page permalink.
	 */
	return apply_filters( 'masteriyo_get_' . $page . '_page_permalink', $permalink );
}

/**
 * Get image asset URL.
 *
 * @since 1.0.0
 *
 * @param string $file Image file name.
 *
 * @return string
 */
function masteriyo_img_url( $file ) {
	$plugin_dir = plugin_dir_url( Constants::get( 'MASTERIYO_PLUGIN_FILE' ) );

	return "{$plugin_dir}assets/img/{$file}";
}

/**
 * Get current logged in user.
 *
 * @since 1.0.0
 *
 * @return User
 */
function masteriyo_get_current_user() {
	if ( is_user_logged_in() ) {
		return masteriyo_get_user( get_current_user_id() );
	}

	return null;
}

/**
 * Get markup for rating indicators.
 *
 * @since 1.0.0
 *
 * @param string $classes
 *
 * @return string[]
 */
function masteriyo_get_rating_indicators_markup( $classes = '' ) {
	/**
	 * Filters rating icons html.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $icons_html Icon type to icon html index array.
	 */
	return apply_filters(
		'masteriyo_rating_indicators_markup',
		array(
			'full_star'  =>
				"<svg class='masteriyo-inline-block masteriyo-fill-current {$classes}' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'>
				<path d='M21.947 9.179a1.001 1.001 0 00-.868-.676l-5.701-.453-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.213 4.107-1.49 6.452a1 1 0 001.53 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082c.297-.268.406-.686.278-1.065z'/>
			</svg>",
			'half_star'  =>
				"<svg class='masteriyo-inline-block masteriyo-fill-current {$classes}' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'>
				<path d='M5.025 20.775A.998.998 0 006 22a1 1 0 00.555-.168L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082a1 1 0 00-.59-1.74l-5.701-.454-2.467-5.461a.998.998 0 00-1.822-.001L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.214 4.107-1.491 6.452zM12 5.429l2.042 4.521.588.047h.001l3.972.315-3.271 2.944-.001.002-.463.416.171.597v.003l1.253 4.385L12 15.798V5.429z'/>
			</svg>",
			'empty_star' =>
				"<svg class='masteriyo-inline-block masteriyo-fill-current {$classes}' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'>
				<path d='M6.516 14.323l-1.49 6.452a.998.998 0 001.529 1.057L12 18.202l5.445 3.63a1.001 1.001 0 001.517-1.106l-1.829-6.4 4.536-4.082a1 1 0 00-.59-1.74l-5.701-.454-2.467-5.461a.998.998 0 00-1.822 0L8.622 8.05l-5.701.453a1 1 0 00-.619 1.713l4.214 4.107zm2.853-4.326a.998.998 0 00.832-.586L12 5.43l1.799 3.981a.998.998 0 00.832.586l3.972.315-3.271 2.944c-.284.256-.397.65-.293 1.018l1.253 4.385-3.736-2.491a.995.995 0 00-1.109 0l-3.904 2.603 1.05-4.546a1 1 0 00-.276-.94l-3.038-2.962 4.09-.326z'/>
			</svg>",
		)
	);
}

/**
 * Get max allowed rating for course.
 *
 * @since 1.0.0
 *
 * @return integer
 */
function masteriyo_get_max_course_rating() {
	/**
	 * Filters max course rating. Default is 5.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $max_rating Max course rating. Default is 5.
	 */
	return apply_filters( 'masteriyo_max_course_rating', 5 );
}

/**
 * Render stars based on rating.
 *
 * @since 1.0.0
 *
 * @param int|float $rating Given rating.
 * @param string    $classes Extra classes to add to the svgs.
 * @param string    $echo Whether to echo or return the html.
 *
 * @return void|string
 */
function masteriyo_render_stars( $rating, $classes = '', $echo = true ) {
	$rating     = (float) $rating;
	$html       = '';
	$max_rating = masteriyo_get_max_course_rating();
	$rating     = $rating > $max_rating ? $max_rating : $rating;
	$rating     = $rating < 0 ? 0 : $rating;
	$stars      = masteriyo_get_rating_indicators_markup( $classes );

	$rating_floor = floor( $rating );
	for ( $i = 1; $i <= $rating_floor; $i++ ) {
		$html .= $stars['full_star'];
	}
	if ( $rating_floor < $rating ) {
		$html .= $stars['half_star'];
	}

	$rating_ceil = ceil( $rating );
	for ( $i = $rating_ceil; $i < $max_rating; $i++ ) {
		$html .= $stars['empty_star'];
	}

	$svg_args = array(
		'svg'   => array(
			'class'           => true,
			'aria-hidden'     => true,
			'aria-labelledby' => true,
			'role'            => true,
			'xmlns'           => true,
			'width'           => true,
			'height'          => true,
			'viewbox'         => true, // <= Must be lower case!
		),
		'g'     => array( 'fill' => true ),
		'title' => array( 'title' => true ),
		'path'  => array(
			'd'    => true,
			'fill' => true,
		),
	);

	if ( true === $echo ) {
		echo wp_kses( $html, $svg_args );
	} else {
		return $html;
	}
}

/**
 * Get related courses.
 *
 * @since 1.0.0
 *
 * @param \Masteriyo\Models\Course $course Course object.
 *
 * @return \Masteriyo\Models\Course[]
 */
function masteriyo_get_related_courses( $course ) {
	/**
	 * Filters max related posts count, which is used to limit the number of related courses shown in course detail page.
	 *
	 * @since 1.0.0
	 * @since 1.5.9 Add the $course parameter.
	 *
	 * @param integer $max_related_posts Maximum related posts to be shown.
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	$max_related_posts = apply_filters(
		'masteriyo_max_related_posts_count',
		3,
		$course
	);
	$max_related_posts = absint( $max_related_posts );

	/**
	 * Ref: https://www.wpbeginner.com/wp-tutorials/how-to-display-related-posts-in-wordpress/
	 */
	$args = array(
		'tax_query'      => array(
			'relation' => 'AND',
			array(
				'taxonomy' => Taxonomy::COURSE_CATEGORY,
				'terms'    => $course->get_category_ids(),
			),
		),
		'post__not_in'   => array( $course->get_id() ),
		'posts_per_page' => $max_related_posts,
		'post_type'      => PostType::COURSE,
	);

	$query           = new WP_Query( $args );
	$related_courses = array_map( 'masteriyo_get_course', $query->posts );

	/**
	 * Filters related course objects.
	 *
	 * @since 1.0.0
	 * @since 1.5.9 Add the $course parameter.
	 *
	 * @param \Masteriyo\Models\Course[] $courses Related courses.
	 * @param \WP_Query $query Query object.
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	return apply_filters( 'masteriyo_get_related_courses', $related_courses, $query, $course );
}

/**
 * Get lessons count for a course.
 *
 * @since 1.0.0
 *
 * @param int|Course|WP_Post $course
 *
 * @return integer
 */
function masteriyo_get_lessons_count( $course ) {
	$count  = 0;
	$course = masteriyo_get_course( $course );

	if ( $course ) {
		$query = new \WP_Query(
			array(
				'post_type'      => PostType::LESSON,
				'post_status'    => PostStatus::PUBLISH,
				'posts_per_page' => 1,
				'meta_key'       => '_course_id',
				'meta_value'     => $course->get_id(),
			)
		);

		$count = $query->found_posts;
	}

	/**
	 * Filters lessons count.
	 *
	 * @since 1.5.17
	 *
	 * @param integer $count Lessons count.
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	return apply_filters( 'masteriyo_get_lessons_count', $count, $course );
}

/**
 * Convert minutes to time length string to display on screen.
 *
 * @since 1.0.0
 *
 * @param int    $minutes Total length in minutes.
 * @param string $format Required format. Example: "%H% : %M%". '%H%' for placing hours and '%M%' for minutes.
 *
 * @return string
 */
function masteriyo_minutes_to_time_length_string( $minutes, $format = null ) {
	$minutes = absint( $minutes );
	$hours   = absint( $minutes / 60 );
	$mins    = $minutes - $hours * 60;
	$str     = '';

	if ( is_string( $format ) ) {
		$str = str_replace( '%H%', $hours, $format );
		$str = str_replace( '%M%', $mins, $str );
	} else {
		$str .= $hours > 0 ? sprintf( '%d%s ', $hours, _x( 'h', 'h for hours', 'learning-management-system' ) ) : '';
		$str .= $mins > 0 ? sprintf( ' %d%s', $mins, _x( 'm', 'm for minutes', 'learning-management-system' ) ) : '';
		$str  = $minutes > 0 ? $str : _x( '0m', 'm for minutes', 'learning-management-system' );
	}

	return $str;
}

/**
 * Get lecture hours for a course as string to display on screen.
 *
 * @since 1.0.0
 *
 * @param int|Course|WP_Post $course
 * @param string             $format Required format. Example: "%H% : %M%". '%H%' for placing hours and '%M%' for minutes.
 *
 * @return string
 */
function masteriyo_get_lecture_hours( $course, $format = null ) {
	$course = masteriyo_get_course( $course );

	// Bail early if the course is null.
	if ( is_null( $course ) ) {
		return '';
	}

	$lessons = masteriyo_get_lessons(
		array(
			'course_id' => $course->get_id(),
		)
	);
	$mins    = 0;

	foreach ( $lessons as $lesson ) {
		$mins += $lesson->get_video_playback_time();
	}

	return masteriyo_minutes_to_time_length_string( $mins, $format );
}

/**
 * Get lecture hours for a section as string to display on screen.
 *
 * @since 1.0.0
 *
 * @param int|Section|WP_Post $course
 * @param string              $format Required format. Example: "%H% : %M%". '%H%' for placing hours and '%M%' for minutes.
 *
 * @return string
 */
function masteriyo_get_lecture_hours_of_section( $section, $format = null ) {
	$section = masteriyo_get_section( $section );

	// Bail early if the section is null.
	if ( is_null( $section ) ) {
		return '';
	}

	$lessons = masteriyo_get_lessons(
		array(
			'parent_id' => $section->get_id(),
		)
	);
	$mins    = 0;

	foreach ( $lessons as $lesson ) {
		$mins += $lesson->get_video_playback_time();
	}

	return masteriyo_minutes_to_time_length_string( $mins, $format );
}

/**
 * Make a dictionary with section id as key and its lessons as value from a course.
 *
 * @since 1.0.0
 *
 * @param int|Course|WP_Post $course
 *
 * @return array
 */
function masteriyo_make_section_to_lessons_dictionary( $course ) {
	$course = masteriyo_get_course( $course );

	// Bail early if the course is null.
	if ( is_null( $course ) ) {
		return array();
	}

	$sections = masteriyo_get_sections(
		array(
			'order'     => 'asc',
			'orderby'   => 'menu_order',
			'course_id' => $course->get_id(),
		)
	);

	$lessons = masteriyo_get_lessons(
		array(
			'order'     => 'asc',
			'orderby'   => 'menu_order',
			'course_id' => $course->get_id(),
		)
	);

	$lessons_dictionary = array();

	foreach ( $lessons as $lesson ) {
		$section_id = $lesson->get_parent_id();

		if ( ! isset( $lessons_dictionary[ $section_id ] ) ) {
			$lessons_dictionary[ $section_id ] = array();
		}

		$lessons_dictionary[ $section_id ][] = $lesson;
	}

	foreach ( $sections as $section ) {
		if ( ! isset( $lessons_dictionary[ $section->get_id() ] ) ) {
			$lessons_dictionary[ $section->get_id() ] = array();
		}
	}

	return compact( 'sections', 'lessons', 'lessons_dictionary' );
}

/** Return "theme support" values from the current theme, if set.
 *
 * @since  1.0.0
 * @param  string $prop Name of prop (or key::sub-key for arrays of props) if you want a specific value. Leave blank to get all props as an array.
 * @param  mixed  $default Optional value to return if the theme does not declare support for a prop.
 * @return mixed  Value of prop(s).
 */
function masteriyo_get_theme_support( $prop = '', $default = null ) {
	$theme_support = get_theme_support( 'masteriyo' );
	$theme_support = is_array( $theme_support ) ? $theme_support[0] : false;

	if ( ! $theme_support ) {
		return $default;
	}

	if ( $prop ) {
		$prop_stack = explode( '::', $prop );
		$prop_key   = array_shift( $prop_stack );

		if ( isset( $theme_support[ $prop_key ] ) ) {
			$value = $theme_support[ $prop_key ];

			if ( count( $prop_stack ) ) {
				foreach ( $prop_stack as $prop_key ) {
					if ( is_array( $value ) && isset( $value[ $prop_key ] ) ) {
						$value = $value[ $prop_key ];
					} else {
						$value = $default;
						break;
					}
				}
			}
		} else {
			$value = $default;
		}

		return $value;
	}

	return $theme_support;
}

/**
 * Get Currency symbol.
 *
 * Currency symbols and names should follow the Unicode CLDR recommendation (http://cldr.unicode.org/translation/currency-names)
 *
 * @since 1.0.0
 *
 * @param string $currency Currency. (default: '').
 *
 * @return string
 */
function masteriyo_get_currency_symbol( $currency = '' ) {
	if ( ! $currency ) {
		$currency = masteriyo_get_currency();
	}

	$symbols = masteriyo_get_currency_symbols();

	$currency_symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '';

	/**
	 * Filters currency symbol.
	 *
	 * @since 1.0.0
	 *
	 * @param string $currency_symbol Currency symbol.
	 * @param string $currency Currency.
	 */
	return apply_filters( 'masteriyo_currency_symbol', $currency_symbol, $currency );
}

/**
 * Get Base Currency Code.
 *
 * @since 1.0.0
 *
 * @return string
 */
function masteriyo_get_currency() {
	/**
	 * Filters base currency code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code Base currency code.
	 */
	return apply_filters( 'masteriyo_currency', masteriyo_get_setting( 'payments.currency.currency' ) );
}

/**
 * Get all available Currency symbols.
 *
 * Currency symbols and names should follow the Unicode CLDR recommendation (http://cldr.unicode.org/translation/currency-names)
 *
 * @since 1.0.0
 *
 * @return array
 */
function masteriyo_get_currency_symbols() {
	/**
	 * Filters currency symbols.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $currency_symbols Currency code to currency symbol index array.
	 */
	$symbols = apply_filters(
		'masteriyo_currency_symbols',
		array(
			'AED' => '&#x62f;.&#x625;',
			'AFN' => '&#x60b;',
			'ALL' => 'L',
			'AMD' => 'AMD',
			'ANG' => '&fnof;',
			'AOA' => 'Kz',
			'ARS' => '&#36;',
			'AUD' => '&#36;',
			'AWG' => 'Afl.',
			'AZN' => 'AZN',
			'BAM' => 'KM',
			'BBD' => '&#36;',
			'BDT' => '&#2547;&nbsp;',
			'BGN' => '&#1083;&#1074;.',
			'BHD' => '.&#x62f;.&#x628;',
			'BIF' => 'Fr',
			'BMD' => '&#36;',
			'BND' => '&#36;',
			'BOB' => 'Bs.',
			'BRL' => '&#82;&#36;',
			'BSD' => '&#36;',
			'BTC' => '&#3647;',
			'BTN' => 'Nu.',
			'BWP' => 'P',
			'BYR' => 'Br',
			'BYN' => 'Br',
			'BZD' => '&#36;',
			'CAD' => '&#36;',
			'CDF' => 'Fr',
			'CHF' => '&#67;&#72;&#70;',
			'CLP' => '&#36;',
			'CNY' => '&yen;',
			'COP' => '&#36;',
			'CRC' => '&#x20a1;',
			'CUC' => '&#36;',
			'CUP' => '&#36;',
			'CVE' => '&#36;',
			'CZK' => '&#75;&#269;',
			'DJF' => 'Fr',
			'DKK' => 'DKK',
			'DOP' => 'RD&#36;',
			'DZD' => '&#x62f;.&#x62c;',
			'EGP' => 'EGP',
			'ERN' => 'Nfk',
			'ETB' => 'Br',
			'EUR' => '&euro;',
			'FJD' => '&#36;',
			'FKP' => '&pound;',
			'GBP' => '&pound;',
			'GEL' => '&#x20be;',
			'GGP' => '&pound;',
			'GHS' => '&#x20b5;',
			'GIP' => '&pound;',
			'GMD' => 'D',
			'GNF' => 'Fr',
			'GTQ' => 'Q',
			'GYD' => '&#36;',
			'HKD' => '&#36;',
			'HNL' => 'L',
			'HRK' => 'kn',
			'HTG' => 'G',
			'HUF' => '&#70;&#116;',
			'IDR' => 'Rp',
			'ILS' => '&#8362;',
			'IMP' => '&pound;',
			'INR' => '&#8377;',
			'IQD' => '&#x639;.&#x62f;',
			'IRR' => '&#xfdfc;',
			'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
			'ISK' => 'kr.',
			'JEP' => '&pound;',
			'JMD' => '&#36;',
			'JOD' => '&#x62f;.&#x627;',
			'JPY' => '&yen;',
			'KES' => 'KSh',
			'KGS' => '&#x441;&#x43e;&#x43c;',
			'KHR' => '&#x17db;',
			'KMF' => 'Fr',
			'KPW' => '&#x20a9;',
			'KRW' => '&#8361;',
			'KWD' => '&#x62f;.&#x643;',
			'KYD' => '&#36;',
			'KZT' => '&#8376;',
			'LAK' => '&#8365;',
			'LBP' => '&#x644;.&#x644;',
			'LKR' => '&#xdbb;&#xdd4;',
			'LRD' => '&#36;',
			'LSL' => 'L',
			'LYD' => '&#x644;.&#x62f;',
			'MAD' => '&#x62f;.&#x645;.',
			'MDL' => 'MDL',
			'MGA' => 'Ar',
			'MKD' => '&#x434;&#x435;&#x43d;',
			'MMK' => 'Ks',
			'MNT' => '&#x20ae;',
			'MOP' => 'P',
			'MRU' => 'UM',
			'MUR' => '&#x20a8;',
			'MVR' => '.&#x783;',
			'MWK' => 'MK',
			'MXN' => '&#36;',
			'MYR' => '&#82;&#77;',
			'MZN' => 'MT',
			'NAD' => 'N&#36;',
			'NGN' => '&#8358;',
			'NIO' => 'C&#36;',
			'NOK' => '&#107;&#114;',
			'NPR' => '&#8360;',
			'NZD' => '&#36;',
			'OMR' => '&#x631;.&#x639;.',
			'PAB' => 'B/.',
			'PEN' => 'S/',
			'PGK' => 'K',
			'PHP' => '&#8369;',
			'PKR' => '&#8360;',
			'PLN' => '&#122;&#322;',
			'PRB' => '&#x440;.',
			'PYG' => '&#8370;',
			'QAR' => '&#x631;.&#x642;',
			'RMB' => '&yen;',
			'RON' => 'lei',
			'RSD' => '&#1088;&#1089;&#1076;',
			'RUB' => '&#8381;',
			'RWF' => 'Fr',
			'SAR' => '&#x631;.&#x633;',
			'SBD' => '&#36;',
			'SCR' => '&#x20a8;',
			'SDG' => '&#x62c;.&#x633;.',
			'SEK' => '&#107;&#114;',
			'SGD' => '&#36;',
			'SHP' => '&pound;',
			'SLL' => 'Le',
			'SOS' => 'Sh',
			'SRD' => '&#36;',
			'SSP' => '&pound;',
			'STN' => 'Db',
			'SYP' => '&#x644;.&#x633;',
			'SZL' => 'L',
			'THB' => '&#3647;',
			'TJS' => '&#x405;&#x41c;',
			'TMT' => 'm',
			'TND' => '&#x62f;.&#x62a;',
			'TOP' => 'T&#36;',
			'TRY' => '&#8378;',
			'TTD' => '&#36;',
			'TWD' => '&#78;&#84;&#36;',
			'TZS' => 'Sh',
			'UAH' => '&#8372;',
			'UGX' => 'UGX',
			'USD' => '&#36;',
			'UYU' => '&#36;',
			'UZS' => 'UZS',
			'VEF' => 'Bs F',
			'VES' => 'Bs.S',
			'VND' => '&#8363;',
			'VUV' => 'Vt',
			'WST' => 'T',
			'XAF' => 'CFA',
			'XCD' => '&#36;',
			'XOF' => 'CFA',
			'XPF' => 'Fr',
			'YER' => '&#xfdfc;',
			'ZAR' => '&#82;',
			'ZMW' => 'ZK',
		)
	);

	return $symbols;
}

/**
 * Get full list of currency codes with symbols.
 *
 * @since 1.0.0
 *
 * @return array
 */
function masteriyo_get_currencies_with_symbols() {
	$currencies = masteriyo_get_currencies();

	foreach ( $currencies as $key => $value ) {
		$currencies[ $key ] = sprintf( '%s (%s)', $value, html_entity_decode( masteriyo_get_currency_symbol( $key ) ) );
	}

	/**
	 * Filters list of currency codes with symbols.
	 *
	 * @since 1.0.0
	 *
	 * @param array $currencies List of currency codes with symbols.
	 */
	return apply_filters( 'masteriyo_currencies_with_symbols', $currencies );
}

/**
 * Get full list of currency codes.
 *
 * Currency symbols and names should follow the Unicode CLDR recommendation (http://cldr.unicode.org/translation/currency-names)
 *
 * @since 1.0.0
 *
 * @return array
 */
function masteriyo_get_currencies() {
	$currencies = array_unique(
		/**
		 * Filters full list of currency codes.
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $currencies Full list of currency codes.
		 */
		apply_filters(
			'masteriyo_currencies',
			array(
				'AED' => __( 'United Arab Emirates dirham', 'learning-management-system' ),
				'AFN' => __( 'Afghan afghani', 'learning-management-system' ),
				'ALL' => __( 'Albanian lek', 'learning-management-system' ),
				'AMD' => __( 'Armenian dram', 'learning-management-system' ),
				'ANG' => __( 'Netherlands Antillean guilder', 'learning-management-system' ),
				'AOA' => __( 'Angolan kwanza', 'learning-management-system' ),
				'ARS' => __( 'Argentine peso', 'learning-management-system' ),
				'AUD' => __( 'Australian dollar', 'learning-management-system' ),
				'AWG' => __( 'Aruban florin', 'learning-management-system' ),
				'AZN' => __( 'Azerbaijani manat', 'learning-management-system' ),
				'BAM' => __( 'Bosnia and Herzegovina convertible mark', 'learning-management-system' ),
				'BBD' => __( 'Barbadian dollar', 'learning-management-system' ),
				'BDT' => __( 'Bangladeshi taka', 'learning-management-system' ),
				'BGN' => __( 'Bulgarian lev', 'learning-management-system' ),
				'BHD' => __( 'Bahraini dinar', 'learning-management-system' ),
				'BIF' => __( 'Burundian franc', 'learning-management-system' ),
				'BMD' => __( 'Bermudian dollar', 'learning-management-system' ),
				'BND' => __( 'Brunei dollar', 'learning-management-system' ),
				'BOB' => __( 'Bolivian boliviano', 'learning-management-system' ),
				'BRL' => __( 'Brazilian real', 'learning-management-system' ),
				'BSD' => __( 'Bahamian dollar', 'learning-management-system' ),
				'BTC' => __( 'Bitcoin', 'learning-management-system' ),
				'BTN' => __( 'Bhutanese ngultrum', 'learning-management-system' ),
				'BWP' => __( 'Botswana pula', 'learning-management-system' ),
				'BYR' => __( 'Belarusian ruble (old)', 'learning-management-system' ),
				'BYN' => __( 'Belarusian ruble', 'learning-management-system' ),
				'BZD' => __( 'Belize dollar', 'learning-management-system' ),
				'CAD' => __( 'Canadian dollar', 'learning-management-system' ),
				'CDF' => __( 'Congolese franc', 'learning-management-system' ),
				'CHF' => __( 'Swiss franc', 'learning-management-system' ),
				'CLP' => __( 'Chilean peso', 'learning-management-system' ),
				'CNY' => __( 'Chinese yuan', 'learning-management-system' ),
				'COP' => __( 'Colombian peso', 'learning-management-system' ),
				'CRC' => __( 'Costa Rican col&oacute;n', 'learning-management-system' ),
				'CUC' => __( 'Cuban convertible peso', 'learning-management-system' ),
				'CUP' => __( 'Cuban peso', 'learning-management-system' ),
				'CVE' => __( 'Cape Verdean escudo', 'learning-management-system' ),
				'CZK' => __( 'Czech koruna', 'learning-management-system' ),
				'DJF' => __( 'Djiboutian franc', 'learning-management-system' ),
				'DKK' => __( 'Danish krone', 'learning-management-system' ),
				'DOP' => __( 'Dominican peso', 'learning-management-system' ),
				'DZD' => __( 'Algerian dinar', 'learning-management-system' ),
				'EGP' => __( 'Egyptian pound', 'learning-management-system' ),
				'ERN' => __( 'Eritrean nakfa', 'learning-management-system' ),
				'ETB' => __( 'Ethiopian birr', 'learning-management-system' ),
				'EUR' => __( 'Euro', 'learning-management-system' ),
				'FJD' => __( 'Fijian dollar', 'learning-management-system' ),
				'FKP' => __( 'Falkland Islands pound', 'learning-management-system' ),
				'GBP' => __( 'Pound sterling', 'learning-management-system' ),
				'GEL' => __( 'Georgian lari', 'learning-management-system' ),
				'GGP' => __( 'Guernsey pound', 'learning-management-system' ),
				'GHS' => __( 'Ghana cedi', 'learning-management-system' ),
				'GIP' => __( 'Gibraltar pound', 'learning-management-system' ),
				'GMD' => __( 'Gambian dalasi', 'learning-management-system' ),
				'GNF' => __( 'Guinean franc', 'learning-management-system' ),
				'GTQ' => __( 'Guatemalan quetzal', 'learning-management-system' ),
				'GYD' => __( 'Guyanese dollar', 'learning-management-system' ),
				'HKD' => __( 'Hong Kong dollar', 'learning-management-system' ),
				'HNL' => __( 'Honduran lempira', 'learning-management-system' ),
				'HRK' => __( 'Croatian kuna', 'learning-management-system' ),
				'HTG' => __( 'Haitian gourde', 'learning-management-system' ),
				'HUF' => __( 'Hungarian forint', 'learning-management-system' ),
				'IDR' => __( 'Indonesian rupiah', 'learning-management-system' ),
				'ILS' => __( 'Israeli new shekel', 'learning-management-system' ),
				'IMP' => __( 'Manx pound', 'learning-management-system' ),
				'INR' => __( 'Indian rupee', 'learning-management-system' ),
				'IQD' => __( 'Iraqi dinar', 'learning-management-system' ),
				'IRR' => __( 'Iranian rial', 'learning-management-system' ),
				'IRT' => __( 'Iranian toman', 'learning-management-system' ),
				'ISK' => __( 'Icelandic kr&oacute;na', 'learning-management-system' ),
				'JEP' => __( 'Jersey pound', 'learning-management-system' ),
				'JMD' => __( 'Jamaican dollar', 'learning-management-system' ),
				'JOD' => __( 'Jordanian dinar', 'learning-management-system' ),
				'JPY' => __( 'Japanese yen', 'learning-management-system' ),
				'KES' => __( 'Kenyan shilling', 'learning-management-system' ),
				'KGS' => __( 'Kyrgyzstani som', 'learning-management-system' ),
				'KHR' => __( 'Cambodian riel', 'learning-management-system' ),
				'KMF' => __( 'Comorian franc', 'learning-management-system' ),
				'KPW' => __( 'North Korean won', 'learning-management-system' ),
				'KRW' => __( 'South Korean won', 'learning-management-system' ),
				'KWD' => __( 'Kuwaiti dinar', 'learning-management-system' ),
				'KYD' => __( 'Cayman Islands dollar', 'learning-management-system' ),
				'KZT' => __( 'Kazakhstani tenge', 'learning-management-system' ),
				'LAK' => __( 'Lao kip', 'learning-management-system' ),
				'LBP' => __( 'Lebanese pound', 'learning-management-system' ),
				'LKR' => __( 'Sri Lankan rupee', 'learning-management-system' ),
				'LRD' => __( 'Liberian dollar', 'learning-management-system' ),
				'LSL' => __( 'Lesotho loti', 'learning-management-system' ),
				'LYD' => __( 'Libyan dinar', 'learning-management-system' ),
				'MAD' => __( 'Moroccan dirham', 'learning-management-system' ),
				'MDL' => __( 'Moldovan leu', 'learning-management-system' ),
				'MGA' => __( 'Malagasy ariary', 'learning-management-system' ),
				'MKD' => __( 'Macedonian denar', 'learning-management-system' ),
				'MMK' => __( 'Burmese kyat', 'learning-management-system' ),
				'MNT' => __( 'Mongolian t&ouml;gr&ouml;g', 'learning-management-system' ),
				'MOP' => __( 'Macanese pataca', 'learning-management-system' ),
				'MRU' => __( 'Mauritanian ouguiya', 'learning-management-system' ),
				'MUR' => __( 'Mauritian rupee', 'learning-management-system' ),
				'MVR' => __( 'Maldivian rufiyaa', 'learning-management-system' ),
				'MWK' => __( 'Malawian kwacha', 'learning-management-system' ),
				'MXN' => __( 'Mexican peso', 'learning-management-system' ),
				'MYR' => __( 'Malaysian ringgit', 'learning-management-system' ),
				'MZN' => __( 'Mozambican metical', 'learning-management-system' ),
				'NAD' => __( 'Namibian dollar', 'learning-management-system' ),
				'NGN' => __( 'Nigerian naira', 'learning-management-system' ),
				'NIO' => __( 'Nicaraguan c&oacute;rdoba', 'learning-management-system' ),
				'NOK' => __( 'Norwegian krone', 'learning-management-system' ),
				'NPR' => __( 'Nepalese rupee', 'learning-management-system' ),
				'NZD' => __( 'New Zealand dollar', 'learning-management-system' ),
				'OMR' => __( 'Omani rial', 'learning-management-system' ),
				'PAB' => __( 'Panamanian balboa', 'learning-management-system' ),
				'PEN' => __( 'Sol', 'learning-management-system' ),
				'PGK' => __( 'Papua New Guinean kina', 'learning-management-system' ),
				'PHP' => __( 'Philippine peso', 'learning-management-system' ),
				'PKR' => __( 'Pakistani rupee', 'learning-management-system' ),
				'PLN' => __( 'Polish z&#x142;oty', 'learning-management-system' ),
				'PRB' => __( 'Transnistrian ruble', 'learning-management-system' ),
				'PYG' => __( 'Paraguayan guaran&iacute;', 'learning-management-system' ),
				'QAR' => __( 'Qatari riyal', 'learning-management-system' ),
				'RON' => __( 'Romanian leu', 'learning-management-system' ),
				'RSD' => __( 'Serbian dinar', 'learning-management-system' ),
				'RUB' => __( 'Russian ruble', 'learning-management-system' ),
				'RWF' => __( 'Rwandan franc', 'learning-management-system' ),
				'SAR' => __( 'Saudi riyal', 'learning-management-system' ),
				'SBD' => __( 'Solomon Islands dollar', 'learning-management-system' ),
				'SCR' => __( 'Seychellois rupee', 'learning-management-system' ),
				'SDG' => __( 'Sudanese pound', 'learning-management-system' ),
				'SEK' => __( 'Swedish krona', 'learning-management-system' ),
				'SGD' => __( 'Singapore dollar', 'learning-management-system' ),
				'SHP' => __( 'Saint Helena pound', 'learning-management-system' ),
				'SLL' => __( 'Sierra Leonean leone', 'learning-management-system' ),
				'SOS' => __( 'Somali shilling', 'learning-management-system' ),
				'SRD' => __( 'Surinamese dollar', 'learning-management-system' ),
				'SSP' => __( 'South Sudanese pound', 'learning-management-system' ),
				'STN' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra', 'learning-management-system' ),
				'SYP' => __( 'Syrian pound', 'learning-management-system' ),
				'SZL' => __( 'Swazi lilangeni', 'learning-management-system' ),
				'THB' => __( 'Thai baht', 'learning-management-system' ),
				'TJS' => __( 'Tajikistani somoni', 'learning-management-system' ),
				'TMT' => __( 'Turkmenistan manat', 'learning-management-system' ),
				'TND' => __( 'Tunisian dinar', 'learning-management-system' ),
				'TOP' => __( 'Tongan pa&#x2bb;anga', 'learning-management-system' ),
				'TRY' => __( 'Turkish lira', 'learning-management-system' ),
				'TTD' => __( 'Trinidad and Tobago dollar', 'learning-management-system' ),
				'TWD' => __( 'New Taiwan dollar', 'learning-management-system' ),
				'TZS' => __( 'Tanzanian shilling', 'learning-management-system' ),
				'UAH' => __( 'Ukrainian hryvnia', 'learning-management-system' ),
				'UGX' => __( 'Ugandan shilling', 'learning-management-system' ),
				'USD' => __( 'United States (US) dollar', 'learning-management-system' ),
				'UYU' => __( 'Uruguayan peso', 'learning-management-system' ),
				'UZS' => __( 'Uzbekistani som', 'learning-management-system' ),
				'VEF' => __( 'Venezuelan bol&iacute;var', 'learning-management-system' ),
				'VES' => __( 'Bol&iacute;var soberano', 'learning-management-system' ),
				'VND' => __( 'Vietnamese &#x111;&#x1ed3;ng', 'learning-management-system' ),
				'VUV' => __( 'Vanuatu vatu', 'learning-management-system' ),
				'WST' => __( 'Samoan t&#x101;l&#x101;', 'learning-management-system' ),
				'XAF' => __( 'Central African CFA franc', 'learning-management-system' ),
				'XCD' => __( 'East Caribbean dollar', 'learning-management-system' ),
				'XOF' => __( 'West African CFA franc', 'learning-management-system' ),
				'XPF' => __( 'CFP franc', 'learning-management-system' ),
				'YER' => __( 'Yemeni rial', 'learning-management-system' ),
				'ZAR' => __( 'South African rand', 'learning-management-system' ),
				'ZMW' => __( 'Zambian kwacha', 'learning-management-system' ),
			)
		)
	);

	return $currencies;
}

/**
 * Get permalink settings for things like courses and taxonomies.
 *
 * @since  1.0.0
 *
 * @param string $id Permalink id.
 *
 * @return array
 */
function masteriyo_get_permalink_structure() {
	$get_slugs = array(
		'courses'            => masteriyo_get_setting( 'advance.permalinks.single_course_permalink' ),
		'courses_category'   => masteriyo_get_setting( 'advance.permalinks.category_base' ),
		'courses_tag'        => masteriyo_get_setting( 'advance.permalinks.tag_base' ),
		'courses_difficulty' => masteriyo_get_setting( 'advance.permalinks.difficulty_base' ),
		'lessons'            => masteriyo_get_setting( 'advance.permalinks.single_lesson_permalink' ),
		'quizzes'            => masteriyo_get_setting( 'advance.permalinks.single_quiz_permalink' ),
		'sections'           => masteriyo_get_setting( 'advance.permalinks.single_section_permalink' ),
	);

	$permalinks = array(
		'course_base'            => _x( 'course', 'slug', 'learning-management-system' ),
		'course_category_base'   => _x( 'course-category', 'slug', 'learning-management-system' ),
		'course_tag_base'        => _x( 'course-tag', 'slug', 'learning-management-system' ),
		'course_difficulty_base' => _x( 'course-difficulty', 'slug', 'learning-management-system' ),
		'lesson_base'            => _x( 'lesson', 'slug', 'learning-management-system' ),
		'quiz_base'              => _x( 'quiz', 'slug', 'learning-management-system' ),
		'section_base'           => _x( 'section', 'slug', 'learning-management-system' ),
	);

	$permalinks['course_rewrite_slug']            = untrailingslashit( empty( $get_slugs['courses'] ) ? $permalinks['course_base'] : $get_slugs['courses'] );
	$permalinks['course_category_rewrite_slug']   = untrailingslashit( empty( $get_slugs['courses_category'] ) ? $permalinks['course_category_base'] : $get_slugs['courses_category'] );
	$permalinks['course_tag_rewrite_slug']        = untrailingslashit( empty( $get_slugs['courses_tag'] ) ? $permalinks['course_tag_base'] : $get_slugs['courses_tag'] );
	$permalinks['course_difficulty_rewrite_slug'] = untrailingslashit( empty( $get_slugs['courses_difficulty'] ) ? $permalinks['course_difficulty_base'] : $get_slugs['courses_difficulty'] );
	$permalinks['lesson_rewrite_slug']            = untrailingslashit( empty( $get_slugs['lessons'] ) ) ? $permalinks['lesson_base'] : $get_slugs['lessons'];
	$permalinks['quiz_rewrite_slug']              = untrailingslashit( empty( $get_slugs['quizzes'] ) ) ? $permalinks['quiz_base'] : $get_slugs['quizzes'];
	$permalinks['section_rewrite_slug']           = untrailingslashit( empty( $get_slugs['sections'] ) ) ? $permalinks['section_base'] : $get_slugs['sections'];

	return $permalinks;
}

/**
 * Check whether to flush rules or not after settings saved.
 *
 * @since 1.0.0
 */
function masteriyo_maybe_flush_rewrite() {

	if ( 'yes' === get_option( 'masteriyo_flush_rewrite_rules' ) ) {
		update_option( 'masteriyo_flush_rewrite_rules', 'no' );
		flush_rewrite_rules();
	}
}
function_exists( 'add_action' ) && add_action( 'masteriyo_after_register_post_type', 'masteriyo_maybe_flush_rewrite' );

/**
 * Filter to allow course_cat in the permalinks for course.
 *
 * @since 1.0.0
 *
 * @param  string  $permalink The existing permalink URL.
 * @param  WP_Post $post WP_Post object.
 * @return string
 */
function masteriyo_course_post_type_link( $permalink, $post ) {
	// Abort if post is not a course.
	if ( 'mto-course' !== $post->post_type ) {
		return $permalink;
	}

	// Abort early if the placeholder rewrite tag isn't in the generated URL.
	if ( false === strpos( $permalink, '%' ) ) {
		return $permalink;
	}

	// Get the custom taxonomy terms in use by this post.
	$terms = get_the_terms( $post->ID, 'course_cat' );

	if ( ! empty( $terms ) ) {
		$terms = wp_list_sort(
			$terms,
			array(
				'parent'  => 'DESC',
				'term_id' => 'ASC',
			)
		);

		/**
		 * Filters course category object to be used in generating course post type link.
		 *
		 * @since 1.0.0
		 *
		 * @param \WP_Term $course_category Course category term object.
		 * @param \WP_Term[] $terms Queried course category term objects.
		 * @param \WP_Post $post WP post object.
		 */
		$category_object = apply_filters( 'masteriyo_course_post_type_link_course_cat', $terms[0], $terms, $post );

		$course_cat = $category_object->slug;

		if ( $category_object->parent ) {
			$ancestors = get_ancestors( $category_object->term_id, 'course_cat' );
			foreach ( $ancestors as $ancestor ) {
				$ancestor_object = get_term( $ancestor, 'course_cat' );

				/**
				 * Filters boolean: true if only parent category should be included in course post type permalink. Default is false.
				 *
				 * @since 1.0.0
				 *
				 * @param boolean $bool true if only parent category should be included in course post type permalink.
				 */
				if ( apply_filters( 'masteriyo_course_post_type_link_parent_category_only', false ) ) {
					$course_cat = $ancestor_object->slug;
				} else {
					$course_cat = $ancestor_object->slug . '/' . $course_cat;
				}
			}
		}
	} else {
		// If no terms are assigned to this post, use a string instead (can't leave the placeholder there).
		$course_cat = _x( 'uncategorized', 'slug', 'learning-management-system' );
	}

	$find = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		'%post_id%',
		'%category%',
		'%course_cat%',
	);

	$replace = array(
		date_i18n( 'Y', strtotime( $post->post_date ) ),
		date_i18n( 'm', strtotime( $post->post_date ) ),
		date_i18n( 'd', strtotime( $post->post_date ) ),
		date_i18n( 'H', strtotime( $post->post_date ) ),
		date_i18n( 'i', strtotime( $post->post_date ) ),
		date_i18n( 's', strtotime( $post->post_date ) ),
		$post->ID,
		$course_cat,
		$course_cat,
	);

	$permalink = str_replace( $find, $replace, $permalink );

	return $permalink;
}
function_exists( 'add_filter' ) && add_filter( 'post_type_link', 'masteriyo_course_post_type_link', 10, 2 );

/**
 * Switch Masteriyo to site language.
 *
 * @since 1.0.0
 */
function masteriyo_switch_to_site_locale() {
	if ( function_exists( 'switch_to_locale' ) ) {
		switch_to_locale( get_locale() );

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		// Init masteriyo locale.
		masteriyo()->load_text_domain();
	}
}

/**
 * Switch Masteriyo language to original.
 *
 * @since 1.0.0
 */
function masteriyo_restore_locale() {
	if ( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		// Remove filter.
		remove_filter( 'plugin_locale', 'get_locale' );

		// Init masteriyo locale.
		masteriyo()->load_text_domain();
	}
}

/**
 * Define a constant if it is not already defined.
 *
 * @since 1.0.0
 * @param string $name  Constant name.
 * @param mixed  $value Value.
 */
function masteriyo_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}


/**
 * Wrapper for nocache_headers which also disables page caching.
 *
 * @since 1.0.0
 */
function masteriyo_nocache_headers() {
	masteriyo_set_nocache_constants();
	nocache_headers();
}


/**
 * Set constants to prevent caching by some plugins.
 *
 * @since 1.0.0
 *
 * @param  mixed $return Value to return. Previously hooked into a filter.
 * @return mixed
 */
function masteriyo_set_nocache_constants( $return = true ) {
	masteriyo_maybe_define_constant( 'DONOTCACHEPAGE', true );
	masteriyo_maybe_define_constant( 'DONOTCACHEOBJECT', true );
	masteriyo_maybe_define_constant( 'DONOTCACHEDB', true );
	return $return;
}

/**
 * Gets the url to the cart page.
 *
 * @since  1.0.0
 *
 * @return string Url to cart page
 */
function masteriyo_get_cart_url() {
	/**
	 * Filters cart page URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Cart page URL.
	 */
	return apply_filters( 'masteriyo_get_cart_url', masteriyo_get_page_permalink( 'cart' ) );
}

/**
 * Gets the url to the checkout page.
 *
 * @since  1.0.0
 *
 * @return string Url to checkout page
 */
function masteriyo_get_checkout_url() {
	/**
	 * Filters Checkout page URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Checkout page URL.
	 */
	return apply_filters( 'masteriyo_get_checkout_url', masteriyo_get_page_permalink( 'checkout' ) );
}

/**
 * Gets the url to the courses page.
 *
 * @since  1.0.0
 *
 * @return string Url to courses page
 */
function masteriyo_get_courses_url() {
	/**
	 * Filters Courses page URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Courses page URL.
	 */
	return apply_filters( 'masteriyo_get_courses_url', masteriyo_get_page_permalink( 'courses' ) );
}

/**
 * Gets the url to the account page.
 *
 * @since  1.0.0
 *
 * @return string Url to checkout page
 */
function masteriyo_get_account_url() {
	global $wp_rewrite;
	$account_url = '';

	if ( is_object( $wp_rewrite ) && ! empty( $wp_rewrite->rules ) ) {
			$account_url = masteriyo_get_page_permalink( 'account' );
	} else {
			$account_url = get_home_url();
	}

	/**
	 * Filters Account page URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Account page URL.
	 */
	return apply_filters( 'masteriyo_get_account_url', $account_url );
}

/**
 * Get current endpoint in the account page.
 *
 * @since 1.0.0
 *
 * @return string
 */
function masteriyo_get_current_account_endpoint() {
	global $wp;

	$endpoints = array_flip( masteriyo_get_account_endpoints() );

	if ( ! empty( $wp->query_vars ) ) {
		foreach ( $wp->query_vars as $key => $value ) {
			// Ignore pagename param.
			if ( 'pagename' === $key ) {
				continue;
			}

			if ( isset( $endpoints[ $key ] ) ) {
				return array(
					'endpoint' => $endpoints[ $key ],
					'slug'     => $key,
					'arg'      => $value,
				);
			}
		}
	}

	// No endpoint found? Default to dashboard.
	return array(
		'endpoint' => 'dashboard',
		'slug'     => 'dashboard',
		'arg'      => null,
	);
}

/**
 * Return default value if the given value is empty. Uses the php function `empty`.
 *
 * @since 1.0.0
 *
 * @param mixed $value
 * @param mixed $default
 *
 * @return mixed
 */
function if_empty( $value, $default = null ) {
	if ( empty( $value ) ) {
		return $default;
	}
	return $value;
}

/**
 * Get account endpoints' slugs.
 *
 * @since 1.0.0
 *
 * @return array
 */
function masteriyo_get_account_endpoints() {
	/**
	 * Filters account endpoints.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $endpoints Endpoint ID to endpoint slug index array.
	 */
	return apply_filters(
		'masteriyo_account_endpoints',
		array(
			'dashboard'      => 'dashboard',
			'view-account'   => masteriyo_get_setting( 'advance.account.view_account' ),
			'edit-account'   => masteriyo_get_setting( 'advance.account.edit_account' ),
			'courses'        => masteriyo_get_setting( 'advance.account.my_courses' ),
			'order-history'  => masteriyo_get_setting( 'advance.account.order_history' ),
			'reset-password' => masteriyo_get_setting( 'advance.account.lost_password' ),
			'signup'         => masteriyo_get_setting( 'advance.account.signup' ),
			'user-logout'    => masteriyo_get_setting( 'advance.account.logout' ),
			'view-order'     => masteriyo_get_setting( 'advance.account.view_order' ),
		)
	);
}

/**
 * Get account endpoint URL.
 *
 * @since 1.0.0
 *
 * @param string $endpoint Endpoint.
 *
 * @return string
 */
function masteriyo_get_account_endpoint_url( $endpoint ) {
	if ( 'dashboard' === $endpoint ) {
		return masteriyo_get_page_permalink( 'account' );
	}

	if ( 'user-logout' === $endpoint ) {
		return masteriyo_logout_url();
	}

	return masteriyo_get_endpoint_url( $endpoint, '', masteriyo_get_page_permalink( 'account' ) );
}


/**
 * Get endpoint URL.
 *
 * Gets the URL for an endpoint, which varies depending on permalink settings.
 *
 * @since 1.0.0
 *
 * @param  string $endpoint  Endpoint slug.
 * @param  string $value     Query param value.
 * @param  string $permalink Permalink.
 *
 * @return string
 */
function masteriyo_get_endpoint_url( $endpoint, $value = '', $permalink = '' ) {
	if ( ! $permalink ) {
		$permalink = get_permalink();
	}

	// Map endpoint to options.
	$query_vars = masteriyo( 'query.frontend' )->get_query_vars();
	$endpoint   = ! empty( $query_vars[ $endpoint ] ) ? $query_vars[ $endpoint ] : $endpoint;

	if ( get_option( 'permalink_structure' ) ) {
		if ( strstr( $permalink, '?' ) ) {
			$query_string = '?' . wp_parse_url( $permalink, PHP_URL_QUERY );
			$permalink    = current( explode( '?', $permalink ) );
		} else {
			$query_string = '';
		}
		$url = trailingslashit( $permalink );

		if ( $value ) {
			$url .= trailingslashit( $endpoint ) . user_trailingslashit( $value );
		} else {
			$url .= user_trailingslashit( $endpoint );
		}

		$url .= $query_string;
	} else {
		$url = add_query_arg( $endpoint, $value, $permalink );
	}

	/**
	 * Filters endpoint URL, which varies depending on permalink settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Endpoint URL.
	 * @param string $endpoint Endpoint slug.
	 * @param string $value Query param value.
	 * @param string $permalink Permalink.
	 */
	return apply_filters( 'masteriyo_get_endpoint_url', $url, $endpoint, $value, $permalink );
}

/**
 * Get logout endpoint.
 *
 * @since 1.0.0
 *
 * @param string $redirect Redirect URL.
 *
 * @return string
 */
function masteriyo_logout_url( $redirect = '' ) {
	/**
	 * Filters default redirection URL after logout.
	 *
	 * Default is account page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Redirection URL.
	 */
	$redirect = $redirect ? $redirect : apply_filters( 'masteriyo_logout_default_redirect_url', masteriyo_get_page_permalink( 'account' ) );

	if ( masteriyo_get_setting( 'logout', 'advance', 'account' ) ) {
		return wp_nonce_url( masteriyo_get_endpoint_url( 'user-logout', '', $redirect ), 'user-logout' );
	}

	return wp_logout_url( $redirect );
}

/**
 * Get a svg file contents.
 *
 * @since 1.0.0
 *
 * @param string  $name SVG filename.
 * @param boolean $echo Whether to echo the contents or not.
 *
 * @return void|string
 */
function masteriyo_get_svg( $name, $echo = false ) {
	$filesystem = masteriyo_get_filesystem();

	if ( ! $filesystem ) {
		return;
	}

	$file_name     = Constants::get( 'MASTERIYO_ASSETS' ) . "/svg/{$name}.svg";
	$file_contents = '';

	if ( file_exists( $file_name ) && is_readable( $file_name ) ) {
		$file_contents = $filesystem->get_contents( $file_name );
	}

	/**
	 * Filters svg file content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_content SVG file content.
	 * @param string $name SVG file name.
	 */
	$file_contents = apply_filters( 'masteriyo_svg_file', $file_contents, $name );

	$svg_args = array(
		'svg'   => array(
			'class'           => true,
			'aria-hidden'     => true,
			'aria-labelledby' => true,
			'role'            => true,
			'xmlns'           => true,
			'width'           => true,
			'height'          => true,
			'viewbox'         => true, // <= Must be lower case!
			'viewBox'         => true,
			'fill'            => true,
		),
		'g'     => array( 'fill' => true ),
		'title' => array( 'title' => true ),
		'path'  => array(
			'd'         => true,
			'fill'      => true,
			'fill-rule' => true,
			'clip-rule' => true,
		),
	);

	if ( $echo ) {
		echo wp_kses( $file_contents, $svg_args );
	} else {
		return $file_contents;
	}
}

/**
 * Get Account menu items.
 *
 * @since 1.0.0
 *
 * @return array
 */
function masteriyo_get_account_menu_items() {
	$endpoints = masteriyo_get_account_endpoints();
	$items     = array(
		'dashboard'     => array(
			'label' => __( 'Dashboard', 'learning-management-system' ),
			'icon'  => masteriyo_get_svg( 'dashboard' ),
		),
		'courses'       => array(
			'label' => __( 'My Courses', 'learning-management-system' ),
			'icon'  => masteriyo_get_svg( 'courses' ),
		),
		'view-account'  => array(
			'label' => __( 'Account Details', 'learning-management-system' ),
			'icon'  => masteriyo_get_svg( 'account-details' ),
		),
		'edit-account'  => array(
			'label' => __( 'Edit Account', 'learning-management-system' ),
			'icon'  => masteriyo_get_svg( 'edit-account' ),
		),
		'order-history' => array(
			'label' => __( 'My Order History', 'learning-management-system' ),
			'icon'  => masteriyo_get_svg( 'order-history' ),
		),
		'user-logout'   => array(
			'label' => __( 'Logout', 'learning-management-system' ),
			'icon'  => masteriyo_get_svg( 'user-logout' ),
		),
	);

	// Remove missing endpoints.
	foreach ( $endpoints as $endpoint_id => $endpoint ) {
		if ( empty( $endpoint ) ) {
			unset( $items[ $endpoint_id ] );
		}
	}

	/**
	 * Filters account menu items.
	 *
	 * @since 1.0.0
	 *
	 * @param array $items Account menu items.
	 * @param array $endpoints Account endpoints.
	 */
	return apply_filters( 'masteriyo_account_menu_items', $items, $endpoints );
}

if ( ! function_exists( 'masteriyo_create_new_user_username' ) ) {
	/**
	 * Create a unique username for a new customer.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email New customer email address.
	 * @param array  $new_user_args Array of new user args, maybe including first and last names.
	 * @param string $suffix Append string to username to make it unique.
	 *
	 * @return string Generated username.
	 */
	function masteriyo_create_new_user_username( $email, $new_user_args = array(), $suffix = '' ) {
		$username_parts = array();

		if ( isset( $new_user_args['first_name'] ) ) {
			$username_parts[] = sanitize_user( $new_user_args['first_name'], true );
		}

		if ( isset( $new_user_args['last_name'] ) ) {
			$username_parts[] = sanitize_user( $new_user_args['last_name'], true );
		}

		// Remove empty parts.
		$username_parts = array_filter( $username_parts );

		// If there are no parts, e.g. name had unicode chars, or was not provided, fallback to email.
		if ( empty( $username_parts ) ) {
			$email_parts    = explode( '@', $email );
			$email_username = $email_parts[0];

			// Exclude common prefixes.
			if ( in_array(
				$email_username,
				array(
					'sales',
					'hello',
					'mail',
					'contact',
					'info',
				),
				true
			) ) {
				// Get the domain part.
				$email_username = $email_parts[1];
			}

			$username_parts[] = sanitize_user( $email_username, true );
		}

		$username = masteriyo_strtolower( implode( '.', $username_parts ) );

		if ( $suffix ) {
			$username .= $suffix;
		}

		/**
		 * WordPress 4.4 - filters the list of blocked usernames.
		 *
		 * @since 1.0.0
		 *
		 * @param array $usernames Array of blocked usernames.
		 */
		$illegal_logins = (array) apply_filters( 'illegal_user_logins', array() );

		// Stop illegal logins and generate a new random username.
		if ( in_array( strtolower( $username ), array_map( 'strtolower', $illegal_logins ), true ) ) {
			$new_args = array();

			/**
			 * Filter generated username.
			 *
			 * @since 1.0.0
			 *
			 * @param string $username      Generated username.
			 * @param string $email         New user email address.
			 * @param array  $new_user_args Array of new user args, maybe including first and last names.
			 * @param string $suffix        Append string to username to make it unique.
			 */
			$new_args['first_name'] = apply_filters(
				'masteriyo_generated_username',
				'masteriyo_user_' . zeroise( wp_rand( 0, 9999 ), 4 ),
				$email,
				$new_user_args,
				$suffix
			);

			return masteriyo_create_new_user_username( $email, $new_args, $suffix );
		}

		if ( username_exists( $username ) ) {
			// Generate something unique to append to the username in case of a conflict with another user.
			$suffix = '-' . zeroise( wp_rand( 0, 9999 ), 4 );
			return masteriyo_create_new_user_username( $email, $new_user_args, $suffix );
		}

		/**
		 * Filter new customer username.
		 *
		 * @since 1.0.0
		 *
		 * @param string $username      Customer username.
		 * @param string $email         New customer email address.
		 * @param array  $new_user_args Array of new user args, maybe including first and last names.
		 * @param string $suffix        Append string to username to make it unique.
		 */
		return apply_filters( 'masteriyo_new_user_username', $username, $email, $new_user_args, $suffix );
	}
}

if ( ! function_exists( 'masteriyo_create_new_user' ) ) {
	/**
	 * Create a new user.
	 *
	 * @since 1.0.0
	 *
	 * @since 1.2.0 Added roles parameter.
	 *
	 * @param string       $email User email.
	 * @param string       $username User username.
	 * @param string       $password User password.
	 * @param string|array $role User roles.
	 * @param array        $args List of other arguments.
	 *
	 * @return int|User|WP_Error Returns WP_Error on failure, Int (user ID) on success.
	 */
	function masteriyo_create_new_user( $email, $username = '', $password = '', $roles = 'masteriyo_student', $args = array() ) {
		if ( empty( $email ) || ! is_email( $email ) ) {
			return new \WP_Error( 'registration-error-invalid-email', __( 'Please provide a valid email address.', 'learning-management-system' ) );
		}

		if ( email_exists( $email ) ) {
			/**
			 * Filters error message for already existing email address while creating new user.
			 *
			 * @since 1.0.0
			 *
			 * @param string $message The error message.
			 * @param string $email The email address.
			 */
			$message = apply_filters( 'masteriyo_registration_error_email_exists', __( 'An account is already registered with your email address.', 'learning-management-system' ), $email );
			return new \WP_Error( 'registration-error-email-exists', $message );
		}

		if ( masteriyo_registration_is_generate_username() && empty( $username ) ) {
			$username = masteriyo_create_new_user_username( $email, $args );
		}

		$username = sanitize_user( $username );

		if ( empty( $username ) || ! validate_username( $username ) ) {
			return new \WP_Error( 'registration-error-invalid-username', __( 'Please enter a valid account username.', 'learning-management-system' ) );
		}

		if ( username_exists( $username ) ) {
			return new \WP_Error( 'registration-error-username-exists', __( 'An account is already registered with that username. Please choose another.', 'learning-management-system' ) );
		}

		// Handle password creation.
		$password_generated = false;
		if ( empty( $password ) && masteriyo_registration_is_generate_password() ) {
			$password           = wp_generate_password();
			$password_generated = true;
		}

		if ( empty( $password ) ) {
			return new \WP_Error( 'registration-error-missing-password', __( 'Please enter an account password.', 'learning-management-system' ) );
		}

		// Use WP_Error to handle registration errors.
		$errors = new \WP_Error();

		/**
		 * Fires before creating a user.
		 *
		 * @since 1.0.0
		 *
		 * @param string $username Username.
		 * @param string $email Email address.
		 * @param \WP_Error $errors Validation errors.
		 */
		do_action( 'masteriyo_register_post', $username, $email, $errors );

		/**
		 * Filters errors while registering new user.
		 *
		 * @since 1.0.0
		 *
		 * @param \WP_Error $errors Errors object.
		 * @param string $username The new user's username.
		 * @param string $email The new user's email.
		 */
		$errors = apply_filters( 'masteriyo_registration_errors', $errors, $username, $email );

		if ( $errors->get_error_code() ) {
			return $errors;
		}

		/** @var \Masteriyo\Models\User $user */
		$user = masteriyo( 'user' );
		$user->set_props( (array) $args );
		$user->set_username( $username );
		$user->set_password( $password );
		$user->set_email( $email );
		$user->set_roles( $roles );

		if ( ! $password_generated && masteriyo_is_email_verification_enabled() ) {
			$user->set_status( UserStatus::SPAM );
		} else {
			$user->set_status( UserStatus::ACTIVE );
		}

		if ( $password_generated ) {
			$user->set_auto_create_user( true );
		}

		$user->save();

		if ( ! $user->get_id() ) {
			return new \WP_Error( 'registration-failure', __( 'Registration failed.', 'learning-management-system' ) );
		}

		$wp_user = get_user_by( 'email', $user->get_email() );

		if ( ! $wp_user ) {
			throw new \Exception( __( 'Invalid username or email', 'learning-management-system' ) );
		}

		$args['password'] = $password;

		/**
		 * Fires after creating a new user.
		 *
		 * @since 1.0.0
		 *
		 * @since 1.13.3 Added $args parameter.
		 *
		 * @param \Masteriyo\Models\User $user User object.
		 * @param string $password_generated The generated password.
		 * @param array $args List of other arguments.
		 */
		do_action( 'masteriyo_created_customer', $user, $password_generated, $args );

		return $user;
	}
}

/**
 * Login a customer (set auth cookie and set global user object).
 *
 * @since 1.0.0
 *
 * @param int $user_id Customer ID.
 */
function masteriyo_set_customer_auth_cookie( $user_id ) {
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );

	// Update session.
	masteriyo( 'session' );
}

/**
 * Get course review.
 *
 * @since 1.0.0
 *
 * @param  int|WP_Comment|Model $course_review Object ID or WP_Comment or Model.
 * @return CourseReview|null
 */
function masteriyo_get_course_review( $course_review ) {
	$course_review_obj   = masteriyo( 'course_review' );
	$course_review_store = masteriyo( 'course_review.store' );

	if ( is_a( $course_review, 'Masteriyo\Models\CourseReview' ) ) {
		$id = $course_review->get_id();
	} elseif ( is_a( $course_review, 'WP_Comment' ) ) {
		$id = $course_review->comment_ID;
	} else {
		$id = $course_review;
	}

	try {
		$id = absint( $id );
		$course_review_obj->set_id( $id );
		$course_review_store->read( $course_review_obj );
	} catch ( \Exception $e ) {
		return null;
	}

	/**
	 * Filters course review object.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\CourseReview $course_review_obj Course review object.
	 * @param int|WP_Comment|\Masteriyo\Models\CourseReview $course_review Object ID or WP_Comment or Model.
	 */
	return apply_filters( 'masteriyo_get_course_review', $course_review_obj, $course_review );
}

/**
 * Get lesson review.
 *
 * @since 1.14.0
 *
 * @param  int|WP_Comment|Model $lesson_review Object ID or WP_Comment or Model.
 * @return LessonReview|null
 */
function masteriyo_get_lesson_review( $lesson_review ) {
	$lesson_review_obj   = masteriyo( 'lesson_review' );
	$lesson_review_store = masteriyo( 'lesson_review.store' );

	if ( is_a( $lesson_review, 'Masteriyo\Models\LessonReview' ) ) {
		$id = $lesson_review->get_id();
	} elseif ( is_a( $lesson_review, 'WP_Comment' ) ) {
		$id = $lesson_review->comment_ID;
	} else {
		$id = $lesson_review;
	}

	try {
		$id = absint( $id );
		$lesson_review_obj->set_id( $id );
		$lesson_review_store->read( $lesson_review_obj );
	} catch ( \Exception $e ) {
		return null;
	}

	/**
	 * Filters lesson review object.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\CourseReview $lesson_review_obj Course review object.
	 * @param int|WP_Comment|\Masteriyo\Models\CourseReview $lesson_review Object ID or WP_Comment or Model.
	 */
	return apply_filters( 'masteriyo_get_lesson_review', $lesson_review_obj, $lesson_review );
}

/**
 * Get count of a lesson review's replies.
 *
 * @since 1.14.0
 *
 * @param integer $lesson_review_id
 *
 * @return integer
 */
function masteriyo_get_lesson_review_replies_count( $lesson_review_id ) {
	global $wpdb;

	$replies_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT COUNT(*) FROM $wpdb->comments
			WHERE comment_parent = %d
			AND comment_approved = '1'
			AND comment_type = 'mto_lesson_review'
			",
			absint( $lesson_review_id )
		)
	);

	/**
	 * Filters replies count for a lesson review.
	 *
	 * @since 1.5.0
	 *
	 * @param integer $replies_count
	 * @param integer $lesson_review_id
	 */
	return apply_filters( 'masteriyo_get_lesson_review_replies_count', absint( $replies_count ), $lesson_review_id );
}


/**
 * Get quiz review.
 *
 * @since 1.7.0
 *
 * @param  int|WP_Comment|Model $quiz_review Object ID or WP_Comment or Model.
 * @return QuizReview|null
 */
function masteriyo_get_quiz_review( $quiz_review ) {
	$quiz_review_obj   = masteriyo( 'quiz_review' );
	$quiz_review_store = masteriyo( 'quiz_review.store' );

	if ( is_a( $quiz_review, 'Masteriyo\Models\QuizReview' ) ) {
		$id = $quiz_review->get_id();
	} elseif ( is_a( $quiz_review, 'WP_Comment' ) ) {
		$id = $quiz_review->comment_ID;
	} else {
		$id = $quiz_review;
	}

	try {
		$id = absint( $id );
		$quiz_review_obj->set_id( $id );
		$quiz_review_store->read( $quiz_review_obj );
	} catch ( \Exception $e ) {
		return null;
	}

	/**
	 * Filters quiz review object.
	 *
	 * @since 1.7.0
	 *
	 * @param \Masteriyo\Models\QuizReview $quiz_review_obj Quiz review object.
	 * @param int|WP_Comment|\Masteriyo\Models\QuizReview $quiz_review Object ID or WP_Comment or Model.
	 */
	return apply_filters( 'masteriyo_get_quiz_review', $quiz_review_obj, $quiz_review );
}

/**
 * Set password reset cookie.
 *
 * @since 1.0.0
 *
 * @param string $value Cookie value.
 */
function masteriyo_set_password_reset_cookie( $value = '' ) {
	$rp_cookie = 'wp-resetpass-' . COOKIEHASH;
	$rp_path   = isset( $_SERVER['REQUEST_URI'] ) ? current( explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : ''; // WPCS: input var ok, sanitization ok.

	if ( $value ) {
		setcookie( $rp_cookie, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
	} else {
		setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );
	}
}

/**
 * Get password reset link.
 *
 * @since 1.0.0
 *
 * @param string $reset_key
 * @param int    $user_id
 */
function masteriyo_get_password_reset_link( $reset_key, $user_id ) {
	return add_query_arg(
		array(
			'key' => $reset_key,
			'id'  => $user_id,
		),
		masteriyo_get_account_endpoint_url( 'reset-password' )
	);
}

/**
 * Create a page and store the ID in an option.
 *
 * @since 1.0.0
 *
 * @param mixed  $slug Slug for the new page.
 * @param string $setting_name Setting name to store the page's ID.
 * @param string $page_title (default: '') Title for the new page.
 * @param string $page_content (default: '') Content for the new page.
 * @param int    $post_parent (default: 0) Parent for the new page.
 *
 * @return int page ID.
 */
function masteriyo_create_page( $slug, $setting_name = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
	global $wpdb;

	$previous_value = masteriyo_get_setting( "general.pages.{$setting_name}" );

	if ( $previous_value > 0 ) {
		$page_object = get_post( $previous_value );

		if ( $page_object && 'page' === $page_object->post_type && ! in_array( $page_object->post_status, array( PostStatus::PENDING, PostStatus::TRASH, PostStatus::FUTURE, PostStatus::AUTO_DRAFT ), true ) ) {
			// Valid page is already in place.
			if ( strlen( $page_content ) > 0 ) {
				// Search for an existing page with the specified page content (typically a shortcode).
				$valid_page_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
			} else {
				// Search for an existing page with the specified page slug.
				$valid_page_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' )  AND post_name = %s LIMIT 1;", $slug ) );
			}

			/**
			 * Filters page ID that was already created.
			 *
			 * @since 1.0.0
			 *
			 * @param integer $page_id The already created page ID.
			 * @param string $slug Page slug.
			 * @param string $content Page content.
			 */
			$valid_page_id = apply_filters( 'masteriyo_create_page_id', $valid_page_id, $slug, $page_content );

			if ( $valid_page_id ) {
				return $valid_page_id;
			}
		}
	}

	// Search for a matching valid trashed page.
	if ( strlen( $page_content ) > 0 ) {
		// Search for an existing page with the specified page content (typically a shortcode).
		$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
	} else {
		$trashed_slug = $slug . '__trashed';
		// Search for an existing page with the specified page slug.
		$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_name = %s LIMIT 1;", $trashed_slug ) );
	}

	$page_id = $trashed_page_found;

	if ( ! $page_id ) {
		$post_page = get_page_by_path( $slug, OBJECT, 'page' );

		if ( $post_page instanceof \WP_Post ) {
			$page_id = $post_page->ID;
		}
	}

	if ( $page_id ) {
		$page_data = array(
			'ID'          => $page_id,
			'post_status' => PostStatus::PUBLISH,
		);
		wp_update_post( $page_data );
	} else {
		$page_data = array(
			'post_status'    => PostStatus::PUBLISH,
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => $slug,
			'post_title'     => $page_title,
			'post_content'   => $page_content,
			'post_parent'    => $post_parent,
			'comment_status' => 'closed',
		);
		$page_id   = wp_insert_post( $page_data );
	}
	return $page_id;
}

/**
 * Add a post display state for special masteriyo pages in the page list table.
 *
 * @since 1.0.0
 *
 * @param array   $post_states An array of post display states.
 * @param WP_Post $post        The current post object.
 */
function masteriyo_add_post_state( $post_states, $post ) {
	if ( masteriyo_get_page_id( 'courses' ) === $post->ID ) {
		$post_states['masteriyo_courses_page'] = __( 'Masteriyo Courses Page', 'learning-management-system' );
	}

	if ( masteriyo_get_page_id( 'account' ) === $post->ID ) {
		$post_states['masteriyo_account_page'] = __( 'Masteriyo Account Page', 'learning-management-system' );
	}

	if ( masteriyo_get_page_id( 'checkout' ) === $post->ID ) {
		$post_states['masteriyo_checkout_page'] = __( 'Masteriyo Checkout Page', 'learning-management-system' );
	}

	if ( masteriyo_get_page_id( 'learn' ) === $post->ID ) {
		$post_states['masteriyo_learn_page'] = __( 'Masteriyo Learn Page', 'learning-management-system' );
	}

	if ( masteriyo_get_page_id( 'instructor-registration' ) === $post->ID ) {
		$post_states['masteriyo_instructor_registration_page'] = __( 'Masteriyo Instructor Registration Page', 'learning-management-system' );
	}

	if ( masteriyo_get_page_id( 'instructors-list' ) === $post->ID ) {
		$post_states['masteriyo_instructors_list_page'] = __( 'Masteriyo Instructors List Page', 'learning-management-system' );
	}

	return $post_states;
}
function_exists( 'add_filter' ) && add_filter( 'display_post_states', 'masteriyo_add_post_state', 10, 2 );

function masteriyo_asort_by_locale( &$data, $locale = '' ) {
	// Use Collator if PHP Internationalization Functions (php-intl) is available.
	if ( class_exists( 'Collator' ) ) {
		$locale   = $locale ? $locale : get_locale();
		$collator = new Collator( $locale );
		$collator->asort( $data, Collator::SORT_STRING );
		return $data;
	}

	$raw_data = $data;

	array_walk(
		$data,
		function ( &$value ) {
			$value = remove_accents( html_entity_decode( $value ) );
		}
	);

	uasort( $data, 'strcmp' );

	foreach ( $data as $key => $val ) {
		$data[ $key ] = $raw_data[ $key ];
	}

	return $data;
}

/**
 * Get the store's base location.
 *
 * @since 1.0.0
 * @return array
 */
function masteriyo_get_base_location() {
	/**
	 * Filters base store location.
	 *
	 * @since 1.0.0
	 *
	 * @param string $location Base location.
	 */
	$default = apply_filters( 'masteriyo_get_base_location', get_option( 'masteriyo_default_country', 'US:CA' ) );

	return masteriyo_format_country_state_string( $default );
}

/**
 * Formats a string in the format COUNTRY:STATE into an array.
 *
 * @since 2.3.0
 * @param  string $country_string Country string.
 * @return array
 */
function masteriyo_format_country_state_string( $country_string ) {
	if ( strstr( $country_string, ':' ) ) {
		list( $country, $state ) = explode( ':', $country_string );
	} else {
		$country = $country_string;
		$state   = '';
	}
	return array(
		'country' => $country,
		'state'   => $state,
	);
}

/**
 * Check whether redirect to cart after course is added.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function masteriyo_cart_redirect_after_add() {
	$redirect_after_add = get_option( 'masteriyo_cart_redirect_after_add', true );
	$redirect_after_add = masteriyo_string_to_bool( $redirect_after_add );

	/**
	 * Filters boolean: true if it should redirected after adding an item to cart.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $bool true if it should redirected after adding an item to cart.
	 */
	return apply_filters( 'masteriyo_cart_redirect_after_add', $redirect_after_add );
}

/**
 * Add precision to a number and return a number.
 *
 * @since  1.0.0
 * @param  float $value Number to add precision to.
 * @param  bool  $round If should round after adding precision.
 * @return int|float
 */
function masteriyo_add_number_precision( $value, $round = true ) {
	$cent_precision = pow( 10, masteriyo_get_price_decimals() );
	$value          = $value * $cent_precision;
	return $round ? masteriyo_round( $value, masteriyo_get_rounding_precision() - masteriyo_get_price_decimals() ) : $value;
}

/**
 * Add precision to an array of number and return an array of int.
 *
 * @since  1.0.0
 * @param  array $value Number to add precision to.
 * @param  bool  $round Should we round after adding precision?.
 * @return int|array
 */
function masteriyo_add_number_precision_deep( $value, $round = true ) {
	if ( ! is_array( $value ) ) {
		return masteriyo_add_number_precision( $value, $round );
	}

	foreach ( $value as $key => $sub_value ) {
		$value[ $key ] = masteriyo_add_number_precision_deep( $sub_value, $round );
	}

	return $value;
}

/**
 * Remove precision from a number and return a float.
 *
 * @since  1.0.0
 * @param  float $value Number to add precision to.
 * @return float
 */
function masteriyo_remove_number_precision( $value ) {
	$cent_precision = pow( 10, masteriyo_get_price_decimals() );
	return $value / $cent_precision;
}

/**
 * Remove precision from an array of number and return an array of int.
 *
 * @since  1.0.0
 * @param  array $value Number to add precision to.
 * @return int|array
 */
function masteriyo_remove_number_precision_deep( $value ) {
	if ( ! is_array( $value ) ) {
		return masteriyo_remove_number_precision( $value );
	}

	foreach ( $value as $key => $sub_value ) {
		$value[ $key ] = masteriyo_remove_number_precision_deep( $sub_value );
	}

	return $value;
}

/**
 * Wrapper for set_time_limit to see if it is enabled.
 *
 * @since 1.0.0.
 *
 * @param int $limit Time limit.
 */
function masteriyo_set_time_limit( $limit = 0 ) {
	if ( ! function_exists( 'set_time_limit' ) ) {
		return;
	}

	if ( true === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) ) {
		return;
	}

	if ( ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		return;
	}

	@set_time_limit( $limit ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
}

/**
 * Get data if set, otherwise return a default value or null. Prevents notices when data is not set.
 *
 * @since  1.0.0
 * @param  mixed  $var     Variable.
 * @param  string $default Default value.
 * @return mixed
 */
function masteriyo_get_var( &$var, $default = null ) {
	return isset( $var ) ? $var : $default;
}

/**
 * User to sort checkout fields based on priority with uasort.
 *
 * @since 1.0.0
 * @param array $a First field to compare.
 * @param array $b Second field to compare.
 * @return int
 */
function masteriyo_checkout_fields_uasort_comparison( $a, $b ) {
	/*
	 * We are not guaranteed to get a priority
	 * setting. So don't compare if they don't
	 * exist.
	 */
	if ( ! isset( $a['priority'], $b['priority'] ) ) {
		return 0;
	}

	return masteriyo_uasort_comparison( $a['priority'], $b['priority'] );
}

/**
 * User to sort two values with ausort.
 *
 * @since 1.0.0
 * @param int $a First value to compare.
 * @param int $b Second value to compare.
 * @return int
 */
function masteriyo_uasort_comparison( $a, $b ) {
	if ( $a === $b ) {
		return 0;
	}
	return ( $a < $b ) ? -1 : 1;
}

/**
 * Get user agent string.
 *
 * @since  1.0.0
 * @return string
 */
function masteriyo_get_user_agent() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? masteriyo_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
}

/**
 * Get WordPress user roles.
 *
 * @since 1.0.0
 * @return string[]
 */
function masteriyo_get_wp_roles() {
	$roles = wp_roles();
	return array_keys( $roles->role_names );
}

/**
 * Get currency code.
 *
 * @since 1.0.0
 *
 * @return string[]
 */
function masteriyo_get_currency_codes() {
	return array_keys( masteriyo_get_currencies() );
}

/**
 * Wrapper for _doing_it_wrong().
 *
 * @since  1.0.0
 *
 * @param string $function Function used.
 * @param string $message Message to log.
 * @param string $version Version the message was added in.
 */
function masteriyo_doing_it_wrong( $function, $message, $version ) {
	// phpcs: disable
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

	if ( masteriyo_is_ajax() || masteriyo_is_rest_api_request() ) {
		/**
		 * Fires when the given function is being used incorrectly.
		 *
		 * @since 1.0.0
		 *
		 * @see https://developer.wordpress.org/reference/hooks/doing_it_wrong_run/
		 *
		 * @param string $function Function used.
		 * @param string $message Message to log.
		 * @param string $version Version the message was added in.
		 */
		do_action( 'doing_it_wrong_run', $function, $message, $version );
		error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
	} else {
		_doing_it_wrong( $function, $message, $version );
	}
	// phpcs: enable
}

/**
 * Return the Masteriyo API URL for a given request.
 *
 * @since 1.0.0
 *
 * @param string    $request Requested endpoint.
 * @param bool|null $ssl     If should use SSL, null if should auto detect. Default: null.
 * @return string
 */
function masteriyo_api_request_url( $request, $ssl = null ) {
	if ( is_null( $ssl ) ) {
		$scheme = wp_parse_url( home_url(), PHP_URL_SCHEME );
	} elseif ( $ssl ) {
		$scheme = 'https';
	} else {
		$scheme = 'http';
	}

	if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
		$api_request_url = trailingslashit( home_url( '/index.php/masteriyo-api/' . $request, $scheme ) );
	} elseif ( get_option( 'permalink_structure' ) ) {
		$api_request_url = trailingslashit( home_url( '/masteriyo-api/' . $request, $scheme ) );
	} else {
		$api_request_url = add_query_arg( 'masteriyo-api', $request, trailingslashit( home_url( '', $scheme ) ) );
	}

	/**
	 * Filters masteriyo API request URL for a given request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_request_url API request URL.
	 * @param string $request Requested endpoint.
	 * @param boolean|null $ssl If should use SSL, null if should auto detect. Default: null.
	 */
	return esc_url_raw( apply_filters( 'masteriyo_api_request_url', $api_request_url, $request, $ssl ) );
}

/**
 * Prints human-readable information about a variable.
 *
 * Some server environments block some debugging functions. This function provides a safe way to
 * turn an expression into a printable, readable form without calling blocked functions.
 *
 * @since 1.0.0
 *
 * @param mixed $expression The expression to be printed.
 * @param bool  $return     Optional. Default false. Set to true to return the human-readable string.
 * @return string|bool False if expression could not be printed. True if the expression was printed.
 *     If $return is true, a string representation will be returned.
 */
function masteriyo_print_r( $expression, $return = false ) {
	$alternatives = array(
		array(
			'func' => 'print_r',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'var_export',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'json_encode',
			'args' => array( $expression ),
		),
		array(
			'func' => 'serialize',
			'args' => array( $expression ),
		),
	);

	/**
	 * Filters print_r function alternatives that will be called.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $alternatives print_r alternative functions.
	 */
	$alternatives = apply_filters( 'masteriyo_print_r_alternatives', $alternatives, $expression );

	foreach ( $alternatives as $alternative ) {
		if ( function_exists( $alternative['func'] ) ) {
			$res = $alternative['func']( ...$alternative['args'] );
			if ( $return ) {
				return $res;
			}

			echo wp_kses_post( $res );
			return true;
		}
	}

	return false;
}

/**
 * Get Masteriyo version.
 *
 * @since 1.0.0
 *
 * @return string
 */
function masteriyo_get_version() {
	return Constants::get( 'MASTERIYO_VERSION' );
}

/**
 * Get Masteriyo plugin url.
 *
 * @since 1.0.0
 *
 * @return string
 */
function masteriyo_get_plugin_url() {
	return untrailingslashit( plugin_dir_url( Constants::get( 'MASTERIYO_PLUGIN_FILE' ) ) );
}

/**
 * Get Masteriyo plugin url.
 *
 * @since 1.3.10
 *
 * @return string
 */
function masteriyo_get_plugin_dir() {
	return untrailingslashit( plugin_dir_path( Constants::get( 'MASTERIYO_PLUGIN_FILE' ) ) );
}

/**
 * Get available lesson video sources.
 *
 * @since 1.0.0
 *
 * @return array
 */
function masteriyo_get_lesson_video_sources() {
	/**
	 * Filters lesson video sources.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $sources Lesson video sources.
	 */
	return apply_filters(
		'masteriyo_lesson_video_sources',
		array(
			'self-hosted' => __( 'Self Hosted', 'learning-management-system' ),
			'youtube'     => __( 'YouTube', 'learning-management-system' ),
			'vimeo'       => __( 'Vimeo', 'learning-management-system' ),
			'embed-video' => __( 'Embed Video', 'learning-management-system' ),
			'live-stream' => __( 'Live Stream', 'learning-management-system' ),
		)
	);
}

/**
 * Generate URL for a self hosted lesson video file.
 *
 * @since 1.0.0
 *
 * @param integer|string $lesson_id
 *
 * @return string
 */
function masteriyo_generate_self_hosted_lesson_video_url( $lesson_id ) {
	$lesson = masteriyo_get_lesson( $lesson_id );

	if ( is_null( $lesson ) ) {
		return '';
	}

	$url = add_query_arg(
		array(
			'masteriyo_lesson_vid' => 'yes',
			'course_id'            => $lesson->get_course_id(),
			'lesson_id'            => $lesson->get_id(),
		),
		home_url( '/' )
	);

	/**
	 * Filters generated URL of a self-hosted lesson video.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The generated URL.
	 * @param \Masteriyo\Models\Lesson $lesson The lesson object.
	 */
	return apply_filters( 'masteriyo_self_hosted_lesson_video_url', trim( $url ), $lesson );
}

/**
 * Get setting object containing all the masteriyo settings.
 *
 * @since 1.0.0
 *
 * @return Setting
 */
function masteriyo_get_settings() {
	$setting      = masteriyo( 'setting' );
	$setting_repo = masteriyo( 'setting.store' );
	$setting_repo->read( $setting );

	return $setting;
}

/**
 * Get user activity statuses.
 *
 * @since 1.0.0
 * @deprecated 1.4.6
 *
 * @return array
 */
function masteriyo_get_user_activity_statuses() {
	/**
	 * Filters user activity statuses.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $statuses User activity statuses.
	 */
	return apply_filters(
		'masteriyo_user_activity_statuses',
		array(
			'started',
			'progress',
			'completed',
		)
	);
}

/**
 * Get ip address for current request.
 *
 * @since 1.0.0
 *
 * @return string
 */
function masteriyo_get_current_ip_address() {
	$geolocation = Geolocation::geolocate_ip( '', true );
	return $geolocation['ip_address'];
}

/**
 * Get placeholder image for an author of a course review.
 *
 * @since 1.0.0
 *
 * @return string
 */
function masteriyo_get_course_review_author_pp_placeholder() {
	/**
	 * Filters course review author profile picture placeholder image URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Course review author profile picture placeholder image URL.
	 */
	return apply_filters( 'masteriyo_course_review_author_pp_placeholder', 'https://www.pngitem.com/pimgs/m/30-307416_profile-icon-png-image-free-download-searchpng-employee.png' );
}

/**
 * Get course reviews and replies.
 *
 * @since 1.0.0
 * @since 1.5.9 Added parameter $page.
 * @since 1.5.9 Added parameter $per_page.
 *
 * @param integer|string|\Masteriyo\Models\Course|\WP_Post $course_id Course ID or object.
 * @param integer                                          $page Page number if paginating. Default 1.
 * @param integer|string                                   $per_page Items per page if paginating. Default empty string, gets all items.
 * @param string                                           $search Search query. Default empty string.
 * @param integer                                          $rating Rating. Default 0.
 *
 * @return array
 */
function masteriyo_get_course_reviews_and_replies( $course_id, $page = 1, $per_page = '', $search = '', $rating = 0 ) {
	$course = masteriyo_get_course( $course_id );

	if ( is_null( $course ) ) {
		return array(
			'reviews' => array(),
			'replies' => array(),
		);
	}

	$args = array(
		'course_id' => $course->get_id(),
		'status'    => array( 'approve', 'trash' ),
		'per_page'  => $per_page,
		'search'    => $search,
		'page'      => $page,
		'paginate'  => true,
		'parent'    => 0,
	);

	$rating = absint( $rating );

	if ( $rating ) {
		$args['comment_karma'] = $rating;
	}

	$result             = masteriyo_get_course_reviews( $args );
	$course_reviews     = $result->course_review;
	$filtered_reviews   = array();
	$indexed_replies    = array();
	$reply_counts       = array();
	$trash_reply_counts = array();
	$course_review_ids  = array();

	foreach ( $course_reviews as $review ) {
		$course_review_ids[] = $review->get_id();
	}

	$all_replies = masteriyo_get_replies_of_course_reviews( $course_review_ids );

	// Count replies.
	foreach ( $all_replies as $reply ) {
		$review_id = $reply->get_parent();

		if ( ! isset( $trash_reply_counts[ $review_id ] ) ) {
			$trash_reply_counts[ $review_id ] = 0;
		}
		if ( CommentStatus::TRASH === $reply->get_status() ) {
			$trash_reply_counts[ $review_id ] += 1;
		}
		if ( ! isset( $reply_counts[ $review_id ] ) ) {
			$reply_counts[ $review_id ] = 0;
		}

		$reply_counts[ $review_id ] += 1;

		if ( ! isset( $indexed_replies[ $review_id ] ) ) {
			$indexed_replies[ $review_id ] = array();
		}

		if ( CommentStatus::TRASH === $reply->get_status() ) {
			continue;
		}

		$indexed_replies[ $review_id ][] = $reply;
	}

	// Remove unnecessary items.
	foreach ( $course_reviews as $review ) {
		$review_id = $review->get_id();

		if ( CommentStatus::TRASH === $review->get_status() ) {
			if (
				! isset( $indexed_replies[ $review_id ] ) ||
				$reply_counts[ $review_id ] === $trash_reply_counts[ $review_id ]
			) {
				continue;
			}
		}
		$filtered_reviews[] = $review;

		if ( isset( $indexed_replies[ $review_id ] ) && $reply_counts[ $review_id ] === $trash_reply_counts[ $review_id ] ) {
			unset( $indexed_replies[ $review_id ] );
		}
	}

	return array(
		'reviews'       => $filtered_reviews,
		'replies'       => $indexed_replies,
		'viewed_total'  => $result->total,
		'max_num_pages' => $result->max_num_pages,
	);
}

/**
 * Get quiz reviews and replies.
 *
 * @since 1.7.0
 *
 * @param integer|string|\Masteriyo\Models\Quiz|\WP_Post $quiz_id Quiz ID or object.
 * @param integer                                          $page Page number if paginating. Default 1.
 * @param integer|string                                   $per_page Items per page if paginating. Default empty string, gets all items.
 *
 * @return array
 */
function masteriyo_get_quiz_reviews_and_replies( $quiz_id, $page = 1, $per_page = '' ) {
	$quiz = masteriyo_get_quiz( $quiz_id );

	if ( is_null( $quiz ) ) {
		return array(
			'reviews' => array(),
			'replies' => array(),
		);
	}

	$result             = masteriyo_get_quiz_reviews(
		array(
			'quiz_id'  => $quiz->get_id(),
			'status'   => array( 'approve', 'trash' ),
			'per_page' => $per_page,
			'page'     => $page,
			'paginate' => true,
			'parent'   => 0,
		)
	);
	$quiz_reviews       = $result->quiz_review;
	$filtered_reviews   = array();
	$indexed_replies    = array();
	$reply_counts       = array();
	$trash_reply_counts = array();
	$quiz_review_ids    = array();

	foreach ( $quiz_reviews as $review ) {
		$quiz_review_ids[] = $review->get_id();
	}

	$all_replies = masteriyo_get_replies_of_quiz_reviews( $quiz_review_ids );

	// Count replies.
	foreach ( $all_replies as $reply ) {
		$review_id = $reply->get_parent();

		if ( ! isset( $trash_reply_counts[ $review_id ] ) ) {
			$trash_reply_counts[ $review_id ] = 0;
		}
		if ( CommentStatus::TRASH === $reply->get_status() ) {
			$trash_reply_counts[ $review_id ] += 1;
		}
		if ( ! isset( $reply_counts[ $review_id ] ) ) {
			$reply_counts[ $review_id ] = 0;
		}

		$reply_counts[ $review_id ] += 1;

		if ( ! isset( $indexed_replies[ $review_id ] ) ) {
			$indexed_replies[ $review_id ] = array();
		}

		if ( CommentStatus::TRASH === $reply->get_status() ) {
			continue;
		}

		$indexed_replies[ $review_id ][] = $reply;
	}

	// Remove unnecessary items.
	foreach ( $quiz_reviews as $review ) {
		$review_id = $review->get_id();

		if ( CommentStatus::TRASH === $review->get_status() ) {
			if (
				! isset( $indexed_replies[ $review_id ] ) ||
				$reply_counts[ $review_id ] === $trash_reply_counts[ $review_id ]
			) {
				continue;
			}
		}
		$filtered_reviews[] = $review;

		if ( isset( $indexed_replies[ $review_id ] ) && $reply_counts[ $review_id ] === $trash_reply_counts[ $review_id ] ) {
			unset( $indexed_replies[ $review_id ] );
		}
	}

	return array(
		'reviews' => $filtered_reviews,
		'replies' => $indexed_replies,
	);
}


/**
 * Get an image size by name or defined dimensions.
 *
 * The returned variable is filtered by masteriyo_get_image_size_{image_size} filter to
 * allow 3rd party customization.
 *
 * Sizes defined by the theme take priority over settings. Settings are hidden when a theme
 * defines sizes.
 *
 * @since 1.0.0
 *
 * @param array|string $image_size Name of the image size to get, or an array of dimensions.
 * @return array Array of dimensions including width, height, and cropping mode. Cropping mode is 0 for no crop, and 1 for hard crop.
 */
function masteriyo_get_image_size( $image_size ) {
	$cache_key = 'size-' . ( is_array( $image_size ) ? implode( '-', $image_size ) : $image_size );
	$size      = wp_cache_get( $cache_key, 'learning-management-system' );

	if ( $size ) {
		return $size;
	}

	$size = array(
		'width'  => 600,
		'height' => 600,
		'crop'   => 1,
	);

	if ( is_array( $image_size ) ) {
		$size       = array(
			'width'  => isset( $image_size[0] ) ? absint( $image_size[0] ) : 600,
			'height' => isset( $image_size[1] ) ? absint( $image_size[1] ) : 600,
			'crop'   => isset( $image_size[2] ) ? absint( $image_size[2] ) : 1,
		);
		$image_size = $size['width'] . '_' . $size['height'];
	} else {
		$image_size = str_replace( 'masteriyo_', '', $image_size );

		if ( 'single' === $image_size ) {
			$size['width']  = absint( masteriyo_get_theme_support( 'single_image_width', get_option( 'masteriyo_single_image_width', 600 ) ) );
			$size['height'] = '';
			$size['crop']   = 0;

		} elseif ( 'gallery_thumbnail' === $image_size ) {
			$size['width']  = absint( masteriyo_get_theme_support( 'gallery_thumbnail_image_width', 100 ) );
			$size['height'] = $size['width'];
			$size['crop']   = 1;

		} elseif ( 'thumbnail' === $image_size ) {
			$size['width'] = absint( masteriyo_get_theme_support( 'thumbnail_image_width', get_option( 'masteriyo_thumbnail_image_width', 300 ) ) );
			$cropping      = get_option( 'masteriyo_thumbnail_cropping', '1:1' );

			if ( 'uncropped' === $cropping ) {
				$size['height'] = '';
				$size['crop']   = 0;
			} elseif ( 'custom' === $cropping ) {
				$width          = max( 1, get_option( 'masteriyo_thumbnail_cropping_custom_width', '4' ) );
				$height         = max( 1, get_option( 'masteriyo_thumbnail_cropping_custom_height', '3' ) );
				$size['height'] = absint( masteriyo_round( ( $size['width'] / $width ) * $height ) );
				$size['crop']   = 1;
			} else {
				$cropping_split = explode( ':', $cropping );
				$width          = max( 1, current( $cropping_split ) );
				$height         = max( 1, end( $cropping_split ) );
				$size['height'] = absint( masteriyo_round( ( $size['width'] / $width ) * $height ) );
				$size['crop']   = 1;
			}
		}
	}

	/**
	 * Filters image size.
	 *
	 * @since 1.0.0
	 *
	 * @param array $size Image size values.
	 */
	$size = apply_filters( 'masteriyo_get_image_size_' . $image_size, $size );

	wp_cache_set( $cache_key, $size, 'learning-management-system' );

	return $size;
}

/**
 * Get the global setting value.
 *
 * @since  1.0.0
 * @param  string $name Name of setting to get.
 * @return mixed
 */
function masteriyo_get_setting( $name ) {
	$setting_in_db = get_option( 'masteriyo_settings', array() );
	$settings      = array_replace_recursive( masteriyo_get_default_settings(), $setting_in_db );

	if ( empty( $name ) ) {
		$value = $settings;
	} else {
		$value = masteriyo_array_get( $settings, $name );
	}

	return $value;
}

/**
 * Get the global setting value.
 *
 * @since  1.0.0
 * @param string $name Name of setting to get.
 * @param string $value Setting value.
 * @return mixed
 */
function masteriyo_set_setting( $name, $value ) {
	$setting      = masteriyo( 'setting' );
	$setting_repo = masteriyo( 'setting.store' );

	$setting_repo->read( $setting );
	$setting->set( $name, $value );
	$setting->save();
}


if ( ! function_exists( 'masteriyo_get_default_settings' ) ) {
	/**
	 * Returns the default settings for the Masteriyo plugin.
	 *
	 * @since 1.12.2
	 *
	 * @return array The default settings.
	 */
	function masteriyo_get_default_settings() {
		return array(
			'general'        => array(
				'styling'       => array(
					'primary_color' => '#4584FF',
					'theme'         => 'minimum',
				),
				'widgets_css'   => '',
				'pages'         => array(
					'courses_page_id'                 => '',
					'account_page_id'                 => '',
					'checkout_page_id'                => '',
					'learn_page_id'                   => '',
					'instructor_registration_page_id' => '',
					'course_thankyou_page'            => array(
						'display_type' => '',
						'page_id'      => 0,
						'custom_url'   => '',
					),
					'instructors_list_page_id'        => '',
					'after_checkout_page'             => array(
						'display_type' => 'default',
						'page_id'      => 0,
						'custom_url'   => '',
					),
				),
				'course_access' => array(
					'enable_course_content_access_without_enrollment' => true,
					'restrict_instructors' => true,
				),
				'registration'  => array(
					'enable_student_registration'    => true,
					'enable_instructor_registration' => true,
					'enable_guest_checkout'          => true,
				),
				'player'        => array(
					'enable_watch_full_video'          => false,
					'use_masteriyo_player_for_youtube' => true,
					'use_masteriyo_player_for_vimeo'   => true,
					'seek_time'                        => 5,
					'unmuted_autoplay'                 => false,
				),
			),
			'course_archive' => array(
				'display'               => array(
					'view_mode'      => 'grid-view',
					'enable_search'  => true,
					'per_page'       => 12,
					'per_row'        => 3,
					'thumbnail_size' => 'masteriyo_thumbnail',
					'order_by'       => 'date',
					'order'          => 'DESC',
					'template'       => array(
						'custom_template' => array(
							'enable'          => false,
							'template_source' => 'elementor',
							'template_id'     => 0,
						),
						'layout'          => 'default',
					),
				),
				'filters_and_sorting'   => array(
					'enable_filters'                 => false,
					'enable_category_filter'         => true,
					'enable_difficulty_level_filter' => true,
					'enable_price_type_filter'       => true,
					'enable_price_filter'            => true,
					'enable_rating_filter'           => true,
					'enable_sorting'                 => false,
					'enable_date_sorting'            => true,
					'enable_price_sorting'           => true,
					'enable_rating_sorting'          => true,
					'enable_course_title_sorting'    => true,
				),
				'components_visibility' => array(
					'single_course_visibility' => true,
					'thumbnail'                => true,
					'difficulty_badge'         => true,
					'course_badge'             => true,
					'featured_ribbon'          => true,
					'categories'               => true,
					'course_title'             => true,
					'author'                   => true,
					'author_avatar'            => true,
					'author_name'              => true,
					'rating'                   => true,
					'course_description'       => true,
					'metadata'                 => true,
					'course_duration'          => true,
					'students_count'           => true,
					'lessons_count'            => true,
					'card_footer'              => true,
					'price'                    => true,
					'enroll_button'            => true,
					'seats_for_students'       => true,
					'date_updated'             => true,
					'date_started'             => true,
					'course_progress'          => true,
				),
				'custom_template'       => array(
					'enable'          => false,
					'template_source' => 'elementor',
					'template_id'     => 0,
				),
				'layout'                => 'default',
				'course_card_styles'    => array(
					'button_size'            => 16,
					'button_radius'          => 2,
					'course_title_font_size' => 16,
					'highlight_side'         => 'left',
				),
			),
			'single_course'  => array(
				'display'         => array(
					'enable_review'                     => true,
					'enable_review_enrolled_users_only' => false,
					'auto_approve_reviews'              => true,
					'course_visibility'                 => false,
					'template'                          => array(
						'custom_template' => array(
							'enable'          => false,
							'template_source' => 'elementor',
							'template_id'     => 0,
						),
						'layout'          => 'default',
					),
				),
				'related_courses' => array(
					'enable' => true,
				),
			),
			'learn_page'     => array(
				'general' => array(
					'logo_id'                => '',
					'auto_load_next_content' => false,
					'lesson_video_url_type'  => 'masteriyo',
				),
				'display' => array(
					'enable_questions_answers' => true,
					'enable_focus_mode'        => false,
					'show_sidebar'             => false,
					'show_header'              => false,
					'enable_lesson_comment'    => false,
					'auto_approve_comments'    => true,
				),
			),
			'payments'       => array(
				'store'           => array(
					'country'       => '',
					'city'          => '',
					'state'         => '',
					'address_line1' => '',
					'address_line2' => '',
				),
				'currency'        => array(
					'currency'           => 'USD',
					'currency_position'  => 'left',
					'thousand_separator' => ',',
					'decimal_separator'  => '.',
					'number_of_decimals' => 2,
				),
				'offline'         => array(
					// Offline payment
					'enable'        => false,
					'title'         => 'Offline payment',
					'description'   => 'Pay with offline payment.',
					'instructions'  => 'Pay with offline payment',
					'wire_transfer' => array(
						'enable'              => false,
						'title'               => 'Wire transfer instructions',
						'bank_name'           => '',
						'account_holder_name' => '',
						'account_number'      => '',
						'swift_code'          => '',
						'description'         => 'If you prefer wire transfer please transfer the total amount to the bank account provided below. Include your order number as the payment reference.',
					),
				),
				'paypal'          => array(
					// Standard Paypal
					'enable'                  => false,
					'title'                   => 'Paypal',
					'description'             => 'Pay via PayPal; you can pay with your credit card if you don\'t have a PayPal account.',
					'ipn_email_notifications' => true,
					'sandbox'                 => false,
					'email'                   => '',
					'receiver_email'          => '',
					'identity_token'          => '',
					'invoice_prefix'          => 'masteriyo-',
					'payment_action'          => 'sale',
					'image_url'               => '',
					'debug'                   => false,
					'sandbox_api_username'    => '',
					'sandbox_api_password'    => '',
					'live_api_username'       => '',
					'live_api_password'       => '',
					'live_api_signature'      => '',
					'sandbox_api_signature'   => '',

				),
				'checkout_fields' => array(
					// Checkout Fields
					'address_1'         => false,
					'address_2'         => false,
					'company'           => false,
					'country'           => false,
					'customer_note'     => false,
					'attachment_upload' => false,
					'phone'             => false,
					'postcode'          => false,
					'state'             => false,
					'city'              => false,
				),
			),
			'quiz'           => array(
				'display' => array(
					'quiz_completion_button'              => false,
					'quiz_review_visibility'              => false,
					'enable_quiz_previously_visited_page' => true,
				),
				'styling' => array(
					'questions_display_per_page' => 5,
				),
				'general' => array(
					'quiz_access'               => 'default',
					'automatically_submit_quiz' => true,
				),
			),
			'emails'         => array(
				'admin'      => array(
					'new_order'               => masteriyo_get_default_email_contents()['admin']['new_order'],
					'instructor_apply'        => masteriyo_get_default_email_contents()['admin']['instructor_apply'],
					'new_withdraw_request'    => masteriyo_get_default_email_contents()['admin']['new_withdraw_request'],
					'instructor_registration' => masteriyo_get_default_email_contents()['admin']['instructor_registration'],
					'student_registration'    => masteriyo_get_default_email_contents()['admin']['student_registration'],
					'course_start'            => masteriyo_get_default_email_contents()['admin']['course_start'],
					'course_completion'       => masteriyo_get_default_email_contents()['admin']['course_completion'],
					'new_quiz_attempt'        => masteriyo_get_default_email_contents()['admin']['new_quiz_attempt'],
				),
				'instructor' => array(
					'instructor_registration'   => masteriyo_get_default_email_contents()['instructor']['instructor_registration'],
					'instructor_apply_approved' => masteriyo_get_default_email_contents()['instructor']['instructor_apply_approved'],
					'withdraw_request_pending'  => masteriyo_get_default_email_contents()['instructor']['withdraw_request_pending'],
					'withdraw_request_approved' => masteriyo_get_default_email_contents()['instructor']['withdraw_request_approved'],
					'course_start'              => masteriyo_get_default_email_contents()['instructor']['course_start'],
					'course_completion'         => masteriyo_get_default_email_contents()['instructor']['course_completion'],
					'withdraw_request_rejected' => masteriyo_get_default_email_contents()['instructor']['withdraw_request_rejected'],
					'new_quiz_attempt'          => masteriyo_get_default_email_contents()['instructor']['new_quiz_attempt'],
				),
				'student'    => array(
					'student_registration'      => masteriyo_get_default_email_contents()['student']['student_registration'],
					'automatic_registration'    => masteriyo_get_default_email_contents()['student']['automatic_registration'],
					'instructor_apply_rejected' => masteriyo_get_default_email_contents()['student']['instructor_apply_rejected'],
					'completed_order'           => masteriyo_get_default_email_contents()['student']['completed_order'],
					'onhold_order'              => masteriyo_get_default_email_contents()['student']['onhold_order'],
					'cancelled_order'           => masteriyo_get_default_email_contents()['student']['cancelled_order'],
					'course_completion'         => masteriyo_get_default_email_contents()['student']['course_completion'],
					'group_course_enroll'       => masteriyo_get_default_email_contents()['student']['group_course_enroll'],
					'group_joining'             => masteriyo_get_default_email_contents()['student']['group_joining'],
				),
				'everyone'   => array(
					'password_reset'     => masteriyo_get_default_email_contents()['everyone']['password_reset'],
					'email_verification' => masteriyo_get_default_email_contents()['everyone']['email_verification'],
				),
			),
			'notification'   => array(
				'student' => array(
					'course_enroll'   => array(
						'type'    => 'course_enroll',
						'content' => 'You have successfully enrolled into this course.',
					),
					'course_complete' => array(
						'type'    => 'course_complete',
						'content' => 'You have successfully completed this course.',
					),
					'created_order'   => array(
						'type'    => 'created_order',
						'content' => 'Your order is successfully created.',
					),
					'completed_order' => array(
						'type'    => 'completed_order',
						'content' => 'Your order is completed.',
					),
					'onhold_order'    => array(
						'type'    => 'onhold_order',
						'content' => 'Your order is on-hold.',
					),
					'cancelled_order' => array(
						'type'    => 'cancelled_order',
						'content' => 'Your order is cancelled.',
					),
				),
			),
			'authentication' => array(
				'email_verification'  => array(
					'enable' => true,
				),
				'limit_login_session' => 0,
				'qr_login'            => array(
					'enable'            => false,
					'attention_message' => 'Attention: Possession of the QR code or login link grants login access to anyone.',
				),
			),
			'advance'        => array(
				'permalinks' => array(
					'category_base'           => 'course-category',
					'tag_base'                => 'course-tag',
					'difficulty_base'         => 'course-difficulty',
					'single_course_permalink' => 'course',
				),
				// Checkout endpoints.
				'checkout'   => array(
					'pay'                        => 'order-pay',
					'order_received'             => 'order-received',
					'add_payment_method'         => 'add-payment-method',
					'delete_payment_method'      => 'delete-payment-method',
					'set_default_payment_method' => 'set-default-payment-method',
				),
				'debug'      => array(
					'template_debug' => false,
					'debug'          => false,
					'enable_logger'  => false,
				),
				'uninstall'  => array(
					'remove_data' => false,
				),
				'tracking'   => array(
					'allow_usage'       => false,
					'subscribe_updates' => false,
					'email'             => get_bloginfo( 'admin_email' ),
				),
				'gdpr'       => array(
					'enable'  => false,
					'message' => "Check the box to confirm you've read our",
				),
				'openai'     => array(
					'api_key' => '',
					'enable'  => false,
				),
				'editor'     => array(
					'default_editor' => 'classic_editor',
				),
			),
			'accounts_page'  => array(
				'display' => array(
					'enable_history_page'     => true,
					'enable_invoice'          => false,
					'enable_profile_page'     => true,
					'enable_instructor_apply' => true,
					'enable_edit_profile'     => true,
					'enable_google_meet'      => false,
					'enable_certificate_page' => true,
					'layout'                  => array(
						'enable_header_footer' => true,
					),
				),
			),
		);
	}
}

/**
 * Get page ID using page slug.
 *
 * @param string $page_slug
 * @return ID
 */
function masteriyo_get_page_id_by_slug( $page_slug ) {
	$page = get_page_by_path( $page_slug );
	if ( $page ) {
		return $page->ID;
	} else {
		return -1;
	}
}

/**
 * Check whether the string starts with substring.
 *
 * @since 1.0.0
 * @return bool
 */
function masteriyo_starts_with( $haystack, $needle ) {
	return substr( $haystack, 0, strlen( $needle ) ) === $needle;
}

/**
 * Check whether the string ends with substring.
 *
 * @since 1.0.0
 * @return bool
 */
function masteriyo_ends_with( $haystack, $needle ) {
	return substr( $haystack, -strlen( $needle ) ) === $needle;
}

/**
 * Get Masteriyo plugin path.
 *
 * @since 1.0.0
 *
 * @return string
 */
function masteriyo_get_plugin_path() {
	return untrailingslashit( plugin_dir_path( Constants::get( 'MASTERIYO_PLUGIN_FILE' ) ) );
}

/**
 * Get Masteriyo templates path.
 *
 * @since 1.0.0
 *
 * @return string
 */
function masteriyo_get_templates_path() {
	return untrailingslashit( Constants::get( 'MASTERIYO_TEMPLATES' ) );
}

/**
 * Get site logo data.
 *
 * @since 1.0.0
 *
 * @return array|false Logo data.
 */
function masteriyo_get_custom_logo_data() {
	$custom_logo_id = get_theme_mod( 'custom_logo' );
	$image_data     = wp_get_attachment_image_src( $custom_logo_id, 'full' );

	/**
	 * Filters custom logo data.
	 *
	 * @since 1.0.0
	 *
	 * @param array|false $image_data Custom logo data.
	 */
	return apply_filters( 'masteriyo_custom_logo', $image_data );
}

/**
 * Get allowed svg elements to use in wp_kses functions.
 *
 * @since 1.0.0
 *
 * @return array
 */
function masteriyo_get_allowed_svg_elements() {
	/**
	 * Filters allowed svg elements and attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $elements_and_attrs The allowed svg elements and attributes.
	 */
	return apply_filters(
		'masteriyo_allowed_svg_elements',
		array(
			'svg'   => array(
				'class'           => true,
				'aria-hidden'     => true,
				'aria-labelledby' => true,
				'role'            => true,
				'xmlns'           => true,
				'width'           => true,
				'height'          => true,
				'viewbox'         => true,
			),
			'g'     => array( 'fill' => true ),
			'title' => array( 'title' => true ),
			'path'  => array(
				'd'    => true,
				'fill' => true,
			),
		)
	);
}

/**
 * Lightens/darkens a given colour (hex format), returning the altered colour in hex format.
 *
 * @see: https://gist.github.com/stephenharris/5532899
 *
 * @since 1.0.4
 *
 * @param string $hex Colour as hexadecimal (with or without hash);
 * @percent float $percent Decimal ( 0.2 = lighten by 20%(), -0.4 = darken by 40%() )
 * @return string|false Lightened/Darkened colour as hexadecimal (with hash); (False if the string is not in hexcolor )
 */
function masteriyo_color_luminance( $hex, $percent ) {
	// validate hex string
	$hex     = preg_replace( '/[^0-9a-f]/i', '', $hex );
	$new_hex = '#';

	// Validate hex string.
	if ( ! ctype_xdigit( $hex ) ) {
		return false;
	}

	if ( strlen( $hex ) >= 3 && strlen( $hex ) < 6 ) {
		$hex = dechex( hexdec( $hex[0] ) + hexdec( $hex[0] ) + hexdec( $hex[1] ) + hexdec( $hex[1] ) + hexdec( $hex[2] ) + hexdec( $hex[2] ) );
	}

	// convert to decimal and change luminosity
	for ( $i = 0; $i < 3; $i++ ) {
		$dec      = hexdec( substr( $hex, $i * 2, 2 ) );
		$dec      = min( max( 0, $dec + $dec * $percent ), 255 );
		$new_hex .= str_pad( dechex( intval( $dec ) ), 2, 0, STR_PAD_LEFT );
	}

	return $new_hex;
}

/**
 * Get the page/post content.
 *
 * @since 1.2.1
 *
 * @param int $id Post/Page ID.
 * @param int $num_of_words Number of words to extract.
 *
 * @return string
 */
function masteriyo_get_page_content( $id, $num_of_words = 55 ) {
	$content = get_the_content( null, false, $id );
	$content = apply_filters( 'the_content', $content );
	$content = str_replace( ']]>', ']]&gt;', $content );

	return $content;
}

/**
 * Output the page/post content.
 *
 * @since 1.2.1
 *
 * @param int $id Post/Page ID.
 * @param int $num_of_words Number of words to extract.
 *
 * @return string
 */
function masteriyo_the_page_content( $id, $num_of_words = 55 ) {
	echo masteriyo_get_page_content( $id, $num_of_words ); // phpcs:ignore
}

/** Get the current user ID.
 *
 * @since 1.2.1
 *
 * @return string|int
 */
function masteriyo_get_current_user_id() {
	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();
	} else {
		$user_id = masteriyo( 'session' )->get_user_id();
	}

	/**
	 * Filters current user ID.
	 *
	 * @since 1.2.1
	 *
	 * @param integer|string $user_id Current user ID.
	 */
	return apply_filters( 'masteriyo_get_current_user_id', $user_id );
}

/**
 * Get current logged in instructor.
 *
 * @since 1.3.0
 *
 * @return Masteriyo\Models\Instructor
 */
function masteriyo_get_current_instructor() {
	if ( is_user_logged_in() && masteriyo_is_current_user_instructor() ) {
		$instructor = masteriyo( 'instructor' );
		$instructor->set_id( get_current_user_id() );
		$store = masteriyo( 'user.store' );
		$store->read( $instructor );

		return $instructor;
	}

	return null;
}

/**
 * Get author id of a course.
 *
 * @since 1.3.2
 *
 * @param integer|Course|WP_Post $course Course id or Course Model or Post.
 *
 * @return integer
 */
function masteriyo_get_course_author_id( $course ) {
	$course = masteriyo_get_course( $course );

	if ( is_null( $course ) ) {
		return 0;
	}
	return $course->get_author_id();
}

/**
 * Get all the shortcodes with the prefix.
 *
 * @since 1.3.4
 *
 * @param string $prefix
 * @return array
 */
function masteriyo_get_shortcode_tags( $prefix = 'masteriyo' ) {
	return array_filter(
		array_keys( $GLOBALS['shortcode_tags'] ),
		function ( $shortcode_tag ) use ( $prefix ) {
			return masteriyo_starts_with( $shortcode_tag, $prefix );
		}
	);
}

/**
 * Get learn page logo data.
 *
 * @since 1.3.8
 *
 * @return array|false
 */
function masteriyo_get_learn_page_logo_data() {
	$logo_id    = masteriyo_get_setting( 'learn_page.general.logo_id' );
	$image_data = wp_get_attachment_image_src( $logo_id, 'full' );

	/**
	 * Filters learn page logo image data.
	 *
	 * @since 1.3.8
	 *
	 * @param array|false $image_data The learn page logo image data.
	 * @param integer|null $logo_id Logo ID.
	 */
	return apply_filters( 'masteriyo_get_learn_page_logo', $image_data, $logo_id );
}


/**
 * Get masteriyo blocks.
 *
 * @since 1.3.8
 *
 * @return array
 */
function masteriyo_get_blocks() {
	/**
	 * Filters names of masteriyo blocks.
	 *
	 * @since 1.3.8
	 *
	 * @param string[] $names Block names.
	 */
	return apply_filters(
		'masteriyo_get_blocks',
		array(
			'masteriyo/courses',
			'masteriyo/course-categories',
			'masteriyo/single-course',
		)
	);
}


/**
 * Get checkout endpoint URL.
 *
 * @since 1.4.2
 *
 * @param string $endpoint
 *
 * @return string
 */
function masteriyo_get_checkout_endpoint_url( $endpoint ) {
	$checkout_url = masteriyo_get_page_permalink( 'checkout' );
	$endpoint     = masteriyo_get_setting( 'advance.checkout.order_received' );
	$endpoint_url = $checkout_url . '/' . $endpoint;

	/**
	 * Filters checkout endpoint URL.
	 *
	 * @since 1.4.2
	 *
	 * @param string $endpoint_url The checkout endpoint URL.
	 * @param string $endpoint The checkout endpoint slug.
	 */
	return apply_filters( 'masteriyo_get_checkout_endpoint_url', $endpoint_url, $endpoint );
}

/**
 * Runs a deprecated action with notice only if used.
 *
 * @since 1.5.0
 * @param string $tag         The name of the action hook.
 * @param array  $args        Array of additional function arguments to be passed to do_action().
 * @param string $version     The version of Masteriyo that deprecated the hook.
 * @param string $replacement The hook that should have been used.
 * @param string $message     A message regarding the change.
 */
function masteriyo_do_deprecated_action( $tag, $args, $version, $replacement = null, $message = null ) {
	if ( ! has_action( $tag ) ) {
		return;
	}

	masteriyo_deprecated_hook( $tag, $version, $replacement, $message );
	do_action_ref_array( $tag, $args );
}

/**
 * Wrapper for deprecated functions so we can apply some extra logic.
 *
 * @since 1.5.0
 * @param string $function Function used.
 * @param string $version Version the message was added in.
 * @param string $replacement Replacement for the called function.
 */
function masteriyo_deprecated_function( $function, $version, $replacement = null ) {
	// @codingStandardsIgnoreStart
	if ( masteriyo_is_ajax() || masteriyo_is_rest_api_request() ) {
		/**
		 * Fires when a deprecated function is called.
		 *
		 * @since 1.5.0
		 *
		 * @see https://developer.wordpress.org/reference/hooks/deprecated_function_run/
		 *
		 * @param string $function Function used.
		 * @param string $replacement Replacement for the called function.
		 * @param string $version Version the message was added in.
		 */
		do_action( 'deprecated_function_run', $function, $replacement, $version );
		$log_string  = "The {$function} function is deprecated since version {$version}.";
		$log_string .= $replacement ? " Replace with {$replacement}." : '';
		error_log( $log_string );
	} else {
		_deprecated_function( $function, $version, $replacement );
	}
	// @codingStandardsIgnoreEnd
}

/**
 * Wrapper for deprecated hook so we can apply some extra logic.
 *
 * @since 1.5.0
 * @param string $hook        The hook that was used.
 * @param string $version     The version of WordPress that deprecated the hook.
 * @param string $replacement The hook that should have been used.
 * @param string $message     A message regarding the change.
 */
function masteriyo_deprecated_hook( $hook, $version, $replacement = null, $message = null ) {
	// @codingStandardsIgnoreStart
	if ( masteriyo_is_ajax() || masteriyo_is_rest_api_request() ) {
		/**
		 * Fires when a deprecated hook is called.
		 *
		 * @since 1.5.0
		 *
		 * @see https://developer.wordpress.org/reference/hooks/deprecated_hook_run/
		 *
		 * @param string $hook        The hook that was used.
		 * @param string $replacement The hook that should have been used.
		 * @param string $version     The version of WordPress that deprecated the hook.
		 * @param string $message     A message regarding the change.
		 */
		do_action( 'deprecated_hook_run', $hook, $replacement, $version, $message );

		$message    = empty( $message ) ? '' : ' ' . $message;
		$log_string = "{$hook} is deprecated since version {$version}";
		$log_string .= $replacement ? "! Use {$replacement} instead." : ' with no alternative available.';

		error_log( $log_string . $message );
	} else {
		_deprecated_hook( $hook, $version, $replacement, $message );
	}
	// @codingStandardsIgnoreEnd
}

/**
 * Function to check if a instructor is approved or not.
 *
 * @since 1.5.0
 *
 * @return boolean
 */
function masteriyo_is_instructor_active() {

	if ( masteriyo_is_current_user_admin() ) {
		return true;
	}

	$instructor = masteriyo_get_current_instructor();

	if ( is_null( $instructor ) ) {
		return false;
	}

	return $instructor->is_active();
}

if ( ! function_exists( 'masteriyo_paginate_links' ) ) {
	/**
	 * Retrieves paginated links for archive post pages. Uses the given WP_Query object if given.
	 *
	 * NOTE: This is a wrapper function for 'paginate_links' to add support for custom WP_Query object.
	 *
	 * @since 2.5.18
	 *
	 * @uses paginate_links WP core pagination function.
	 * @see https://developer.wordpress.org/reference/functions/paginate_links/
	 *
	 * @param string|array $args Array or string of arguments for generating paginated links for archives.
	 * @param \WP_Query|null $query Query object to use. If it's not provided, the global wp_query object will be used.
	 *
	 * @return string|array|void String of page links or array of page links, depending on 'type' argument.
	 *                           Void if total number of pages is less than 2.
	 */
	function masteriyo_paginate_links( $args = '', $query = null ) {
		$result = '';

		if ( $query instanceof \WP_Query ) {
			// Backup original query object.
			$old_query = $GLOBALS['wp_query'];

			// Switch to the given query object.
			$GLOBALS['wp_query'] = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			// Generate pagination links with the new query object.
			$result = paginate_links( $args );

			// Restore the origin query object.
			$GLOBALS['wp_query'] = $old_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			$result = paginate_links( $args );
		}

		return $result;
	}
}


if ( ! function_exists( 'masteriyo_get_filesystem' ) ) {
	/**
	 * Get direct filesystem.
	 *
	 * @since 1.6.7
	 * @global $wp_filesystem
	 *
	 * @return null|\WP_Filesystem_Direct
	 */
	function masteriyo_get_filesystem() {
		/**
		 * WP_Filesystem_Direct instance.
		 *
		 * @var \WP_Filesystem_Direct|null $wp_filesystem
		 */
		global $wp_filesystem;

		if ( ! $wp_filesystem || 'direct' !== $wp_filesystem->method ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			$credentials = request_filesystem_credentials( '', 'direct' );
			WP_Filesystem( $credentials );
		}

		return $wp_filesystem;
	}
}

if ( ! function_exists( 'masteriyo_print_block_support_styles' ) ) {
	/**
	 * Print block support styles.
	 *
	 * @since 1.6.5
	 * @return void
	 */
	function masteriyo_print_block_support_styles() {
		// Bail early if function does not exists.
		if ( ! function_exists( 'wp_style_engine_get_stylesheet_from_context' ) ) {
			return;
		}

		$core_styles_keys         = array( 'block-supports' );
		$compiled_core_stylesheet = '';

		foreach ( $core_styles_keys as $style_key ) {
			$compiled_core_stylesheet .= wp_style_engine_get_stylesheet_from_context( $style_key, array() );
		}

		if ( empty( $compiled_core_stylesheet ) ) {
			return;
		}

		wp_register_style( 'masteriyo-block-supports', false );
		wp_enqueue_style( 'masteriyo-block-supports' );
		wp_add_inline_style( 'masteriyo-block-supports', $compiled_core_stylesheet );
	}
}

if ( ! function_exists( 'masteriyo_is_guest_checkout_enabled' ) ) {
	/**
	 * Checks whether guest checkout is enabled or not.
	 *
	 * @since 1.6.12
	 *
	 * @return boolean True if guest checkout is enabled or false otherwise
	 */
	function masteriyo_is_guest_checkout_enabled() {
		$enable = is_user_logged_in() ? false : masteriyo_get_setting( 'general.registration.enable_guest_checkout' );
		/**
		 * Filter for enabling/disabling guest checkout.
		 *
		 * @since 1.6.12
		 *
		 * @param bool
		 */
		return apply_filters( 'masteriyo_guest_checkout_enable', $enable );
	}
}

if ( ! function_exists( 'masteriyo_add_iframe_to_post_context' ) ) {
	/**
	 * Add iframe tag to 'post' context allowed tags.
	 *
	 * @since 1.6.13
	 *
	 * @param array $allowed_tags Existing allowed tags for 'post' context.
	 *
	 * @return array Modified allowed tags.
	 */
	function masteriyo_add_iframe_to_post_context( $allowed_tags ) {
		$allowed_tags['iframe'] = array(
			'src'             => true,
			'height'          => true,
			'width'           => true,
			'frameborder'     => true,
			'allowfullscreen' => true,
		);

		return $allowed_tags;
	}
}

if ( ! function_exists( 'masteriyo_get_current_url' ) ) {

	/**
	 * Function to get current url.
	 *
	 * @since 1.6.13
	 *
	 * @return string
	 */
	function masteriyo_get_current_url() {
		if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
			return set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . untrailingslashit( $_SERVER['REQUEST_URI'] ) );
		}
		return '';
	}
}

if ( ! function_exists( 'masteriyo_is_email_verification_enabled' ) ) {

	/**
	 * Check if email verification feature is enabled.
	 *
	 * This function can be used to determine whether the email verification
	 * feature is enabled or disabled in your WordPress site.
	 *
	 * @since 1.6.14
	 *
	 * @return bool True if email verification is enabled, false otherwise.
	 */
	function masteriyo_is_email_verification_enabled() {

		/**
		 * Filter whether email verification is enabled or disabled.
		 *
		 * Use this filter to customize the behavior of email verification in your
		 * WordPress site. By default, it returns true, indicating that email
		 * verification is enabled. You can override this by returning false in
		 * your own filter callback.
		 *
		 * @since 1.6.14
		 *
		 * @param bool $enabled Whether email verification is enabled or not.
		 */
		return apply_filters( 'masteriyo_email_verification_enabled', masteriyo_get_setting( 'authentication.email_verification.enable' ) );
	}
}

if ( ! function_exists( 'masteriyo_cache' ) ) {
	/**
	 * Get the cache handler object.
	 *
	 * @since 1.6.16
	 *
	 * @return \Masteriyo\Cache\Cache
	 */
	function masteriyo_cache() {
		return masteriyo( 'cache' );
	}
}

if ( ! function_exists( 'masteriyo_transient_cache' ) ) {
	/**
	 * Get the transient cache handler object.
	 *
	 * @since 1.11.0
	 *
	 * @return \Masteriyo\Cache\TransientCache
	 */
	function masteriyo_transient_cache() {
		return masteriyo( 'transient-cache' );
	}
}

if ( ! function_exists( 'masteriyo_get_wp_env_status' ) ) {
	/**
	 * Ger WordPress Environment status.
	 *
	 * @since 1.7.3
	 *
	 * @return array
	 */
	function masteriyo_get_wp_env_status() {
		$memory_limit = WP_MEMORY_LIMIT;
		if ( function_exists( 'memory_get_usage' ) ) {
			$memory_limit = max( $memory_limit, @ini_get( 'memory_limit' ) );  // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		return array(
			'masteriyo_ver'         => MASTERIYO_VERSION,
			'version'               => get_bloginfo( 'version' ),
			'site_url'              => get_option( 'siteurl' ),
			'home_url'              => get_option( 'home' ),
			'multisite'             => is_multisite(),
			'external_object_cache' => wp_using_ext_object_cache(),
			'memory_limit'          => $memory_limit,
			'debug_mode'            => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
			'cron'                  => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'language'              => get_locale(),
		);
	}
}

if ( ! function_exists( 'masteriyo_get_server_status' ) ) {
	/**
	 * Get server information.
	 *
	 * @since 1.7.3
	 *
	 * @return array
	 */
	function masteriyo_get_server_status() {
		global $wpdb;

		if ( function_exists( 'curl_version' ) ) {
			$curl_version = curl_version();
			$curl_version = $curl_version['version'] . ', ' . $curl_version['ssl_version'];
		}

		return array(
			// PHP Info
			'php_version'              => phpversion(),
			'php_post_max_size'        => @ini_get( 'post_max_size' ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			'php_max_execution_time'   => @ini_get( 'max_execution_time' ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			'php_max_input_vars'       => @ini_get( 'max_input_vars' ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			// Server info
			'server_info'              => isset( $_SERVER['SERVER_SOFTWARE'] ) ? wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) : '',
			'curl_version'             => ! empty( $curl_version ) ? $curl_version : '',
			'max_upload_size'          => wp_max_upload_size(),
			'mysql_version'            => $wpdb->db_version(),
			'default_timezone'         => date_default_timezone_get(),
			'enable_fsockopen_or_curl' => ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ),
			'enable_soapclient'        => class_exists( 'SoapClient' ),
			'enable_domdocument'       => class_exists( 'DOMDocument' ),
			'enable_gzip'              => is_callable( 'gzopen' ),
			'enable_mbstring'          => extension_loaded( 'mbstring' ),
			'suhosin_installed'        => extension_loaded( 'suhosin' ),
		);
	}
}

if ( ! function_exists( 'masteriyo_get_course_item_learn_page_url' ) ) {
	/**
	 * Get course item learn page URL.
	 *
	 * @since 1.8.0
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param \Masteriyo\Models\Lesson|\Masteriyo\Models\Quiz|null $item Item object.
	 *
	 * @return string
	 */
	function masteriyo_get_course_item_learn_page_url( $course, $item = null ) {
		if ( ! ( $course instanceof \Masteriyo\Models\Course ) || ! ( $item instanceof \Masteriyo\Models\Lesson || $item instanceof \Masteriyo\Models\Quiz ) ) {
			return '';
		}

		$learn_page_url = masteriyo_get_page_permalink( 'learn' );
		$url            = trailingslashit( $learn_page_url ) . 'course/' . $course->get_slug();

		if ( '' === get_option( 'permalink_structure' ) ) {
			$url = add_query_arg(
				array(
					'course_name' => $course->get_id(),
				),
				$learn_page_url
			);
		}

		$url .= '#/course/' . $course->get_id();

		$url .= "/{$item->get_object_type()}/" . $item->get_id();

		/**
		 * Filter start course item URL.
		 *
		 * @since 1.8.0
		 *
		 * @param string $url Start course URL.
		 * @param Masteriyo\Models\Course $course Course object.
		 * @param \Masteriyo\Models\Lesson|\Masteriyo\Models\Quiz $item Whether to append the item or not.
		*/
		return apply_filters( "masteriyo_start_{$item->get_object_type()}_url", $url, $course, $item );
	}
}

if ( ! function_exists( 'masteriyo_get_temp_dir' ) ) {
	/**
	 * Get writable temporary directory.
	 *
	 * @since 1.8.0
	 *
	 * @return string
	 */
	function masteriyo_get_temp_dir() {
		$upload_dir = wp_upload_dir()['basedir'];
		$temp_dir   = $upload_dir . DIRECTORY_SEPARATOR . 'masteriyo';
		$filesystem = masteriyo_get_filesystem();

		if ( ! is_dir( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		if ( $filesystem && ! is_file( $temp_dir . DIRECTORY_SEPARATOR . 'index.php' ) ) {
			$filesystem->put_contents( $temp_dir . DIRECTORY_SEPARATOR . 'index.php', '' );
		}

		/**
		 * Filters writable temporary directory.
		 *
		 * @since 1.8.0
		 *
		 * @param string $temp_dir
		 */
		return apply_filters( 'masteriyo_temp_dir', $temp_dir );
	}
}

if ( ! function_exists( 'masteriyo_build_item_meta_data' ) ) {
	/**
	 * Builds the meta data array for an order item.
	 *
	 * @since 1.8.1
	 *
	 * @param int    $item_id The order item ID.
	 * @param \Masteriyo\Models\Order\OrderItemCourse $item The order item object.
	 *
	 * @return array An array of meta data ready for insertion. Each element is an associative array
	 *               containing the keys 'order_item_id', 'meta_key', and 'meta_value'.
	 */
	function masteriyo_build_item_meta_data( $item_id, $item ) {

		if ( ! $item_id || ! $item ) {
			return array();
		}

		return array(
			array(
				'order_item_id' => $item_id,
				'meta_key'      => 'course_id',
				'meta_value'    => $item->get_course_id(),
			),
			array(
				'order_item_id' => $item_id,
				'meta_key'      => 'quantity',
				'meta_value'    => $item->get_quantity(),
			),
			array(
				'order_item_id' => $item_id,
				'meta_key'      => 'subtotal',
				'meta_value'    => $item->get_subtotal(),
			),
			array(
				'order_item_id' => $item_id,
				'meta_key'      => 'total',
				'meta_value'    => $item->get_total(),
			),
		);
	}
}

if ( ! function_exists( 'masteriyo_insert_item_meta_batch' ) ) {
	/**
	 * Inserts order item meta data in batch to the database.
	 *
	 * @since 1.8.1
	 *
	 * @param array $data An array of meta data arrays, each containing 'order_item_id', 'meta_key', and 'meta_value'.
	 *
	 * @return void
	 */
	function masteriyo_insert_item_meta_batch( $data ) {
		global $wpdb;

		if ( ! $wpdb || empty( $data ) ) {
			return;
		}

		$table_name = $wpdb->prefix . 'masteriyo_order_itemmeta';

		foreach ( $data as $item_meta ) {
			$wpdb->insert( $table_name, $item_meta );
		}
	}
}

if ( ! function_exists( 'masteriyo_download_certificate_fonts' ) ) {
	/**
	 * Download certificate fonts.
	 *
	 * @since 1.8.2
	 * @param boolean $force Force download fonts.
	 *
	 * @return void
	 */
	function masteriyo_download_certificate_fonts( $force = false ) {
		if ( ! $force && get_option( '_masteriyo_certificate_fonts_downloaded' ) ) {
			return;
		}
		$filesystem = masteriyo_get_filesystem();
		if ( ! $filesystem || ! class_exists( \ZipArchive::class ) ) {
			return;
		}

		$destination = wp_upload_dir()['basedir'] . '/masteriyo/certificate-fonts';

		$api = 'https://d1sb0nhp4t2db4.cloudfront.net/resources/masteriyo/certificate/fonts.zip';

		$response = wp_remote_get( $api );

		if ( is_wp_error( $response ) ) {
			return;
		}

		if ( ! $filesystem->is_dir( $destination ) ) {
			$filesystem->mkdir( $destination );
		}

		$temp_file = tempnam( sys_get_temp_dir(), 'fonts' );
		$filesystem->put_contents( $temp_file, wp_remote_retrieve_body( $response ) );

		$zip = new \ZipArchive();
		$zip->open( $temp_file );

		$font_exts = array( 'ttf', 'otf' );

		for ( $i = 0; $i < $zip->numFiles; $i++ ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$filename  = $zip->getNameIndex( $i );
			$file_info = pathinfo( $filename );

			if ( '.' !== $file_info['basename'] && '..' !== $file_info['basename'] && isset( $file_info['extension'] ) && in_array( $file_info['extension'], $font_exts, true ) && ! $filesystem->exists( $destination . '/' . $file_info['basename'] ) ) {
				$font = $destination . '/' . $file_info['basename'];
				$filesystem->copy( "zip://{$temp_file}#{$filename}", $font );
			}
		}
		$zip->close();

		wp_delete_file( $temp_file );
		update_option( '_masteriyo_certificate_fonts_downloaded', true );
	}
}

/**
 * Get certificate font urls.
 *
 * @since 1.8.2
 * @return array
 */
if ( ! function_exists( 'masteriyo_get_certificate_font_urls' ) ) {
	function masteriyo_get_certificate_font_urls() {
		$base_url = wp_upload_dir()['baseurl'] . '/masteriyo/certificate-fonts';
		return array(
			'Cinzel'              => $base_url . '/Cinzel-VariableFont_wght.ttf',
			'DejaVuSansCondensed' => $base_url . '/DejaVuSansCondensed.ttf',
			'DMSans'              => $base_url . '/DMSans-Regular.ttf',
			'GreatVibes'          => $base_url . '/GreatVibes-Regular.ttf',
			'GrenzeGotisch'       => $base_url . '/GrenzeGotisch-VariableFont_wght.ttf',
			'LibreBaskerville'    => $base_url . '/LibreBaskerville-Regular.ttf',
			'Lora'                => $base_url . '/Lora-VariableFont_wght.ttf',
			'Poppins'             => $base_url . '/Poppins-Regular.otf',
			'Roboto'              => $base_url . '/Roboto-Regular.ttf',
			'AbhayaLibre'         => $base_url . '/AbhayaLibre-Regular.ttf',
			'AdineKirnberg'       => $base_url . '/AdineKirnberg.ttf',
			'AlexBrush'           => $base_url . '/AlexBrush-Regular.ttf',
			'Allura'              => $base_url . '/Allura-Regular.ttf',
		);
	}
}

if ( ! function_exists( 'masteriyo_is_qr_login_enabled' ) ) {

	/**
	 * Checks if QR login functionality is enabled in the Masteriyo settings.
	 *
	 * @since 1.9.0
	 *
	 * @@return bool True if QR login is enabled, false otherwise. The return value
	 *                    can be filtered using the 'masteriyo_qr_login_enabled' hook.
	 */
	function masteriyo_is_qr_login_enabled() {

		/**
		 * Allows modification of the QR login enabled setting.
		 *
		 * @since 1.9.0
		 *
		 * @param bool $enabled The current state of the QR login feature. True if enabled,
		 *                      false otherwise. This value is derived from the Masteriyo
		 *                      settings and can be altered through this filter.
		 */
		return apply_filters( 'masteriyo_qr_login_enabled', masteriyo_get_setting( 'authentication.qr_login.enable' ) );
	}
}

if ( ! function_exists( 'masteriyo_get_sections_count_by_course' ) ) {
	/**
	 * Get sections count by course.
	 *
	 * @since 1.10.0
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return int
	 */
	function masteriyo_get_sections_count_by_course( $course_id ) {
		$count = 0;

		$posts = get_posts(
			array(
				'post_type'      => PostType::SECTION,
				'post_status'    => PostStatus::PUBLISH,
				'post_parent'    => $course_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$count = count( array_filter( $posts ) );

		return $count;
	}
}

if ( ! function_exists( 'get_course_section_children_count_by_course' ) ) {
	/**
	 * Get lessons count by course.
	 *
	 * @since 1.10.0
	 *
	 * @param int $course_id Course ID.
	 * @param string $type The type of section items. Default is 'lesson'.
	 *
	 * @return int
	 */
	function get_course_section_children_count_by_course( $course_id, $type = 'lesson' ) {
		$children_count = 0;

		$section_ids = get_posts(
			array(
				'post_type'      => PostType::SECTION,
				'post_status'    => PostStatus::PUBLISH,
				'post_parent'    => $course_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$section_ids = array_filter( $section_ids );

		if ( empty( $section_ids ) ) {
			return $children_count;
		}

		foreach ( $section_ids as $section_id ) {
			$lessons = get_posts(
				array(
					'post_type'      => $type,
					'post_status'    => PostStatus::PUBLISH || GoogleMeetStatus::UPCOMING || GoogleMeetStatus::ACTIVE,
					'post_parent'    => $section_id,
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			$children_count += count( array_filter( $lessons ) );
		}

		return $children_count;
	}
}


if ( ! function_exists( 'get_course_section_children_count_by_section' ) ) {
	/**
	 * Get lessons count by course.
	 *
	 * @since 1.10.0
	 *
	 * @param int $section_id Course ID.
	 * @param string $type The type of section items. Default is 'lesson'.
	 *
	 * @return int
	 */
	function get_course_section_children_count_by_section( $section_id, $type = 'lesson' ) {
		$count = 0;

		$post_ids = get_posts(
			array(
				'post_type'      => 'quiz' === $type ? PostType::QUIZ : PostType::LESSON,
				'post_status'    => PostStatus::PUBLISH,
				'post_parent'    => $section_id,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$count = count( array_filter( $post_ids ) );

		return $count;
	}
}

if ( ! function_exists( 'get_course_section_children_by_section' ) ) {
	/**
	 * Get all published child posts of a given section ID.
	 *
	 * Retrieves all published lessons, quizzes, etc. for a section. Maps them to model objects.
	 *
	 * @since 1.10.0
	 *
	 * @param int $section_id Section ID.
	 *
	 * @return array Array of model objects for section children.
	 */
	function get_course_section_children_by_section( $section_id ) {
		$posts = get_posts(
			array(
				'post_type'      => SectionChildrenPostType::all(),
				'post_status'    => PostStatus::PUBLISH,
				'post_parent'    => $section_id,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'asc',
			)
		);

		$objects = array_filter(
			array_map(
				function ( $post ) {
					try {
						$object = masteriyo( $post->post_type );
						$object->set_id( $post->ID );
						$store = masteriyo( $post->post_type . '.store' );
						$store->read( $object );
					} catch ( \Exception $e ) {
						$object = null;
					}

					return $object;
				},
				$posts
			)
		);

		return $objects;
	}
}

if ( ! function_exists( 'masteriyo_get_instructor_user_ids' ) ) {
	/**
	 * Retrieves the user IDs associated with a given instructor.
	 *
	 * @since 1.11.0
	 *
	 * @param int|null $instructor_id The ID of the instructor. If not provided, the current user's ID will be used.
	 *
	 * @return array An array of user IDs associated with the specified instructor.
	 */
	function masteriyo_get_instructor_user_ids( $instructor_id = null ) {
		if ( is_null( $instructor_id ) ) {
			$instructor_id = masteriyo_is_current_user_instructor() ? get_current_user_id() : 0;
		}

		if ( ! $instructor_id ) {
			return array();
		}

		$args = array(
			'post_type'      => PostType::COURSE,
			'post_status'    => PostStatus::PUBLISH,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'author'         => $instructor_id,
		);

		$course_ids = get_posts( $args );

		if ( empty( $course_ids ) ) {
			return array();
		}

		global  $wpdb;

		$course_ids_placeholder = implode( ',', array_fill( 0, count( $course_ids ), '%d' ) );

		$query = "
		SELECT user_id
			FROM {$wpdb->prefix}masteriyo_user_items
			WHERE item_id IN ($course_ids_placeholder)
			  AND item_type = 'user_course'
		";

		$sql      = $wpdb->prepare( $query, $course_ids ); // phpcs:ignore
		$user_ids = $wpdb->get_col( $sql ); // phpcs:ignore

		/**
		 * Filter the list of user IDs for an instructor.
		 *
		 * @since 1.11.0
		 *
		 * @param array $user_ids The array of user IDs.
		 * @param int $instructor_id The instructor ID.
		 */
		$user_ids = apply_filters( 'masteriyo_get_instructor_user_ids', $user_ids, $instructor_id );

		return $user_ids;
	}
}

if ( ! function_exists( 'masteriyo_get_currency_from_code' ) ) {
	/**
	 * Get currency name from currency code.
	 *
	 * @since 1.11.0
	 *
	 * @param string $code Currency code.
	 *
	 * @return string
	*/
	function masteriyo_get_currency_from_code( $code ) {
		$currencies = masteriyo_get_currencies();
		$currency   = isset( $currencies[ $code ] ) ? $currencies[ $code ] : '';

		/**
		 * Filters currency name found using currency code.
		 *
		 * @since 1.11.0
		 *
		 * @param string $currency Currency name.
		 * @param string $code The currency code.
		 */
		return apply_filters( 'masteriyo_get_currency_from_code', $currency, $code );
	}
}

if ( ! function_exists( 'masteriyo_check_plugin_active_in_network' ) ) {
	/**
	 * Check if a plugin is active in the network.
	 *
	 * @since 1.11.3
	 *
	 * @param string $plugin
	 * @return boolean
	 */
	function masteriyo_check_plugin_active_in_network( $plugin ) {
		// Makes sure the plugin is defined before trying to use it
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active_for_network( $plugin ) ) {
			return true;
		} else {
			return false;
		}
	}
}


if ( ! function_exists( 'masteriyo_get_logger' ) ) {
	/**
	 * Get a shared logger instance.
	 *
	 * Use the masteriyo_logging_class filter to change the logging class. You may provide one of the following:
	 *     - a class name which will be instantiated as `new $class` with no arguments
	 *     - an instance which will be used directly as the logger
	 * In either case, the class or instance *must* implement Logger.
	 *
	 * @see LoggerInterface
	 * @since 1.12.2
	 * @return \Masteriyo\Logger
	 */
	function masteriyo_get_logger() {
		static $logger = null;
		if ( null === $logger ) {
			/**
			 * Applies the 'masteriyo_logging_class' filter to customize the logger class.
			 *
			 * @since 1.12.2
			 *
			 * @param string|object $class The class name or an instance of the logger.
			 */
			$class      = apply_filters( 'masteriyo_logging_class', new Logger() );
			$implements = class_implements( $class );
			if ( is_array( $implements ) && in_array( 'Masteriyo\Contracts\LoggerInterface', $implements ) ) {
				$logger = is_object( $class ) ? $class : new $class();
			} else {
				masteriyo_doing_it_wrong(
					__FUNCTION__,
					sprintf(
					/* translators: %s: Class */
						__( 'The class <code>%s</code> provided by masteriyo_logging_class filter must implement <code>Logger</code>.', 'learning-management-system' ),
						esc_html( is_object( $class ) ? get_class( $class ) : $class )
					),
					'1.12.2'
				);
				$logger = new Logger();
			}
		}

		return $logger;
	}
}

/**
 * Get a log file path.
 *
 * @since 1.12.2
 *
 * @param string $handle name.
 *
 * @return string the log file path.
 */
function masteriyo_get_log_file_path( $handle ) {
	return LogHandlerFile::get_log_file_path( $handle );
}

/**
 * Registers the default log handler.
 *
 * @since 1.12.2
 *
 * @param array $handlers Log handlers.
 *
 * @return array
 */
function masteriyo_register_default_log_handler( $handlers ) {

	if ( defined( 'MASTERIYO_LOG_HANDLER' ) && class_exists( MASTERIYO_LOG_HANDLER ) ) {
		$handler_class   = MASTERIYO_LOG_HANDLER;
		$default_handler = new $handler_class();
	} else {
		$default_handler = new LogHandlerFile();
	}

	array_push( $handlers, $default_handler );

	return $handlers;
}

if ( ! function_exists( 'masteriyo_is_logger_enabled' ) ) {
	/**
	 * Checks whether logger is enable or not.
	 *
	 * @since 1.12.2
	 *
	 * @return boolean
	 */
	function masteriyo_is_logger_enabled() {
		return masteriyo_string_to_bool( masteriyo_get_setting( 'advance.debug.enable_logger' ) );
	}
}

if ( ! function_exists( 'masteriyo_get_shortcode_attributes' ) ) {
	/**
	 * Get the shortcode attributes for a given shortcode.
	 *
	 * @since 1.12.0
	 *
	 * @param string $shortcode The shortcode tag to search for.
	 *
	 * @return array|false The shortcode attributes array if found, false otherwise.
	 */
	function masteriyo_get_shortcode_attributes( $shortcode ) {
		global $post;

		if ( $post && isset( $post->post_content ) && has_shortcode( $post->post_content, $shortcode ) ) {
			$pattern = get_shortcode_regex( array( $shortcode ) );

			if ( preg_match_all( '/' . $pattern . '/s', $post->post_content, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $shortcode_match ) {
					if ( $shortcode === $shortcode_match[2] ) {
						return shortcode_parse_atts( $shortcode_match[3] );
					}
				}
			}
		}

		return false;
	}
}

if ( ! function_exists( 'masteriyo_is_categories_slider_enabled' ) ) {
	/**
	 * Checks if the course categories slider is enabled.
	 *
	 * This function checks if the 'enable_slider' attribute is set to true for the 'masteriyo_course_categories' shortcode.
	 *
	 * @since 1.12.0
	 *
	 * @return bool True if the course categories slider is enabled, false otherwise.
	 */
	function masteriyo_is_categories_slider_enabled() {
		$is_enabled = false;

		if ( ! masteriyo_is_categories_page() ) {
			return $is_enabled;
		}

		$attributes = masteriyo_get_shortcode_attributes( 'masteriyo_course_categories' );

		if ( isset( $attributes['enable_slider'] ) && masteriyo_string_to_bool( $attributes['enable_slider'] ) ) {
			$is_enabled = true;
		}

		/**
		 * Filters the value indicating whether the course categories slider is enabled.
		 *
		 * @since 1.12.0
		 *
		 * @param bool $is_enabled True if the course categories slider is enabled, false otherwise.
		 *
		 * @return bool The filtered value indicating whether the course categories slider is enabled.
		 */
		return apply_filters( 'masteriyo_is_categories_slider_enabled', $is_enabled );
	}
}

if ( ! function_exists( 'masteriyo_is_slider_enabled' ) ) {
	/**
	 * Checks if the course categories slider is enabled.
	 *
	 * @since 1.12.0
	 *
	 * @return bool True if the slider is enabled, false otherwise.
	 */
	function masteriyo_is_slider_enabled() {
		$is_enabled = masteriyo_is_categories_slider_enabled();

		/**
		 * Filters the value indicating whether the course categories slider is enabled.
		 *
		 * @since 1.12.0
		 *
		 * @param bool $is_enabled True if the slider is enabled, false otherwise.
		 *
		 * @return bool The filtered value indicating whether the slider is enabled.
		 */
		return apply_filters( 'masteriyo_is_slider_enabled', $is_enabled );
	}
}

if ( ! function_exists( 'masteriyo_addon_menu_slugs' ) ) {
	/**
	 * Retrieves the menu slugs for Masteriyo addons.
	 *
	 * @since 1.13.0
	 *
	 * * @param bool $is_active_only Whether to only return active menu slugs.
	 *
	 * @return array The menu slugs for Masteriyo addons.
	 */
	function masteriyo_addon_menu_slugs( $is_active_only = false ) {

		$addons_menus = array(

			'course-announcement'          => array(
				'menu_slug'  => 'course-announcements',
				'menu_title' => __( 'Announcements', 'learning-management-system' ),
				'position'   => 62,

			),
			'google-classroom-integration' => array(
				'menu_slug'  => 'google-classrooms',
				'menu_title' => __( 'Google Classroom', 'learning-management-system' ),
				'position'   => 64,

			),
			'google-meet'                  => array(
				'menu_slug'  => 'google-meet/meetings',
				'menu_title' => __( 'Google Meet', 'learning-management-system' ),
				'position'   => 65,

			),
			'group-courses'                => array(
				'menu_slug'  => 'groups',
				'menu_title' => __( 'Groups', 'learning-management-system' ),
				'position'   => 66,

			),
			'multiple-currency'            => array(
				'menu_slug'  => 'multiple-currency/pricing-zones',
				'menu_title' => __( 'Multiple Currency', 'learning-management-system' ),
				'position'   => 63,

			),
			'certificate'                  => array(
				'menu_slug'  => 'certificates',
				'menu_title' => __( 'Certificates', 'learning-management-system' ),
				'position'   => 75,

			),

		);

		if ( masteriyo_string_to_bool( masteriyo_get_setting( 'payments.revenue_sharing.enable' ) ) ) {
			$addons_menus['revenue-sharing'] = array(
				'menu_slug'  => 'withdraws',
				'menu_title' => __( 'Withdraws', 'learning-management-system' ),
				'position'   => 72,

			);
		}

		uasort(
			$addons_menus,
			function ( $a, $b ) {
				if ( $a['position'] === $b['position'] ) {
					return 0;
				}

				return ( $a['position'] < $b['position'] ) ? -1 : 1;
			}
		);

		$menus = array();

		foreach ( $addons_menus as  $slug => $submenu ) {
			if ( ( $is_active_only && ( new Addons() )->is_active( $slug ) ) || ! $is_active_only ) {
				$menus[ $slug ] = array(
					'menu_slug'  => "admin.php?page=masteriyo#/{$submenu['menu_slug']}",
					'menu_title' => $submenu['menu_title'],
					'slug'       => $slug,
				);
			}
		}

		return $menus;
	}
}

if ( ! function_exists( 'masteriyo_get_submenus_if_slugs_present' ) ) {
	/**
	 * Checks if any of the specified slugs have corresponding submenus.
	 * Returns all submenus if any slug matches, otherwise returns an empty array.
	 *
	 * @since 1.13.0
	 *
	 * @param string|array $slugs A single slug or an array of slugs to check.
	 *
	 * @return array The submenus if at least one slug matches, otherwise an empty array.
	 */
	function masteriyo_get_submenus_if_slugs_present( $slugs ) {
		$submenus = masteriyo_addon_menu_slugs();

		$slugs = is_string( $slugs ) ? array( $slugs ) : (array) $slugs;

		if ( is_array( $slugs ) && ! empty( $slugs ) ) {
			foreach ( $slugs as $slug ) {
				if ( array_key_exists( $slug, $submenus ) ) {
					return array_values( $submenus );
				}
			}
		}

		return array();
	}
}

/**
 * Checks if the course carousel is enabled.
 *
 * This function returns a boolean value indicating whether the course carousel is enabled.
 *
 * @since 1.13.0
 *
 * @return bool True if the course carousel is enabled, false otherwise.
 */
if ( ! function_exists( 'masteriyo_is_course_carousel_enabled' ) ) {
	/**
	 * Checks if the course carousel is enabled.
	 *
	 * This function returns a boolean value indicating whether the course carousel is enabled.
	 *
	 * @since 1.13.0
	 *
	 * @return bool True if the course carousel is enabled, false otherwise.
	 */
	function masteriyo_is_course_carousel_enabled() {
		/**
		 * Checks if the course carousel is enabled.
		 *
		 * This function returns a boolean value indicating whether the course carousel is enabled.
		 *
		 * @since 1.13.0
		 *
		 * @return bool True if the course carousel is enabled, false otherwise.
		 */
		return apply_filters( 'masteriyo_is_course_carousel_enabled', false );
	}
}

if ( ! function_exists( 'masteriyo_get_current_request_url' ) ) {
	/**
	 * Returns current URL after adding or excluding query params.
	 *
	 * @since 2.5.18
	 *
	 * @param string|array $new_params Name value pairs.
	 * @param array $exclude Keys to exclude.
	 *
	 * @return string
	 */
	function masteriyo_get_current_request_url( $new_params = array(), $exclude = array() ) {
		global $wp;

		$args = array_merge( $_GET, $new_params ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! empty( $exclude ) ) {
			$args = array_filter(
				$args,
				function ( $value, $key ) use ( $exclude ) {
					return ! in_array( $key, $exclude, true );
				},
				ARRAY_FILTER_USE_BOTH
			);
		}

		return add_query_arg( $args, home_url( $wp->request ) );
	}
}

if ( ! function_exists( 'masteriyo_get_query_string_form_fields' ) ) {
	/**
	 * Returns hidden form inputs for each query string variable.
	 *
	 * @since 2.5.18
	 *
	 * @param string|array $values Name value pairs, or a URL to parse.
	 * @param array $exclude Keys to exclude.
	 * @param string $current_key Current key we are outputting.
	 *
	 * @return string
	 */
	function masteriyo_get_query_string_form_fields( $values = null, $exclude = array(), $current_key = '' ) {
		if ( is_null( $values ) ) {
			$values = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( is_string( $values ) ) {
			$url_parts = wp_parse_url( $values );
			$values    = array();

			if ( ! empty( $url_parts['query'] ) ) {
				// This is to preserve full-stops, pluses and spaces in the query string when ran through parse_str.
				$replace_chars = array(
					'.' => '{dot}',
					'+' => '{plus}',
				);

				$query_string = str_replace( array_keys( $replace_chars ), array_values( $replace_chars ), $url_parts['query'] );

				// Parse the string.
				parse_str( $query_string, $parsed_query_string );

				// Convert the full-stops, pluses and spaces back and add to values array.
				foreach ( $parsed_query_string as $key => $value ) {
					$new_key            = str_replace( array_values( $replace_chars ), array_keys( $replace_chars ), $key );
					$new_value          = str_replace( array_values( $replace_chars ), array_keys( $replace_chars ), $value );
					$values[ $new_key ] = $new_value;
				}
			}
		}
		$html = '';

		foreach ( $values as $key => $value ) {
			if ( in_array( $key, $exclude, true ) ) {
				continue;
			}
			if ( $current_key ) {
				$key = $current_key . '[' . $key . ']';
			}
			if ( is_array( $value ) ) {
				$html .= masteriyo_get_query_string_form_fields( $value, $exclude, $key );
			} else {
				$html .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( wp_unslash( $value ) ) . '" />';
			}
		}

		return $html;
	}
}

if ( ! function_exists( 'masteriyo_render_query_string_form_fields' ) ) {
	/**
	 * Outputs hidden form inputs for each query string variable.
	 *
	 * @since 2.5.18
	 *
	 * @param string|array $values Name value pairs, or a URL to parse.
	 * @param array $exclude Keys to exclude.
	 * @param string $current_key Current key we are outputting.
	 */
	function masteriyo_render_query_string_form_fields( $values = null, $exclude = array(), $current_key = '' ) {
		echo masteriyo_get_query_string_form_fields( $values, $exclude, $current_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Get currencies list.
 *
 * @since 1.15.0
 *
 * @return array
 */
if ( ! function_exists( 'masteriyo_get_currencies_array' ) ) {
	/**
	 * Get currencies list.
	 *
	 * @since 1.15.0
	 *
	 * @return array
	 */
	function masteriyo_get_currencies_array() {
		$currencies = masteriyo_get_currencies();

		foreach ( $currencies as $code => $name ) {
			$currencies_arr[] = array(
				'code'   => $code,
				'name'   => html_entity_decode( $name ),
				'symbol' => html_entity_decode( masteriyo_get_currency_symbol( $code ) ),
			);
		}

		$response = $currencies_arr;

		return $response;
	}
}

/**
 * Get pages list.
 *
 * @since 1.15.0
 *
 * @return array
 */
if ( ! function_exists( 'masteriyo_get_all_pages' ) ) {
	/**
	 * Get pages list.
	 *
	 * @since 1.15.0
	 *
	 * @return array
	 */
	function masteriyo_get_all_pages() {
		$pages = get_pages();

		$formatted_pages = array();

		foreach ( $pages as $page ) {
			$formatted_pages[] = array(
				'id'    => $page->ID,
				'title' => $page->post_title,
			);
		}

		return $formatted_pages;
	}
}

if ( ! function_exists( 'masteriyo_get_states' ) ) {
	/**
	 * Get states list.
	 *
	 * @since 1.15.0
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	function masteriyo_get_states() {
		$countries = array_keys( masteriyo( 'countries' )->get_countries() );

		foreach ( $countries as $country ) {
			$states = masteriyo( 'countries' )->get_states( $country );

			if ( empty( $states ) ) {
				continue;
			}

			$states_list = array();
			foreach ( $states as $state_code => $state_name ) {
				$states_list[] = array(
					'code' => $state_code,
					'name' => $state_name,
				);
			}

			$states_arr[] = array(
				'country' => $country,
				'states'  => $states_list,
			);
		}

		return $states_arr;
	}
}

if ( ! function_exists( 'masteriyo_get_countries' ) ) {
	/**
	 * Get countries list.
	 *
	 * @since 1.15.0
	 *
	 * @return array
	 */
	function masteriyo_get_countries() {
		$countries = masteriyo( 'countries' )->get_countries();

		$countries = array_map( 'html_entity_decode', $countries );

		foreach ( $countries as $code => $name ) {
			$countries_arr[] = array(
				'code' => $code,
				'name' => $name,
			);
		}

		return $countries_arr;
	}
}

if ( ! function_exists( 'masteriyo_notify_pages_missing' ) ) {
	/**
	 * Display an admin notice when required pages are missing or misconfigured.
	 *
	 * @since 1.15.0
	 * @return void
	 */
	function masteriyo_notify_pages_missing() {
		// Define required pages with their setting keys and display names
		$required_pages = array(
			'learn'    => array(
				'setting_key' => 'general.pages.learn_page_id',
				'name'        => 'Learn',
			),
			'account'  => array(
				'setting_key' => 'general.pages.account_page_id',
				'name'        => 'Account',
			),
			'checkout' => array(
				'setting_key' => 'general.pages.checkout_page_id',
				'name'        => 'Checkout',
			),
		);

		$missing_pages = array();

		// Check each required page.
		foreach ( $required_pages as $slug => $details ) {
			// Get page ID from settings.
			$page_id = absint( masteriyo_get_setting( $details['setting_key'] ) );

			// Check if page ID is empty or page does not exist or is not published.
			if ( empty( $page_id ) || 'publish' !== get_post_status( $page_id ) ) {
				$missing_pages[ $slug ] = $details['name'];
			}
		}

		// If there are missing pages, display a notice.
		if ( ! empty( $missing_pages ) ) {
				add_action(
					'masteriyo_admin_notices',
					function() use ( $missing_pages ) {
						$notice_title        = '<strong>' . __( 'Masteriyo:', 'learning-management-system' ) . '</strong>';
						$missing_pages_count = count( $missing_pages );
						$missing_pages_list  = implode(
							', ',
							array_map(
								function( $name ) {
									return "<strong>{$name}</strong>";
								},
								array_values( $missing_pages )
							)
						);

						$onboarding_data    = get_option( 'masteriyo_onboarding_data', array() );
						$onboarding_started = $onboarding_data['started'] ?? false;

						$notice_message = sprintf(
						/* translators: 1: Notice title, 2: Number of missing pages, 3: List of missing pages */
							_n(
								'%1$s %2$d page is missing: %3$s.',
								'%1$s %2$d pages are missing: %3$s.',
								$missing_pages_count,
								'learning-management-system'
							),
							$notice_title,
							$missing_pages_count,
							wp_kses_post( $missing_pages_list )
						);

						if ( $onboarding_started ) {
							$notice_message .= ' ' . sprintf(
							/* translators: %s: "click here" link text */
								__( 'Please configure it, or <a href="#" id="masteriyo-setup-pages">%s</a> to set it up automatically.', 'learning-management-system' ),
								esc_html__( 'click here', 'learning-management-system' )
							);
						} else {
							$notice_message .= ' ' . sprintf(
							/* translators: 1: Onboarding URL, 2: "click here" link text */
								__(
									'Missing required setup. <a class="masteriyo-onboarding-notice-link" href="%1$s">Complete the onboarding process</a> (recommended) or <a class="masteriyo-onboarding-notice-link" href="#" id="masteriyo-setup-pages">%2$s</a> to automatically create the missing pages.',
									'learning-management-system'
								),
								esc_url( admin_url( 'admin.php?page=masteriyo-onboard' ) ),
								esc_html__( 'click here', 'learning-management-system' )
							);
						}

						printf(
							'<div class="notice notice-warning is-dismissible masteriyo-pages-missing-notice"><p>%s</p></div>',
							wp_kses_post( $notice_message )
						);

						// Enqueue JavaScript for AJAX setup.
						wp_enqueue_script(
							'masteriyo-admin-notice',
							plugin_dir_url( __FILE__ ) . 'assets/js/admin-notice.js',
							array( 'jquery' ),
							MASTERIYO_VERSION,
							true
						);

						wp_localize_script(
							'masteriyo-admin-notice',
							'masteriyoData',
							array(
								'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
								'nonce'         => wp_create_nonce( 'masteriyo-setup-pages' ),
								'settingUpText' => __( 'Setting up...', 'learning-management-system' ),
								'setupFailed'   => __( 'Failed to set up. Retry?', 'learning-management-system' ),
								'setupSuccess'  => __( 'Pages set up successfully.', 'learning-management-system' ),
								'pages'         => array_keys( $missing_pages ),
							)
						);

						// Inline script to handle the AJAX call.
						wp_add_inline_script(
							'masteriyo-admin-notice',
							"jQuery(document).ready(function($) {
										$(document).on('click', '#masteriyo-setup-pages', function(e) {
												e.preventDefault();
												var link = $(this);
												var notice = $(this).closest('.masteriyo-pages-missing-notice');

												link.text(masteriyoData.settingUpText);

												$.ajax({
														url: masteriyoData.ajaxUrl,
														type: 'POST',
														data: {
																action: 'masteriyo_setup_pages',
																nonce: masteriyoData.nonce,
																pages: masteriyoData.pages
														},
														success: function(response) {
																if (response.success) {
																		notice.slideUp(300, function() {
																				$(this).remove();
																		});
																} else {
																	link.text(masteriyoData.setupFailed);
																}
														},
														error: function() {
															link.text(masteriyoData.setupFailed);
														}
												});
										});
								});"
						);
					}
				);
		}
	}
}

if ( ! function_exists( 'masteriyo_get_total_user_count_by_roles_and_statuses' ) ) {
	/**
	 * Get the total count of users with specific roles and statuses.
	 *
	 * @since 1.15.0
	 *
	 * @param array $roles Array of user roles to include.
	 * @param string $status Status of the users.
	 *
	 * @return int Total number of users matching the criteria.
	 */
	function masteriyo_get_total_user_count_by_roles_and_statuses( $roles, $status ) {
		$args = array(
			'fields'      => 'ID',
			'user_status' => $status,
			'role__in'    => $roles,
		);

		$user_query  = new WPUserQuery( $args );
		$total_users = $user_query->total_users;

		return absint( $total_users );
	}
}

if ( ! function_exists( 'masteriyo_string_translation' ) ) {
	/**
	 * Registers and retrieves a translated string for WPML.
	 *
	 * This function checks if the WPML functions `icl_register_string` and `icl_t` are available.
	 * It registers the string for translation and then retrieves its translated value based on the current language.
	 *
	 * @since 1.17.1
	 *
	 * @param string $context The context or domain for the string, used for grouping translations.
	 * @param string $name The name of the string to be translated.
	 * @param string $value The default value of the string, if no translation is available.
	 *
	 * @return string The translated string if available, otherwise the original value.
	 */
	function masteriyo_string_translation( $context, $name, $value ) {
		$context = 'masteriyo_' . preg_replace( '/\./', '_', $context );

		if ( function_exists( 'icl_register_string' ) ) {
			icl_register_string( $context, $name, $value );
		}

		if ( function_exists( 'icl_t' ) ) {
			$value = icl_t( $context, $name, $value );
		}

		return $value;
	}
}

if ( ! function_exists( 'masteriyo_show_onboarding_completion_notice' ) ) {
	/**
	 * Shows an admin notice to remind about completing the onboarding process.
	 *
	 * @since 1.18.0
	 *
	 * @return void
	 */
	function masteriyo_show_onboarding_completion_notice() {
		add_action(
			'masteriyo_admin_notices',
			function () {
				// New onboarding feature release date (YYYY-MM-DD).
				$onboarding_release_date = '2025-05-20';
				$install_date            = get_option( 'masteriyo_install_date' );

				// Don't show notice if installed before onboarding existed.
				if ( $install_date && strtotime( $install_date ) < strtotime( $onboarding_release_date ) ) {
					return;
				}

				$onboarding_data = get_option( 'masteriyo_onboarding_data', array() );
				if ( ! $onboarding_data || ! isset( $onboarding_data['started'] ) || ! $onboarding_data['started'] ) {
					return;
				}

				$valid_steps  = array( 'business_type', 'marketplace', 'course', 'payment' );
				$current_step = null;

				foreach ( $valid_steps as $step ) {
					$step_data = $onboarding_data['steps'][ $step ] ?? array();

					$skipped   = $step_data['skipped'] ?? false;
					$completed = $step_data['completed'] ?? false;

					// Show notice if the step is either skipped or not completed.
					if ( $skipped || ! $completed ) {

						// Special case: show 'marketplace' step only if business_type is 'marketplace'.
						if ( 'marketplace' === $step ) {
							$business_type = $onboarding_data['steps']['business_type']['options']['business_type'] ?? 'individual';

							if ( 'marketplace' !== $business_type ) {
								continue;
							}
						}

						$current_step = $step;
						break;
					}
				}

				if ( $current_step ) {
					$url  = esc_url( admin_url( 'admin.php?page=masteriyo-onboard&step=' . $current_step ) );
					$text = esc_html( ucfirst( str_replace( '_', ' ', $current_step ) ) );

					$message = sprintf(
						/* translators: %1$s: URL, %2$s: onboarding step name. */
						__( 'Please complete the Masteriyo onboarding process. <a href="%1$s">Continue with %2$s step</a>', 'learning-management-system' ),
						$url,
						$text
					);

					echo wp_kses_post(
						sprintf(
							'<div class="notice notice-warning"><p>%s</p></div>',
							$message
						)
					);
				}
			}
		);
	}
}
