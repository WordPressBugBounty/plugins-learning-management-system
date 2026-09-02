<?php
/**
 * Abstract ability base.
 *
 * @package Masteriyo\Abilities\Support
 */

namespace Masteriyo\Abilities\Support;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Contracts\AbilityInterface;

/**
 * Base implementation for all Masteriyo abilities.
 *
 * Concrete subclasses must implement: get_name(), get_label(), get_description(),
 * get_input_schema(), get_output_schema(), get_permission_callback(), get_execute_callback().
 */
abstract class AbstractAbility implements AbilityInterface {

	/**
	 * {@inheritdoc}
	 */
	public function get_category(): string {
		return 'masteriyo-lms';
	}

	/**
	 * Whether this ability only reads data (no side effects).
	 *
	 * @return bool
	 */
	public function is_readonly(): bool {
		return false;
	}

	/**
	 * Whether this ability may cause irreversible data loss.
	 *
	 * @return bool
	 */
	public function is_destructive(): bool {
		return false;
	}

	/**
	 * Whether repeated invocations with the same input have no additional effect.
	 *
	 * @return bool
	 */
	public function is_idempotent(): bool {
		return false;
	}

	/**
	 * Whether this ability interacts with external systems (payment gateways, email, etc.).
	 * Override to true for abilities that touch open-world external entities.
	 *
	 * @return bool
	 */
	public function is_open_world(): bool {
		return false;
	}

	/**
	 * {@inheritdoc}
	 * Defaults to true. Override to false on sensitive abilities that should not be
	 * exposed by default (payment settings, hard-delete, etc.). Site admins can
	 * further override via the masteriyo_ability_mcp_public filter.
	 */
	public function is_mcp_public(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_meta(): array {
		return array(
			'show_in_rest' => true,
			'annotations'  => array(
				'readOnlyHint'    => $this->is_readonly(),
				'destructiveHint' => $this->is_destructive(),
				'idempotentHint'  => $this->is_idempotent(),
				'openWorldHint'   => $this->is_open_world(),
			),
			'mcp'          => array(
				'public' => $this->is_mcp_public(),
				'type'   => 'tool',
			),
		);
	}
}
