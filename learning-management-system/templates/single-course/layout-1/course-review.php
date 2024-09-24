<?php

/**
 * The Template for displaying course review.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-1/course-review.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.10.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! $course_review ) {
	return;
}

$created_date = strtotime( $course_review->get_date_created() );
$created_date = gmdate( 'M j, Y @ g:i a', $created_date );

?>
<li class="masteriyo-course-review masteriyo-single-body__main--review-list" data-id="<?php echo esc_attr( $course_review->get_id() ); ?>">
	<input type="hidden" name="parent" value="<?php echo esc_attr( $course_review->get_parent() ); ?>">


	<?php if ( ! $course_review->get_author() ) : ?>
		<img src="<?php echo esc_attr( $pp_placeholder ); ?>" alt="<?php echo esc_attr( $course_review->get_author_name() ); ?>" />
	<?php else : ?>
		<img src="<?php echo esc_attr( $course_review->get_author()->get_avatar_url() ); ?>" alt="<?php echo esc_attr( $course_review->get_author_name() ); ?>" />
	<?php endif; ?>

	<div class="masteriyo-single-body__main--review-list-content">
		<span class="author-name masteriyo-single-body__main--review-list-name" data-value="<?php echo esc_attr( $course_review->get_author_name() ); ?>"><?php echo esc_html( $course_review->get_author_name() ); ?></span>
		<span class="date-created masteriyo-single-body__main--review-list-date-created" data-value="<?php echo esc_attr( $created_date ); ?>"><?php echo esc_html( $created_date ); ?></span>
		<div class="masteriyo-single-body__main--review-list-content-wrapper">
			<h5 class="title masteriyo-review-title" data-value="<?php echo esc_attr( $course_review->get_title() ); ?>"><?php echo esc_html( $course_review->get_title() ); ?></h5>

			<div class="rating masteriyo-single-body__main--review-list-content__rating-star" data-value="<?php echo esc_attr( $course_review->get_rating() ); ?>">
				<?php masteriyo_render_stars( $course_review->get_rating() ); ?>
			</div>

			<?php if ( masteriyo_current_user_can_edit_course_review( $course_review ) ) : ?>
				<nav class="masteriyo-dropdown">
					<label class="menu-toggler">
						<span class='icon_box'>
							<?php masteriyo_get_svg( 'small-hamburger', true ); ?>
						</span>
					</label>
					<ul class="slide menu">
						<li class="masteriyo-edit-course-review"><strong><?php esc_html_e( 'Edit', 'learning-management-system' ); ?></strong></li>
						<li class="masteriyo-delete-course-review"><strong><?php esc_html_e( 'Delete', 'learning-management-system' ); ?></strong></li>
					</ul>
				</nav>
			<?php endif; ?>

		</div>

		<p class="content masteriyo-single-body__main--review-list-content-desc" data-value="<?php echo esc_attr( $course_review->get_content() ); ?>">
			<?php echo esc_html( $course_review->get_content() ); ?>
		</p>

		<a href="javascript:;" class="masteriyo-single-body__main--review-list-content-reply-btn">
			<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
				<path stroke="#7A7A7A" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 17-5-5 5-5" />
				<path stroke="#7A7A7A" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 18v-2a4 4 0 0 0-4-4H4" />
			</svg>
			<?php esc_html_e( 'Reply', 'learning-management-system' ); ?>
		</a>

		<?php
		/**
		 * Fires after the reply button in a course review list item.
		 *
		 * Allows plugins/themes to hook in and add custom content after the reply button.
		 *
		 * @since 1.10.0
		 *
		 * @param \Masteriyo\Models\CourseReview $course_review Course review object.
		 * @param array                         $replies       Array of review reply objects.
		 * @param int                           $course_id     Course ID.
		 */
		do_action( 'masteriyo_layout_1_single_course_review_list_after_reply_btn', $course_review, $replies, $course_id );
		?>
	</div>
</li>


<?php
