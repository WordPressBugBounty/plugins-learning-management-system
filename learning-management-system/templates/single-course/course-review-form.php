<?php
/**
 * Review form (Layout 1) — Nelwo theme override
 *
 * Copy to:
 * yourtheme/masteriyo/single-course/layout-1/review-form.php
 *
 * @package Masteriyo\Templates
 * @version 1.10.0 [Free] (customized for Nelwo)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Before: review form hook.
 *
 * @since 1.10.0 [Free]
 */
do_action( 'masteriyo_before_single_course_review_form' );

$review_for_enrolled_user_only = masteriyo_get_setting( 'single_course.display.enable_review_enrolled_users_only' );
$can_review                    = function_exists( 'masteriyo_can_user_review_course' ) ? masteriyo_can_user_review_course( $course ) : true;
?>

<?php if ( is_user_logged_in() ) : ?>
	<div class="masteriyo-single-body__main--review-form nelwo-review-form">
		<h3 class="masteriyo-single-body__main--review-form-heading">
			<?php esc_html_e( 'Create a new review.', 'learning-management-system' ); ?>
		</h3>

		<?php if ( $review_for_enrolled_user_only && ! $can_review ) : ?>
			<div class="masteriyo-enroll-msg">
				<p><?php esc_html_e( 'You must be enrolled to submit a review.', 'learning-management-system' ); ?></p>
			</div>
		<?php else : ?>
			<form method="POST" class="masteriyo-submit-review-form" novalidate>
				<input type="hidden" name="course_id" value="<?php echo esc_attr( $course->get_id() ); ?>">
				<input type="hidden" name="id" value="">
				<input type="hidden" name="parent" value="0">

				<!-- Title -->
				<div class="masteriyo-title masteriyo-single-form-group">
					<label for="masteriyo-review-title">
						<?php esc_html_e( 'Title', 'learning-management-system' ); ?>
					</label>
					<input id="masteriyo-review-title" type="text" name="title" class="masteriyo-text-input" required />
				</div>

				<!-- Rating (respect visibility settings) -->
				<?php
				$show_rating =
					( masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' ) &&
						masteriyo_get_setting( 'course_archive.components_visibility.rating' ) )
					|| ! masteriyo_get_setting( 'course_archive.components_visibility.single_course_visibility' );

				if ( $show_rating ) :
					?>
					<div class="masteriyo-rating masteriyo-single-form-group">
						<label for="masteriyo-review-rating">
							<?php esc_html_e( 'Rating', 'learning-management-system' ); ?>
						</label>
						<input id="masteriyo-review-rating" type="hidden" name="rating" value="0" />
						<div class="masteriyo-stab-rs border-none">
							<span class="masteriyo-icon-svg masteriyo-flex masteriyo-rstar">
								<?php masteriyo_render_stars( 0, 'masteriyo-rating-input-icon' ); ?>
							</span>
						</div>
					</div>
				<?php endif; ?>

				<!-- Content -->
				<div class="masteriyo-message masteriyo-single-form-group">
					<label for="masteriyo-review-content">
						<?php esc_html_e( 'Content', 'learning-management-system' ); ?>
					</label>
					<textarea id="masteriyo-review-content" name="content" cols="30" rows="10" required></textarea>
				</div>

				<button type="submit" name="masteriyo-submit-review" value="yes" class="masteriyo-single--review-submit masteriyo-btn masteriyo-btn-primary">
					<?php esc_html_e( 'Submit', 'learning-management-system' ); ?>
				</button>

				<?php wp_nonce_field( 'masteriyo-submit-review' ); ?>
			</form>
		<?php endif; ?>
	</div>

<?php else : ?>
	<!-- Logged-out message -->
	<div class="masteriyo-login-msg masteriyo-submit-container nelwo-review-login-msg">
		<p>
			<?php
			$enrollment_text = $review_for_enrolled_user_only ? __( 'and enrolled', 'learning-management-system' ) : '';

			printf(
				/* translators: 1: <a>logged in</a>, 2: 'and enrolled' when restricted */
				esc_html__( 'You must be %1$s %2$s to submit a review.', 'learning-management-system' ),
				wp_kses_post(
					sprintf(
						'<a href="%s" class="masteriyo-link-primary">%s</a>',
						esc_url( masteriyo_get_page_permalink( 'account' ) ),
						esc_html__( 'logged in', 'learning-management-system' )
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
 * After: review form hook.
 *
 * @since 1.10.0 [Free]
 */
do_action( 'masteriyo_after_single_course_review_form' );
