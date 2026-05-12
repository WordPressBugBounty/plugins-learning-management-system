<?php
/**
 * Ability registrar.
 *
 * Subscribes to the WordPress Abilities API hooks, exposes extensibility filters,
 * and calls wp_register_ability() for every ability in the registry.
 *
 * @package Masteriyo\Abilities\Registry
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Registry;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Contracts\AbilityInterface;

/**
 * Orchestrates ability category and ability registration with WordPress.
 *
 * @since x.x.x
 */
class AbilityRegistrar {

	/**
	 * The ability registry.
	 *
	 * @since x.x.x
	 * @var AbilityRegistry
	 */
	private $registry;

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 * @param AbilityRegistry $registry The populated ability registry.
	 */
	public function __construct( AbilityRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Attach WP hooks.
	 * Called from AbilitiesServiceProvider::boot().
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function init(): void {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_all' ) );
	}

	/**
	 * Register the 'masteriyo-lms' ability category.
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'masteriyo-lms',
			array(
				'label'       => __( 'Masteriyo LMS', 'learning-management-system' ),
				'description' => __( 'Abilities for Masteriyo LMS course authoring and management.', 'learning-management-system' ),
			)
		);
	}

	/**
	 * Register all abilities with the WordPress Abilities API.
	 *
	 * Fires on wp_abilities_api_init — at this point all REST controllers
	 * are bound in the DI container. Addons may append abilities via:
	 *   do_action( 'masteriyo_register_abilities', $registry )
	 *   apply_filters( 'masteriyo_abilities', $registry )
	 *
	 * @since x.x.x
	 * @return void
	 */
	public function register_all(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		/**
		 * Action: let addons append abilities before the registry is frozen.
		 *
		 * @since x.x.x
		 * @param AbilityRegistry $registry
		 */
		do_action( 'masteriyo_register_abilities', $this->registry );

		/**
		 * Filter: mutate the registry (add, remove, replace abilities).
		 *
		 * @since x.x.x
		 * @param AbilityRegistry $registry
		 */
		$this->registry = apply_filters( 'masteriyo_abilities', $this->registry );

		$this->registry->freeze();

		foreach ( $this->registry->all() as $ability ) {
			/**
			 * Filter: control per-ability MCP / REST exposure.
			 * Return false to prevent an ability from being registered with WordPress.
			 *
			 * @since x.x.x
			 * @param bool   $is_public   Whether the ability should be registered (default from is_mcp_public()).
			 * @param string $ability_name The ability's namespaced slug, e.g. "masteriyo/course-list".
			 */
			$is_public = apply_filters(
				'masteriyo_ability_mcp_public',
				$ability->is_mcp_public(),
				$ability->get_name()
			);
			if ( ! $is_public ) {
				continue;
			}

			try {
				wp_register_ability(
					$ability->get_name(),
					array(
						'label'               => $ability->get_label(),
						'description'         => $ability->get_description(),
						'category'            => $ability->get_category(),
						'execute_callback'    => $ability->get_execute_callback(),
						'permission_callback' => $ability->get_permission_callback(),
						'input_schema'        => $ability->get_input_schema(),
						'output_schema'       => $ability->get_output_schema(),
						'meta'                => $ability->get_meta(),
					)
				);
			} catch ( \Throwable $e ) {
				$message = sprintf(
					'[Masteriyo Abilities] Failed to register ability "%s": %s (in %s:%d)',
					$ability->get_name(),
					$e->getMessage(),
					$e->getFile(),
					$e->getLine()
				);
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
					trigger_error( esc_html( $message ), E_USER_WARNING );
				} else {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( $message );
				}
			}
		}
	}
}
