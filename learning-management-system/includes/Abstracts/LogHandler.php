<?php
/**
 * Abstract Masteriyo Log Handler Class.
 *
 * @since 1.12.2
 */

namespace Masteriyo\Abstracts;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Contracts\LogHandlerInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Masteriyo LogHandler Class.
 */
abstract class LogHandler implements LogHandlerInterface {

	/**
	 * Formats a timestamp for use in log messages.
	 *
	 * @param int $timestamp Log timestamp.
	 *
	 * @return string Formatted time for use in log entry.
	 */
	protected static function format_time( $timestamp ) {
		return date_i18n( 'c', $timestamp );
	}

	/**
	 * Builds a log entry text from level, timestamp and message.
	 *
	 * @param int    $timestamp Log timestamp.
	 * @param string $level     emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message   Log message.
	 * @param array  $context   Additional information for log handlers.
	 *
	 * @return string Formatted log entry.
	 */
	protected static function format_entry( $timestamp, $level, $message, $context ) {
		$time_string  = self::format_time( $timestamp );
		$level_string = strtoupper( $level );
		$entry        = "{$time_string} {$level_string} {$message}";
		/**
		 * Filter the format log entry.
		 *
		 * @param array $entry The entry.
		 */
		return apply_filters(
			'masteriyo_format_log_entry',
			$entry,
			array(
				'timestamp' => $timestamp,
				'level'     => $level,
				'message'   => $message,
				'context'   => $context,
			)
		);
	}
}
