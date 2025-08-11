<?php

/**
 * Course categories block class.
 *
 * @since 1.20.0
 * @package Masteriyo\Blocks
 */

namespace Masteriyo\Blocks;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abstracts\BlockHandler;


/**
 * Class GroupPriceButton
 *
 * Displays a list/grid of course categories.
 *
 * @since 1.20.0
 */
class GroupPriceButton extends BlockHandler {

	/**
	 * Block name.
	 *
	 * @since 1.20.0
	 * @var string
	 */
	protected $block_name = 'group-price-button';

	/**
	 * Build HTML output for the block.
	 *
	 * @since 1.20.0
	 *
	 * @param string $content Not used. The original block content.
	 * @return string Rendered HTML output.
	 */
	protected function build_html( $content ) {
		$attr      = $this->attributes;
		$block_css = $attr['blockCSS'] ?? '';
		$course_id = $attr['courseId'] ?? 0;
		$client_id = esc_attr( $attr['clientId'] ?? 0 );

		if ( ! $course_id ) {
			\ob_start();
			?>
		<div style="color:red;padding-left:60px;">
			<?php esc_html_e( 'Please ensure that only individual course elements are added inside the single course block container.', 'learning-management-system' ); ?>
		</div>
			<?php
			return \ob_get_clean();
		}

		$course            = $this->get_block_preview_course( $course_id );
		$GLOBALS['course'] = $course;

		\ob_start();
		?>
	<style>
		<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</style>
		<?php

		printf(
			'<div class="masteriyo-block masteriyo-group-price-button-block--%s">',
			esc_attr( $client_id )
		);

		$group_price   = get_post_meta( $course->get_id(), '_group_courses_group_price', true );
		$is_block_page = $this->is_block_editor();
		if ( ! $group_price && $is_block_page ) {
			remove_action( 'masteriyo_template_group_btn', array( $this, 'get_group_btn_template' ), 20, 1 );
			echo '<div style="color:red;padding-left:60px;">';
			esc_html_e( 'Please select the course with group price.', 'learning-management-system' );
			echo '</div>';
			echo '</div>';
			return \ob_get_clean();
		}

		if ( masteriyo_is_courses_page() ) {
			echo '</div>';
			return \ob_get_clean();
		}

		if ( ! $course->is_purchasable() || ! $course->get_price() ) {
			echo '</div>';
			return \ob_get_clean();
		}

		$group_price = floatval( get_post_meta( $course->get_id(), '_group_courses_group_price', true ) );

		if ( ! $group_price && $is_block_page ) {
			echo '<div style="color:red;padding-left:60px;">';
			esc_html_e( 'Please select the course with group price.', 'learning-management-system' );
			echo '</div>';
			echo '</div>';
			return \ob_get_clean();
		}

		if ( ! $group_price && ! $is_block_page ) {
			return \ob_get_clean();
		}

		/**
		 * Filter the price for the group buy button.
		 *
		 * @since 1.17.1
		 *
		 * @param int    $group_price The group price for the course.
		 * @param int    $course_id   The course ID.
		 *
		 * @return int The filtered group price.
		 */
		$group_price = apply_filters( 'masteriyo_group_buy_btn_price', $group_price, $course->get_id() );

		$currency = '';

		if ( function_exists( 'masteriyo_get_currency_and_pricing_zone_based_on_course' ) ) {
			list( $currency, ) = masteriyo_get_currency_and_pricing_zone_based_on_course( $course->get_id() );
		}

		masteriyo_get_template(
			'group-courses/group-buy-btn.php',
			array(
				'group_price' => masteriyo_price( $group_price, array( 'currency' => $currency ) ),
				'course_id'   => $course->get_id(),
				'course'      => $course,
			)
		);

		echo '</div>';

		return \ob_get_clean();
	}
}
