<?php

defined( 'ABSPATH' ) || exit;

/**
 * Learn page.
 */
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title><?php the_title(); ?></title>
		<?php wp_head(); ?>
	</head>

	<body <?php body_class(); ?> translate="no">
		<?php wp_body_open(); ?>
		<div id="masteriyo-interactive-course"></div>
		<?php wp_footer(); ?>
	</body>
</html>

<?php

