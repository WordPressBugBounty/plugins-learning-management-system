<?php

/**
 * The Template for displaying course review reply.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-1/course-review-reply.php.
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

if ( ! $reply ) {
	return;
}

$created_date = strtotime( $reply->get_date_created() );
$created_date = gmdate( 'M j, Y @ g:i a', $created_date );

?>
<li class="masteriyo-course-review masteriyo-single-body__main--reply-list is-course-review-reply" data-id="<?php echo esc_attr( $reply->get_id() ); ?>">
	<input type="hidden" name="parent" value="<?php echo esc_attr( $course_review->get_id() ); ?>">
	<?php if ( ! $reply->get_author() ) : ?>
		<img src="<?php echo esc_attr( $pp_placeholder ); ?>" alt="<?php echo esc_attr( $reply->get_author_name() ); ?>" />
	<?php else : ?>
		<img src="<?php echo esc_attr( $reply->get_author()->get_avatar_url() ); ?>" alt="<?php echo esc_attr( $reply->get_author_name() ); ?>" />
	<?php endif; ?>

	<div class="masteriyo-single-body__main--review-list-content">
		<span class="author-name masteriyo-single-body__main--review-list-name" data-value="<?php echo esc_attr( $reply->get_author_name() ); ?>"><?php echo esc_html( $reply->get_author_name() ); ?></span>
		<span class="date-created masteriyo-single-body__main--review-list-date" data-value="<?php echo esc_attr( $created_date ); ?>"><?php echo esc_html( $created_date ); ?></span>

		<div class="masteriyo-single-body__main--review-list-content-wrapper">

			<p class="content masteriyo-single-body__main--review-list-content-desc" data-value="<?php echo esc_attr( $reply->get_content() ); ?>">
				<?php echo esc_html( $reply->get_content() ); ?>
			</p>

			<?php if ( masteriyo_current_user_can_edit_course_review( $reply ) ) : ?>
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
	</div>
</li>
<?php
