<?php
/**
 *
 * Buttons for single course page.
 *
 * @package Masteriyo\Addons\SureCartIntegration\Templates
 * @version 1.12.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="masteriyo-single-course--surecart-prices" >
	<?php foreach ( $prices as $price ) : ?>
		<a class="<?php echo 'layout-1' === $layout ? 'masteriyo-single-course--surecart-add-to-cart-btn-layout-1' : 'masteriyo-single-course--surecart-add-to-cart-btn'; ?>"
			href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'line_items' => array(
								array(
									'price_id' => $price->id,
									'quantity' => 1,
								),
							),
						),
						\SureCart::pages()->url( 'checkout' )
					)
				);
				?>
		">
			<sc-format-number type="currency" currency="<?php echo esc_attr( $price->currency ); ?>" value="<?php echo (int) $price->amount; ?>">
				<?php esc_html_e( 'Add to Cart', 'learning-management-system' ); ?>
			</sc-format-number>
			&nbsp;
			<sc-format-interval value="<?php echo (int) $price->recurring_interval_count; ?>" interval="<?php echo esc_attr( $price->recurring_interval ); ?>"></sc-format-interval>
			<?php
			if ( ! empty( $price->name ) ) {
				echo esc_html( '(' . $price->name . ')' );
			}
			?>
		</a>
	<?php endforeach; ?>
</div>


