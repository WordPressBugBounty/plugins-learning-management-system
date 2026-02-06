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
	<div class="masteriyo-single-body__main--review-form-wrapper">
		<div class="review-container">
			<button
				type="button"
				class="review-button"
				id="masteriyo-show-review-form"
			>
			<svg xmlns="http://www.w3.org/2000/svg" fill="#000" viewBox="0 0 24 24">
					<path d="M20.179 12a8.182 8.182 0 1 0-16.364 0 8.182 8.182 0 0 0 16.364 0Zm1.818 0c0 5.523-4.477 10-10 10s-10-4.477-10-10c0-5.522 4.477-10 10-10s10 4.478 10 10Z"/>
					<path d="M15.633 11.091a.91.91 0 0 1 0 1.819H8.361a.91.91 0 0 1 0-1.819h7.272Z"/>
					<path d="M11.088 15.637V8.364a.91.91 0 1 1 1.818 0v7.273a.91.91 0 1 1-1.818 0Z"/>
				</svg>
				<?php esc_html_e( 'Write a Review', 'learning-management-system' ); ?>
			</button>
		</div>

		<div class="masteriyo-single-body__main--review-form" id="masteriyo-review-form" style="display:none;">
			<h3 class="masteriyo-single-body__main--review-form-heading">
				<?php esc_html_e( 'Create a new review.', 'learning-management-system' ); ?>
			</h3>

			<form method="POST" class="masteriyo-submit-review-form">
				<input type="hidden" name="course_id" value="<?php echo esc_attr( $course->get_id() ); ?>">
				<input type="hidden" name="id" value="">
				<input type="hidden" name="parent" value="0">

				<div class="masteriyo-title masteriyo-single-form-group">
					<label for="title"><?php esc_html_e( 'Title', 'learning-management-system' ); ?></label>
					<input type="text" name="title" class="masteriyo-text-input" placeholder="<?php esc_html_e( 'Summarize your experience in one line', 'learning-management-system' ); ?>" />
				</div>

				<div class="masteriyo-rating masteriyo-single-form-group">
					<label for="rating"><?php esc_html_e( 'Rating', 'learning-management-system' ); ?></label>
					<input type="hidden" name="rating" value="0" />
					<div class="border-none masteriyo-stab-rs">
						<span class="masteriyo-icon-svg masteriyo-flex masteriyo-rstar">
							<?php masteriyo_render_stars( 0, 'masteriyo-rating-input-icon' ); ?>
						</span>
					</div>
				</div>

				<div class="masteriyo-message masteriyo-single-form-group">
					<label for="content"><?php esc_html_e( 'Content', 'learning-management-system' ); ?></label>
					<textarea name="content" cols="30" rows="10" placeholder="<?php esc_html_e( 'Share your detailed experience to help others', 'learning-management-system' ); ?>"></textarea>
				</div>

				<div class="masteriyo-single-form-actions">
					<button
						type="button"
						class="masteriyo-single--review-cancel masteriyo-btn masteriyo-btn-secondary"
						id="masteriyo-cancel-review-form"
					>
						<?php esc_html_e( 'Cancel', 'learning-management-system' ); ?>
					</button>
					<button
						type="submit"
						name="masteriyo-submit-review"
						value="yes"
						class="masteriyo-single--review-submit masteriyo-btn masteriyo-btn-primary"
					>
						<?php esc_html_e( 'Submit', 'learning-management-system' ); ?>
					</button>
				</div>

				<?php wp_nonce_field( 'masteriyo-submit-review' ); ?>
			</form>
		</div>
	</div>

<?php else : ?>

	<div class="masteriyo-login-msg masteriyo-submit-container">
		<p>
			<?php
			$review_for_enrolled_user_only = masteriyo_get_setting( 'single_course.display.enable_review_enrolled_users_only' );
			$enrollment_text               = $review_for_enrolled_user_only ? __( 'and enrolled', 'learning-management-system' ) : '';

			printf(
				/* translators: %s: Anchor tag html with text "logged in", %s: additional enrollment text when review is restricted to enrolled users. */
				esc_html__( 'Want to submit a review? %1$s %2$s', 'learning-management-system' ),
				wp_kses_post(
					sprintf(
						'<a href="%s" class="masteriyo-link-primary">%s</a>',
						masteriyo_get_page_permalink( 'account' ),
						__( 'Login', 'learning-management-system' )
					)
				),
				esc_html( $enrollment_text )
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
