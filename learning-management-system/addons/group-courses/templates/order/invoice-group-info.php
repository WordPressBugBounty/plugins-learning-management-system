<?php
/**
 * Group information section for order invoice.
 *
 * @since 1.20.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $invoice_data['group_info'] ) || empty( $invoice_data['group_info'] ) ) {
	return;
}

$group_info = $invoice_data['group_info'];
?>

<!-- Group Information Section -->
<div class="masteriyo-invoice-body--group-info" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #999999;">
	<h3 style="color: #383838; font-size: 16px; font-weight: 600; line-height: 24px; margin-bottom: 16px;">
		<?php esc_html_e( 'Group Details', 'learning-management-system' ); ?>
	</h3>

	<div class="masteriyo-invoice-body--form-data" style="margin-bottom: 10px;">
		<div style="float: left; width: 180px; color: #222222; font-size: 14px; font-weight: 500; line-height: 24px;">
			<?php echo esc_html( __( 'Group Name:', 'learning-management-system' ) ); ?>
		</div>

		<div class="masteriyo-invoice-body--form-data__content" style="float: right; color: #383838; font-size: 14px; font-weight: 400; line-height: 24px;">
			<?php echo esc_html( $group_info['name'] ); ?>
		</div>
	</div>

	<div class="masteriyo-invoice-body--form-data" style="margin-bottom: 10px;">
		<div style="float: left; width: 180px; color: #222222; font-size: 14px; font-weight: 500; line-height: 24px;">
			<?php echo esc_html( __( 'Group ID:', 'learning-management-system' ) ); ?>
		</div>

		<div class="masteriyo-invoice-body--form-data__content" style="float: right; color: #383838; font-size: 14px; font-weight: 400; line-height: 24px;">
			<?php echo esc_html( sprintf( '#%d', $group_info['id'] ) ); ?>
		</div>
	</div>

	<div class="masteriyo-invoice-body--form-data" style="margin-bottom: 10px;">
		<div style="float: left; width: 180px; color: #222222; font-size: 14px; font-weight: 500; line-height: 24px;">
			<?php echo esc_html( __( 'Group Status:', 'learning-management-system' ) ); ?>
		</div>

		<div class="masteriyo-invoice-body--form-data__content" style="float: right; color: #383838; font-size: 14px; font-weight: 400; line-height: 24px;">
			<?php
			// Display user-friendly status
			if ( 'publish' === $group_info['status'] ) {
				echo esc_html( __( 'Active', 'learning-management-system' ) );
			} else {
				echo esc_html( __( 'Pending', 'learning-management-system' ) );
			}
			?>
		</div>
	</div>

	<div class="masteriyo-invoice-body--form-data" style="margin-bottom: 10px;">
		<div style="float: left; width: 180px; color: #222222; font-size: 14px; font-weight: 500; line-height: 24px;">
			<?php echo esc_html( __( 'Total Members:', 'learning-management-system' ) ); ?>
		</div>

		<div class="masteriyo-invoice-body--form-data__content" style="float: right; color: #383838; font-size: 14px; font-weight: 400; line-height: 24px;">
			<?php echo esc_html( sprintf( '%d', $group_info['member_count'] ) ); ?>
		</div>
	</div>
</div>
