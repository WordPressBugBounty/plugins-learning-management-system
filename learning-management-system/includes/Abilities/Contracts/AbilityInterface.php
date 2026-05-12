<?php
/**
 * Ability contract.
 *
 * @package Masteriyo\Abilities\Contracts
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Contract that every ability must satisfy.
 *
 * @since x.x.x
 */
interface AbilityInterface {

	/**
	 * Namespaced slug, e.g. "masteriyo/course-create".
	 *
	 * @since x.x.x
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Human-readable label.
	 *
	 * @since x.x.x
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * MCP-discoverable description of what this ability does.
	 *
	 * @since x.x.x
	 * @return string
	 */
	public function get_description(): string;

	/**
	 * Category slug (used to group abilities in the registry).
	 *
	 * @since x.x.x
	 * @return string
	 */
	public function get_category(): string;

	/**
	 * JSON Schema for the input the ability accepts.
	 *
	 * @since x.x.x
	 * @return array
	 */
	public function get_input_schema(): array;

	/**
	 * JSON Schema for the value the ability returns on success.
	 *
	 * @since x.x.x
	 * @return array
	 */
	public function get_output_schema(): array;

	/**
	 * Metadata: show_in_rest, annotations (readOnlyHint, destructiveHint, idempotentHint).
	 *
	 * @since x.x.x
	 * @return array
	 */
	public function get_meta(): array;

	/**
	 * Whether this ability only reads data (no side effects).
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function is_readonly(): bool;

	/**
	 * Whether this ability may cause irreversible data loss.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function is_destructive(): bool;

	/**
	 * Whether repeated invocations with the same input have no additional effect.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function is_idempotent(): bool;

	/**
	 * Whether this ability interacts with external systems (payment gateways, email, etc.).
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function is_open_world(): bool;

	/**
	 * Whether this ability should be exposed via MCP / the WordPress Abilities REST endpoint.
	 * Site admins can override per-ability via the masteriyo_ability_mcp_public filter.
	 *
	 * @since x.x.x
	 * @return bool
	 */
	public function is_mcp_public(): bool;

	/**
	 * Callable that returns bool; receives the same $input as execute_callback.
	 *
	 * @since x.x.x
	 * @return callable
	 */
	public function get_permission_callback(): callable;

	/**
	 * Callable that executes the ability; receives validated $input.
	 *
	 * @since x.x.x
	 * @return callable
	 */
	public function get_execute_callback(): callable;
}
