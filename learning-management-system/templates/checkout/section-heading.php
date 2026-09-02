<?php

defined( 'ABSPATH' ) || exit;

/**
 * The Template for displaying a section heading in the checkout form.
 *
 * Headings are rendered as single elements hooked into `masteriyo_checkout_form_content`,
 * never as a wrapper opened by one callback and closed by another — so a third party that
 * removes, reorders or replaces one cannot leave the form's markup unbalanced.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/checkout/section-heading.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.0.0
 */

// `$title` is a WordPress global, so the heading text arrives as `$heading`.
$slug    = isset( $slug ) ? $slug : '';
$heading = isset( $heading ) ? $heading : '';
?>

<h3 class="masteriyo-checkout-section--title masteriyo-checkout-section--title-<?php echo esc_attr( $slug ); ?>">
	<?php echo esc_html( $heading ); ?>
</h3>
<?php
