<?php
/**
 * The Template for displaying course featured image in single course page
 *
 * @package Masteriyo\Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$show_thumbnail    = (bool) masteriyo_get_setting( 'single_course.components_visibility.thumbnail' );
$show_difficulty   = (bool) masteriyo_get_setting( 'single_course.components_visibility.difficulty_badge' );
$show_course_badge = (bool) masteriyo_get_setting( 'single_course.components_visibility.course_badge' );

$show_block = $show_thumbnail;

/** Derived badge visibility */
$has_difficulty = ! empty( $difficulty );
$can_show_diff  = ( $show_difficulty && $has_difficulty );

$course_badge_val = $course->get_course_badge();
$can_show_cbadge  = (
	$show_course_badge && $show_thumbnail && ! empty( $course_badge_val )
);
?>

<?php if ( $show_block ) : ?>
	<div class="masteriyo-course--img-wrap">

		<?php if ( $can_show_diff || $can_show_cbadge ) : ?>
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

		<div class="masteriyo-feature-img">
			<?php echo wp_kses( $course->get_image( 'masteriyo_single' ), 'masteriyo_image' ); ?>
		</div>

	</div><!-- /.masteriyo-course--img-wrap -->
<?php endif; ?>
