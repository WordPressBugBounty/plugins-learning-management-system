<?php
/**
 * Registry for account page sidebar links.
 *
 * Addons register links via ::add() before the 'wp_enqueue_scripts' hook.
 * Use ::remove() to deregister a link added by another addon.
 *
 * @since x.x.x
 * @package Masteriyo\Account
 */

namespace Masteriyo\Account;

defined( 'ABSPATH' ) || exit;

/**
 * SidebarLinkRegistry class.
 *
 * @since x.x.x
 */
class SidebarLinkRegistry {

	/**
	 * Registered links keyed by ID.
	 *
	 * @since x.x.x
	 * @var array<string, array>
	 */
	private static $links = array();

	/**
	 * Register a sidebar link.
	 *
	 * @since x.x.x
	 *
	 * @param string $id   Unique slug (sanitize_key will be applied).
	 * @param array  $args {
	 *     @type string $label    Display text. Required.
	 *     @type string $url      Link destination. Required. Internal routes ('_self'): a path like
	 *                            '/courses'. External links ('_blank'): a full URL.
	 *     @type string $target   '_blank' for external, '_self' for internal route. Default '_self'.
	 *     @type string $icon     Inline SVG string or image URL. Optional.
	 *     @type int    $priority Sort order, lower = earlier. Default 10.
	 * }
	 */
	public static function add( string $id, array $args ): void {
		self::$links[ sanitize_key( $id ) ] = wp_parse_args(
			$args,
			array(
				'label'    => '',
				'url'      => '',
				'target'   => '_self',
				'icon'     => '',
				'priority' => 10,
			)
		);
	}

	/**
	 * Remove a previously registered link.
	 *
	 * @since x.x.x
	 *
	 * @param string $id Link ID.
	 */
	public static function remove( string $id ): void {
		unset( self::$links[ sanitize_key( $id ) ] );
	}

	/**
	 * Return all links sanitized and sorted, after applying the filter.
	 *
	 * @since x.x.x
	 *
	 * @return array[]
	 */
	public static function get_links(): array {
		/**
		 * Filters the registered account sidebar links before they are sanitized.
		 *
		 * Kept for backward compatibility with addons/themes that registered
		 * links via this filter before the SidebarLinkRegistry API existed.
		 *
		 * @since x.x.x
		 *
		 * @param array $links Links keyed by ID.
		 */
		$links = apply_filters( 'masteriyo_account_sidebar_common_links', self::$links );

		// The filter is a public input source, so drop any malformed (non-array) entries.
		$links = array_filter( (array) $links, 'is_array' );

		// Sort by priority.
		uasort(
			$links,
			function ( $a, $b ) {
				return ( (int) ( $a['priority'] ?? 10 ) ) - ( (int) ( $b['priority'] ?? 10 ) );
			}
		);

		// Inject the array key as 'id' so sanitize_link can read it, then drop nulls.
		$sanitized = array();
		foreach ( $links as $id => $link ) {
			$link['id'] = $link['id'] ?? $id;
			$result     = self::sanitize_link( $link );
			if ( null !== $result ) {
				$sanitized[] = $result;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize a single link definition.
	 *
	 * @since x.x.x
	 *
	 * @param array $link Raw link data (must already have 'id' set).
	 * @return array|null Sanitized link, or null if label or url is missing.
	 */
	private static function sanitize_link( array $link ): ?array {
		if ( empty( $link['label'] ) || empty( $link['url'] ) ) {
			return null;
		}

		$allowed_targets = array( '_blank', '_self' );
		$target          = $link['target'] ?? '_self';
		$target          = in_array( $target, $allowed_targets, true ) ? $target : '_self';

		$url = (string) $link['url'];
		if ( '_self' === $target ) {
			// Normalize internal route to a leading-slash path so hash routing works.
			$url = '/' . ltrim( $url, '#/' );
		}

		$icon = $link['icon'] ?? '';
		// SVG is developer-provided via a registered hook — sanitize attributes but keep SVG structure.
		// For plain image URLs, ensure it is a valid URL.
		if ( '' !== $icon ) {
			$icon = masteriyo_starts_with( ltrim( $icon ), '<' )
				? wp_kses( $icon, masteriyo_get_allowed_svg_elements() )
				: esc_url_raw( $icon );
		}

		return array(
			'id'       => sanitize_key( $link['id'] ),
			'label'    => sanitize_text_field( $link['label'] ),
			'url'      => esc_url_raw( $url ),
			'target'   => $target,
			'icon'     => $icon,
			'priority' => (int) ( $link['priority'] ?? 10 ),
		);
	}
}
