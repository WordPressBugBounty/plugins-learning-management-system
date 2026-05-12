<?php
/**
 * Schema translator.
 *
 * Derives JSON Schema for ability input/output from REST controller introspection.
 * Results are cached per controller+verb within the instance lifecycle.
 *
 * @package Masteriyo\Abilities\Support
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Translates REST controller schemas to JSON Schema for the Abilities API.
 *
 * Bind as a shared singleton via masteriyo('abilities.schema_translator') so the
 * instance cache is reused across all abilities within a single request.
 *
 * @since x.x.x
 */
class SchemaTranslator {

	/**
	 * Per-instance cache: class::verb::direction => schema array.
	 *
	 * @since x.x.x
	 * @var array
	 */
	private $cache = array();

	/**
	 * REST-internal keys stripped from every field before exposing to the AI.
	 *
	 * @since x.x.x
	 * @var string[]
	 */
	private static $strip_keys = array(
		'validate_callback',
		'sanitize_callback',
		'arg_options',
		'context',
	);

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Derive the input schema for a given controller+verb combination.
	 *
	 * @since x.x.x
	 *
	 * @param \WP_REST_Controller $controller REST controller instance.
	 * @param string              $verb        list|get|create|update|delete|restore|clone.
	 * @return array JSON Schema object.
	 */
	public function get_input( \WP_REST_Controller $controller, string $verb ): array {
		$key = get_class( $controller ) . '::' . $verb . '::input';
		if ( isset( $this->cache[ $key ] ) ) {
			return $this->cache[ $key ];
		}
		$schema              = $this->build_input( $controller, $verb );
		$this->cache[ $key ] = $schema;
		return $schema;
	}

	/**
	 * Derive the output schema for a given controller+verb combination.
	 *
	 * @since x.x.x
	 *
	 * @param \WP_REST_Controller $controller REST controller instance.
	 * @param string              $verb        list|get|create|update|delete|restore|clone.
	 * @return array JSON Schema object.
	 */
	public function get_output( \WP_REST_Controller $controller, string $verb ): array {
		$key = get_class( $controller ) . '::' . $verb . '::output';
		if ( isset( $this->cache[ $key ] ) ) {
			return $this->cache[ $key ];
		}
		$schema              = $this->build_output( $controller, $verb );
		$this->cache[ $key ] = $schema;
		return $schema;
	}

	/**
	 * Strip REST-internal keys from a single schema field, preserving valid JSON Schema keys.
	 * Recursively cleans nested items/properties.
	 *
	 * @since x.x.x
	 *
	 * @param array $field Raw schema field from a REST controller.
	 * @return array Cleaned JSON Schema field.
	 */
	public function clean_field( array $field ): array {
		$cleaned = array_diff_key( $field, array_flip( self::$strip_keys ) );

		if ( isset( $cleaned['items'] ) && is_array( $cleaned['items'] ) ) {
			$cleaned['items'] = $this->clean_field( $cleaned['items'] );
		}

		if ( isset( $cleaned['properties'] ) && is_array( $cleaned['properties'] ) ) {
			foreach ( $cleaned['properties'] as $k => $v ) {
				$cleaned['properties'][ $k ] = $this->clean_field( $v );
			}
		}

		return $cleaned;
	}

	/**
	 * Like clean_field() but additionally makes scalar types nullable.
	 *
	 * Used for output schemas where any REST field can realistically be null
	 * (e.g. featured_image returns null when no image is set, even though the
	 * controller schema declares type:integer). Recursively applies to nested
	 * items and properties so deeply nested fields are also covered.
	 *
	 * @since x.x.x
	 *
	 * @param array $field Raw schema field from a REST controller.
	 * @return array Cleaned JSON Schema field with nullable scalar types.
	 */
	public function clean_output_field( array $field ): array {
		$cleaned = array_diff_key( $field, array_flip( self::$strip_keys ) );

		if ( isset( $cleaned['items'] ) && is_array( $cleaned['items'] ) ) {
			$cleaned['items'] = $this->clean_output_field( $cleaned['items'] );
		}

		if ( isset( $cleaned['properties'] ) && is_array( $cleaned['properties'] ) ) {
			foreach ( $cleaned['properties'] as $k => $v ) {
				$cleaned['properties'][ $k ] = $this->clean_output_field( $v );
			}
		}

		// Scalar types (integer, string, number, boolean) can be null in REST
		// responses when the underlying data has not been set. Object and array
		// types are returned as {} / [] rather than null, so they stay as-is.
		if ( isset( $cleaned['type'] ) && is_string( $cleaned['type'] )
			&& ! in_array( $cleaned['type'], array( 'object', 'array' ), true ) ) {
			$cleaned['type'] = array( $cleaned['type'], 'null' );
		}

		return $cleaned;
	}

	// -------------------------------------------------------------------------
	// Input builders
	// -------------------------------------------------------------------------

	/**
	 * Dispatch to the correct input-schema builder for the given verb.
	 *
	 * @since x.x.x
	 * @param \WP_REST_Controller $controller REST controller instance.
	 * @param string              $verb        The ability verb (list, get, etc.).
	 * @return array JSON Schema.
	 */
	private function build_input( \WP_REST_Controller $controller, string $verb ): array {
		switch ( $verb ) {
			case 'list':
				return $this->from_collection_params( $controller );
			case 'get':
			case 'clone':
			case 'restore':
				return $this->id_only_schema();
			case 'create':
				return $this->from_item_schema( $controller, false );
			case 'update':
				return $this->from_item_schema( $controller, true );
			case 'delete':
				return $this->delete_schema();
			default:
				return array(
					'type'                 => 'object',
					'properties'           => array(),
					'additionalProperties' => false,
				);
		}
	}

	/**
	 * Build input schema from collection params (for list verbs).
	 *
	 * @since x.x.x
	 * @param \WP_REST_Controller $controller REST controller instance.
	 * @return array JSON Schema object.
	 */
	private function from_collection_params( \WP_REST_Controller $controller ): array {
		try {
			$params = $controller->get_collection_params();
		} catch ( \Throwable $e ) {
			return array(
				'type'                 => 'object',
				'properties'           => array(),
				'additionalProperties' => false,
			);
		}

		$props = array();
		foreach ( $params as $key => $param ) {
			$props[ $key ] = $this->clean_field( $param );
		}

		return array(
			'type'                 => 'object',
			'properties'           => $props,
			'additionalProperties' => false,
		);
	}

	/**
	 * Build input schema from item schema (for create/update verbs).
	 * Excludes readonly fields and fields not available in the 'edit' context.
	 *
	 * @since x.x.x
	 * @param \WP_REST_Controller $controller REST controller instance.
	 * @param bool                $include_id  Whether to add a required 'id' property (update).
	 * @return array JSON Schema object.
	 */
	private function from_item_schema( \WP_REST_Controller $controller, bool $include_id ): array {
		try {
			$schema = $controller->get_item_schema();
		} catch ( \Throwable $e ) {
			return array(
				'type'                 => 'object',
				'properties'           => array(),
				'additionalProperties' => false,
			);
		}

		$all_props = isset( $schema['properties'] ) ? $schema['properties'] : array();
		$props     = array();
		$required  = array();

		if ( $include_id ) {
			$props['id'] = array(
				'type'        => 'integer',
				'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
			);
			$required[]  = 'id';
		}

		foreach ( $all_props as $key => $field ) {
			if ( 'id' === $key ) {
				continue;
			}
			// Skip server-managed fields.
			if ( ! empty( $field['readonly'] ) ) {
				continue;
			}
			// Skip fields that have an explicit context array that excludes 'edit'.
			if ( isset( $field['context'] ) && is_array( $field['context'] ) && ! in_array( 'edit', $field['context'], true ) ) {
				continue;
			}

			$cleaned = $this->clean_field( $field );
			// Remove per-field 'required'; we track it at the object level.
			unset( $cleaned['required'] );

			if ( isset( $field['required'] ) && $field['required'] ) {
				$required[] = $key;
			}

			$props[ $key ] = $cleaned;
		}

		$result = array(
			'type'                 => 'object',
			'properties'           => $props,
			'additionalProperties' => false,
		);
		if ( ! empty( $required ) ) {
			$result['required'] = $required;
		}

		return $result;
	}

	/**
	 * Schema for single-ID operations (get, clone, restore).
	 *
	 * @since x.x.x
	 * @return array
	 */
	private function id_only_schema(): array {
		return array(
			'type'                 => 'object',
			'required'             => array( 'id' ),
			'properties'           => array(
				'id' => array(
					'type'        => 'integer',
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'minimum'     => 1,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Schema for delete operations.
	 *
	 * @since x.x.x
	 * @return array
	 */
	private function delete_schema(): array {
		return array(
			'type'                 => 'object',
			'required'             => array( 'id' ),
			'properties'           => array(
				'id'    => array(
					'type'        => 'integer',
					'description' => __( 'Unique identifier for the resource.', 'learning-management-system' ),
					'minimum'     => 1,
				),
				'force' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to permanently delete rather than trash.', 'learning-management-system' ),
					'default'     => false,
				),
			),
			'additionalProperties' => false,
		);
	}

	// -------------------------------------------------------------------------
	// Output builders
	// -------------------------------------------------------------------------

	/**
	 * Dispatch to the correct output-schema builder for the given verb.
	 *
	 * @since x.x.x
	 * @param \WP_REST_Controller $controller REST controller instance.
	 * @param string              $verb        The ability verb (list, get, etc.).
	 * @return array JSON Schema.
	 */
	private function build_output( \WP_REST_Controller $controller, string $verb ): array {
		$item = $this->item_output_schema( $controller );

		if ( 'list' === $verb ) {
			return array(
				'type'       => 'object',
				'required'   => array( 'data', 'pagination' ),
				'properties' => array(
					'data'       => array(
						'type'  => 'array',
						'items' => $item,
					),
					'pagination' => array(
						'type'       => 'object',
						'required'   => array( 'total', 'total_pages', 'page', 'per_page' ),
						'properties' => array(
							'total'       => array( 'type' => 'integer' ),
							'total_pages' => array( 'type' => 'integer' ),
							'page'        => array( 'type' => 'integer' ),
							'per_page'    => array( 'type' => 'integer' ),
						),
					),
				),
			);
		}

		if ( 'delete' === $verb ) {
			return array(
				'type'       => 'object',
				'properties' => array(
					'deleted'  => array( 'type' => 'boolean' ),
					'previous' => $item,
				),
			);
		}

		return $item;
	}

	/**
	 * Build a JSON Schema object from a controller's get_item_schema().
	 *
	 * Uses clean_output_field() so scalar types become nullable — REST responses
	 * can return null for any unset field (featured_image, parent_id, sale_price…).
	 *
	 * @since x.x.x
	 * @param \WP_REST_Controller $controller REST controller instance.
	 * @return array JSON Schema object.
	 */
	private function item_output_schema( \WP_REST_Controller $controller ): array {
		try {
			$schema = $controller->get_item_schema();
		} catch ( \Throwable $e ) {
			return array( 'type' => 'object' );
		}

		$all_props = isset( $schema['properties'] ) ? $schema['properties'] : array();
		$props     = array();

		foreach ( $all_props as $key => $field ) {
			$cleaned = $this->clean_output_field( $field );
			unset( $cleaned['required'] );
			$props[ $key ] = $cleaned;
		}

		return array(
			'type'       => 'object',
			'properties' => $props,
		);
	}
}
