<?php
/**
 * The Template for displaying course featured image in single course page
 *
 * @package Masteriyo\Templates
 * @version 1.0.0
 */

use Masteriyo\Enums\VideoSource;

defined( 'ABSPATH' ) || exit;

/**
 * Precompute visibility flags to avoid messy, duplicated conditions.
 */
$show_thumbnail       = (bool) masteriyo_get_setting( 'single_course.components_visibility.thumbnail' );
$show_featured_ribbon = (bool) masteriyo_get_setting( 'single_course.components_visibility.featured_ribbon' );
$show_difficulty      = (bool) masteriyo_get_setting( 'single_course.components_visibility.difficulty_badge' );
$show_course_badge    = (bool) masteriyo_get_setting( 'single_course.components_visibility.course_badge' );

/**
 * Show the whole image block if:
 * - single visibility is ON and thumbnails are ON
 * - OR single visibility is OFF (global fallback)
 */
$show_image_block = $show_thumbnail;
?>

<?php if ( $show_image_block ) : ?>
	<div class="masteriyo-course--img-wrap">

		<?php if ( $show_featured_ribbon && $course->is_featured() ) : ?>
			<div class="course-featured">
				<?php echo esc_html( $course->featured_text() ); ?>
			</div>
		<?php endif; ?>

		<?php
		/**
		 * Build badges (difficulty + custom course badge) together to keep DOM stable.
		 */
		$has_difficulty = ! empty( $difficulty );
		$can_show_diff  = ( $show_difficulty && $has_difficulty );

		$course_badge_val = $course->get_course_badge();
		$can_show_cbadge  = (
			$show_course_badge && $show_thumbnail && ! empty( $course_badge_val )
		);

		if ( $can_show_diff || $can_show_cbadge ) :
			?>
			<div class="masteriyo-course--badges">
				<?php if ( $can_show_diff ) : ?>
					<div class="difficulty-badge <?php echo esc_attr( $difficulty['slug'] ); ?>" data-id="<?php echo esc_attr( $difficulty['id'] ); ?>">
						<?php if ( ! empty( $difficulty['color'] ) ) : ?>
							<span class="masteriyo-badge" style="background-color: <?php echo esc_attr( $difficulty['color'] ); ?>">
								<?php echo esc_html( $difficulty['name'] ); ?>
							</span>
						<?php else : ?>
							<span class="masteriyo-badge <?php echo esc_attr( masteriyo_get_difficulty_badge_css_class( $difficulty['slug'] ) ); ?>">
								<?php echo esc_html( $difficulty['name'] ); ?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $can_show_cbadge ) : ?>
					<div class="masteriyo-single-course--badge">
						<span class="masteriyo-badge"><?php echo esc_html( $course_badge_val ); ?></span>
					</div>
				<?php endif; ?>
			</div><!-- /.masteriyo-course--badges -->
		<?php endif; ?>

		<?php if ( empty( $course->get_featured_image() ) && $course->has_featured_video() ) : ?>
			<?php
			// Render video only (no image available).
			if ( $course->is_self_hosted_featured_video() || VideoSource::EXTERNAL === $course->get_featured_video_source() ) {
				masteriyo_get_video_html(
					array(
						'width'    => '100%',
						'style'    => 'height: calc(9 * 800px / 16);',
						'src'      => esc_attr( $course->get_featured_video_embed_url() ),
						'controls' => true,
					),
					true
				);
			} else {
				masteriyo_get_iframe_html(
					array(
						'width'           => '100%',
						'style'           => 'min-height: 500px;',
						'src'             => esc_attr( $course->get_featured_video_embed_url() ),
						'allowFullScreen' => true,
					),
					true
				);
			}
			?>
		<?php else : ?>
			<div class="masteriyo-feature-img">
				<?php echo wp_kses( $course->get_image( 'masteriyo_single' ), 'masteriyo_image' ); ?>

				<?php if ( $course->has_featured_video() ) : ?>
					<div class="masteriyo-play-featured-video-btn">
						<?php masteriyo_get_svg( 'play', true ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div><!-- /.masteriyo-course--img-wrap -->
<?php endif; ?>

<?php if ( $course->has_featured_video() ) : ?>
	<div class="masteriyo-overlay masteriyo-v-center masteriyo-featured-video-modal" style="display:none;">
		<div class="masteriyo--modal masteriyo-modal-featured-video masteriyo-transparent width-lg">
			<?php
			if ( $course->is_self_hosted_featured_video() || VideoSource::EXTERNAL === $course->get_featured_video_source() ) {
				masteriyo_get_video_html(
					array(
						'width'    => '100%',
						'style'    => 'height: calc(9 * 800px / 16);',
						'src'      => esc_attr( $course->get_featured_video_embed_url() ),
						'controls' => true,
					),
					true
				);
			} else {
				masteriyo_get_iframe_html(
					array(
						'width'           => '100%',
						'style'           => 'height: calc(9 * 800px / 16);',
						'src'             => esc_attr( $course->get_featured_video_embed_url() ),
						'allowFullScreen' => true,
					),
					true
				);
			}
			?>
		</div>
	</div>
<?php endif; ?>
