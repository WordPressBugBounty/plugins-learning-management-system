<?php
/**
 * Ability contract.
 *
 * @package Masteriyo\Abilities\Contracts
 */

namespace Masteriyo\Abilities\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Contract that every ability must satisfy.
 */
interface AbilityInterface {

	/**
	 * Namespaced slug, e.g. "masteriyo/course-create".
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Human-readable label.
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * MCP-discoverable description of what this ability does.
	 *
	 * @return string
	 */
	public function get_description(): string;

	/**
	 * Category slug (used to group abilities in the registry).
	 *
	 * @return string
	 */
	public function get_category(): string;

	/**
	 * JSON Schema for the input the ability accepts.
	 *
	 * @return array
	 */
	public function get_input_schema(): array;

	/**
	 * JSON Schema for the value the ability returns on success.
	 *
	 * @return array
	 */
	public function get_output_schema(): array;

	/**
	 * Metadata: show_in_rest, annotations (readOnlyHint, destructiveHint, idempotentHint).
	 *
	 * @return array
	 */
	public function get_meta(): array;

	/**
	 * Whether this ability only reads data (no side effects).
	 *
	 * @return bool
	 */
	public function is_readonly(): bool;

	/**
	 * Whether this ability may cause irreversible data loss.
	 *
	 * @return bool
	 */
	public function is_destructive(): bool;

	/**
	 * Whether repeated invocations with the same input have no additional effect.
	 *
	 * @return bool
	 */
	public function is_idempotent(): bool;

	/**
	 * Whether this ability interacts with external systems (payment gateways, email, etc.).
	 *
	 * @return bool
	 */
	public function is_open_world(): bool;

	/**
	 * Whether this ability should be exposed via MCP / the WordPress Abilities REST endpoint.
	 * Site admins can override per-ability via the masteriyo_ability_mcp_public filter.
	 *
	 * @return bool
	 */
	public function is_mcp_public(): bool;

	/**
	 * Callable that returns bool; receives the same $input as execute_callback.
	 *
	 * @return callable
	 */
	public function get_permission_callback(): callable;

	/**
	 * Callable that executes the ability; receives validated $input.
	 *
	 * @return callable
	 */
	public function get_execute_callback(): callable;
}
