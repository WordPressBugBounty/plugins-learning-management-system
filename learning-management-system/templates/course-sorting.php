<?php

/**
 * The template for displaying course sorting options.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/course-sorting.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.16.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="masteriyo-courses-sorting-section">
	<?php
	/**
	 * Fires before rendering course sorting section content.
	 *
	 * @since 1.16.0
	 *
	 * @param array $options
	 * @param string $sorting_order
	 */
	do_action( 'masteriyo_before_courses_sorting_section_content', $options, $sorting_order );
	?>

	<div class="masteriyo-courses-sorting">
		<form method="get" action="<?php echo esc_url( masteriyo_get_page_permalink( 'courses' ) ); ?>">
			<select class="masteriyo-courses-order-by" name="orderby">
				<option value="" disabled><?php esc_html_e( 'Sort by', 'learning-management-system' ); ?></option>
				<?php
				foreach ( $options as $option ) {
					$selected = $sort_by === $option['value'] && $sorting_order === $option['order'] ? 'selected' : '';

					printf(
						'<option value="%s" data-order="%s" %s>%s</option>',
						esc_attr( $option['value'] ),
						esc_attr( $option['order'] ),
						esc_attr( $selected ),
						esc_html( $option['label'] )
					);
				}
				?>
			</select>
			<input class="masteriyo-courses-sorting-order" type="hidden" name="order" value="<?php echo esc_attr( $sorting_order ); ?>" />
			<?php masteriyo_render_query_string_form_fields( null, array( 'orderby', 'order' ) ); ?>
		</form>
	</div>

	<?php
	/**
	 * Fires after rendering course sorting section content.
	 *
	 * @since 1.16.0
	 *
	 * @param array $options
	 * @param string $sorting_order
	 */
	do_action( 'masteriyo_after_courses_sorting_section_content', $options, $sorting_order );
	?>
</div>
<?php
