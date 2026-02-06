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
use Masteriyo\CoreFeatures\CourseComingSoon\Helper;


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
		<div style="color:red;padding-left:60px;width:340px;text-wrap:wrap;white-space: normal;">
			<?php esc_html_e( 'Please ensure that only individual course elements are added inside the single course block container.', 'learning-management-system' ); ?>
		</div>
			<?php
			return \ob_get_clean();
		}

		$course            = $this->get_block_preview_course( $course_id );
		$GLOBALS['course'] = $course;

		// Enqueue group pricing tiers script for frontend
		$this->enqueue_group_pricing_script( $course_id );

		\ob_start();
		?>
	<style>
		<?php echo $block_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</style>
		<?php

		printf(
			'<div class="masteriyo-block masteriyo-group-price-button-block--%s" style="white-space: nowrap;" >',
			esc_attr( $client_id )
		);

		$group_price   = get_post_meta( $course->get_id(), '_group_courses_group_price', true );
		$is_block_page = $this->is_block_editor();

		// Get pricing tiers (new format)
		$pricing_tiers_json = get_post_meta( $course->get_id(), '_group_courses_pricing_tiers', true );
		$pricing_tiers      = ! empty( $pricing_tiers_json ) ? json_decode( $pricing_tiers_json, true ) : array();
		$max_group_size     = get_post_meta( $course->get_id(), '_group_courses_max_group_size', true );

		// If no pricing tiers exist but legacy data exists, migrate it
		if ( empty( $pricing_tiers ) ) {
			// If legacy data exists, create a default tier
			if ( ! empty( $group_price ) || ! empty( $max_group_size ) ) {
				$pricing_tiers = array(
					array(
						'id'            => 'tier_1',
						'seat_model'    => 'fixed',
						'group_name'    => __( 'Group', 'learning-management-system' ),
						'group_size'    => ! empty( $max_group_size ) ? intval( $max_group_size ) : 0,
						'pricing_type'  => 'one_time',
						'regular_price' => ! empty( $group_price ) ? $group_price : '',
						'sale_price'    => '',
					),
				);
			}
		}

		if ( empty( $pricing_tiers ) && $is_block_page ) {
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

		// Check if course coming soon is active and enrollment is not yet available
		$is_coming_soon      = false;
		$coming_soon_enabled = get_post_meta( $course->get_id(), '_course_coming_soon_enable', true );
		if ( masteriyo_string_to_bool( $coming_soon_enabled ) ) {
			// Check if the enrollment time has been reached
			$coming_soon_satisfied = Helper::course_coming_soon_satisfied( $course );
			if ( ! $coming_soon_satisfied ) {
				$is_coming_soon = true;
			}
		}

		$group_price = floatval( get_post_meta( $course->get_id(), '_group_courses_group_price', true ) );

		if ( empty( $pricing_tiers ) && ! $is_block_page ) {
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
				'group_price'    => masteriyo_price( $group_price, array( 'currency' => $currency ) ),
				'course_id'      => $course->get_id(),
				'course'         => $course,
				'max_group_size' => $max_group_size,
				'pricing_tiers'  => $pricing_tiers,
				'currency'       => $currency,
				'is_coming_soon' => $is_coming_soon,
			)
		);

		echo '</div>';

		return \ob_get_clean();
	}

	/**
	 * Enqueue group pricing tiers script for the block.
	 *
	 * @since 2.1.0
	 *
	 * @param int $course_id Course ID.
	 * @return void
	 */
	private function enqueue_group_pricing_script( $course_id ) {
		// Only enqueue on frontend, not in block editor
		if ( $this->is_block_editor() ) {
			return;
		}

		// Use minified version in production
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Enqueue the JavaScript file
		wp_enqueue_script(
			'masteriyo-group-pricing-tiers',
			plugin_dir_url( MASTERIYO_PLUGIN_FILE ) . 'addons/group-courses/assets/js/group-pricing-tiers' . $suffix . '.js',
			array( 'jquery' ),
			MASTERIYO_VERSION,
			true
		);

		// Localize script with necessary data
		wp_localize_script(
			'masteriyo-group-pricing-tiers',
			'masteriyoGroupPricing',
			array(
				'courseId' => $course_id,
				'urls'     => array(
					'checkout' => masteriyo_get_page_permalink( 'checkout' ),
				),
				'currency' => array(
					'symbol'   => masteriyo_get_currency_symbol(),
					'position' => masteriyo_get_setting( 'payments.currency.currency_position' ),
					'decimals' => masteriyo_get_setting( 'payments.currency.number_of_decimals' ),
				),
			)
		);
	}
}
