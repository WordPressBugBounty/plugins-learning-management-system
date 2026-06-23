<?php
/**
 * Base class for Masteriyo single-course Elementor widgets.
 *
 * @package Masteriyo\Addons\ElementorIntegration
 *
 * @since x.x.x
 */

namespace Masteriyo\Addons\ElementorIntegration;

use Masteriyo\Addons\ElementorIntegration\DocumentTypes\SingleCoursePageDocumentType;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for Masteriyo single-course Elementor widgets.
 *
 * Widgets that extend this class are grouped under the "Masteriyo - Single Course"
 * category and are only visible in the panel when editing a Single Course page template.
 *
 * @package Masteriyo\Addons\ElementorIntegration
 *
 * @since x.x.x
 */
abstract class SingleCourseWidgetBase extends WidgetBase {

	/**
	 * Get widget categories.
	 *
	 * @since x.x.x
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'masteriyo-single-course' );
	}

	/**
	 * Whether to show the widget in the Elementor panel.
	 *
	 * Returns true only when editing a Masteriyo Single Course page template
	 * so these widgets don't clutter the panel on archive or general pages.
	 *
	 * @since x.x.x
	 *
	 * @return bool
	 */
	public function show_in_panel() {
		$document = \Elementor\Plugin::$instance->documents->get_current();

		if ( ! $document ) {
			return false;
		}

		return SingleCoursePageDocumentType::TYPE_SLUG === $document->get_name();
	}
}
