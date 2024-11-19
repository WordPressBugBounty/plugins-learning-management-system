<?php
/**
 * Register User shortcode.
 *
 * @since 1.14.0
 * @class RegisterUserShortcode
 * @package Masteriyo\Shortcodes
 */

namespace Masteriyo\Shortcodes;

use Masteriyo\Abstracts\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Register User shortcode.
 */
class RegisterUserShortcode extends Shortcode {

	/**
	 * Shortcode tag.
	 *
	 * @since 1.14.0
	 *
	 * @var string
	 */
	protected $tag = 'masteriyo_student_registration';

	/**
	 * Get shortcode content.
	 *
	 * @since  1.14.0
	 *
	 * @return string
	 */
	public function get_content() {
		$template_path = $this->get_template_path();

		/**
		 * Render the template.
		 */

		if ( ! is_user_logged_in() ) {
			return $this->get_rendered_html(
				array_merge(
					$this->get_attributes(),
					$this->get_template_args()
				),
				$template_path
			);
		}
	}

	/**
	 * Get template path to render.
	 *
	 * @since  1.14.0
	 *
	 * @return string
	 */
	protected function get_template_path() {

		return masteriyo( 'template' )->locate( 'account/form-signup.php' );

	}
}
