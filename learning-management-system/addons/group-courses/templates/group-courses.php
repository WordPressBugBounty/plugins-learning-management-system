<?php

/**
 * The Template for displaying group courses info in the checkout form.
 *
 * @version 1.9.0
 */
?>
<div class="masteriyo-group-course__checkout">
	<h2 class="masteriyo-group-course__checkout-title">
		<?php esc_html_e( 'Group Details', 'learning-management-system' ); ?>
	</h2>

	<div class="masteriyo-group-course__checkout-table">
	<table>
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Groups', 'learning-management-system' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Members', 'learning-management-system' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $groups ) ) : ?>
				<?php foreach ( $groups as $group ) : ?>
					<?php
					$group_title = $group->get_title();
					$group_count = count( $group->get_emails() );
					?>
					<tr>
						<td><?php echo esc_html( $group_title ); ?></td>
						<td><?php echo esc_html( $group_count ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="2"><?php esc_html_e( 'No groups found.', 'learning-management-system' ); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>

</div>
<?php
