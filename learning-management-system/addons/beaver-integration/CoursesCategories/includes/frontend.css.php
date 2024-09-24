<?php
/**
 * Render css in Frontend based on the setting values for course categories element
 *
 * contains the css rules which will use to style the elements
 *
 * @since 1.10.0
 */



FLBuilderCSS::border_field_rule(
	array(
		'settings'     => $settings,
		'setting_name' => 'card_border',
		'selector'     => ".fl-node-$id .masteriyo-course--card",
		'unit'         => 'px',
	)
);


?>
<?php
/**
 * Render the units data
 */
?>
.fl-node-<?php echo $id; ?> .masteriyo-col {
	padding-top: <?php echo $settings->categories_columns_gap_top . 'px' . '!important'; ?>;
	padding-bottom: <?php echo $settings->categories_columns_gap_bottom . 'px' . '!important'; ?>;
	padding-left: <?php echo $settings->categories_columns_gap_left . 'px' . '!important'; ?>;
	padding-right: <?php echo $settings->categories_columns_gap_right . 'px' . '!important'; ?>;
}
