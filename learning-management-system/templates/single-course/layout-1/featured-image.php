<?php
/**
 * The Template for displaying course featured image in single course page
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/featured-image.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( masteriyo_get_setting( 'single_course.components_visibility.thumbnail' ) ) :
	?>
<div class="masteriyo-single-header__img-wrap">
	<?php if ( masteriyo_get_setting( 'single_course.components_visibility.difficulty_badge' ) && $difficulty ) : ?>
		<div class="masteriyo-course--badges">
			<div class="difficulty-badge <?php echo esc_attr( $difficulty['slug'] ); ?>" data-id="<?php echo esc_attr( $difficulty['id'] ); ?>">
				<?php if ( $difficulty['color'] ) : ?>
					<span class="masteriyo-badge" style="background-color: <?php echo esc_attr( $difficulty['color'] ); ?>">
						<?php echo esc_html( $difficulty['name'] ); ?>
					</span>
				<?php else : ?>
					<span class="masteriyo-badge <?php echo esc_attr( masteriyo_get_difficulty_badge_css_class( $difficulty['slug'] ) ); ?>">
						<?php echo esc_html( $difficulty['name'] ); ?>
					</span>
				<?php endif; ?>
			</div>

			<?php
			if ( masteriyo_get_setting( 'single_course.components_visibility.course_badge' ) &&
				masteriyo_get_setting( 'single_course.components_visibility.thumbnail' ) &&
				! empty( $course->get_course_badge() )
			) :
				?>
				<div class="masteriyo-single-course--badge">
					<span class="masteriyo-badge"><?php echo esc_html( $course->get_course_badge() ); ?></span>
				</div>
			<?php endif; ?>

		</div><!-- /.masteriyo-course--badges -->
	<?php endif; ?>

	<?php if ( masteriyo_get_setting( 'single_course.components_visibility.thumbnail' ) ) : ?>
			<div class="masteriyo-single-header__image">
				<img src="<?php echo esc_attr( $course->get_featured_image_url( 'masteriyo_single' ) ); ?>" alt="<?php echo esc_attr( $course->get_title() ); ?>">
			</div>
		<?php endif; ?>
</div>
<?php endif; ?>
