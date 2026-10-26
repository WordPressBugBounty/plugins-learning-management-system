<?php
/**
 * Course custom field values for the single course page.
 *
 * Prints the values as a JSON island; the single-course script renders them
 * into the container. Shared by every layout that shows the course overview.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/single-course/custom-fields.php.
 *
 * Template variables:
 *   $course (\Masteriyo\Models\Course)
 *
 * @package Masteriyo\Templates
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! isset( $course ) || ! is_object( $course ) ) {
	return;
}

$course_values = $course->get_custom_fields();
$course_values = is_array( $course_values ) ? $course_values : array();

if ( empty( $course_values ) ) {
	return;
}
?>
<div id="masteriyo-custom-fields">
	<script type="application/json" id="masteriyo-course-values">
		<?php echo wp_json_encode( $course_values, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ); ?>
	</script>
	<div class="custom-fields-container"></div>
</div>
