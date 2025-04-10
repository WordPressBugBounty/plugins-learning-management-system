<?php
/**
 * Compatibility with Lite speed cache plugin.
 *
 * @since 1.5.36
 */

namespace Masteriyo\Compatibility\Cache;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Abstracts\CachePluginCompatibility;

class LiteSpeed extends CachePluginCompatibility {
	/**
	 * Cache plugin slug.
	 *
	 * @since 1.5.36
	 *
	 * @var string
	 */
	protected $plugin = 'litespeed-cache/litespeed-cache.php';

	/**
	 * Do not page.
	 *
	 * @since 1.5.36
	 */
	public function do_not_cache() {
		masteriyo_maybe_define_constant( 'DONOTCACHEPAGE', true );
	}
}
