<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Masteriyo Course Functions
 *
 * Functions for course specific things.
 *
 * @package Masteriyo\Functions
 * @version 1.0.0
 */

use Masteriyo\Activation;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Query\CourseProgressQuery;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\CourseChildrenPostType;

/**
 * For a given course, and optionally price/qty, work out the price with tax excluded, based on store settings.
 *
 * @since  1.0.0
 * @param  Course $course MASTERIYO_Course object.
 * @param  array      $args Optional arguments to pass course quantity and price.
 * @return float|string Price with tax excluded, or an empty string if price calculation failed.
 */
function masteriyo_get_price_excluding_tax( $course, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'qty'   => '',
			'price' => '',
		)
	);

	$price = '' !== $args['price'] ? max( 0.0, (float) $args['price'] ) : $course->get_price();
	$qty   = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;

	if ( '' === $price ) {
		return '';
	} elseif ( empty( $qty ) ) {
		return 0.0;
	}

	$line_price   = $price * $qty;
	$return_price = $line_price;

	/**
	 * Filters the course price with tax excluded, based on store settings.
	 *
	 * @since 1.0.0
	 *
	 * @param float $price The course price with tax excluded, based on store settings.
	 * @param integer $qty Item quantity.
	 * @param Masteriyo\Models\Course $course Course object.
	 */
	return apply_filters( 'masteriyo_get_price_excluding_tax', $return_price, $qty, $course );
}

/**
 * Check whether the current user can start taking the course.
 *
 * @since 1.0.0
 *
 * @param int|Masteriyo\Models\Course $course Course object or Course ID.
 * @param int|Masteriyo\Models\User $user User object or User ID.
 *
 * @return bool
 */
function masteriyo_can_start_course( $course, $user = null ) {
	$can_start_course = false;
	$user             = is_null( $user ) ? masteriyo_get_current_user() : $user;
	$user             = is_a( $user, 'Masteriyo\Models\User' ) ? $user : masteriyo_get_user( $user );
	$course           = masteriyo_get_course( $course );

	if ( $user && ! is_wp_error( $user ) && $course ) {
		$query = new UserCourseQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => $user->get_id(),
				'per_page'  => 1,
			)
		);

		if ( $course && ! in_array( $course->get_access_mode(), array( CourseAccessMode::OPEN, CourseAccessMode::NEED_REGISTRATION ), true ) ) {
			$user_course = current( $query->get_user_courses() );

			if ( $user_course ) {
				$order            = $user_course->get_order();
				$can_start_course = $order ? OrderStatus::COMPLETED === $order->get_status() : false;

				if ( 'active' === $user_course->get_status() && $user_course->get_date_start() ) {
					$can_start_course = true;
				}
			}
		}
	}

	if ( $course ) {
		if ( CourseAccessMode::OPEN === $course->get_access_mode() ) {
			$can_start_course = true;
		} elseif ( is_user_logged_in() && CourseAccessMode::NEED_REGISTRATION === $course->get_access_mode() ) {
			$can_start_course = true;
		} elseif ( ! $can_start_course ) {
			$can_start_course = masteriyo_check_course_content_access_for_current_user( $course );
		}
	}

	/**
	 * Filters boolean: true if given user can start the given course.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $can_start_course true if given user can start the given course.
	 * @param Masteriyo\Models\Course $course Course object.
	 * @param Masteriyo\Models\User $user User object.
	 */
	return apply_filters( 'masteriyo_can_start_course', $can_start_course, $course, $user );
}

/**
 * Get the placeholder image.
 *
 * Uses wp_get_attachment_image if using an attachment ID handle responsiveness.
 *
 * @since 1.0.0
 *
 * @param string       $size Image size.
 * @param string|array $attr Optional. Attributes for the image markup. Default empty.
 * @return string
 */
function masteriyo_placeholder_img( $size = 'masteriyo_thumbnail', $attr = '' ) {
	$dimensions        = masteriyo_get_image_size( $size );
	$placeholder_image = get_option( 'masteriyo_placeholder_image', 0 );

	$default_attr = array(
		'class' => 'masteriyo-placeholder wp-post-image',
		'alt'   => __( 'Placeholder', 'learning-management-system' ),
	);

	$attr = wp_parse_args( $attr, $default_attr );

	if ( ! wp_attachment_is_image( $placeholder_image ) ) {
		Activation::attach_placeholder_image();
	}

	$image_html = wp_get_attachment_image(
		$placeholder_image,
		$size,
		false,
		$attr
	);

	/**
	 * Filters placeholder image html.
	 *
	 * @since 1.0.0
	 *
	 * @param string $image_html The placeholder image html.
	 * @param string $size Image size.
	 * @param array $dimensions Image dimensions.
	 */
	return apply_filters( 'masteriyo_placeholder_img', $image_html, $size, $dimensions );
}


/**
 * Get the placeholder image URL either from media, or use the fallback image.
 *
 * @since 1.0.0
 *
 * @param string $size Thumbnail size to use.
 * @return string
 */
function masteriyo_placeholder_img_src( $size = 'masteriyo_thumbnail' ) {
	$src               = masteriyo_get_plugin_url() . '/assets/img/placeholder.jpg';
	$placeholder_image = get_option( 'masteriyo_placeholder_image', 0 );

	if ( ! empty( $placeholder_image ) && is_numeric( $placeholder_image ) ) {
		$src = wp_get_attachment_image_url( $placeholder_image, $size );
	}

	/**
	 * Filters placeholder image URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $src The placeholder image URL.
	 */
	return apply_filters( 'masteriyo_placeholder_img_src', $src );
}

/**
 * Count comments on a course.
 *
 * @since 1.0.0
 *
 * @param mixed $course
 *
 * @return integer
 */
function masteriyo_count_course_comments( $course ) {
	$course = masteriyo_get_course( $course );

	if ( is_null( $course ) ) {
		return 0;
	}

	global $wpdb;

	$comments_count = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(comment_ID)
			FROM {$wpdb->comments}
			WHERE comment_approved = '1'
				AND comment_type = 'mto_course_review'
				AND comment_post_ID = %d
			",
			$course->get_id()
		)
	);

	return absint( $comments_count );
}

/**
 * Get CSS class for course difficulty badge.
 *
 * @since 1.0.0
 *
 * @param string $difficulty
 *
 * @return string
 */
function masteriyo_get_difficulty_badge_css_class( $difficulty ) {
	$classes     = array(
		'beginner'     => 'masteriyo-badge-green',
		'intermediate' => 'masteriyo-badge-yellow',
		'expert'       => 'masteriyo-badge-pink',
	);
	$badge_class = 'masteriyo-badge-green';

	if ( isset( $classes[ $difficulty ] ) ) {
		$badge_class = $classes[ $difficulty ];
	}

	/**
	 * Filters course difficulty badge CSS class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $badge_class CSS class for the badge.
	 * @param string $difficulty Course difficulty.
	 */
	return apply_filters( 'masteriyo_difficulty_badge_css_class', $badge_class, $difficulty );
}

/**
 * Trim course highlights. Selects only the first given number of items.
 *
 * @since 1.0.0
 *
 * @param string $highlights
 * @param integer $limit
 *
 * @return string
 */
function masteriyo_trim_course_highlights( $highlights, $limit = 3 ) {
	/**
	 * Filters maximum number of course highlights limit.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $limit Highlights limit.
	 * @param string $highlights Course highlights.
	 */
	$limit = apply_filters( 'masteriyo_course_highlights_limit', $limit, $highlights );

	// Reference: https://www.regextester.com/27540
	$regex       = '/(<\s*li[^>]*>.*?<\s*\/\s*li>){1,' . $limit . '}/m';
	$result      = preg_match( $regex, $highlights, $matches );
	$matched_str = '';

	if ( $result && ! empty( $matches ) ) {
		$matched_str = $matches[0];
	}
	$trimmed_highlights = '';

	if ( ! empty( $matched_str ) ) {
		$trimmed_highlights = '<ul>' . $matched_str . '</ul>';
	}

	/**
	 * Filters the trimmed (with max number limit) course highlights.
	 *
	 * @since 1.0.0
	 *
	 * @param string $trimmed_highlights Trimmed course highlights.
	 * @param integer $limit Highlights limit.
	 * @param string $highlights Full list of course highlights.
	 */
	return apply_filters( 'masteriyo_trimmed_course_highlights', $trimmed_highlights, $limit, $highlights );
}

/**
 * Get course contents.
 *
 * @since 1.0.0
 * @since 1.5.15 $course parameter can be WP_Post or Course Object.
 * @since 1.5.15 Added $status parameter.
 * @since 1.6.10 Return course contents in hierarchial order according to the course builder in a flat array.
 *
 * @param WP_Post|\Masteriyo\Models\Course|integer $course Course object or Course Post or Course ID.
 *
 * @return \Masteriyo\Database\Model[]
 */
function masteriyo_get_course_contents( $course, $status = PostStatus::PUBLISH ) {
	$course = masteriyo_get_course( $course );
	$result = array();

	if ( $course ) {
		$posts = get_posts(
			array(
				'post_type'      => CourseChildrenPostType::all(),
				'posts_per_page' => -1,
				'post_status'    => $status,
				'meta_key'       => '_course_id',
				'meta_value'     => $course->get_id(),
				'meta_compare'   => 'numeric',
			)
		);

		$contents = array_filter(
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

		$sections = array_filter(
			$contents,
			function ( $content ) {
				return PostType::SECTION === $content->get_post_type();
			}
		);

		// Sort sections by menu order in ascending order.
		usort(
			$sections,
			function ( $a, $b ) {
				if ( $a->get_menu_order() === $b->get_menu_order() ) {
					return 0;
				}

				return $a->get_menu_order() > $b->get_menu_order() ? 1 : -1;
			}
		);

		$result = array();

		foreach ( $sections as $section ) {
			$section_children = array_filter(
				$contents,
				function ( $content ) use ( $section ) {
					return $section->get_id() === $content->get_parent_id();
				}
			);

			// Sort sections by menu order in ascending order.
			usort(
				$section_children,
				function ( $a, $b ) {
					if ( $a->get_menu_order() === $b->get_menu_order() ) {
						return 0;
					}

					return $a->get_menu_order() > $b->get_menu_order() ? 1 : -1;
				}
			);

			$result[] = $section;
			foreach ( $section_children as $section_child ) {
				$result[] = $section_child;
			}
		}
	}

	/**
	 * Filters course contents objects.
	 *
	 * @since 1.0.0
	 *
	 * @since 1.5.15 $course parameter can be WP_Post or Course Object.
	 *
	 * @param \Masteriyo\Database\Model $contents Course contents objects.
	 * @param WP_Post|\Masteriyo\Models\Course|integer $course Course ID or WP Post or Course object.
	 */
	return apply_filters( 'masteriyo_course_contents', array_values( $result ), $course );
}

/**
 * Get course structure.
 *
 * @since 1.0.0
 * @since 1.5.15 $course parameter can be WP_Post or Course object.
 *
 * @param WP_Post|\Masteriyo\Models\Course|integer $course Course object or Course Post or Course ID.
 *
 * @return array
 */
function masteriyo_get_course_structure( $course ) {
	$sections = array();
	$objects  = masteriyo_get_course_contents( $course );

	if ( $objects ) {
		$sections = array_values(
			array_filter(
				$objects,
				function ( $object ) {
					return CourseChildrenPostType::SECTION === $object->get_post_type();
				}
			)
		);

		// Sort sections by menu order in ascending order.
		usort(
			$sections,
			function ( $a, $b ) {
				if ( $a->get_menu_order() === $b->get_menu_order() ) {
					return 0;
				}

				return $a->get_menu_order() > $b->get_menu_order() ? 1 : -1;
			}
		);
	}

	/**
	 * Filter course structure.
	 *
	 * @since 1.0.0
	 * @since 1.5.15 $course parameter can be Course object or WP_Post.
	 *
	 * @param array $sections Ordered sections.
	 * @param int|WP_Post|\Masteriyo\Models\Course|null $course_id Course ID or Course object or WP_post.
	 */
	return apply_filters( 'masteriyo_course_structure', $sections, $course );
}

/**
 * Get count of a course review's replies.
 *
 * @since 1.0.5
 *
 * @param integer $course_review_id
 *
 * @return integer
 */
function masteriyo_get_course_review_replies_count( $course_review_id ) {
	global $wpdb;

	$replies_count = (int) $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT COUNT(*) FROM $wpdb->comments
			WHERE comment_parent = %d
			AND comment_approved = '1'
			AND comment_type = 'mto_course_review'
			",
			absint( $course_review_id )
		)
	);

	/**
	 * Filters replies count for a course review.
	 *
	 * @since 1.5.0
	 *
	 * @param integer $replies_count
	 * @param integer $course_review_id
	 */
	return apply_filters( 'masteriyo_get_course_review_replies_count', absint( $replies_count ), $course_review_id );
}

/**
 * Get post counts of post author.
 *
 * @since 1.5.0
 *
 * @param string $type Post type.
 * @param int $user_id User ID.
 * @return stdClass
 */
function masteriyo_count_posts( $type, $user_id ) {
	global $wpdb;

	if ( ! post_type_exists( $type ) ) {
		return new stdClass();
	}

	$results = (array) $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d GROUP BY post_status",
			$type,
			$user_id
		),
		ARRAY_A
	);
	$counts  = array_fill_keys( get_post_stati(), 0 );

	foreach ( $results as $row ) {
		$counts[ $row['post_status'] ] = $row['num_posts'];
	}

	$counts = (object) $counts;

	/**
	 * Modify returned post counts by status for the current post type.
	 *
	 * @since 1.5.0
	 *
	 * @param stdClass $counts An object containing the current post_type's post
	 *                         counts by status.
	 * @param string   $type   Post type.
	 * @param string   $user_id User ID.
	 */
	return apply_filters( 'masteriyo_count_posts', $counts, $type, $user_id );
}


if ( ! function_exists( 'masteriyo_get_courses_view_mode' ) ) {
	/**
	 * Get the courses view mode selected by the user.
	 *
	 * This function checks if the user has set a preferred view mode for courses by
	 * checking the 'MasteriyoCoursesViewMode' cookie. If the cookie is set, it returns
	 * the user's preferred view mode. Otherwise, it returns the default view mode 'grid'.
	 *
	 * @since 1.6.11
	 *
	 * @return string The view mode for courses ('grid' or the user's preference).
	 */
	function masteriyo_get_courses_view_mode() {
		global $course_archive_view;
		if ( isset( $course_archive_view ) && in_array( $course_archive_view, array( 'grid-view', 'list-view' ), true ) ) {
			return sanitize_text_field( $course_archive_view );
		}

		if ( isset( $_COOKIE['MasteriyoCoursesViewMode'] ) && in_array( $_COOKIE['MasteriyoCoursesViewMode'], array( 'grid-view', 'list-view' ), true ) ) {
			return sanitize_text_field( $_COOKIE['MasteriyoCoursesViewMode'] );
		}

		return masteriyo_get_setting( 'course_archive.display.view_mode' );
	}
}

if ( ! function_exists( 'masteriyo_check_course_content_access_for_current_user' ) ) {
	/**
	 * Check whether the current user can start taking the course based on the course content access settings.
	 *
	 * @since 1.6.15
	 *
	 * @param \Masteriyo\Models\Course $course Course object or Course ID.
	 *
	 * @return boolean
	 */
	function masteriyo_check_course_content_access_for_current_user( $course ) {
		$can_start_course = false;

		if ( masteriyo_string_to_bool( masteriyo_get_setting( 'general.course_access.enable_course_content_access_without_enrollment' ) ) ) {
			if ( masteriyo_is_current_user_admin() ) {
				$can_start_course = true;
			} elseif ( masteriyo_is_current_user_instructor() ) {
				$restrict         = masteriyo_string_to_bool( masteriyo_get_setting( 'general.course_access.restrict_instructors' ) );
				$can_start_course = $restrict ? masteriyo_is_current_user_post_author( $course->get_id() ) : true;
			}
		}

		return $can_start_course;
	}
}

if ( ! function_exists( 'masteriyo_get_course_buy_button' ) ) {
	/**
	 * Get buy button information for a course.
	 *
	 * @since 1.12.2
	 *
	 * @param int|\Masteriyo\Models\Course|\WP_Post $course_id
	 * @param mixed $user_id
	 *
	 * @return array
	 */
	function masteriyo_get_course_buy_button( $course_id, $user_id = null ) {
		$button = array(
			'text' => '',
			'url'  => '',
		);
		$course = masteriyo_get_course( $course_id );

		if ( is_null( $course ) || ! $course->is_purchasable() ) {
			return $button;
		}

		$query      = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => $user_id ? $user_id : get_current_user_id(),
			)
		);
		$progresses = $query->get_course_progress();
		$progress   = empty( $progresses ) ? null : $progresses[0];

		if ( masteriyo_can_start_course( $course ) ) {
			if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() ) {
				$button['text'] = wp_kses_post( $course->single_course_completed_text() );
				$button['url']  = esc_url( $course->start_course_url() );
			} elseif ( $progress && CourseProgressStatus::PROGRESS === $progress->get_status() ) {
				$button['text'] = wp_kses_post( $course->single_course_continue_text() );
				$button['url']  = esc_url( $course->start_course_url() );
			} else {
				$button['text'] = wp_kses_post( $course->single_course_start_text() );
				$button['url']  = esc_url( $course->start_course_url() );
			}
		} else {
			$button['text'] = wp_kses_post( $course->add_to_cart_text() );
			$button['url']  = esc_url( $course->add_to_cart_url() );
		}

		/**
		 * Filters course buy button information.
		 *
		 * @since 2.3.4
		 *
		 * @param array $button
		 * @param int|\Masteriyo\Models\Course|\WP_Post $course_id
		 * @param mixed $user_id
		 */
		return apply_filters( 'masteriyo_course_buy_button', $button, $course_id, $user_id );
	}
}

if ( ! function_exists( 'masteriyo_get_remaining_days_for_course_end' ) ) {

	/**
	 * Calculates the remaining days until the end date of a course.
	 *
	 * @param \Masteriyo\Models\Course $course The course object for which the end date is to be calculated.
	 * @param boolean $format If true, returns a formatted string; otherwise, returns an integer.
	 * @return int|string|null Returns an integer of the remaining days, a formatted string, or null if the end date is past or not set.
	 * @throws Exception If the date format is invalid.
	 */
	function masteriyo_get_remaining_days_for_course_end( $course, $format = false ) {
		$raw_end_date = $course->get_end_date();

		if ( ! $raw_end_date ) {
			return null;
		}

		try {
			$end_date = new DateTime( $raw_end_date );
			$today    = new DateTime( current_time( 'Y-m-d' ) );
		} catch ( Exception $e ) {
			return null;
		}

		$interval       = $today->diff( $end_date );
		$remaining_days = $interval->days;

		if ( $today > $end_date ) {
			return null;
		}

		if ( $format ) {
			return $remaining_days . ' ' . _n( 'day', 'days', $remaining_days, 'learning-management-system' );
		}

		return $remaining_days;
	}
}

if ( ! function_exists( 'masteriyo_get_youtube_thumbnail' ) ) {
	/**
	 * Extract the video ID from the YouTube embed URL and construct the max resolution thumbnail URL.
	 *
	 * @since 1.11.3
	 *
	 * @param string $embed_url The YouTube embed URL.
	 *
	 * @return string|false The max resolution thumbnail URL, or false if the video ID could not be extracted.
	 */
	function masteriyo_get_youtube_thumbnail( $embed_url ) {
		preg_match( '/embed\/([a-zA-Z0-9_-]+)\?/', $embed_url, $matches );

		if ( isset( $matches[1] ) ) {
			$thumbnail_url = 'https://img.youtube.com/vi/' . $matches[1] . '/maxresdefault.jpg';

			return $thumbnail_url;
		}

		return false;
	}
}

if ( ! function_exists( 'masteriyo_get_instructor_course_ids' ) ) {
	/**
	 * Retrieves the course IDs associated with a given instructor.
	 *
	 * @since 1.11.0
	 *
	 * @param int|null $instructor_id The ID of the instructor. If not provided, the current user's ID will be used.
	 *
	 * @return array An array of course IDs associated with the specified instructor.
	 */
	function masteriyo_get_instructor_course_ids( $instructor_id = null ) {
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

		/**
		 * Filter the list of course IDs for an instructor.
		 *
		 * @since 1.11.0
		 *
		 * @param array $course_ids The array of course IDs.
		 * @param int $instructor_id The instructor ID.
		 */
		$course_ids = apply_filters( 'masteriyo_get_instructor_course_ids', $course_ids, $instructor_id );

		return $course_ids;
	}
}

if ( ! function_exists( 'masteriyo_get_instructor_lesson_ids' ) ) {
	/**
	 * Retrieves the lesson IDs associated with a given instructor.
	 *
	 * @since 1.14.0
	 *
	 * @param int|null $instructor_id The ID of the instructor. If not provided, the current user's ID will be used.
	 *
	 * @return array An array of lesson IDs associated with the specified instructor.
	 */
	function masteriyo_get_instructor_lesson_ids( $instructor_id = null ) {
		if ( is_null( $instructor_id ) ) {
			$instructor_id = masteriyo_is_current_user_instructor() ? get_current_user_id() : 0;
		}

		if ( ! $instructor_id ) {
			return array();
		}

		$args = array(
			'post_type'      => PostType::LESSON,
			'post_status'    => PostStatus::PUBLISH,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'author'         => $instructor_id,
		);

		$lesson_ids = get_posts( $args );

		/**
		 * Filter the list of lesson IDs for an instructor.
		 *
		 * @since 1.14.0
		 *
		 * @param array $lesson_ids The array of lesson IDs.
		 * @param int $instructor_id The instructor ID.
		 */
		$lesson_ids = apply_filters( 'masteriyo_get_instructor_lesson_ids', $lesson_ids, $instructor_id );

		return $lesson_ids;
	}
}

if ( ! function_exists( 'masteriyo_user_has_completed_course' ) ) {
	/**
	 * Check if a user has completed a course.
	 *
	 * @since 1.13.0
	 *
	 * @param int|\Masteriyo\Models\Course|\WP_Post $course_id
	 * @param integer $user_id
	 *
	 * @return boolean
	 */
	function masteriyo_user_has_completed_course( $course_id, $user_id ) {
		$course       = masteriyo_get_course( $course_id );
		$is_completed = false;

		if ( $course ) {
			$query    = new CourseProgressQuery(
				array(
					'course_id' => $course->get_id(),
					'user_id'   => $user_id,
				)
			);
			$progress = current( $query->get_course_progress() );

			if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() ) {
				$is_completed = true;
			}
		}

		/**
		 * Filters boolean: true if the given user has completed the given course.
		 *
		 * @since 1.13.0
		 *
		 * @param boolean $is_completed True if the given user has completed the given course.
		 * @param int|\Masteriyo\Models\Course|\WP_Post $course_id
		 * @param integer $user_id
		 */
		return apply_filters( 'masteriyo_has_user_completed_course', $is_completed, $course_id, $user_id );
	}
}


if ( ! function_exists( 'masteriyo_can_user_review_course' ) ) {
	/**
	 * Retrieves the enrolled users for a course.
	 *
	 * @since 1.18.0
	 *
	 * @param int $course_id The ID of the course.
	 *
	 * @return boolean true if the user is enrolled in the course, false otherwise.
	 */
	function masteriyo_can_user_review_course( $course ) {
		if ( ! $course || ! is_user_logged_in() ) {
			return false;
		}

		if ( ! masteriyo_get_setting( 'single_course.display.enable_review' ) ) {
			return false;
		}

		if ( masteriyo_is_current_user_admin() || masteriyo_is_current_user_manager() || masteriyo_get_current_user_id() === $course->get_author_id() ) {
			return true;
		}

		if ( masteriyo_get_setting( 'single_course.display.enable_review_enrolled_users_only' ) ) {
			$enrolled_users = masteriyo_is_user_enrolled_in_course( $course->get_id() );
			return $enrolled_users;
		}

		return true;
	}
}
