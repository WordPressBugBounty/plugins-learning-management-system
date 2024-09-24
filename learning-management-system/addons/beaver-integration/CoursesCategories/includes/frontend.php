<?php
/**
 * Renders the Course Categories element in the frontend.
 *
 * @since 1.10.0 [Free]
 */


use Masteriyo\Addons\BeaverIntegration\Helper;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Taxonomy\Taxonomy;

$setting_frontend = wp_parse_args(
	array(
		'per_page'               => $settings->categories_per_page ?? 12,
		'columns'                => $settings->categories_columns ?? 3,
		'order'                  => $settings->categories_order ?? 'ASC',
		'order_by'               => $settings->categories_order_by ?? 'DESC',
		'include_sub_categories' => $settings->categories_include_sub_categories ?? 'yes',
		'show_courses_count'     => $settings->categories_show_courses_count ?? 'yes',
	)
);

$current_url  = $_SERVER['REQUEST_URI'];
$current_page = Helper::get_page_from_url( $current_url ); // Retrieve the current page number from the URL query parameter.

$limit                  = max( absint( $setting_frontend['per_page'] ), 1 );
$columns                = max( absint( $setting_frontend['columns'] ), 1 );
$attrs                  = array();
$include_sub_categories = ! empty( $setting_frontend['include_sub_categories'] ) && 'yes' === $setting_frontend['include_sub_categories'];
$hide_courses_count     = ! ( empty( $setting_frontend['show_courses_count'] ) || 'yes' === $setting_frontend['show_courses_count'] );
$args                   = array(
	'taxonomy'   => Taxonomy::COURSE_CATEGORY,
	'order'      => masteriyo_array_get( $setting_frontend, 'order', 'ASC' ),
	'orderby'    => masteriyo_array_get( $setting_frontend, 'order_by', 'name' ),
	'number'     => $limit,
	'hide_empty' => false,
	'pagination' => true,
	'page'       => absint( $current_page ), // Add the current page number to the query args.
	'offset'     => ( absint( $current_page ) - 1 ) * absint( $limit ),
);
// Get the total count
$total_count = wp_count_terms( Taxonomy::COURSE_CATEGORY );

// Get intended posts per page (adjust how this is derived if needed)
$posts_per_page = $setting_frontend['per_page'];

// Calculate max pages
$max_num_pages = ceil( $total_count / $posts_per_page );


if ( ! masteriyo_string_to_bool( $include_sub_categories ) ) {
	$args['parent'] = 0;
}

$query      = new \WP_Term_Query();
$result     = $query->query( $args );
$categories = array_filter( array_map( 'masteriyo_get_course_cat', $result ) );

$attrs['count']                  = $limit;
$attrs['columns']                = $columns;
$attrs['categories']             = $categories;
$attrs['hide_courses_count']     = $hide_courses_count;
$attrs['include_sub_categories'] = $include_sub_categories;


$node_id = "fl-node-$id";

if ( isset( $settings->id ) && ! empty( $settings->id ) ) {
	$node_id = $settings->id;
}


echo '<div class="' . esc_attr( $node_id ) . '">';
echo '<div class="masteriyo">';
masteriyo_get_template( 'shortcodes/course-categories/list.php', $attrs );
echo wp_kses(
	paginate_links(
		array(
			'type'      => 'list',
			'prev_text' => masteriyo_get_svg( 'left-arrow' ),
			'next_text' => masteriyo_get_svg( 'right-arrow' ),
			'total'     => $max_num_pages,
			'current'   => $current_page,
		)
	),
	'masteriyo_pagination'
);
echo '</div>';
echo '</div>';


