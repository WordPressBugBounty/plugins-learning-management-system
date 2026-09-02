<?php
/**
 * Dismissible hint telling settings managers the single course page has
 * multiple layouts. Never rendered for students or guests.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/layout-hint.php.
 *
 * Template variables:
 *   $settings_url (string) Deep link to the Single Course Page display settings.
 *
 * @package Masteriyo\Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/** @var string $settings_url */
$settings_url = isset( $settings_url ) ? $settings_url : '';
?>
<div class="masteriyo-layout-hint" role="note">
	<svg class="masteriyo-layout-hint__icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
		<circle cx="12" cy="12" r="10" />
		<line x1="12" y1="8" x2="12" y2="12" />
		<line x1="12" y1="16" x2="12.01" y2="16" />
	</svg>
	<span class="masteriyo-layout-hint__text">
		<?php esc_html_e( 'Did you know? This course page has multiple layouts to choose from.', 'learning-management-system' ); ?>
	</span>
	<a class="masteriyo-layout-hint__link" href="<?php echo esc_url( $settings_url ); ?>">
		<?php esc_html_e( 'Explore layouts', 'learning-management-system' ); ?>
	</a>
	<button type="button" class="masteriyo-layout-hint__dismiss" aria-label="<?php esc_attr_e( 'Dismiss this notice', 'learning-management-system' ); ?>">&times;</button>
</div>
