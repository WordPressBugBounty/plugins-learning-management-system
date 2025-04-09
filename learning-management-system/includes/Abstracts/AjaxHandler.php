<?php
/**
 * Abstract ajax handler class.
 *
 * @since 1.4.3
 * @package Masteriyo\Abstracts
 */

namespace Masteriyo\Abstracts;

defined( 'ABSPATH' ) || exit;


/**
 * Abstract ajax handler class.
 */
abstract class AjaxHandler {

	/**
	 * Register ajax handlers.
	 *
	 * @since 1.4.3
	 */
	abstract public function register();
}
