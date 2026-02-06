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


	<?php
	$author = $course_review->get_author();
	if ( empty( $author ) || is_wp_error( $author ) ) :
		?>
		<img src="<?php echo esc_attr( $pp_placeholder ); ?>" alt="<?php echo esc_attr( $course_review->get_author_name() ); ?>" />
	<?php else : ?>
		<img src="<?php echo esc_attr( $author->get_avatar_url() ); ?>" alt="<?php echo esc_attr( $course_review->get_author_name() ); ?>" />
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
					<ul class="masteriyo-slide menu">
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

		<div class="masteriyo-reply-form masteriyo-hidden">
			<div class="masteriyo-form-header">
				<svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24">
				<path d="M6.187 3.863a10 10 0 1 1 8.411 17.793 10 10 0 0 1-6.78-.573.908.908 0 0 0-.462-.042l-3.05.892-.021.006a1.82 1.82 0 0 1-2.248-2.123l.026-.098.948-2.929a.907.907 0 0 0-.043-.499A9.999 9.999 0 0 1 6.187 3.863Zm6.463-.02a8.182 8.182 0 0 0-8.17 11.38l.15.329.025.06c.2.506.246 1.06.129 1.59a.902.902 0 0 1-.023.085l-.937 2.892L6.9 19.28a2.727 2.727 0 0 1 1.396.044l.181.063.062.026a8.183 8.183 0 1 0 4.112-15.57Z"/>
				</svg>
				<?php
				/* translators: %s: Review author name */
				printf( __( '<strong>Reply to %s</strong>', 'learning-management-system' ), $course_review->get_author_name() );
				?>
			</div>
			<div class="masteriyo-single-body__main--review-form">
			<form method="POST" class="masteriyo-submit-review-form">
			<input type="hidden" name="course_id" value="<?php echo esc_attr( $course_id ); ?>">
			<input type="hidden" name="id" value="">
			<input type="hidden" name="parent" value="<?php echo esc_attr( $course_review->get_id() ); ?>">
			<div class="masteriyo-message  masteriyo-single-form-group">
				<textarea
					id="masteriyo-reply-content"
					name="content"
					placeholder="Write your reply here..."
				></textarea>
			</div>
			<div class="masteriyo-single-form-actions">
				<button type="button" class="masteriyo-cancel-reply masteriyo-btn masteriyo-btn-secondary" ><?php esc_attr_e( 'Cancel', 'learning-management-system' ); ?></button>
				<button type="submit" value="yes" name="masteriyo-submit-review" class="masteriyo-single--review-submit masteriy-submit-reply masteriyo-btn masteriyo-btn-primary"><?php esc_attr_e( 'Reply', 'learning-management-system' ); ?></button>
			</div>
				<?php wp_nonce_field( 'masteriyo-submit-review' ); ?>
			</form>
			</div>
		</div>
		<?php
		/**
		 * Fires after the reply button in a course review list item.
		 *
		 * Allows plugins/themes to hook in and add custom content after the reply button.
		 *
		 * @since 1.10.0 [Free]
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
