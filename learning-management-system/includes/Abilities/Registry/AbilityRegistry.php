<?php
/**
 * Ability registry.
 *
 * Collects all ability instances before registration, exposes a filter for
 * Pro addons to append their own abilities, and freezes after registration.
 *
 * @package Masteriyo\Abilities\Registry
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Registry;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Contracts\AbilityInterface;

/**
 * Collection of registered abilities.
 *
 * @since x.x.x
 */
class AbilityRegistry {

	/**
	 * Registered ability instances keyed by their namespaced name.
	 *
	 * @since x.x.x
	 * @var AbilityInterface[]
	 */
	private $abilities = array();

	/**
	 * Whether the registry has been frozen (i.e. registration is complete).
	 *
	 * @since x.x.x
	 * @var bool
	 */
	private $frozen = false;

	/**
	 * Add an ability to the registry.
	 *
	 * Emits an E_USER_WARNING (visible when WP_DEBUG is true) if called after freeze,
	 * giving addon developers an actionable signal rather than a silent no-op.
	 *
	 * @since x.x.x
	 *
	 * @param AbilityInterface $ability The ability instance to register.
	 * @return void
	 */
	public function add( AbilityInterface $ability ): void {
		if ( $this->frozen ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error(
					sprintf(
						'Masteriyo Abilities: tried to register "%s" after the registry was frozen. Hook into masteriyo_register_abilities instead.',
						esc_html( $ability->get_name() )
					),
					E_USER_WARNING
				);
			}
			return;
		}

		$this->abilities[ $ability->get_name() ] = $ability;
	}

	/**
	 * Return all registered abilities.
	 *
	 * @since x.x.x
	 * @return AbilityInterface[]
	 */
	public function all(): array {
		return $this->abilities;
	}

	/**
	 * Check if the registry contains an ability by name.
	 *
	 * @since x.x.x
	 * @param string $name The ability slug to look up.
	 * @return bool
	 */
	public function has( string $name ): bool {
		return isset( $this->abilities[ $name ] );
	}

	/**
	 * Count of registered abilities.
	 *
	 * @since x.x.x
	 * @return int
	 */
	public function count(): int {
		return count( $this->abilities );
	}

	/**
	 * Prevent further additions.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function freeze(): void {
		$this->frozen = true;
	}

	/**
	 * Whether the registry has been frozen.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function is_frozen(): bool {
		return $this->frozen;
	}
}
