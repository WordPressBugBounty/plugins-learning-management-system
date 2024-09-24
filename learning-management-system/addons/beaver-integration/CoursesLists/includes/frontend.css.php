<?php
/**
 * Render css in Frontend based on the setting values for course list element
 *
 * contains the css rules which will use to style the elements
 *
 * @since 1.10.0
 */


// Course gap Padding
FLBuilderCSS::dimension_field_rule(
	array(
		'settings'     => $settings,
		'setting_name' => 'difficulty_badge_padding',
		'selector'     => ".fl-node-$id .difficulty-badge .masteriyo-badge",
		'unit'         => 'px',
		'props'        => array(
			'padding-top'    => 'difficulty_badge_padding_top',
			'padding-right'  => 'difficulty_badge_padding_right',
			'padding-bottom' => 'difficulty_badge_padding_bottom',
			'padding-left'   => 'difficulty_badge_padding_left',
		),
	),
	array(
		'settings'     => $settings,
		'setting_name' => 'categories_margin',
		'selector'     => ".fl-node-$id .masteriyo-course--content__category",
		'unit'         => 'px',
		'props'        => array(
			'margin-top'    => 'categories_margin_margin_top',
			'margin-right'  => 'categories_margin_margin_right',
			'margin-bottom' => 'categories_margin_margin_bottom',
			'margin-left'   => 'categories_margin_margin_left',
		),
	),
	array(
		'settings'     => $settings,
		'setting_name' => 'author_margin',
		'selector'     => ".fl-node-$id .masteriyo-course-author--name",
		'unit'         => 'px',
		'props'        => array(
			'margin-top'    => 'author_margin_top',
			'margin-right'  => 'author_margin_right',
			'margin-bottom' => 'author_margin_bottom',
			'margin-left'   => 'author_margin_left',
		),
	),
	array(
		'settings'     => $settings,
		'setting_name' => 'highlight_gap',
		'selector'     => ".fl-node-$id .masteriyo-course--content__description ul li:not(:last-child)",
		'unit'         => 'px',
		'props'        => array(
			'margin-top'    => 'highlight_gap_margin_top',
			'margin-right'  => 'highlight_gap_margin_right',
			'margin-bottom' => 'highlight_gap_margin_bottom',
			'margin-left'   => 'highlight_gap_margin_left',
		),
	),
	array(
		'settings'     => $settings,
		'setting_name' => 'button',
		'selector'     => ".fl-node-$id .masteriyo-enroll-btn",
		'unit'         => 'px',
		'props'        => array(
			'margin-top'    => 'button_margin_top',
			'margin-right'  => 'button_margin_right',
			'margin-bottom' => 'button_margin_bottom',
			'margin-left'   => 'button_margin_left',
		),
	)
);

// Label Typography
FLBuilderCSS::typography_field_rule(
	array(
		'selector'     => ".fl-node-$id .difficulty-badge .masteriyo-badge",
		'setting_name' => 'difficulty_badge_typography',
		'settings'     => $settings,
	),
	array(
		'selector'     => ".fl-node-$id .masteriyo-course--content__category .masteriyo-course--content__category-items",
		'setting_name' => 'category_typography',
		'settings'     => $settings,
	),
	array(
		'settings'     => $settings,
		'setting_name' => 'author_typography',
		'selector'     => ".fl-node-$id .masteriyo-course-author .masteriyo-course-author--name",

		// 'props'        => array(
		// 	'font-family'     => $settings->author_typography['font_family'],
		// 	'font-weight'     => $settings->author_typography['font_weight'],
		// 	'font-size'       => $settings->author_typography['font_size']['length'] . $settings->author_typography['font_size']['unit'],
		// 	'line-height'     => $settings->author_typography['line_height']['length'] . $settings->author_typography['line_height']['unit'],
		// 	'text-align-left' => $settings->author_typography['text_align'],
		// 	'letter-spacing'  => $settings->author_typography['letter_spacing']['length'] . $settings->author_typography['letter_spacing']['unit'],
		// 	'text-transform'  => $settings->author_typography['text_transform'],
		// 	'text-decoration' => $settings->author_typography['text_decoration'],
		// 	'font-style'      => $settings->author_typography['font_style'],
		// 	'text-shadow'     => $settings->author_typography['text_shadow']['color'] . $settings->author_typography['text_shadow']['horizontal'] . $settings->author_typography['text_shadow']['vertical'] . $settings->author_typography['text_shadow']['blur'],
		// ),
	),
	array(
		'selector'     => ".fl-node-$id .masteriyo-course--content__rt .masteriyo-rating",
		'setting_name' => 'rating_typography',
		'settings'     => $settings,
	),
	array(
		'selector'     => ".fl-node-$id .masteriyo-course--content__stats span",
		'setting_name' => 'metadata_typography',
		'settings'     => $settings,
	),
	array(
		'selector'     => ".fl-node-$id .masteriyo-course-price .current-amount",
		'setting_name' => 'price',
		'settings'     => $settings,
	),
	array(
		'selector'     => ".fl-node-$id .masteriyo-enroll-btn",
		'setting_name' => 'button_typography',
		'settings'     => $settings,
	)
);


/**
 * Renders the rule/properties for a box shadow field.
 */
?>


.fl-node-<?php echo $id; ?> .masteriyo-course--card {
	box-shadow: <?php echo FLBuilderColor::shadow( $settings->card_box_shadow ); ?>
	text-decoration:none
}

.fl-node-<?php echo $id; ?> .difficulty-badge .masteriyo-badge {
	box-shadow: <?php echo FLBuilderColor::shadow( $settings->difficulty_badge_border_shadow ); ?>
}


<?php
/**
 * Render the units data
 */
?>
.fl-node-<?php echo $id; ?> .masteriyo-col {
	padding-top: <?php echo $settings->courses_gap_top . 'px' . '!important'; ?>;
	padding-bottom: <?php echo $settings->courses_gap_bottom . 'px' . '!important'; ?>;
	padding-left: <?php echo $settings->courses_gap_left . 'px' . '!important'; ?>;
	padding-right: <?php echo $settings->courses_gap_right . 'px' . '!important'; ?>;
}

<?php
/**
 * Render the position data
 */
?>
.fl-node-<?php echo $id; ?> .difficulty-badge .masteriyo-badge {
	position: absolute;
	left: <?php echo $settings->difficulty_badge_horizontal_position . 'px'; ?>;
}

.fl-node-<?php echo $id; ?> .difficulty-badge .masteriyo-badge {
	position: absolute;
	top: <?php echo $settings->difficulty_badge_vertical_position . 'px'; ?>;
}

.fl-node-<?php echo $id; ?> .masteriyo-rating > svg {
	width:<?php echo $settings->rating_gap . 'px' . '!important'; ?>;
	;
}
