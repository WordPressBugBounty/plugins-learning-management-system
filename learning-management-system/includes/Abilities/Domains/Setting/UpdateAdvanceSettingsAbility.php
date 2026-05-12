<?php
/**
 * Update Advance Settings ability.
 *
 * @package Masteriyo\Abilities\Domains\Setting
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Setting;

defined( 'ABSPATH' ) || exit;

/**
 * Ability: write Masteriyo advanced settings (debug flags, cache, etc.).
 *
 * Requires manage_options in addition to manage_masteriyo_settings because
 * advanced flags can affect site stability and are admin-only.
 * Not exposed via MCP by default — opt-in via masteriyo_ability_mcp_public filter.
 *
 * @since x.x.x
 */
class UpdateAdvanceSettingsAbility extends AbstractScopedSettingsAbility {

	/** {@inheritdoc} */
	protected function settings_section(): string {
		return 'advance';
	}

	/**
	 * {@inheritdoc}
	 * Advanced settings require manage_options on top of the controller's own check.
	 *
	 * @param mixed $input Ability input (array or null).
	 */
	public function check_permission( $input = null ): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return parent::check_permission( $input );
	}

	/**
	 * {@inheritdoc}
	 * Not publicly exposed via MCP by default — site admin must opt-in.
	 */
	public function is_mcp_public(): bool {
		return false;
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/settings-update-advance';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Update Advanced Settings', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Write Masteriyo advanced settings (debug, cache, performance flags). Requires administrator privileges. Accepts a partial advance settings object; only provided keys are updated.', 'learning-management-system' );
	}
}
