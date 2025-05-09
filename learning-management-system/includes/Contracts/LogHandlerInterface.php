<?php
/**
 * Masteriyo Log Handler Interface
 *
 * Functions that must be defined to correctly fulfill log handler API.
 *
 * @since 1.12.2
 * @package Masteriyo\Interfaces
 */

namespace Masteriyo\Contracts;

defined( 'ABSPATH' ) || exit;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Masteriyo Log Handler Interface
 */
interface LogHandlerInterface {

	/**
	 * Handle a log entry.
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level     emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message   Log message.
	 * @param array  $context   Additional information for log handlers.
	 *
	 * @return bool False if value was not handled and true if value was handled.
	 */
	public function handle( $timestamp, $level, $message, $context );
}
