<?php
/**
 * Student preview floating pill — frontend footer.
 *
 * Styles are loaded via masteriyo-student-preview-banner (ScriptStyle).
 *
 * Template variables:
 *   $exit_url           (string) URL to clear the preview cookie server-side.
 *   $switcher_label     (string) 'Switch to Admin' or 'Switch to Instructor'.
 *   $button_color       (string) Brand button color from settings.
 *   $button_hover_color (string) Brand button hover color from settings.
 *
 * @since x.x.x
 * @package Masteriyo\Templates
 */

defined( 'ABSPATH' ) || exit;

/** @var string $exit_url */
/** @var string $switcher_label */
/** @var string $button_color */
/** @var string $button_hover_color */
$exit_url           = isset( $exit_url ) ? $exit_url : '';
$switcher_label     = isset( $switcher_label ) ? $switcher_label : __( 'Switch to Admin', 'learning-management-system' );
$button_color       = isset( $button_color ) ? $button_color : '#4584FF';
$button_hover_color = isset( $button_hover_color ) ? $button_hover_color : '#2B6CB0';
?>
<div id="mto-preview-pill" style="--mto-btn-color:<?php echo esc_attr( $button_color ); ?>;--mto-btn-hover-color:<?php echo esc_attr( $button_hover_color ); ?>">
	<span class="mto-preview-label"><?php esc_html_e( 'Viewing as Demo Student', 'learning-management-system' ); ?></span>
	<span class="mto-preview-divider" aria-hidden="true"></span>
	<a href="<?php echo esc_url( $exit_url ); ?>">
		<?php echo esc_html( $switcher_label ); ?>
	</a>
</div>
