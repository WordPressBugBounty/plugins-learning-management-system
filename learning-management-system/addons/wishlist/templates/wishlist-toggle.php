<?php
/**
 * Template for displaying wishlist toggle button.
 *
 * @package Masteriyo\Wishlist\Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="masteriyo-wishlist">
	<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
		<input type="hidden" name="course_id" value="<?php echo absint( $course->get_id() ); ?>">
		<?php wp_nonce_field( 'masteriyo-wishlist-toggle-nonce' ); ?>

		<span
			class=" <?php echo esc_attr( $class ); ?>"
			title="<?php echo esc_attr( $title ); ?>"
			data-course-id="<?php echo esc_attr( $course->get_id() ); ?>"
			data-is-added-to-wishlist="<?php echo esc_attr( masteriyo_bool_to_string( $added_to_wishlist ) ); ?>"
			tabindex="0"
		>
			<?php masteriyo_get_svg( 'heart-filled', true ); ?>
		</span>
	</form>
</div>
<?php
