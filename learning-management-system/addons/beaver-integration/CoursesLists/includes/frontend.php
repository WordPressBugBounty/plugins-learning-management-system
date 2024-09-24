<?php
/**
 * Course List FrontEnd Page
 *
 * @since 1.10.0
 */


use Masteriyo\Addons\BeaverIntegration\Helper;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Taxonomy\Taxonomy;


$setting_frontend = wp_parse_args(
	array(
		'per_page'               => $settings->courses_per_page ?? 12,
		'columns'                => $settings->courses_columns ?? 3,
		'order'                  => $settings->courses_order ?? 'ASC',
		'order_by'               => $settings->courses_order_by ?? 'DESC',
		'include_category_ids'   => '',
		'exclude_category_ids'   => '',
		'include_instructor_ids' => '',
		'exclude_instructor_ids' => '',
	)
);
$course           = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;
$limit            = max( absint( $setting_frontend['per_page'] ), 1 );

$tax_query = array(
	'relation' => 'AND',
);

if ( ! empty( $setting_frontend['include_category_ids'] ) ) {
	$ids = array_values( $setting_frontend['include_category_ids'] );

	$tax_query[] = array(
		'taxonomy' => Taxonomy::COURSE_CATEGORY,
		'terms'    => $ids,
		'field'    => 'term_id',
		'operator' => 'IN',
	);
}

if ( ! empty( $setting_frontend['exclude_category_ids'] ) ) {
	$ids = array_values( $setting_frontend['exclude_category_ids'] );

	$tax_query[] = array(
		'taxonomy' => Taxonomy::COURSE_CATEGORY,
		'terms'    => $ids,
		'field'    => 'term_id',
		'operator' => 'NOT IN',
	);
}
$current_url  = $_SERVER['REQUEST_URI'];
$current_page = Helper::get_page_from_url( $current_url ); // Retrieve the current page number from the URL query parameter.

$args = array(
	'post_type'      => PostType::COURSE,
	'status'         => array( PostStatus::PUBLISH ),
	'posts_per_page' => $limit,
	'order'          => 'DESC',
	'orderby'        => 'date',
	'tax_query'      => $tax_query,
	'page'           => absint( $current_page ), // Add the current page number to the query args.
	'pagination'     => true,
	'page'           => absint( $current_page ), // Add the current page number to the query args.
	'offset'         => ( absint( $current_page ) - 1 ) * absint( $limit ),
);

if ( ! empty( $setting_frontend['include_instructor_ids'] ) ) {
	$ids                = array_values( $setting_frontend['include_instructor_ids'] );
	$args['author__in'] = $ids;
}

if ( ! empty( $setting_frontend['exclude_instructor_ids'] ) ) {
	$ids                    = array_values( $setting_frontend['exclude_instructor_ids'] );
	$args['author__not_in'] = $ids;
}

$orders = strtoupper( $setting_frontend['order'] );

switch ( $setting_frontend['order_by'] ) {
	case 'date':
		$args['orderby'] = 'date';
		$args['order']   = ( 'ASC' === $orders ) ? 'ASC' : 'DESC';
		break;

	case 'price':
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = '_price';
		$args['order']    = ( 'DESC' === $orders ) ? 'DESC' : 'ASC';
		break;

	case 'title':
		$args['orderby'] = 'title';
		$args['order']   = ( 'DESC' === $orders ) ? 'DESC' : 'ASC';
		break;

	case 'rating':
		$args['orderby']  = 'meta_value_num';
		$args['meta_key'] = '_average_rating';
		$args['order']    = ( 'ASC' === $orders ) ? 'ASC' : 'DESC';
		break;

	default:
		$args['orderby'] = 'date';
		$args['order']   = ( 'ASC' === $orders ) ? 'ASC' : 'DESC';
		break;
}

$courses_query = new \WP_Query( $args );
$courses       = array_filter( array_map( 'masteriyo_get_course', $courses_query->posts ) );
$count         = count( $courses );
$columns       = $count > 2 ? max( absint( $setting_frontend['columns'] ), 1 ) : 2;
$node_id       = "fl-node-$id";

if ( isset( $settings->id ) && ! empty( $settings->id ) ) {
	$node_id = $settings->id;
}


echo '<div class="' . esc_attr( $node_id ) . '">';
?>
<div class="<?php echo ( 1 === $count ) ? '' : 'masteriyo'; ?>"> 
<?php
masteriyo_set_loop_prop( 'columns', $columns );
if ( count( $courses ) > 0 ) {
	$original_course = isset( $GLOBALS['course'] ) ? $GLOBALS['course'] : null;

	masteriyo_course_loop_start();

	foreach ( $courses as $course ) {
		$GLOBALS['course'] = $course;

		masteriyo_get_template(
			'content-course.php'
		);
	}
	$GLOBALS['course'] = $original_course;
	masteriyo_course_loop_end();
	masteriyo_reset_loop();
}

echo wp_kses(
	paginate_links(
		array(
			'type'      => 'list',
			'prev_text' => masteriyo_get_svg( 'left-arrow' ),
			'next_text' => masteriyo_get_svg( 'right-arrow' ),
			'total'     => $courses_query->max_num_pages,
			'current'   => $current_page,
		)
	),
	'masteriyo_pagination'
);
echo '</div>';
echo '</div>';
