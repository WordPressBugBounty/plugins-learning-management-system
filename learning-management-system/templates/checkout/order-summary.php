<?php
/**
 * Order summary.
 *
 * @package Masteriyo\Templates
 * @since 1.0.0
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="masteriyo-checkout-summary-your-order">
	<h2 class="masteriyo-checkout-summary--title">
		<?php esc_html_e( 'Your Order', 'learning-management-system' ); ?>
	</h2>
	<ul class="masteriyo-checkout-summary-order-details">
		<li class="h-border">
				<strong><?php esc_html_e( 'Courses', 'learning-management-system' ); ?></strong>
				<strong><?php esc_html_e( 'Subtotal', 'learning-management-system' ); ?></strong>
		</li>

	<?php foreach ( $courses as $course ) : ?>
		<li>
				<span>
				<?php
				echo wp_kses(
					$course->get_name(),
					$allowed_html = array(
						'span' => array(
							'class' => array(
								'masteriyo-badge',
							),
							'style' => array(
								'background-color',
							),
						),
					)
				);
				?>
						</span> 
				<span><?php echo wp_kses_post( masteriyo_price( $course->get_price(), array( 'currency' => $course->get_currency() ) ) ); ?></span>
		</li>
	<?php endforeach; ?>
		<li>
				<strong><?php esc_html_e( 'Subtotal', 'learning-management-system' ); ?></strong>
				<span><?php echo wp_kses_post( masteriyo_price( $cart->get_subtotal(), array( 'currency' => $course->get_currency() ) ) ); ?></span>
		</li>
		<li>
				<strong><?php esc_html_e( 'Total', 'learning-management-system' ); ?></strong>
				<strong><?php echo wp_kses_post( masteriyo_price( $cart->get_total(), array( 'currency' => $course->get_currency() ) ) ); ?></strong>
		</li>
	</ul>
</div>
<?php
