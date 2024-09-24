<?php

/**
 * The Template for displaying review form for single course.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-1/review-form.php
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


/**
 * Fires before rendering author and rating section in single course page.
 *
 * @since 1.10.0
 */
do_action( 'masteriyo_before_single_course_review_form' );

?>
<?php if ( is_user_logged_in() ) : ?>
<div class="masteriyo-single-body__main--review-form">
	<h3 class="masteriyo-single-body__main--review-form-heading"><?php esc_html_e( 'Create a new review.', 'learning-management-system' ); ?></h3>

	<form method="POST" class="masteriyo-submit-review-form">
	<input type="hidden" name="course_id" value="<?php echo esc_attr( $course->get_id() ); ?>">
	<input type="hidden" name="id" value="">
			<input type="hidden" name="parent" value="0">
		<div class="masteriyo-title masteriyo-single-form-group">
			<label for="title"><?php esc_html_e( 'Title', 'learning-management-system' ); ?></label>
			<input type="text" name="title" class="masteriyo-text-input" />
		</div>

		<div class="masteriyo-rating masteriyo-single-form-group">
			<label for="rating"><?php esc_html_e( 'Rating', 'learning-management-system' ); ?></label>
			<input type="hidden" name="rating" value="0" />
				<div class="masteriyo-stab-rs border-none">
					<span class="masteriyo-icon-svg masteriyo-flex masteriyo-rstar">
						<?php masteriyo_render_stars( 0, 'masteriyo-rating-input-icon' ); ?>
					</span>
				</div>
		</div>

		<div class="masteriyo-message masteriyo-single-form-group">
			<label for="content"><?php esc_html_e( 'Content', 'learning-management-system' ); ?></label>
			<textarea name="content" cols="30" rows="10"></textarea>
		</div>

		<button type="submit" name="masteriyo-submit-review" value="yes" class="masteriyo-single--review-submit">
					<?php esc_html_e( 'Submit', 'learning-management-system' ); ?>
				</button>
				<?php wp_nonce_field( 'masteriyo-submit-review' ); ?>
	</form>
</div>
<?php else : ?>
	<div class="masteriyo-login-msg masteriyo-submit-container">
		<p>
			<?php
			printf(
				/* translators: %s: Achor tag html with text "logged in" */
				esc_html__( 'You must be %s to submit a review.', 'learning-management-system' ),
				wp_kses_post(
					sprintf(
						'<a href="%s" class="masteriyo-link-primary">%s</a>',
						masteriyo_get_page_permalink( 'account' ),
						__( 'logged in', 'learning-management-system' )
					)
				)
			);
			?>
		</p>
	</div>
<?php endif; ?>
<?php

/**
 * Fires after rendering author and rating section in single course page.
 *
 * @since 1.10.0
 */
do_action( 'masteriyo_after_single_course_review_form' );
