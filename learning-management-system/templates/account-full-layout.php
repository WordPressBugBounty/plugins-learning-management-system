<?php
/**
 * Account full page layout.
 *
 * @since 1.13.3
 */

 /**
 * Fires before rendering account page template.
 *
 * @since 1.13.3
 */
do_action( 'masteriyo_before_account' );
?>

<!DOCTYPE html>
<html lang="en" <?php echo( is_rtl() ? 'dir="rtl"' : '' ); ?> >
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title><?php the_title(); ?></title>
		<?php wp_head(); ?>
	</head>

	<body <?php body_class(); ?> translate="no">
		<div id="masteriyo-account-page"></div>
		<?php wp_footer(); ?>
	</body>
</html>

<?php
/**
 * Fires after rendering account page template.
 *
 * @since 1.13.3
 */
do_action( 'masteriyo_after_account' );
