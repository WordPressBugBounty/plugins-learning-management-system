<?php

//As this files autoload from composer.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Template functions.
 *
 * @since 1.0.0
 */

use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Query\UserCourseQuery;
use Masteriyo\Query\CourseProgressQuery;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\CourseChildrenPostType;
use Masteriyo\Enums\SectionChildrenPostType;
use Masteriyo\Query\CourseCategoryQuery;
use Masteriyo\Taxonomy\Taxonomy;

if ( ! ( function_exists( 'add_action' ) && function_exists( 'add_filter' ) ) ) {
	return;
}

/**
 * Handle redirects before content is output - hooked into template_redirect so is_page works.
 *
 * @since 1.0.0
 */
function masteriyo_template_redirect() {
	global $wp_query, $wp;

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	// When default permalinks are enabled, redirect courses list page to post type archive url.
	if ( ! empty( $_GET['page_id'] ) && '' === get_option( 'permalink_structure' )
		&& masteriyo_get_page_id( 'courses' ) === absint( $_GET['page_id'] )
		&& get_post_type_archive_link( 'mto-course' ) ) {
			wp_safe_redirect( get_post_type_archive_link( 'mto-course' ) );
			exit;
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	/**
	 * Filters boolean: true if it should be redirected to a different page if cart is empty.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $bool true if it should be redirected to a different page if cart is empty.
	 */
	$redirect_empty_cart = apply_filters( 'masteriyo_checkout_redirect_empty_cart', true );

	// When on the checkout with an empty cart, redirect to courses list page.
	if (
		is_page( masteriyo_get_page_id( 'checkout' ) ) && masteriyo_get_page_id( 'checkout' ) !== masteriyo_get_page_id( 'cart' )
		&& masteriyo( 'cart' )->is_empty() && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] )
		&& ! is_customize_preview() && $redirect_empty_cart
	) {
		wp_safe_redirect( masteriyo_get_courses_url() );
		exit;
	}

	/**
	 * Filters boolean: true if it should be redirected to the found course's detail page, if search result has only one item.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $bool true if it should be redirected to the found course's detail page, if search result has only one item.
	 */
	$redirect_single_search_result = apply_filters( 'masteriyo_redirect_single_search_result', true );

	// Redirect to the course page if we have a single course.
	if (
		is_search() && is_post_type_archive( 'mto-course' )
		&& $redirect_single_search_result && 1 === absint( $wp_query->found_posts )
	) {
		$course = masteriyo_get_course( $wp_query->post );

		if ( $course && $course->is_visible() ) {
			wp_safe_redirect( $course->get_permalink(), 302 );
			exit;
		}
	}
}
function_exists( 'add_action' ) && add_action( 'template_redirect', 'masteriyo_template_redirect' );

/**
 * Should the Masteriyo loop be displayed?
 *
 * This will return true if we have posts (courses) or if we have sub categories to display.
 *
 * @since 1.0.0
 * @return bool
 */
function masteriyo_course_loop() {
	return have_posts();
}

if ( ! function_exists( 'masteriyo_course_loop_start' ) ) {

	/**
	 * Output the start of a course loop. By default this is a UL.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $echo Should echo?.
	 * @param string $template_name Template file to load.
	 * @return string
	 */
	function masteriyo_course_loop_start( $echo = true, $template_name = 'loop/loop-start.php' ) {
		ob_start();

		masteriyo_set_loop_prop( 'loop', 0 );

		masteriyo_get_template( $template_name );

		/**
		 * Filter masteriyo courses loop start content.
		 *
		 * @since 1.5.30
		 */
		$loop_start = apply_filters( 'masteriyo_course_loop_start', ob_get_clean() );

		if ( $echo ) {
			echo wp_kses_post( $loop_start );
		} else {
			return $loop_start;
		}
	}
}

if ( ! function_exists( 'masteriyo_course_loop_end' ) ) {

	/**
	 * Output the end of a course loop. By default this is a UL.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $echo Should echo?.
	 * @return string
	 */
	function masteriyo_course_loop_end( $echo = true ) {
		ob_start();

		masteriyo_get_template( 'loop/loop-end.php' );

		/**
		 * Filters course loop end html.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html The course loop end html.
		 */
		$loop_end = apply_filters( 'masteriyo_course_loop_end', ob_get_clean() );

		if ( $echo ) {
			echo wp_kses_post( $loop_end );
		} else {
			return $loop_end;
		}
	}
}

if ( ! function_exists( 'masteriyo_page_title' ) ) {

	/**
	 * Page Title function.
	 *
	 * @since 1.0.0
	 *
	 * @param  bool $echo Should echo title.
	 * @return string
	 */
	function masteriyo_page_title( $echo = true ) {
		$page_title = '';

		if ( is_author() ) {
			global $wp_query;

			if ( have_posts() ) {
				// Queue the first post to know the archive author.
				the_post();

				$author = get_the_author();
				/* translators: %s: Author */
				$page_title = sprintf( __( 'Instructor | %s', 'learning-management-system' ), esc_html( $author ) );

				// Since we called the_post() above, we need to rewind the loop back.
				rewind_posts();
			} elseif ( isset( $wp_query->query['author_name'] ) ) {
				$user = get_user_by( 'slug', $wp_query->query['author_name'] );

				if ( $user ) {
					/* translators: %s: Author */
					$page_title = sprintf( __( 'Instructor | %s', 'learning-management-system' ), esc_html( $user->data->display_name ) );
				}
			}
		} elseif ( is_search() ) {
			/* translators: %s: search query */
			$page_title = sprintf( __( 'Search results: &ldquo;%s&rdquo;', 'learning-management-system' ), get_search_query() );

			if ( get_query_var( 'paged' ) ) {
				/* translators: %s: page number */
				$page_title .= sprintf( __( '&nbsp;&ndash; Page %s', 'learning-management-system' ), get_query_var( 'paged' ) );
			}
		} elseif ( is_tax() ) {
			$page_title = single_term_title( '', false );
		} else {
			$courses_page_id = masteriyo_get_page_id( 'courses' );
			$page_title      = get_the_title( $courses_page_id );
		}

		/**
		 * Filters current page title.
		 *
		 * @since 1.0.0
		 *
		 * @param string $page_title The current page title.
		 */
		$page_title = apply_filters( 'masteriyo_page_title', $page_title );

		if ( $echo ) {
			echo esc_html( $page_title );
		} else {
			return $page_title;
		}
	}
}

/**
 * Sets up the masteriyo_loop global from the passed args or from the main query.
 *
 * @since 1.0.0
 * @param array $args Args to pass into the global.
 */
function masteriyo_setup_loop( $args = array() ) {
	$default_args = array(
		'loop'         => 0,
		'columns'      => masteriyo_get_default_courses_per_row(),
		'name'         => '',
		'is_shortcode' => false,
		'is_paginated' => true,
		'is_search'    => false,
		'is_filtered'  => false,
		'total'        => 0,
		'total_pages'  => 0,
		'per_page'     => 0,
		'current_page' => 1,
	);

	// If this is a main Masteriyo query, use global args as defaults.
	if ( $GLOBALS['wp_query']->get( 'masteriyo_query' ) ) {
		$default_args = array_merge(
			$default_args,
			array(
				'is_search'    => $GLOBALS['wp_query']->is_search(),
				'is_filtered'  => masteriyo_is_filtered(),
				'total'        => $GLOBALS['wp_query']->found_posts,
				'total_pages'  => $GLOBALS['wp_query']->max_num_pages,
				'per_page'     => $GLOBALS['wp_query']->get( 'posts_per_page' ),
				'current_page' => max( 1, $GLOBALS['wp_query']->get( 'paged', 1 ) ),
			)
		);
	}

	// Merge any existing values.
	if ( isset( $GLOBALS['masteriyo_loop'] ) ) {
		$default_args = array_merge( $default_args, $GLOBALS['masteriyo_loop'] );
	}

	$GLOBALS['masteriyo_loop'] = wp_parse_args( $args, $default_args );
}
function_exists( 'add_action' ) && add_action( 'masteriyo_before_courses_loop', 'masteriyo_setup_loop' );

/**
 * Resets the masteriyo_loop global.
 *
 * @since 1.0.6
 */
function masteriyo_reset_loop() {
	unset( $GLOBALS['masteriyo_loop'] );
}

/**
 * Get the default columns setting - this is how many courses will be shown per row in loops.
 *
 * @since 1.0.0
 *
 * @return int
 */
function masteriyo_get_default_courses_per_row() {
	$columns     = masteriyo_get_setting( 'course_archive.display.per_row' );
	$course_grid = masteriyo_get_theme_support( 'course_grid' );
	$min_columns = isset( $course_grid['min_columns'] ) ? absint( $course_grid['min_columns'] ) : 0;
	$max_columns = isset( $course_grid['max_columns'] ) ? absint( $course_grid['max_columns'] ) : 0;

	if ( $min_columns && $columns < $min_columns ) {
		$columns = $min_columns;
		update_option( 'masteriyo.courses.per_row', $columns );
	} elseif ( $max_columns && $columns > $max_columns ) {
		$columns = $max_columns;
		update_option( 'masteriyo.courses.per_row', $columns );
	}

	$columns = max( 1, absint( $columns ) );

	/**
	 * Filters number of columns for a course loop.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $columns The number of columns for a course loop.
	 */
	return apply_filters( 'masteriyo_loop_courses_columns', $columns );
}

/**
 * Get the default rows setting - this is how many course rows will be shown in loops.
 *
 * @since 1.0.0
 * @return int
 */
function masteriyo_get_default_course_rows_per_page() {
	$rows        = masteriyo_get_setting( 'course_archive.display.per_page' );
	$course_grid = masteriyo_get_theme_support( 'course_grid' );
	$min_rows    = isset( $course_grid['min_rows'] ) ? absint( $course_grid['min_rows'] ) : 0;
	$max_rows    = isset( $course_grid['max_rows'] ) ? absint( $course_grid['max_rows'] ) : 0;

	if ( $min_rows && $rows < $min_rows ) {
		$rows = $min_rows;
		update_option( 'masteriyo.courses.per_page', $rows );
	} elseif ( $max_rows && $rows > $max_rows ) {
		$rows = $max_rows;
		update_option( 'masteriyo.courses.per_page', $rows );
	}

	return $rows;
}

/**
 * Sets a property in the masteriyo_loop global.
 *
 * @since 1.0.0
 * @param string $prop Prop to set.
 * @param string $value Value to set.
 */
function masteriyo_set_loop_prop( $prop, $value = '' ) {
	if ( ! isset( $GLOBALS['masteriyo_loop'] ) ) {
		masteriyo_setup_loop();
	}
	$GLOBALS['masteriyo_loop'][ $prop ] = $value;
}

/**
 * Gets a property from the masteriyo_loop global.
 *
 * @since 1.0.0
 * @param string $prop Prop to get.
 * @param string $default Default if the prop does not exist.
 * @return mixed
 */
function masteriyo_get_loop_prop( $prop, $default = '' ) {
	masteriyo_setup_loop(); // Ensure courses loop is setup.

	return isset( $GLOBALS['masteriyo_loop'], $GLOBALS['masteriyo_loop'][ $prop ] ) ? $GLOBALS['masteriyo_loop'][ $prop ] : $default;
}

/**
 * When the_post is called, put course data into a global.
 *
 * @param mixed $post Post Object.
 * @return Masteriyo\Models\Course
 */
function masteriyo_setup_course_data( $post ) {
	unset( $GLOBALS['course'] );

	if ( is_int( $post ) ) {
		$post = get_post( $post );
	}

	if ( empty( $post->post_type ) || ! in_array( $post->post_type, array( 'mto-course' ), true ) ) {
		return;
	}

	$GLOBALS['course'] = masteriyo_get_course( $post );

	/**
	 * Filters the course data before it is returned.
	 *
	 * @since 1.11.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 *
	 * @return \Masteriyo\Models\Course The filtered course object.
	 */
	return apply_filters( 'masteriyo_setup_course_data', $GLOBALS['course'] );
}
function_exists( 'add_action' ) && add_action( 'the_post', 'masteriyo_setup_course_data' );

/**
 * Add class to the body tag.
 *
 * @since 1.0.0
 *
 * @param string[] $classes An array of body class names.
 * @param string[] $class   An array of additional class names added to the body.
 * @return string[]
 */

function masteriyo_add_body_class( $classes, $class ) {
	global $post;

	if ( masteriyo_is_archive_course_page() ) {
		$classes[] = 'masteriyo masteriyo-courses-page';
	} elseif ( masteriyo_is_learn_page() ) {
		$classes[] = 'masteriyo notranslate masteriyo-interactive-page';
	} elseif ( masteriyo_is_account_page() ) {
		$classes[] = 'masteriyo masteriyo-account-page';
	} elseif ( is_tax( 'course_cat' ) ) {
		$classes[] = 'masteriyo masteriyo-course-category-page';
	} elseif ( masteriyo_is_single_course_page() ) {
		$classes[] = 'masteriyo';
	}

	if ( $post ) {
		$tags = masteriyo_get_shortcode_tags();

		foreach ( $tags as $tag ) {
			if ( has_shortcode( $post->post_content, $tag ) ) {
				$classes[] = 'masteriyo';
				break;
			}
		}

		$blocks = masteriyo_get_blocks();
		foreach ( $blocks as $block ) {
			if ( has_block( $block, $post->post_content ) ) {
				$content = apply_filters( 'the_content', get_the_content() );

				if ( strpos( $content, 'masteriyo-single-course-block' ) !== false ) {
					$classes[] = 'masteriyo single-mto-course';
				} else {
					$classes[] = 'masteriyo';
				}
				break;
			}
		}
	}

	return $classes;
}

if ( ! function_exists( 'masteriyo_add_admin_body_class' ) ) {
	/**
	 * Add class to the body tag in admin page.
	 *
	 * @since 1.3.0
	 *
	 * @param string $classes An string of body class names.
	 *
	 * @return string
	 */
	function masteriyo_add_admin_body_class( $classes ) {
		if ( masteriyo_is_post_edit_page() && masteriyo_is_block_editor() ) {
			$classes .= ' masteriyo';
		}

		return $classes;
	}
}

if ( ! function_exists( 'masteriyo_template_enroll_button' ) ) {
	/**
	 * Show enroll now button.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_template_enroll_button( $course ) {
		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress = current( $query->get_course_progress() );

		$class = array(
			'masteriyo-btn',
			'masteriyo-btn-primary',
			'masteriyo-single-course--btn',
			'masteriyo-enroll-btn',
			'mb-0',
			'masteriyo-enroll-btn',
		);

		if ( 0 !== $course->get_enrollment_limit() && 0 >= $course->get_enrollment_limit() - masteriyo_count_enrolled_users( $course->get_id() ) && empty( $progress ) ) {
			$class[] = 'masteriyo-btn-disabled';
		}
		if ( masteriyo_can_start_course( $course ) ) {
			if ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() ) {
				$class[] = 'masteriyo-btn-complete';
			} elseif ( $progress && CourseProgressStatus::COMPLETED === $progress->get_status() ) {
				$class[] = 'masteriyo-btn-continue';
			} else {
				$class[] = 'masteriyo-btn-start-course';
			}

			if ( post_password_required( get_post( $course->get_id() ) ) ) {
				$class[] = 'masteriyo-password-protected';
			}
		} else {
			$class[] = 'masteriyo-course--btn';
		}

		/**
		 * Filters enroll button class.
		 *
		 * @since 1.5.12
		 *
		 * @param string[] $class An array of class names.
		 * @param \Masteriyo\Models\Course $course Course object.
		 * @param \Masteriyo\Models\CourseProgress $progress Course progress object.
		 */
		$class = apply_filters( 'masteriyo_enroll_button_class', $class, $course, $progress );

		$layout               = masteriyo_get_setting( 'course_archive.display.template.layout' ) ?? 'default';
		$single_course_layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';

		if ( masteriyo_is_single_course_page() ) {
			if ( 'layout1' === $single_course_layout ) {
				$class[] = 'masteriyo-single-body__aside--price-button';
			}
		}

		if ( 'layout2' === $layout ) {
			$class[] = 'masteriyo-course-card__content--primary-button';
		}

		masteriyo_get_template(
			'enroll-button.php',
			array(
				'course'   => $course,
				'progress' => $progress,
				'class'    => join( ' ', $class ),
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_retake_button_modal' ) ) {
	/**
	 * Include modals for the single course page.
	 *
	 * @since 1.8.0
	 */
	function masteriyo_single_course_retake_button_modal() {
		masteriyo_get_template( 'single-course/retake-button-modal.php' );
	}
}

if ( ! function_exists( 'masteriyo_template_course_retake_button' ) ) {
	/**
	 * Show retake now button.
	 *
	 * @since 1.8.0
	 */
	function masteriyo_template_course_retake_button( $course ) {
		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress = current( $query->get_course_progress() );

		$class = array(
			'masteriyo-btn',
			'masteriyo-single-course--btn',
			'masteriyo-retake-btn',
			'mb-0',
		);

		/**
		 * Filters retake button class.
		 *
		 * @since 1.8.0
		 *
		 * @param string[] $class An array of class names.
		 * @param \Masteriyo\Models\Course $course Course object.
		 * @param \Masteriyo\Models\CourseProgress $progress Course progress object.
		 */
		$class = apply_filters( 'masteriyo_retake_button_class', $class, $course, $progress );

		masteriyo_get_template(
			'retake-button.php',
			array(
				'course'   => $course,
				'progress' => $progress,
				'class'    => join( ' ', $class ),
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_account_sidebar_content' ) ) {
	/**
	 * Show sidebar on account page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_account_sidebar_content() {
		$data = array(
			'menu_items'       => masteriyo_get_account_menu_items(),
			'user'             => masteriyo_get_current_user(),
			'current_endpoint' => masteriyo_get_current_account_endpoint(),
		);

		masteriyo_get_template( 'account/sidebar-content.php', $data );
	}
}

if ( ! function_exists( 'masteriyo_account_courses_endpoint' ) ) {
	/**
	 * Show courses on account page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_account_courses_endpoint() {
		$query = new UserCourseQuery(
			array(
				'user_id' => get_current_user_id(),
				'limit'   => -1,
			)
		);

		$user_courses = $query->get_user_courses();
		$all_courses  = array_filter(
			array_map(
				function ( $user_course ) {
					$course = masteriyo_get_course( $user_course->get_course_id() );

					if ( is_null( $course ) ) {
						return null;
					}

					return $course;
				},
				$user_courses
			)
		);

		$active_courses = masteriyo_get_active_courses( get_current_user_id() );

		masteriyo_get_template(
			'account/courses.php',
			array(
				'active_courses' => $active_courses,
				'all_courses'    => $all_courses,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_account_edit_account_endpoint' ) ) {
	/**
	 * Edit account on account page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_account_edit_account_endpoint() {
		$data = array(
			'user' => masteriyo_get_current_user(),
		);

		masteriyo_get_template( 'account/edit-account.php', $data );
	}
}

if ( ! function_exists( 'masteriyo_account_view_account_endpoint' ) ) {
	/**
	 * View profile on account page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_account_view_account_endpoint() {
		$data = array(
			'user' => masteriyo_get_current_user(),
		);

		masteriyo_get_template( 'account/view-account.php', $data );
	}
}

if ( ! function_exists( 'masteriyo_account_order_history_endpoint' ) ) {
	/**
	 * Show order history on account page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_account_order_history_endpoint() {
		$orders = masteriyo_get_orders(
			array(
				'customer_id' => get_current_user_id(),
			)
		);

		masteriyo_get_template(
			'account/order-history.php',
			array(
				'orders' => $orders,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_account_view_order_endpoint' ) ) {
	/**
	 * Show order detail on account page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_account_view_order_endpoint( $order_id ) {
		$order       = masteriyo_get_order( $order_id );
		$customer_id = $order->get_customer_id();

		if ( ! masteriyo_is_current_user_admin() && ! masteriyo_is_current_user_manager() && get_current_user_id() !== $customer_id ) {
			esc_html_e( 'You are not allowed to view this content.', 'learning-management-system' );
			return;
		}

		$customer_note = $order->get_customer_note();
		$order_items   = $order->get_items( 'course' );

		/**
		 * Filters order status list for showing purchase note when they exist.
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $statuses The order status list for showing purchase note when they exist.
		 */
		$show_purchase_note = $order->has_status( apply_filters( 'masteriyo_purchase_note_order_statuses', array( OrderStatus::COMPLETED, OrderStatus::PROCESSING ) ) );

		$show_customer_details = masteriyo_is_current_user_admin() || ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() );

		masteriyo_get_template(
			'account/view-order.php',
			array(
				'order'                 => $order,
				'customer_note'         => $customer_note,
				'order_items'           => $order_items,
				'show_purchase_note'    => $show_purchase_note,
				'show_customer_details' => $show_customer_details,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_account_main_content' ) ) {
	/**
	 * Handle account page's main content.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_account_main_content() {
		$endpoint         = masteriyo_get_current_account_endpoint();
		$current_endpoint = $endpoint['endpoint'];

		if ( has_action( 'masteriyo_account_' . $current_endpoint . '_endpoint' ) ) {
			/**
			 * Action for rendering content of an endpoint in account page.
			 *
			 * @since 1.0.0
			 *
			 * @param string $arg Endpoint argument.
			 */
			do_action( 'masteriyo_account_' . $current_endpoint . '_endpoint', $endpoint['arg'] );
			return;
		}

		$endpoint['user']           = masteriyo_get_current_user();
		$endpoint['active_courses'] = masteriyo_get_active_courses( get_current_user_id() );

		// No endpoint found? Default to dashboard.
		masteriyo_get_template( 'account/dashboard.php', $endpoint );
	}
}

if ( ! function_exists( 'masteriyo_email_header' ) ) {
	/**
	 * Get the email header.
	 *
	 * @param mixed $email_heading Heading for the email.
	 */
	function masteriyo_email_header( $email_heading ) {
		masteriyo_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
	}
}

if ( ! function_exists( 'masteriyo_email_footer' ) ) {
	/**
	 * Get the email footer.
	 */
	function masteriyo_email_footer() {
		masteriyo_get_template( 'emails/email-footer.php' );
	}
}

if ( ! function_exists( 'masteriyo_single_course_featured_image' ) ) {
	/**
	 * Show course featured image.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_featured_image( $course ) {
		masteriyo_get_template(
			'single-course/featured-image.php',
			array(
				'course'     => $course,
				'difficulty' => $course->get_difficulty(),
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_categories' ) ) {
	/**
	 * Show course categories.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_categories( $course ) {
		if ( empty( $course->get_categories() ) ) {
			return;
		}

		masteriyo_get_template(
			'single-course/categories.php',
			array(
				'course' => $course,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_title' ) ) {
	/**
	 * Show course title.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_title( $course ) {
		masteriyo_get_template(
			'single-course/title.php',
			array(
				'course' => $course,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_badge' ) ) {
	/**
	 * Show course badge.
	 *
	 * @since 1.9.3
	 */
	function masteriyo_single_course_badge( $course ) {
		masteriyo_get_template(
			'single-course/course-badge.php',
			array(
				'course' => $course,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_archive_course_badge' ) ) {
	/**
	 * Show course badge.
	 *
	 * @since 1.10.0
	 */
	function masteriyo_archive_course_badge( $course ) {
		masteriyo_get_template(
			'course-badge.php',
			array(
				'course' => $course,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_course_archive_layout_1_stats' ) ) {
	/**
	 * Show course stats.
	 *
	 * @since 1.12.0
	 */
	function masteriyo_course_archive_layout_1_stats( $course ) {
		$course_meta = get_post_meta( $course->get_id() );

		if ( isset( $course_meta['_course_coming_soon_enable'] ) ) {
			$enable = $course_meta['_course_coming_soon_enable'] ? $course_meta['_course_coming_soon_enable'] : false;
			$enable = end( $enable );

			if ( $enable ) {
				$ending_date = $course_meta['_course_coming_soon_ending_date'] ? $course_meta['_course_coming_soon_ending_date'] : false;
				$timestamp   = $course_meta['_course_coming_soon_timestamp'] ? $course_meta['_course_coming_soon_timestamp'] : false;
				if ( $timestamp ) {
					$timestamp = end( $timestamp );
				}
				if ( $timestamp > time() ) {
					if ( isset( $course_meta['_course_coming_soon_hide_meta_data'] ) ) {
						$hide_meta_data = $course_meta['_course_coming_soon_hide_meta_data'];
						$hide_meta_data = end( $hide_meta_data );
						if ( $hide_meta_data ) {
							return;
						}
					}
				}
			}
		}

		masteriyo_get_template(
			'course-meta-data-1.php',
			array(
				'course'            => $course,
				'quiz_count'        => get_course_section_children_count_by_course( $course->get_id(), PostType::QUIZ ),
				'google_meet_count' => get_course_section_children_count_by_course( $course->get_id(), PostType::GOOGLEMEET ),
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_course_archive_layout_2_stats' ) ) {
	/**
	 * Show course stats.
	 *
	 * @since 1.12.0
	 */
	function masteriyo_course_archive_layout_2_stats( $course ) {
		$course_meta = get_post_meta( $course->get_id() );

		if ( isset( $course_meta['_course_coming_soon_enable'] ) ) {
			$enable = $course_meta['_course_coming_soon_enable'] ? $course_meta['_course_coming_soon_enable'] : false;
			$enable = end( $enable );

			if ( $enable ) {
				$ending_date = $course_meta['_course_coming_soon_ending_date'] ? $course_meta['_course_coming_soon_ending_date'] : false;
				$timestamp   = $course_meta['_course_coming_soon_timestamp'] ? $course_meta['_course_coming_soon_timestamp'] : false;
				if ( $timestamp ) {
					$timestamp = end( $timestamp );
				}
				if ( $timestamp > time() ) {
					if ( isset( $course_meta['_course_coming_soon_hide_meta_data'] ) ) {
						$hide_meta_data = $course_meta['_course_coming_soon_hide_meta_data'];
						$hide_meta_data = end( $hide_meta_data );
						if ( $hide_meta_data ) {
							return;
						}
					}
				}
			}
		}

		masteriyo_get_template(
			'course-meta-data-2.php',
			array(
				'course'            => $course,
				'quiz_count'        => get_course_section_children_count_by_course( $course->get_id(), PostType::QUIZ ),
				'google_meet_count' => get_course_section_children_count_by_course( $course->get_id(), PostType::GOOGLEMEET ),
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_author_and_rating' ) ) {
	/**
	 * Show course author and rating.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_author_and_rating( $course ) {
		masteriyo_get_template(
			'single-course/author-and-rating.php',
			array(
				'course' => $course,
				'author' => masteriyo_get_user( $course->get_author_id() ),
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_price_and_enroll_button' ) ) {
	/**
	 * Show course price and enroll button.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_price_and_enroll_button( $course ) {
		masteriyo_get_template(
			'single-course/price-and-enroll-button.php',
			array(
				'course' => $course,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_progress_bar' ) ) {
	/**
	 * Show course stats in single course page.
	 *
	 * @since 1.14.0
	 */
	function masteriyo_single_course_progress_bar( $course ) {

		$check_course = masteriyo_is_standard_course_type( $course );

		if ( ! $check_course ) {
			return;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress = current( $query->get_course_progress() );

		$summary = $progress ? $progress->get_summary( 'all' ) : '';

		if ( ! empty( $summary ) && isset( $summary['total']['total'] ) && $summary['total']['total'] > 0 ) {
			masteriyo_get_template(
				'single-course/course-progress.php',
				array(
					'course'   => $course,
					'progress' => $progress,
					'summary'  => $summary,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_single_course_stats' ) ) {
	/**
	 * Show course stats in single course page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_stats( $course ) {
		$comments_count = masteriyo_count_course_comments( $course );

		$course_meta = get_post_meta( $course->get_id() );

		if ( isset( $course_meta['_course_coming_soon_enable'] ) ) {
			$enable = $course_meta['_course_coming_soon_enable'] ? $course_meta['_course_coming_soon_enable'] : false;
			$enable = end( $enable );

			if ( $enable ) {
				$ending_date = $course_meta['_course_coming_soon_ending_date'] ? $course_meta['_course_coming_soon_ending_date'] : false;
				$timestamp   = $course_meta['_course_coming_soon_timestamp'] ? $course_meta['_course_coming_soon_timestamp'] : false;
				if ( $timestamp ) {
					$timestamp = end( $timestamp );
				}
				if ( $timestamp > time() ) {
					if ( isset( $course_meta['_course_coming_soon_hide_meta_data'] ) ) {
						$hide_meta_data = $course_meta['_course_coming_soon_hide_meta_data'];
						$hide_meta_data = end( $hide_meta_data );
						if ( $hide_meta_data ) {
							return;
						}
					}
				}
			}
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress = current( $query->get_course_progress() );

		$summary = $progress ? $progress->get_summary( 'all' ) : '';

		masteriyo_get_template(
			'single-course/course-stats.php',
			array(
				'course'                    => $course,
				'comments_count'            => $comments_count,
				'enrolled_users_count'      => masteriyo_count_enrolled_users( $course->get_id() ) + $course->get_fake_enrolled_count(),
				'remaining_available_seats' => $course->get_enrollment_limit() > 0 ? $course->get_enrollment_limit() - masteriyo_count_enrolled_users( $course->get_id() ) : 0,
				'progress'                  => $progress,
				'summary'                   => $summary,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_layout_1_progress' ) ) {
	/**
	 * Show course stats in single course page for layout-1.
	 *
	 * @since 1.14.0
	 */
	function masteriyo_single_course_layout_1_progress( $course ) {

		$check_course = masteriyo_is_standard_course_type( $course );

		if ( ! $check_course ) {
			return;
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress = current( $query->get_course_progress() );

		$summary = $progress ? $progress->get_summary( 'all' ) : '';

		if ( ! empty( $summary ) && isset( $summary['total']['total'] ) && $summary['total']['total'] > 0 ) {
			masteriyo_get_template(
				'single-course/layout-1/course-progress.php',
				array(
					'course'   => $course,
					'progress' => $progress,
					'summary'  => $summary,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_single_course_layout_1_stats' ) ) {
	/**
	 * Show course stats in single course page for layout-1.
	 *
	 * @since 1.12.0
	 */
	function masteriyo_single_course_layout_1_stats( $course ) {
		$comments_count = masteriyo_count_course_comments( $course );
		$difficulty     = $course->get_difficulty();

		$course_meta = get_post_meta( $course->get_id() );

		if ( isset( $course_meta['_course_coming_soon_enable'] ) ) {
			$enable = $course_meta['_course_coming_soon_enable'] ? $course_meta['_course_coming_soon_enable'] : false;
			$enable = end( $enable );

			if ( $enable ) {
				$ending_date = $course_meta['_course_coming_soon_ending_date'] ? $course_meta['_course_coming_soon_ending_date'] : false;
				$timestamp   = $course_meta['_course_coming_soon_timestamp'] ? $course_meta['_course_coming_soon_timestamp'] : false;
				if ( $timestamp ) {
					$timestamp = end( $timestamp );
				}
				if ( $timestamp > time() ) {
					if ( isset( $course_meta['_course_coming_soon_hide_meta_data'] ) ) {
						$hide_meta_data = $course_meta['_course_coming_soon_hide_meta_data'];
						$hide_meta_data = end( $hide_meta_data );
						if ( $hide_meta_data ) {
							return;
						}
					}
				}
			}
		}

		$query = new CourseProgressQuery(
			array(
				'course_id' => $course->get_id(),
				'user_id'   => get_current_user_id(),
			)
		);

		$progress = current( $query->get_course_progress() );

		$summary = $progress ? $progress->get_summary( 'all' ) : '';

		masteriyo_get_template(
			'single-course/layout-1/course-stats.php',
			array(
				'course'                    => $course,
				'comments_count'            => $comments_count,
				'enrolled_users_count'      => masteriyo_count_enrolled_users( $course->get_id() ) + $course->get_fake_enrolled_count(),
				'remaining_available_seats' => $course->get_enrollment_limit() > 0 ? $course->get_enrollment_limit() - masteriyo_count_enrolled_users( $course->get_id() ) : 0,
				'difficulty'                => $difficulty,
				'progress'                  => $progress,
				'summary'                   => $summary,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_archive_course_stats' ) ) {
	/**
	 * Show course stats in archive course page.
	 *
	 * @since 1.11.0
	 */
	function masteriyo_archive_course_stats( $course ) {

		$comments_count = masteriyo_count_course_comments( $course );

		$course_meta = get_post_meta( $course->get_id() );

		if ( isset( $course_meta['_course_coming_soon_enable'] ) ) {
			$enable = $course_meta['_course_coming_soon_enable'] ? $course_meta['_course_coming_soon_enable'] : false;
			$enable = end( $enable );

			if ( $enable ) {
				$ending_date = $course_meta['_course_coming_soon_ending_date'] ? $course_meta['_course_coming_soon_ending_date'] : false;
				$timestamp   = $course_meta['_course_coming_soon_timestamp'] ? $course_meta['_course_coming_soon_timestamp'] : false;
				if ( $timestamp ) {
					$timestamp = end( $timestamp );
				}
				if ( $timestamp > time() ) {
					if ( isset( $course_meta['_course_coming_soon_hide_meta_data'] ) ) {
						$hide_meta_data = $course_meta['_course_coming_soon_hide_meta_data'];
						$hide_meta_data = end( $hide_meta_data );
						if ( $hide_meta_data ) {
							return;
						}
					}
				}
			}
		}

		masteriyo_get_template(
			'course-archive-stats.php',
			array(
				'course'                    => $course,
				'comments_count'            => $comments_count,
				'enrolled_users_count'      => masteriyo_count_enrolled_users( $course->get_id() ) + $course->get_fake_enrolled_count(),
				'remaining_available_seats' => $course->get_enrollment_limit() > 0 ? $course->get_enrollment_limit() - masteriyo_count_enrolled_users( $course->get_id() ) : 0,
				'quiz_count'                => get_course_section_children_count_by_course( $course->get_id(), PostType::QUIZ ),
				'google_meet_count'         => get_course_section_children_count_by_course( $course->get_id(), PostType::GOOGLEMEET ),
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_highlights' ) ) {
	/**
	 * Show course highlights in single course page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_highlights( $course ) {
		masteriyo_get_template(
			'single-course/highlights.php',
			array(
				'course' => $course,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_single_course_main_content' ) ) {
	/**
	 * Show course main content in single course page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_template_single_course_main_content( $course ) {
		masteriyo_get_template(
			'single-course/main-content.php',
			array(
				'course' => $course,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_tab_handles' ) ) {
	/**
	 * Show tab handles in single course page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_tab_handles( $course ) {

		$show_curriculum = masteriyo_should_show_curriculum( $course );

		masteriyo_get_template(
			'single-course/tab-handles.php',
			array(
				'course'          => $course,
				'show_curriculum' => $show_curriculum,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_overview' ) ) {
	/**
	 * Show course overview in single course page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_overview( $course ) {
		masteriyo_get_template(
			'single-course/overview.php',
			array(
				'course' => $course,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_single_course_curriculum' ) ) {
	/**
	 * Show course curriculum in single course page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_curriculum( $course ) {

		$show_curriculum = masteriyo_should_show_curriculum( $course );
		if ( $show_curriculum ) {
			if ( $course->get_show_curriculum() || masteriyo_can_start_course( $course ) ) {
				$sections = masteriyo_get_course_structure( $course->get_id() );

				masteriyo_get_template(
					'single-course/curriculum.php',
					array(
						'course'   => $course,
						'sections' => $sections,
					)
				);
			}
		}
	}
}

if ( ! function_exists( 'masteriyo_single_course_reviews' ) ) {
	/**
	 * Show course reviews in single course page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_reviews( $course ) {
		if ( $course->is_review_allowed() ) {

			$reviews_and_replies = masteriyo_get_course_reviews_and_replies( $course );

			masteriyo_get_template(
				'single-course/reviews.php',
				array(
					'course'         => $course,
					'course_reviews' => $reviews_and_replies['reviews'],
					'replies'        => $reviews_and_replies['replies'],
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_single_course_review_form' ) ) {
	/**
	 * Show course review form in single course page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_review_form( $course ) {
		$preview = $course ? masteriyo_can_user_review_course( $course ) : 0;
		masteriyo_get_template(
			'single-course/course-review-form.php',
			array(
				'course'  => $course,
				'preview' => $preview,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_checkout_order_summary' ) ) {
	/**
	 * Display billing form.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_checkout_order_summary() {
		$cart = masteriyo( 'cart' );
		if ( is_null( $cart ) ) {
			return;
		}

		$courses = array();

		if ( ! $cart->is_empty() ) {
			foreach ( $cart->get_cart_contents() as $cart_content ) {
				if ( ! isset( $cart_content['course_id'] ) ) {
					continue;
				}

				if ( isset( $cart_content['data'] ) ) {
					$course = $cart_content['data'];
				} else {
					$course = masteriyo_get_course( $cart_content['course_id'] );
				}

				if ( is_null( $course ) ) {
					continue;
				}

				/**
				 * Allows modification of course details in the checkout order summary.
				 *
				 * This filter enables customization of course attributes, such as price and title, based on the cart content.
				 *
				 * @since 1.9.0
				 *
				 * @param \Masteriyo\Models\Course $course The course object being modified.
				 * @param array $cart_content Current cart item data, which may include group IDs or other relevant information.
				 * @param \Masteriyo\Cart\Cart $cart The entire cart object, offering context for the modification.
				 */
				$course = apply_filters( 'masteriyo_checkout_modify_course_details', $course, $cart_content, $cart );

				$courses[] = $course;
			}
		}

		$data = array(
			'cart'    => $cart,
			'courses' => $courses,
		);

		masteriyo_get_template( 'checkout/order-summary.php', $data );
	}
}

if ( ! function_exists( 'masteriyo_checkout_payment' ) ) {
	/**
	 * Display checkout form payment.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_checkout_payment() {
		$available_gateways = array();

		if ( masteriyo( 'cart' )->needs_payment() ) {
			$available_gateways = masteriyo( 'payment-gateways' )->get_available_payment_gateways();
		}

		/**
		 * Filters available payment gateways.
		 *
		 * @since 1.15.0
		 *
		 * @param array $available_gateways Array of available payment gateways.
		 */
		$available_gateways = apply_filters( 'masteriyo_checkout_available_gateways', $available_gateways );

		/**
		 * Filters order button text.
		 *
		 * @since 1.0.0
		 *
		 * @param string $text The order button text.
		 */
		// translators: Cart total order
		$order_button_text = apply_filters( 'masteriyo_order_button_text', __( 'Confirm Payment', 'learning-management-system' ) );

		masteriyo_get_template(
			'checkout/payment.php',
			array(
				'checkout'           => masteriyo( 'checkout' ),
				'available_gateways' => $available_gateways,
				'order_button_text'  => $order_button_text,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_display_item_meta' ) ) {
	/**
	 * Display item meta data.
	 *
	 * @since  1.0.0
	 * @param  OrderItem $item Order Item.
	 * @param  array         $args Arguments.
	 * @return string|void
	 */
	function masteriyo_display_item_meta( $item, $args = array() ) {
		$strings = array();
		$html    = '';
		$args    = wp_parse_args(
			$args,
			array(
				'before'       => '<ul class="masteriyo-item-meta"><li>',
				'after'        => '</li></ul>',
				'separator'    => '</li><li>',
				'echo'         => true,
				'autop'        => false,
				'label_before' => '<strong class="masteriyo-item-meta-label">',
				'label_after'  => ':</strong> ',
			)
		);

		foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
			$value     = $args['autop'] ? wp_kses_post( $meta->display_value ) : wp_kses_post( make_clickable( trim( $meta->display_value ) ) );
			$strings[] = $args['label_before'] . wp_kses_post( $meta->display_key ) . $args['label_after'] . $value;
		}

		if ( $strings ) {
			$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
		}

		/**
		 * Filters order item meta html.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html The order item meta html.
		 * @param object $order_item Order item object.
		 * @param array $args Arguments.
		 */
		$html = apply_filters( 'masteriyo_display_item_meta', $html, $item, $args );

		if ( $args['echo'] ) {
			echo wp_kses_post( $html );
		} else {
			return $html;
		}
	}
}

if ( ! function_exists( 'masteriyo_archive_navigation' ) ) {

	/**
	 * Display course archive navigation.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_archive_navigation() {
		masteriyo_get_template( 'course-pagination.php' );
	}
}

if ( ! function_exists( 'masteriyo_get_course_search_form' ) ) {

	/**
	 * Display course search form.
	 *
	 * Will first attempt to locate the course-searchform.php file in either the child or.
	 * the parent, then load it. If it doesn't exist, then the default search form.
	 * will be displayed.
	 *
	 * The default searchform uses html5.
	 *
	 * @since 1.0.0
	 * @param bool $echo (default: true).
	 * @return string
	 */
	function masteriyo_get_course_search_form( $echo = true ) {
		global $course_search_form_index;

		ob_start();

		if ( empty( $course_search_form_index ) ) {
			$course_search_form_index = 0;
		}

		/**
		 * Fires before rendering course search form.
		 *
		 * @since 1.0.0
		 */
		do_action( 'before_masteriyo_get_course_search_form' );

		masteriyo_get_template(
			'course-searchform.php',
			array(
				'index' => $course_search_form_index++,
			)
		);

		/**
		 * Filters course search form html.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html The course search form html.
		 */
		$search_form = apply_filters( 'masteriyo_get_course_search_search_form', ob_get_clean() );

		if ( ! $echo ) {
			return $search_form;
		}

		echo $search_form; // phpcs:ignore
	}
}

if ( ! function_exists( 'masteriyo_course_search_form' ) ) {

	/**
	 * Course Search Form.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_course_search_form() {
		$show_search_box = masteriyo_get_setting( 'course_archive.display.enable_search' );

		if ( true !== $show_search_box ) {
			return;
		}

		printf( '<div class="masteriyo-search-section">' );

		/**
		 * Fires before rendering search section content.
		 *
		 * @since 1.5.39
		 */
		do_action( 'masteriyo_before_search_section_content' );

		?>
		<div class="masteriyo-search">
			<?php masteriyo_get_course_search_form(); ?>
		</div>
		<?php

		/**
		 * Fires after rendering search section content.
		 *
		 * @since 1.5.39
		 */
		do_action( 'masteriyo_after_search_section_content' );

		printf( '</div>' );
	}
}

if ( ! function_exists( 'masteriyo_courses_view_mode' ) ) {

	/**
	 * Courses View Mode.
	 *
	 * @since 1.6.7
	 */
	function masteriyo_courses_view_mode() {
		$layout = masteriyo_get_setting( 'course_archive.display.template.layout' ) ?? 'default';

		$courses_class = 'masteriyo-course';
		if ( 'layout1' === $layout ) {
			$courses_class = 'masteriyo-archive-cards';
		}
		if ( 'layout2' === $layout ) {
			$courses_class = 'masteriyo-course-cards';
		}

		if ( 'default' !== $layout ) {
			return;
		}

		printf( '<div class="masteriyo-courses-view-mode-section">' );

		/**
		 * Fires before rendering courses view mode content.
		 *
		 * @since 1.6.7
		 */
		do_action( 'masteriyo_before_courses_view_mode_content' );

		?>
			<span><?php esc_html_e( 'Select View:', 'learning-management-system' ); ?></span>
			<ul class="masteriyo-courses-view-mode-item-lists" data-courses-class="<?php echo esc_attr( $courses_class ); ?>">
				<li class="masteriyo-courses-view-mode-item <?php echo ( 'list-view' === masteriyo_get_courses_view_mode() ? 'active' : '' ); ?>">
					<button
						class="view-mode list"
						title="<?php echo esc_attr__( 'View on list Mode', 'learning-management-system' ); ?>"
						data-mode="list-view"
					>
						<?php masteriyo_get_svg( 'list', true ); ?>
					</button>
				</li>
				<li class="masteriyo-courses-view-mode-item <?php echo ( 'grid-view' === masteriyo_get_courses_view_mode() ? 'active' : '' ); ?> ">
					<button
						class="view-mode grid"
						title="<?php echo esc_attr__( 'View on Grid Mode', 'learning-management-system' ); ?>"
						data-mode="grid-view"
					>
						<?php masteriyo_get_svg( 'grid', true ); ?>
					</button>
				</li>
			</ul>
		<?php

		/**
		 * Fires after rendering courses view mode content.
		 *
		 * @since 1.6.7
		 */
		do_action( 'masteriyo_after_courses_view_mode_content' );

		printf( '</div>' );
	}
}

if ( ! function_exists( 'masteriyo_template_no_courses_found' ) ) {
	/**
	 * Show notice when there are no courses found.
	 *
	 * @since 1.5.39
	 */
	function masteriyo_template_no_courses_found() {
		printf( '<div class="masteriyo-courses-wrapper">' );

		masteriyo_get_template(
			'notices/info.php',
			array(
				'message' => __( 'No courses found.', 'learning-management-system' ),
			)
		);

		printf( '</div>' );
	}
}

if ( ! function_exists( 'masteriyo_email_order_details' ) ) {
	/**
	 * Show order details.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_email_order_details( $order, $email ) {
		masteriyo_get_template(
			'emails/order-details.php',
			array(
				'order' => $order,
				'email' => $email,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_email_order_meta' ) ) {
	/**
	 * Show order metas.
	 *
	 * @since 1.0.0
	 * @since 1.5.35 Added $email parameter.
	 *
	 * @param \Masteriyo\Models\Order $order Order object.
	 * @param \Masteriyo\Models\Email $email Email object.
	 */
	function masteriyo_email_order_meta( $order, $email ) {
		/**
		 * Filters order email meta fields.
		 *
		 * @since 1.0.0
		 *
		 * @param array $fields Meta fields.
		 * @param false|\Masteriyo\Models\Order $order Order object.
		 */
		$fields = apply_filters( 'masteriyo_email_order_meta_fields', array(), $order );

		/**
		 * Deprecated masteriyo_email_order_meta_keys filter.
		 *
		 * @since 1.0.0
		 *
		 * @param array $fields Meta fields.
		 */
		$_fields = apply_filters( 'masteriyo_email_order_meta_keys', array() );

		if ( $_fields ) {
			foreach ( $_fields as $key => $field ) {
				if ( is_numeric( $key ) ) {
					$key = $field;
				}

				$fields[ $key ] = array(
					'label' => wptexturize( $key ),
					'value' => wptexturize( get_post_meta( $order->get_id(), $field, true ) ),
				);
			}
		}

		if ( $fields ) {
			foreach ( $fields as $field ) {
				if ( isset( $field['label'] ) && isset( $field['value'] ) && $field['value'] ) {
					echo wp_kses_post( '<p><strong>' . $field['label'] . ':</strong> ' . $field['value'] . '</p>' );
				}
			}
		}
	}
}

if ( ! function_exists( 'masteriyo_email_customer_addresses' ) ) {
	/**
	 * Show order metas.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_email_customer_addresses( $order, $email = null ) {
		masteriyo_get_template(
			'emails/customer-addresses.php',
			array(
				'order' => $order,
				'email' => $email,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_get_email_order_items' ) ) {
	/**
	 * Get HTML for the order items to be shown in emails.
	 *
	 * @param Masteriyo\Models\Order\Order $order Order object.
	 * @param array $args Arguments.
	 *
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function masteriyo_get_email_order_items( $order, $args = array() ) {
		ob_start();

		$defaults = array(
			'show_sku'         => false,
			'show_image'       => false,
			'image_size'       => array( 32, 32 ),
			'show_course_name' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		masteriyo_get_template(
			'emails/order-items.php',
			/**
			 * Filters order item args for email.
			 *
			 * @since 1.0.0
			 *
			 * @param array $args Order item args.
			 */
			apply_filters(
				'masteriyo_email_order_items_args',
				array(
					'order'              => $order,
					'items'              => $order->get_items(),
					'show_sku'           => $args['show_sku'],
					'show_purchase_note' => $order->is_paid(),
					'show_image'         => $args['show_image'],
					'image_size'         => $args['image_size'],
					'show_course_name'   => $args['show_course_name'],
				)
			)
		);

		/**
		 * Filters order items table html for email.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html
		 * @param Masteriyo\Models\Order\Order $order Order object.
		 */
		return apply_filters( 'masteriyo_email_order_items_table', ob_get_clean(), $order );
	}
}

if ( ! function_exists( 'masteriyo_the_email_order_items' ) ) {
	/**
	 * Get HTML for the order items to be shown in emails.
	 *
	 * @param Order $order Order object.
	 * @param array $args Arguments.
	 *
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	function masteriyo_the_email_order_items( $order, $args = array() ) {
		$html = masteriyo_get_email_order_items( $order, $args );
		echo wp_kses_post( $html );
	}
}

if ( ! function_exists( 'masteriyo_display_item_meta' ) ) {
	/**
	 * Display item meta data.
	 *
	 * @since  1.0.0
	 * @param  OrderItem $item Order Item.
	 * @param  array         $args Arguments.
	 * @return string|void
	 */
	function masteriyo_display_item_meta( $item, $args = array() ) {
		$strings = array();
		$html    = '';
		$args    = wp_parse_args(
			$args,
			array(
				'before'       => '<ul class="masteriyo-item-meta"><li>',
				'after'        => '</li></ul>',
				'separator'    => '</li><li>',
				'echo'         => true,
				'autop'        => false,
				'label_before' => '<strong class="masteriyo-item-meta-label">',
				'label_after'  => ':</strong> ',
			)
		);

		foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
			$value     = $args['autop'] ? wp_kses_post( $meta->display_value ) : wp_kses_post( make_clickable( trim( $meta->display_value ) ) );
			$strings[] = $args['label_before'] . wp_kses_post( $meta->display_key ) . $args['label_after'] . $value;
		}

		if ( $strings ) {
			$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
		}

		/**
		 * Filters html to display order item meta.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html The html to display order item meta.
		 * @param object $order_item Order item object.
		 * @param array $args Arguments.
		 */
		$html = apply_filters( 'masteriyo_display_item_meta', $html, $item, $args );

		if ( $args['echo'] ) {
			echo wp_kses_post( $html );
		} else {
			return $html;
		}
	}
}

if ( ! function_exists( 'masteriyo_single_course_modals' ) ) {
	/**
	 * Include modals for the single course page.
	 *
	 * @since 1.0.0
	 */
	function masteriyo_single_course_modals() {
		masteriyo_get_template( 'single-course/modals.php' );
	}
}

if ( ! function_exists( 'masteriyo_template_course_review' ) ) {
	/**
	 * Print course review item.
	 *
	 * @since 1.0.5
	 *
	 * @param \Masteriyo\Models\CourseReview $course_review Course review object.
	 * @param \Masteriyo\Models\CourseReview[] $replies Replies to the course review.
	 * @param integer $course_id Course ID.
	 */
	function masteriyo_template_course_review( $course_review, $replies, $course_id ) {

		if ( ! $course_review ) {
			return;
		}

		if ( 'trash' === $course_review->get_status() ) {
			masteriyo_get_template( 'notices/review-deleted.php' );
			return;
		}

		$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';

		$template = 'single-course/course-review.php';

		if ( 'layout1' === $layout ) {
			$template = 'single-course/layout-1/course-review.php';
		}

		masteriyo_get_template(
			$template,
			array(
				'pp_placeholder' => masteriyo_get_course_review_author_pp_placeholder(),
				'course_review'  => $course_review,
				'replies'        => $replies,
				'course_id'      => $course_id,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_course_review_reply' ) ) {
	/**
	 * Print course review reply.
	 *
	 * @since 1.0.5
	 */
	function masteriyo_template_course_review_reply( $args = array() ) {
		$args['pp_placeholder'] = masteriyo_get_course_review_author_pp_placeholder();

		$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';

		$template = 'single-course/course-review-reply.php';

		if ( 'layout1' === $layout ) {
			return;
		}

		masteriyo_get_template( $template, $args );
	}
}

if ( ! function_exists( 'masteriyo_template_course_reviews_stats' ) ) {
	/**
	 * Print course reviews stats.
	 *
	 * @since 1.0.5
	 */
	function masteriyo_template_course_reviews_stats( $course, $course_reviews, $replies ) {
		$is_review_enabled = masteriyo_get_setting( 'single_course.display.enable_review' );
		$review_length     = count( $course_reviews );
		if ( $is_review_enabled && $review_length ) {
			masteriyo_get_template( 'single-course/reviews-stats.php', compact( 'course', 'course_reviews', 'replies' ) );
		}
	}
}

if ( ! function_exists( 'masteriyo_template_course_reviews_filters' ) ) {
	/**
	 * Undocumented function
	 *
	 * @param [type] $course
	 * @param [type] $course_reviews
	 * @param [type] $replies
	 * @return void
	 */
	function masteriyo_template_course_reviews_filters( $course, $course_reviews, $replies ) {
		$is_review_enabled = masteriyo_get_setting( 'single_course.display.enable_review' );
		$review_length     = count( $course_reviews );
		if ( $is_review_enabled && $review_length ) {
			masteriyo_get_template( 'single-course/reviews-filters.php', compact( 'course', 'course_reviews', 'replies' ) );
		}
	}
}

if ( ! function_exists( 'masteriyo_template_course_reviews_list' ) ) {
	/**
	 * Print course reviews list.
	 *
	 * @since 1.0.5
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param \Masteriyo\Models\CourseReview[] $course_reviews Course reviews.
	 * @param \Masteriyo\Models\CourseReview[] $replies Replies to the course review.
	 */
	function masteriyo_template_course_reviews_list( $course, $course_reviews, $replies ) {

		$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';

		$template = 'single-course/reviews-list.php';

		if ( 'layout1' === $layout ) {
			return;
		}

		masteriyo_get_template(
			$template,
			array(
				'course'         => $course,
				'course_reviews' => $course_reviews,
				'replies'        => $replies,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_shortcode_course_categories' ) ) {
	/**
	 * Print course categories.
	 *
	 * @since 1.2.0
	 */
	function masteriyo_template_shortcode_course_categories( $attrs ) {
		masteriyo_get_template( 'shortcodes/course-categories/list.php', $attrs );
	}
}

if ( ! function_exists( 'masteriyo_template_shortcode_course_category' ) ) {
	/**
	 * Print course category.
	 *
	 * @since 1.2.0
	 */
	function masteriyo_template_shortcode_course_category( $attrs ) {
		masteriyo_get_template( 'shortcodes/course-categories/list-item.php', $attrs );
	}
}

if ( ! function_exists( 'masteriyo_course_category_description' ) ) {
	/**
	 * Display course category description in course category archive page.
	 *
	 * @since 1.4.3
	 */
	function masteriyo_course_category_description() {
		$term = get_queried_object();

		if ( ! is_object( $term ) || ! isset( $term->description ) ) {
			return;
		}

		$html  = '<p class="masteriyo-courses-header__description">';
		$html .= wp_kses_post( $term->description );
		$html .= '</p>';

		echo wp_kses_post( $html );
	}
}

if ( ! function_exists( 'masteriyo_template_single_course_review_replies' ) ) {
	/**
	 * Display review replies in single course page's reviews list section.
	 *
	 * @since 1.5.9
	 *
	 * @param \Masteriyo\Models\CourseReview $course_review Course review object.
	 * @param \Masteriyo\Models\CourseReview[] $replies Replies to the course review.
	 */
	function masteriyo_template_single_course_review_replies( $course_review, $replies ) {
		if ( ! empty( $replies ) ) {

			$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';

			$template = 'single-course/review-replies.php';

			if ( 'layout1' === $layout ) {
				return;
			}

			masteriyo_get_template(
				$template,
				array(
					'course_review' => $course_review,
					'replies'       => $replies,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_single_course_see_more_reviews_button' ) ) {
	/**
	 * Display see more reviews button in single course page's reviews list section.
	 *
	 * @since 1.5.9
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	function masteriyo_template_single_course_see_more_reviews_button( $course ) {
		if ( masteriyo_get_course_reviews_infinite_loading_pages_count( $course ) > 1 ) {
			masteriyo_get_template(
				'single-course/see-more-reviews-button.php',
				array(
					'course' => $course,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_single_course_related_courses' ) ) {

	/**
	 * Display related courses in single course page.
	 *
	 * @since 1.5.9
	 */
	function masteriyo_template_single_course_related_courses() {
		if ( masteriyo_get_setting( 'single_course.related_courses.enable' ) ) {

			$layout = masteriyo_get_setting( 'course_archive.display.template.layout' ) ?? 'default';

			$template = 'related-posts';

			if ( 'layout1' === $layout ) {
				$template = 'related-posts-1';
			} elseif ( 'layout2' === $layout ) {
				$template = 'related-posts-2';
			}

			masteriyo_get_template_part( 'content', $template );
		}
	}
}

if ( ! function_exists( 'masteriyo_template_single_course_curriculum_summary' ) ) {

	/**
	 * Display single course curriculum summary.
	 *
	 * @since 1.5.15
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 */
	function masteriyo_template_single_course_curriculum_summary( $course ) {
		$posts = get_posts(
			array(
				'post_type'      => CourseChildrenPostType::all(),
				'post_status'    => PostStatus::PUBLISH,
				'meta_key'       => '_course_id',
				'meta_value'     => $course->get_id(),
				'posts_per_page' => -1,
			)
		);

		$section_count = array_reduce(
			$posts,
			function ( $count, $post ) {
				if ( PostType::SECTION === $post->post_type ) {
					++$count;
				}

				return $count;
			},
			0
		);

		$lesson_count = array_reduce(
			$posts,
			function ( $count, $post ) {
				if ( PostType::LESSON === $post->post_type ) {
					++$count;
				}

				return $count;
			},
			0
		);

		$quiz_count = array_reduce(
			$posts,
			function ( $count, $post ) {
				if ( PostType::QUIZ === $post->post_type ) {
					++$count;
				}

				return $count;
			},
			0
		);

		$summaries = array();

		// Add only if section count is greater than 0.
		if ( $section_count > 0 ) {
			$summaries[] = array(
				'wrapper_start' => '<li class="masteriyo-list-none">',
				'wrapper_end'   => '</li>',
				'content'       => sprintf(
					/* translators: %d: Course sections count */
					esc_html( _nx( '%s Section', '%s Sections', $section_count, 'Sections Count', 'learning-management-system' ) ),
					esc_html( number_format_i18n( $section_count ) )
				),
			);
		}

		// Add only if lesson count is greater than 0.
		if ( $lesson_count > 0 ) {
			$summaries[] = array(
				'wrapper_start' => '<li>',
				'wrapper_end'   => '</li>',
				'content'       => sprintf(
					/* translators: %d: Course lessons count */
					esc_html( _nx( '%s Lesson', '%s Lessons', $lesson_count, 'Lessons Count', 'learning-management-system' ) ),
					esc_html( number_format_i18n( $lesson_count ) )
				),
			);
		}

		// Add only if quiz count is greater than 0.
		if ( $quiz_count > 0 ) {
			$summaries[] = array(
				'wrapper_start' => '<li>',
				'wrapper_end'   => '</li>',
				'content'       => sprintf(
					/* translators: %d: Course quiz count */
					esc_html( _nx( '%s Quiz', '%s Quizzes', $quiz_count, 'Quizzes Count', 'learning-management-system' ) ),
					esc_html( number_format_i18n( $quiz_count ) )
				),
			);
		}

		// Always include the duration summary.
		$summaries[] = array(
			'wrapper_start' => '<li>',
			'wrapper_end'   => '</li>',
			'content'       => sprintf(
				/* translators: %s: Lecture hours */
				__( '%s Duration', 'learning-management-system' ),
				masteriyo_minutes_to_time_length_string( $course->get_duration() )
			),
		);

		/**
		 * Filters masteriyo single course summaries.
		 *
		 * @since 1.5.15
		 *
		 * @param array $summaries Summaries.
		 * @param \Masteriyo\Models\Course $course Course object.
		 * @param WP_Post[] $posts Array of sections, quizzes and sections.
		 */
		$summaries = apply_filters(
			'masteriyo_single_course_curriculum_summaries',
			$summaries,
			$course,
			$posts
		);

		$html = '';
		foreach ( $summaries as $summary ) {
			$html .= $summary['wrapper_start'] . $summary['content'] . $summary['wrapper_end'];
		}

		echo wp_kses_post( $html );
	}
}

if ( ! function_exists( 'masteriyo_template_single_course_curriculum_section_summary' ) ) {
	/**
	 * Display section summary (number of lessons and quizzes) in single course curriculum page.
	 *
	 * @since 1.5.15
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param \Masteriyo\Models\Section $section Section object.
	 */
	function masteriyo_template_single_course_curriculum_section_summary( $course, $section ) {
		$posts = get_posts(
			array(
				'post_type'      => SectionChildrenPostType::all(),
				'post_status'    => PostStatus::PUBLISH,
				'post_parent'    => $section->get_id(),
				'posts_per_page' => -1,
			)
		);

		$lesson_count = array_reduce(
			$posts,
			function ( $count, $post ) {
				if ( SectionChildrenPostType::LESSON === $post->post_type ) {
					++$count;
				}

				return $count;
			},
			0
		);

		$quiz_count = array_reduce(
			$posts,
			function ( $count, $post ) {
				if ( SectionChildrenPostType::QUIZ === $post->post_type ) {
					++$count;
				}

				return $count;
			},
			0
		);

		$summaries = array();

		// Add only if lesson count is greater than 0.
		if ( $lesson_count > 0 ) {
			$summaries[] = array(
				'wrapper_start' => '<span class="masteriyo-clessons">',
				'wrapper_end'   => '</span>',
				'content'       => sprintf(
					/* translators: %d: Section lessons count */
					esc_html( _nx( '%s Lesson', '%s Lessons', $lesson_count, 'Lessons Count', 'learning-management-system' ) ),
					esc_html( number_format_i18n( $lesson_count ) )
				),
			);
		}

		// Add only if quiz count is greater than 0.
		if ( $quiz_count > 0 ) {
			$summaries[] = array(
				'wrapper_start' => '<span class="masteriyo-cquizzes">',
				'wrapper_end'   => '</span>',
				'content'       => sprintf(
					/* translators: %d: Section quizzes count */
					esc_html( _nx( '%s Quiz', '%s Quizzes', $quiz_count, 'Quizzes Count', 'learning-management-system' ) ),
					esc_html( number_format_i18n( $quiz_count ) )
				),
			);
		}

		/**
		 * Filters single course curriculum section summaries.
		 *
		 * @since 1.5.15
		 *
		 * @param array $summaries Section summaries.
		 * @param \Masteriyo\Models\Course $course Course object.
		 * @param \Masteriyo\Models\Section $section Section object.
		 * @param \WP_Post[] $posts Children of section (lessons and quizzes).
		 */
		$summaries = apply_filters(
			'masteriyo_single_course_curriculum_section_summaries',
			$summaries,
			$course,
			$section,
			$posts
		);

		$html = '';
		foreach ( $summaries as $summary ) {
			$html .= $summary['wrapper_start'] . $summary['content'] . $summary['wrapper_end'];
		}

		$summary_wrapper = array(
			'wrapper_start' => '<div class="masteriyo-ltc masteriyo-flex-ycenter">',
			'wrapper_end'   => '</div>',
		);

		$html = $summary_wrapper['wrapper_start'] . $html . $summary_wrapper['wrapper_end'];

		$summary_plus_minus[] = array(
			'wrapper_start' => '<span class="masteriyo-cplus masteriyo-icon-svg">',
			'wrapper_end'   => '</span>',
			'content'       => masteriyo_get_svg( 'plus' ),
		);

		$summary_plus_minus[] = array(
			'wrapper_start' => '<span class="masteriyo-cminus masteriyo-icon-svg">',
			'wrapper_end'   => '</span>',
			'content'       => masteriyo_get_svg( 'minus' ),
		);

		foreach ( $summary_plus_minus as $summary ) {
			$html .= $summary['wrapper_start'] . $summary['content'] . $summary['wrapper_end'];
		}

		echo $html; // phpcs:ignore
	}
}

if ( ! function_exists( 'masteriyo_template_single_course_curriculum_section_content' ) ) {
	/**
	 * Display single course curriculum section content.
	 *
	 * @since 1.5.15
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param \Masteriyo\Models\Section $section Section object.
	 */
	function masteriyo_template_single_course_curriculum_section_content( $course, $section ) {
		$posts = get_posts(
			array(
				'post_type'      => SectionChildrenPostType::all(),
				'post_status'    => PostStatus::PUBLISH,
				'post_parent'    => $section->get_id(),
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

		foreach ( $objects as $object ) {
			$html  = '<li>';
			$html .= '<div class="masteriyo-lesson-list__content">';
			$html .= '<span class="masteriyo-lesson-list__content-item">';
			$html .= '<span class="masteriyo-lesson-icon">';
			$html .= $object->get_icon();
			$html .= '</span>';
			$html .= $object->get_name();
			$html .= '</span>';
			$html .= '</div>';
			$html .= '</li>';

			/**
			 * Filters single course curriculum section content html.
			 *
			 * @since 1.5.15
			 * @param string $html HTML content.
			 * @param \Masteriyo\Database\Model $object Lesson or Quiz object.
			 */
			$html = apply_filters( 'masteriyo_single_course_curriculum_section_content_html', $html, $object );

			echo $html; // phpcs:ignore
		}
	}
}

/**
 * Add current theme to the body tag.
 *
 * @since 1.5.27
 *
 * @param string[] $classes An array of body class names.
 * @param string[] $class   An array of additional class names added to the body.
 * @return string[]
 */

function masteriyo_add_current_theme_slug_to_body_tag( $classes, $class ) {
	if ( ! function_exists( 'wp_get_theme' ) ) {
		return $classes;
	}

	$current_theme = wp_get_theme();
	$classes[]     = 'theme-' . $current_theme->get_template();

	return $classes;
}


if ( ! function_exists( 'masteriyo_template_courses_sorting_input' ) ) {
	/**
	 * Display courses sorting input.
	 *
	 * @since 1.16.0
	 */
	function masteriyo_template_courses_sorting_input() {
		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_sorting' ) ) ) {
			return;
		}

		$options = array();

		if ( masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_date_sorting' ) ) ) {
			$options[] = array(
				'value' => 'date',
				'label' => __( 'Latest First', 'learning-management-system' ),
				'order' => 'DESC',
			);
			$options[] = array(
				'value' => 'date',
				'label' => __( 'Oldest First', 'learning-management-system' ),
				'order' => 'ASC',
			);
		}

		if ( masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_price_sorting' ) ) ) {
			$options[] = array(
				'value' => 'price',
				'label' => __( 'Price: Low to High', 'learning-management-system' ),
				'order' => 'ASC',
			);
			$options[] = array(
				'value' => 'price',
				'label' => __( 'Price: High to Low', 'learning-management-system' ),
				'order' => 'DESC',
			);
		}

		if ( masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_rating_sorting' ) ) ) {
			$options[] = array(
				'value' => 'rating',
				'label' => __( 'Highest rated first', 'learning-management-system' ),
				'order' => 'DESC',
			);
			$options[] = array(
				'value' => 'rating',
				'label' => __( 'Lowest rated first', 'learning-management-system' ),
				'order' => 'ASC',
			);
		}

		if ( masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_course_title_sorting' ) ) ) {
			$options[] = array(
				'value' => 'title',
				'label' => __( 'Sort by Course Title (A-Z)', 'learning-management-system' ),
				'order' => 'ASC',
			);
			$options[] = array(
				'value' => 'title',
				'label' => __( 'Sort by Course Title (Z-A)', 'learning-management-system' ),
				'order' => 'DESC',
			);
		}

		/**
		 * Filters courses sorting options.
		 *
		 * @since 2.5.18
		 *
		 * @param array $options
		 */
		$options = apply_filters( 'masteriyo_courses_sorting_options', $options );

		$sort_by       = masteriyo_array_get( $_GET, 'orderby', 'date' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sort_by       = empty( $sort_by ) ? 'date' : $sort_by;
		$sorting_order = masteriyo_array_get( $_GET, 'order', 'DESC' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sorting_order = strtoupper( empty( $sorting_order ) ? 'DESC' : $sorting_order );

		masteriyo_get_template(
			'course-sorting.php',
			array(
				'options'       => $options,
				'sort_by'       => $sort_by,
				'sorting_order' => $sorting_order,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_render_course_filter_and_sorting_nonce_field' ) ) {
	/**
	 * Renders course filter and sorting nonce field.
	 *
	 * @since 1.16.0
	 */
	function masteriyo_render_course_filter_and_sorting_nonce_field() {
		wp_nonce_field( 'masteriyo_course_filter_and_sorting_nonce', 'masteriyo_course_filter_and_sorting_nonce' );
	}
}

if ( ! function_exists( 'masteriyo_template_course_filters' ) ) {
	/**
	 * Display course filters section.
	 *
	 * @since 1.16.0
	 */
	function masteriyo_template_course_filters() {
		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_filters' ) ) ) {
			return;
		}

		$exclude_query_string_render = array( 'categories', 'difficulties', 'price-type', 'price-from', 'price-to' );

		/**
		 * Filters list of query variables to exclude while rendering form fields to include URL query params in course filter form.
		 *
		 * @since 1.16.0
		 *
		 * @param string[] $exclude
		 */
		$exclude_query_string_render = apply_filters( 'masteriyo_exclude_query_string_render_for_course_filter', $exclude_query_string_render );

		masteriyo_get_template(
			'course-filters.php',
			array(
				'exclude_query_string_render' => $exclude_query_string_render,
				'form_action_url'             => masteriyo_get_page_permalink( 'courses' ),
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_course_categories_filter' ) ) {
	/**
	 * Display categories filter.
	 *
	 * @since 1.16.0
	 */
	function masteriyo_template_course_categories_filter() {
		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_category_filter' ) ) ) {
			return;
		}

		$categories_query = new CourseCategoryQuery(
			array(
				'order'   => 'ASC',
				'orderby' => 'name',
				'number'  => '',
			)
		);
		$categories       = $categories_query->get_categories();
		$selected         = array_map( 'absint', (array) masteriyo_array_get( $_GET, 'categories', array() ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		/**
		 * Filters limit for showing categories in course category filter.
		 *
		 * @since 1.16.0
		 *
		 * @param integer $limit
		 */
		$initially_visible_categories_limit = apply_filters( 'masteriyo_initially_visible_course_categories_filter_limit', 7 );

		masteriyo_get_template(
			'course-filters/categories-filter.php',
			array(
				'categories'                         => $categories,
				'selected_categories'                => $selected,
				'initially_visible_categories_limit' => absint( $initially_visible_categories_limit ),
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_course_difficulties_filter' ) ) {
	/**
	 * Display difficulties filter.
	 *
	 * @since 1.16.0
	 */
	function masteriyo_template_course_difficulties_filter() {
		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_difficulty_level_filter' ) ) ) {
			return;
		}

		$term_query   = new \WP_Term_Query();
		$difficulties = $term_query->query(
			array(
				'taxonomy'   => Taxonomy::COURSE_DIFFICULTY,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => false,
				'number'     => '',
			)
		);
		$difficulties = array_filter( array_map( 'masteriyo_get_course_difficulty', $difficulties ) );
		$selected     = array_map( 'absint', (array) masteriyo_array_get( $_GET, 'difficulties', array() ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		masteriyo_get_template(
			'course-filters/difficulties-filter.php',
			array(
				'difficulties'          => $difficulties,
				'selected_difficulties' => $selected,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_course_price_type_filter' ) ) {
	/**
	 * Display price type filter.
	 *
	 * @since 1.16.0
	 */
	function masteriyo_template_course_price_type_filter() {
		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_price_type_filter' ) ) ) {
			return;
		}

		$selected = masteriyo_array_get( $_GET, 'price-type', null ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		masteriyo_get_template(
			'course-filters/price-type-filter.php',
			array(
				'selected' => $selected,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_course_price_filter' ) ) {
	/**
	 * Display price filter.
	 *
	 * @since 1.16.0
	 */
	function masteriyo_template_course_price_filter() {
		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_price_filter' ) ) ) {
			return;
		}

		$price_from = masteriyo_array_get( $_GET, 'price-from', '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$price_to   = masteriyo_array_get( $_GET, 'price-to', '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		masteriyo_get_template(
			'course-filters/price-filter.php',
			array(
				'price_from' => $price_from,
				'price_to'   => $price_to,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_course_rating_filter' ) ) {
	/**
	 * Display rating filter.
	 *
	 * @since 1.16.0
	 */
	function masteriyo_template_course_rating_filter() {
		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_rating_filter' ) ) ) {
			return;
		}

		$filter_urls = array_reduce(
			array( 0, 1, 2, 3, 4, 5 ),
			function ( $arr, $rating ) {
				if ( 0 === $rating ) {
					$arr[ $rating ] = masteriyo_get_current_request_url( array(), array( 'rating' ) );
				} else {
					$arr[ $rating ] = masteriyo_get_current_request_url( array( 'rating' => $rating ) );
				}
				return $arr;
			},
			array()
		);

		masteriyo_get_template(
			'course-filters/rating-filter.php',
			array(
				'filter_urls' => $filter_urls,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_clear_course_filter' ) ) {
	/**
	 * Display button to clear course filter.
	 *
	 * @since 1.16.0
	 */
	function masteriyo_template_clear_course_filter() {
		masteriyo_get_template(
			'course-filters/clear-filter.php',
			array(
				'clear_url' => masteriyo_get_page_permalink( 'courses' ),
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_course_filter_sidebar_toggle' ) ) {
	/**
	 * Display course filter trigger button.
	 *
	 * @since 1.16.0
	 */
	function masteriyo_template_course_filter_sidebar_toggle() {
		if ( ! masteriyo_string_to_bool( masteriyo_get_setting( 'course_archive.filters_and_sorting.enable_filters' ) ) ) {
			return;
		}

		masteriyo_get_template( 'course-filters/course-filter-sidebar-toggle.php' );
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_first_and_last_name' ) ) {

	/**
	 * Render first and last name in the checkout form.
	 *
	 * @since 1.6.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_first_and_last_name( $user, $checkout ) {
		masteriyo_get_template(
			'checkout/fields/first-and-last-name.php',
			array(
				'user'     => $user,
				'checkout' => $checkout,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_email' ) ) {

	/**
	 * Render email field in the checkout form.
	 *
	 * @since 1.6.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_email( $user, $checkout ) {
		masteriyo_get_template(
			'checkout/fields/email.php',
			array(
				'user'     => $user,
				'checkout' => $checkout,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_company' ) ) {

	/**
	 * Render company name in the checkout form.
	 *
	 * @since 1.6.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_company( $user, $checkout ) {
		if ( masteriyo_get_setting( 'payments.checkout_fields.company' ) ) {
			masteriyo_get_template(
				'checkout/fields/company.php',
				array(
					'user'     => $user,
					'checkout' => $checkout,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_country' ) ) {

	/**
	 * Render country in the checkout form.
	 *
	 * @since 1.6.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_country( $user, $checkout ) {
		if ( masteriyo_get_setting( 'payments.checkout_fields.country' ) ) {
			masteriyo_get_template(
				'checkout/fields/country.php',
				array(
					'user'     => $user,
					'checkout' => $checkout,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_address_1' ) ) {

	/**
	 * Render address 1 in the checkout form.
	 *
	 * @since 1.6.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_address_1( $user, $checkout ) {
		if ( masteriyo_get_setting( 'payments.checkout_fields.address_1' ) ) {
			masteriyo_get_template(
				'checkout/fields/address-1.php',
				array(
					'user'     => $user,
					'checkout' => $checkout,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_address_2' ) ) {

	/**
	 * Render address 2 in the checkout form.
	 *
	 * @since 1.6.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_address_2( $user, $checkout ) {
		if ( masteriyo_get_setting( 'payments.checkout_fields.address_2' ) ) {
			masteriyo_get_template(
				'checkout/fields/address-2.php',
				array(
					'user'     => $user,
					'checkout' => $checkout,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_state' ) ) {

	/**
	 * Render state in the checkout form.
	 *
	 * @since 1.6.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_state( $user, $checkout ) {
		if (
			masteriyo_get_setting( 'payments.checkout_fields.country' ) &&
			masteriyo_get_setting( 'payments.checkout_fields.state' )
		) {
			masteriyo_get_template(
				'checkout/fields/state.php',
				array(
					'user'     => $user,
					'checkout' => $checkout,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_city' ) ) {

	/**
	 * Render city in the checkout form.
	 *
	 * @since 1.6.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_city( $user, $checkout ) {
		if (
			masteriyo_get_setting( 'payments.checkout_fields.country' ) &&
			masteriyo_get_setting( 'payments.checkout_fields.city' )
		) {
			masteriyo_get_template(
				'checkout/fields/city.php',
				array(
					'user'     => $user,
					'checkout' => $checkout,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_postcode' ) ) {

	/**
	 * Render postcode in the checkout form.
	 *
	 * @since 1.6.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_postcode( $user, $checkout ) {
		if (
			masteriyo_get_setting( 'payments.checkout_fields.country' ) &&
			masteriyo_get_setting( 'payments.checkout_fields.postcode' )
		) {
			masteriyo_get_template(
				'checkout/fields/postcode.php',
				array(
					'user'     => $user,
					'checkout' => $checkout,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_phone_number' ) ) {

	/**
	 * Render phone number in the checkout form.
	 *
	 * @since 1.6.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_phone_number( $user, $checkout ) {
		if ( masteriyo_get_setting( 'payments.checkout_fields.phone' ) ) {
			masteriyo_get_template(
				'checkout/fields/phone-number.php',
				array(
					'user'     => $user,
					'checkout' => $checkout,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_customer_note' ) ) {

	/**
	 * Render customer note in the checkout form.
	 *
	 * @since 1.6.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_customer_note( $user, $checkout ) {
		if ( masteriyo_get_setting( 'payments.checkout_fields.customer_note' ) ) {
			masteriyo_get_template(
				'checkout/fields/customer-note.php',
				array(
					'user'     => $user,
					'checkout' => $checkout,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_attachment_upload' ) ) {

	/**
	 * Render attachment upload in the checkout form.
	 *
	 * @since 1.12.2
	 */
	function masteriyo_template_checkout_attachment_upload() {
		if ( masteriyo_get_setting( 'payments.checkout_fields.attachment_upload' ) ) {
			masteriyo_get_template(
				'checkout/fields/attachment-upload.php'
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_create_user_checkbox' ) ) {

	/**
	 * Render create user checkbox in the checkout form.
	 *
	 * @since 1.6.12
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_create_user_checkbox( $user, $checkout ) {
		if ( masteriyo_is_guest_checkout_enabled() ) {
			masteriyo_get_template(
				'checkout/fields/create-user.php',
				array(
					'user'     => $user,
					'checkout' => $checkout,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_template_checkout_gdpr' ) ) {

	/**
	 * Render GDPR message in the checkout form.
	 *
	 * @since 1.6.5
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	function masteriyo_template_checkout_gdpr( $user, $checkout ) {
		if ( masteriyo_show_gdpr_msg() ) {
			masteriyo_get_template(
				'checkout/fields/gdpr.php',
				array(
					'user'         => $user,
					'checkout'     => $checkout,
					'gdpr_message' => masteriyo_show_gdpr_msg(),
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_custom_header' ) ) {

	/**
	 * Custom header to support block theme and traditional theme.
	 *
	 * @since 1.6.5
	 *
	 * @return void
	 */
	function masteriyo_custom_header( $header_name ) {
		global $wp_version;
		if (
			version_compare( $wp_version, '5.9', '>=' ) &&
			function_exists( 'wp_is_block_theme' ) &&
			wp_is_block_theme()
		) {
			?>
			<!doctype html>
				<html <?php language_attributes(); ?>>
				<head>
					<meta charset="<?php bloginfo( 'charset' ); ?>">
					<?php wp_head(); ?>
				</head>

				<body <?php body_class(); ?>>
				<?php wp_body_open(); ?>
					<div class="wp-site-blocks">
						<header class="wp-block-template-part site-header">
							<?php block_header_area(); ?>
						</header>
			<?php
		} else {
			get_header( esc_attr( $header_name ) );
		}
	}
}

if ( ! function_exists( 'masteriyo_custom_footer' ) ) {

	/**
	 * Custom footer to support block theme and traditional theme.
	 *
	 * @since 1.6.5
	 *
	 * @return void
	 */
	function masteriyo_custom_footer( $footer_name ) {
		global $wp_version;
		if (
			version_compare( $wp_version, '5.9', '>=' ) &&
			function_exists( 'wp_is_block_theme' ) &&
			wp_is_block_theme()
		) {
			?>
			<footer class="wp-block-template-part site-footer">
			<?php block_footer_area(); ?>
			</footer>
			</div>
			<?php masteriyo_print_block_support_styles(); ?>
			<?php wp_footer(); ?>
			</body>
			</html>
			<?php
		} else {
			get_footer( esc_attr( $footer_name ) );
		}
	}
}

if ( ! function_exists( 'masteriyo_template_shortcode_instructors_list' ) ) {
	/**
	 * Print instructors.
	 *
	 * @since 1.6.16
	 */
	function masteriyo_template_shortcode_instructors_list( $attrs ) {
		masteriyo_get_template( 'shortcodes/instructors-list/list.php', $attrs );
	}
}

if ( ! function_exists( 'masteriyo_template_shortcode_instructors_list_item' ) ) {
	/**
	 * Print course category.
	 *
	 * @since 1.6.16
	 */
	function masteriyo_template_shortcode_instructors_list_item( $attrs ) {
		masteriyo_get_template( 'shortcodes/instructors-list/list-item.php', $attrs );
	}
}

if ( ! function_exists( 'masteriyo_course_expiration_info' ) ) {
	/**
	 * Show the course expiration info.
	 *
	 * @since 1.7.0
	 */
	function masteriyo_course_expiration_info( $course ) {
		masteriyo_get_template(
			'course-expiration-info.php',
			array(
				'course' => $course,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_session_info_update' ) ) {
	/**
	 * Updates the session information based session login time.
	 *
	 * @since 1.9.3
	 */
	function masteriyo_session_info_update() {
		$token        = wp_get_session_token();
		$sessions     = WP_Session_Tokens::get_instance( masteriyo_get_current_user_id() );
		$session_info = $sessions->get( $token );

		if ( ! get_user_meta( masteriyo_get_current_user_id(), masteriyo_get_current_user_id() . '_session_' . $session_info['login'], true ) ) {
			update_user_meta( masteriyo_get_current_user_id(), masteriyo_get_current_user_id() . '_session_' . $session_info['login'], masteriyo_session_information() );
		}
	}
}

/**
 * Single Course layout 1 template.
 *
 * @since 1.10.0
 */

if ( ! function_exists( 'masteriyo_layout_1_single_course_header' ) ) {
	/**
	 * Show course header in single course page.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	function masteriyo_layout_1_single_course_header( $course ) {
		masteriyo_get_template(
			'single-course/layout-1/header.php',
			array(
				'course'                    => $course,
				'remaining_available_seats' => $course->get_enrollment_limit() > 0 ? $course->get_enrollment_limit() - masteriyo_count_enrolled_users( $course->get_id() ) : 0,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_layout_1_single_course_main_content' ) ) {
	/**
	 * Show course main content in single course page.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	function masteriyo_layout_1_single_course_main_content( $course ) {

		$show_curriculum = masteriyo_should_show_curriculum( $course );

		masteriyo_get_template(
			'single-course/layout-1/main-content.php',
			array(
				'course'          => $course,
				'show_curriculum' => $show_curriculum,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_layout_1_single_course_review_count' ) ) {
	/**
	 * Show course review count in single course page layout 1.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	function masteriyo_layout_1_single_course_review_count( $course, $reviews_and_replies ) {
		$is_review_enabled = masteriyo_get_setting( 'single_course.display.enable_review' );
		$review_length     = count( $reviews_and_replies['reviews'] );
		if ( $is_review_enabled && $review_length ) {
			masteriyo_get_template(
				'single-course/layout-1/review-count.php',
				array(
					'course'              => $course,
					'reviews_and_replies' => $reviews_and_replies,
				)
			);
		}
	}

	if ( ! function_exists( 'masteriyo_layout_1_single_course_user_review_content' ) ) {
		/**
		 * Show user review content in single course page layout 1.
		 *
		 * @since 1.10.0
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 */
		function masteriyo_layout_1_single_course_user_review_content( $course, $reviews_and_replies ) {
			$is_review_enabled = masteriyo_get_setting( 'single_course.display.enable_review' );
			$review_length     = count( $reviews_and_replies['reviews'] );
			if ( $is_review_enabled && $review_length ) {
					masteriyo_get_template(
						'single-course/layout-1/user-review-content.php',
						array(
							'course'              => $course,
							'reviews_and_replies' => $reviews_and_replies,
						)
					);
			}
		}
	}

	if ( ! function_exists( 'masteriyo_layout_1_single_course_review_form' ) ) {
		/**
		 * Show course review form in single course page layout 1.
		 *
		 * @since 1.10.0
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 */
		function masteriyo_layout_1_single_course_review_form( $course, $reviews_and_replies ) {
			masteriyo_get_template(
				'single-course/layout-1/review-form.php',
				array(
					'course'              => $course,
					'reviews_and_replies' => $reviews_and_replies,
				)
			);
		}
	}

	if ( ! function_exists( 'masteriyo_layout_1_single_course_review_filters' ) ) {
		/**
		 * Show course review filters in single course page layout 1.
		 *
		 * @since 1.10.0
		 *
		 * @param \Masteriyo\Models\Course $course The course object.
		 */
		function masteriyo_layout_1_single_course_review_filters( $course, $reviews_and_replies ) {
			masteriyo_get_template(
				'single-course/layout-1/review-filters.php',
				array(
					'course'              => $course,
					'reviews_and_replies' => $reviews_and_replies,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_layout_1_single_course_review_lists' ) ) {
	/**
	 * Show course reviews lists in single course page layout 1.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	function masteriyo_layout_1_single_course_review_lists( $course, $reviews_and_replies ) {
		masteriyo_get_template(
			'single-course/layout-1/reviews-list.php',
			array(
				'course'              => $course,
				'reviews_and_replies' => $reviews_and_replies,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_layout_1_template_single_course_review_replies' ) ) {
	/**
	 * Display review replies in single course page's reviews list section.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\CourseReview $course_review Course review object.
	 * @param \Masteriyo\Models\CourseReview[] $replies Replies to the course review.
	 */
	function masteriyo_layout_1_template_single_course_review_replies( $course_review, $replies ) {
		if ( ! empty( $replies ) ) {

			$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';

			if ( 'layout1' !== $layout ) {
				return;
			}

			$template = 'single-course/layout-1/review-replies.php';

			masteriyo_get_template(
				$template,
				array(
					'course_review' => $course_review,
					'replies'       => $replies,
				)
			);
		}
	}
}

if ( ! function_exists( 'masteriyo_layout_1_template_course_review_reply' ) ) {
	/**
	 * Displays a course review reply template in single course page layout 1.
	 *
	 * Checks if current single course layout is layout 1 before rendering template.
	 * Passes review reply data to the template.
	 *
	 * @since 1.10.0
	 *
	 * @param array $args {
	 *     Arguments for rendering the review reply template.
	 *
	 *     @type array $review_reply   Review reply data.
	 * }
	 */
	function masteriyo_layout_1_template_course_review_reply( $args = array() ) {
		$args['pp_placeholder'] = masteriyo_get_course_review_author_pp_placeholder();

		$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';

		$template = 'single-course/layout-1/course-review-reply.php';

		if ( 'layout1' !== $layout ) {
			return;
		}

		masteriyo_get_template( $template, $args );
	}
}

if ( ! function_exists( 'masteriyo_layout_1_single_course_expiration_info' ) ) {
	/**
	 * Renders the course expiration information template in single course page layout 1.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	function masteriyo_layout_1_single_course_expiration_info( $course ) {
		$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';

		if ( masteriyo_is_single_course_page() && 'layout1' === $layout ) {
			masteriyo_course_expiration_info( $course );
		}
	}
}

if ( ! function_exists( 'masteriyo_layout_1_single_course_retake_button' ) ) {
	/**
	 * Renders the course retake button template in single course page layout 1.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	function masteriyo_layout_1_single_course_retake_button( $course ) {
		$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';

		if ( masteriyo_is_single_course_page() && 'layout1' === $layout ) {
			masteriyo_template_course_retake_button( $course );
		}
	}
}

if ( ! function_exists( 'masteriyo_layout_1_single_course_price_and_enroll_button' ) ) {
	/**
	 * Renders the course price & enroll button template in single course page layout 1.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	function masteriyo_layout_1_single_course_price_and_enroll_button( $course ) {
		masteriyo_get_template(
			'single-course/layout-1/price-and-enroll-button.php',
			array(
				'course' => $course,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_layout_1_single_course_aside_items' ) ) {
	/**
	 * Renders the aside items template in single course page layout 1.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	function masteriyo_layout_1_single_course_aside_items( $course ) {
		masteriyo_get_template(
			'single-course/layout-1/aside--items.php',
			array(
				'course' => $course,
			)
		);
	}
}

if ( ! function_exists( 'masteriyo_layout_1_single_course_highlights' ) ) {
	/**
	 * Renders the course highlights template in single course page layout 1.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 */
	function masteriyo_layout_1_single_course_highlights( $course ) {
		masteriyo_get_template(
			'single-course/layout-1/highlights.php',
			array(
				'course' => $course,
			)
		);
	}
}


if ( ! function_exists( 'masteriyo_template_payment_wire_transfer' ) ) {
	/**
	 * Display checkout form payment.
	 *
	 * @since 1.16.0
	 */
	function masteriyo_template_payment_wire_transfer() {
		$wire_transfer_enabled  = masteriyo_get_setting( 'payments.offline.wire_transfer.enable' );
		$offline_payment_enable = masteriyo_get_setting( 'payments.offline.enable' );

		if ( ! $wire_transfer_enabled || ! $offline_payment_enable ) {
			return;
		}

		masteriyo_get_template(
			'checkout/wire-transfer-details.php',
			array(
				'wire_transfer' => masteriyo_get_setting( 'payments.offline.wire_transfer' ),
			)
		);
	}
}
