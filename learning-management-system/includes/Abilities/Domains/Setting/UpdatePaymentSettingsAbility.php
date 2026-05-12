<?php
/**
 * Update Payment Settings ability.
 *
 * @package Masteriyo\Abilities\Domains\Setting
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Setting;

defined( 'ABSPATH' ) || exit;

/**
 * Ability: write Masteriyo payment settings (gateway keys, currency, etc.).
 *
 * Requires manage_options in addition to manage_masteriyo_settings because
 * payment gateway credentials are site-critical and admin-only.
 * Not exposed via MCP by default — opt-in via masteriyo_ability_mcp_public filter.
 *
 * @since x.x.x
 */
class UpdatePaymentSettingsAbility extends AbstractScopedSettingsAbility {

	/** {@inheritdoc} */
	protected function settings_section(): string {
		return 'payments';
	}

	/**
	 * {@inheritdoc}
	 * Payment settings require manage_options on top of the controller's own check.
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
		return 'masteriyo/settings-update-payments';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Update Payment Settings', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Write Masteriyo payment gateway settings. Requires administrator privileges. Accepts a partial payments settings object; only provided keys are updated.', 'learning-management-system' );
	}
}
