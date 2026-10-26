<?php
/**
 * Schema translator.
 *
 * Derives JSON Schema for ability input/output from REST controller introspection.
 * Results are cached per controller+verb within the instance lifecycle.
 *
 * @package Masteriyo\Abilities\Support
 */

namespace Masteriyo\Abilities\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Translates REST controller schemas to JSON Schema for the Abilities API.
 *
 * Bind as a shared singleton via masteriyo('abilities.schema_translator') so the
 * instance cache is reused across all abilities within a single request.
 */
class SchemaTranslator {

	/**
	 * Per-instance cache: class::verb::direction => schema array.
	 *
	 * @var array
	 */
	private $cache = array();

	/**
	 * The JSON Schema type names. A REST `type: mixed` becomes all of them.
	 */
	const JSON_TYPES = array( 'string', 'number', 'integer', 'boolean', 'object', 'array', 'null' );

	/**
	 * The JSON Schema keywords a field may carry. Anything else beside them that
	 * looks like a schema is a sub-field written outside `properties`.
	 */
	const SCHEMA_KEYWORDS = array(
		'type',
		'description',
		'title',
		'default',
		'enum',
		'const',
		'items',
		'properties',
		'patternProperties',
		'additionalProperties',
		'required',
		'format',
		'minimum',
		'maximum',
		'exclusiveMinimum',
		'exclusiveMaximum',
		'multipleOf',
		'minLength',
		'maxLength',
		'pattern',
		'minItems',
		'maxItems',
		'uniqueItems',
		'minProperties',
		'maxProperties',
		'readOnly',
		'writeOnly',
		'deprecated',
		'examples',
		'oneOf',
		'anyOf',
		'allOf',
		'not',
	);

	/**
	 * Type names the controllers use that JSON Schema does not: the type they mean,
	 * and the format that carries the rest of the meaning, or null.
	 */
	const TYPE_ALIASES = array(
		'any'       => array( 'mixed', null ),
		'email'     => array( 'string', 'email' ),
		'text/html' => array( 'string', null ),
		'url'       => array( 'string', 'uri' ),
		'datetime'  => array( 'string', 'date-time' ),
		'numeric'   => array( 'number', null ),
		'float'     => array( 'number', null ),
		'int'       => array( 'integer', null ),
		'bool'      => array( 'boolean', null ),
	);

	/**
	 * Formats a strict client knows. Any other format is dropped, except the ones
	 * FORMAT_PATTERNS can say as a pattern.
	 */
	const FORMATS = array( 'date-time', 'date', 'time', 'duration', 'email', 'hostname', 'ipv4', 'ipv6', 'uri', 'uri-reference', 'uuid', 'regex', 'json-pointer' );

	const FORMAT_PATTERNS = array(
		'hex-color' => '^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$',
	);

	/**
	 * Response fields whose controller schema describes the input, not what the
	 * response holds. WordPress validates an ability's output against its schema,
	 * so a wrong type there makes the whole ability fail when called.
	 */
	const OUTPUT_SHAPES = array(
		// Declared an integer; the Q&A controller returns is_created_by_current_user().
		'by_current_user' => array( 'type' => 'boolean' ),
		// Written as an attachment ID, returned as the ID with its URL.
		'profile_image'   => array(
			'type'       => 'object',
			'properties' => array(
				'id'  => array( 'type' => 'integer' ),
				'url' => array( 'type' => 'string' ),
			),
		),
	);

	/**
	 * WordPress REST keywords and their JSON Schema spellings.
	 */
	const RENAMED_KEYS = array(
		'readonly' => 'readOnly',
	);

	/**
	 * REST-internal keys stripped from every field before exposing to the AI.
	 * `min` and `max` are WordPress REST spellings it never enforced; spelling
	 * them as JSON Schema's minimum and maximum would start rejecting requests.
	 *
	 * @var string[]
	 */
	private static $strip_keys = array(
		'validate_callback',
		'sanitize_callback',
		'arg_options',
		'context',
		'min',
		'max',
	);

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Derive the input schema for a given controller+verb combination.
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
	 * Strip REST-internal keys from a single schema field and make it valid JSON Schema.
	 * Recursively cleans nested items/properties.
	 *
	 * @param array<string, mixed> $field Raw schema field from a REST controller.
	 * @return array<string, mixed> Cleaned JSON Schema field.
	 */
	public function clean_field( array $field ): array {
		return $this->clean( $field, false );
	}

	/**
	 * Like clean_field(), but describing what a response holds rather than what a
	 * request may send.
	 *
	 * WordPress validates an ability's result against this schema and discards the
	 * whole result on one mismatch, so the types admit what PHP actually returns:
	 * null for an unset field, [] for an empty object, a numeric or boolean value
	 * stored as text, and "" for an unset choice. Input-only checks (format,
	 * pattern, required) are left out.
	 *
	 * @param array<string, mixed> $field Raw schema field from a REST controller.
	 * @return array<string, mixed> Cleaned JSON Schema field for a response.
	 */
	public function clean_output_field( array $field ): array {
		return $this->clean( $field, true );
	}

	/**
	 * The one cleaner behind clean_field() and clean_output_field().
	 *
	 * WordPress REST accepts shapes JSON Schema does not, and MCP clients validate
	 * strictly (#1645): `type: mixed`, pipe-joined types, a boolean `required` on
	 * a property, an `enum` whose PHP keys have gaps and so encodes as an object,
	 * WordPress's own `readonly` spelling, and an empty `properties` array, which
	 * encodes as a list.
	 *
	 * Where the controller schema was malformed, WordPress REST skipped validating
	 * it: a type it does not know, or members written under `items` or beside the
	 * keywords. Made well-formed, those parts would start rejecting requests the
	 * endpoint accepts, so they get the same tolerant rules a response does.
	 *
	 * @param array<string, mixed> $field  Raw schema field from a REST controller.
	 * @param bool                 $output Whether the schema describes a response.
	 * @param bool                 $loose  Whether REST never validated this part of a request.
	 * @return array<string, mixed> Valid JSON Schema field.
	 */
	private function clean( array $field, bool $output, bool $loose = false ): array {
		$relaxed = $output || $loose || ! isset( $field['type'] ) || $this->is_unknown_type( $field['type'] );
		$cleaned = array_diff_key( $field, array_flip( self::$strip_keys ) );

		foreach ( self::RENAMED_KEYS as $rest => $json ) {
			if ( array_key_exists( $rest, $cleaned ) ) {
				if ( ! array_key_exists( $json, $cleaned ) ) {
					$cleaned[ $json ] = $cleaned[ $rest ];
				}
				unset( $cleaned[ $rest ] );
			}
		}

		// The declared type decides structure; the output widening below must not.
		$declared = isset( $cleaned['type'] ) ? $this->normalize_type( $cleaned['type'], false )[0] : null;

		if ( isset( $cleaned['type'] ) ) {
			list( $cleaned['type'], $format ) = $this->normalize_type( $cleaned['type'], $relaxed );
			if ( $format && empty( $cleaned['format'] ) ) {
				$cleaned['format'] = $format;
			}
		}

		if ( isset( $cleaned['format'] ) && ! in_array( $cleaned['format'], self::FORMATS, true ) ) {
			if ( isset( self::FORMAT_PATTERNS[ $cleaned['format'] ] ) && empty( $cleaned['pattern'] ) ) {
				$cleaned['pattern'] = self::FORMAT_PATTERNS[ $cleaned['format'] ];
			}
			unset( $cleaned['format'] );
		}

		if ( $relaxed ) {
			unset( $cleaned['format'], $cleaned['pattern'] );
		}

		if ( isset( $cleaned['enum'] ) && is_array( $cleaned['enum'] ) ) {
			$cleaned['enum'] = array_values( $cleaned['enum'] );
			// Stored values drift from the declared choices (the tax method's own default
			// is not among them), so a response names the choices instead of enforcing them.
			if ( $relaxed ) {
				$choices = array_filter( $cleaned['enum'], 'is_scalar' );
				if ( $choices ) {
					$note                   = 'One of: ' . implode( ', ', $choices ) . '.';
					$cleaned['description'] = isset( $cleaned['description'] ) && is_string( $cleaned['description'] ) && '' !== $cleaned['description']
						? rtrim( $cleaned['description'] ) . ' ' . $note
						: $note;
				}
				unset( $cleaned['enum'] );
			}
		}

		// Members REST never validated, because they sat where it does not look.
		$unvalidated = array();

		// Controllers describe an object's members under `items`: as sub-schemas, as a
		// bare name-to-type map, or as a whole schema with its own properties.
		if ( isset( $cleaned['items'] ) && $this->is_object_type( $declared ) && $this->is_assoc( $cleaned['items'] ) ) {
			$items   = $cleaned['items'];
			$members = isset( $items['properties'] ) && is_array( $items['properties'] )
				? $items['properties']
				: ( isset( $items['type'] ) ? array() : $items );
			foreach ( $members as $key => $member ) {
				$cleaned['properties'][ $key ] = is_string( $member ) ? array( 'type' => $member ) : $member;
				$unvalidated[ $key ]           = true;
			}
			unset( $cleaned['items'] );
		}

		if ( isset( $cleaned['items'] ) && is_array( $cleaned['items'] ) ) {
			$cleaned['items'] = $this->clean( $cleaned['items'], $output, $relaxed );
		}

		// Beside the keywords a controller may leave a sub-field, e.g. billing.first_name
		// or one named title, which belongs in properties, or a stray key, which goes.
		foreach ( $cleaned as $key => $value ) {
			$is_keyword   = in_array( $key, self::SCHEMA_KEYWORDS, true );
			$is_sub_field = $this->is_assoc( $value ) && ( ! $is_keyword || in_array( $key, array( 'title', 'description' ), true ) );

			if ( $is_sub_field ) {
				$cleaned['properties'][ $key ] = $value;
				$unvalidated[ $key ]           = true;
				unset( $cleaned[ $key ] );
			} elseif ( ! $is_keyword ) {
				unset( $cleaned[ $key ] );
			}
		}

		if ( isset( $cleaned['properties'] ) && array() === $cleaned['properties'] ) {
			unset( $cleaned['properties'] );
		}

		if ( isset( $cleaned['properties'] ) && is_array( $cleaned['properties'] ) ) {
			$required = isset( $cleaned['required'] ) && is_array( $cleaned['required'] ) ? $cleaned['required'] : array();

			foreach ( $cleaned['properties'] as $key => $property ) {
				if ( ! is_array( $property ) ) {
					continue;
				}
				// REST puts `required: true` on the property; JSON Schema lists it on the parent.
				if ( isset( $property['required'] ) && is_bool( $property['required'] ) ) {
					// A member REST never validated must not become required.
					if ( $property['required'] && ! isset( $unvalidated[ $key ] ) ) {
						$required[] = $key;
					}
					unset( $property['required'] );
				}
				$cleaned['properties'][ $key ] = $this->clean( $property, $output, $relaxed || isset( $unvalidated[ $key ] ) );
			}

			if ( ! empty( $required ) ) {
				$cleaned['required'] = array_values( array_unique( $required ) );
			}
		}

		if ( $relaxed ) {
			unset( $cleaned['required'] );
		}

		// A field declared without a type: WordPress's own validator needs one.
		if ( ! isset( $cleaned['type'] ) ) {
			$cleaned['type'] = isset( $cleaned['properties'] ) ? $this->normalize_type( 'object', true )[0] : self::JSON_TYPES;
		}

		return $cleaned;
	}

	/**
	 * Whether WordPress REST would not recognise a type, and so skip validating it.
	 *
	 * @param mixed $type The controller's type value.
	 * @return bool
	 */
	private function is_unknown_type( $type ): bool {
		foreach ( (array) $type as $one ) {
			if ( ! is_string( $one ) || ! in_array( $one, self::JSON_TYPES, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a normalized type names an object and not also an array.
	 *
	 * @param string|string[]|null $type Normalized type.
	 * @return bool
	 */
	private function is_object_type( $type ): bool {
		$types = (array) $type;

		return in_array( 'object', $types, true ) && ! in_array( 'array', $types, true );
	}

	/**
	 * Whether a value is a non-empty array with string keys: a schema or a map, not a list.
	 *
	 * @param mixed $value Any schema value.
	 * @return bool
	 */
	private function is_assoc( $value ): bool {
		return is_array( $value ) && ! empty( $value ) && array_keys( $value ) !== range( 0, count( $value ) - 1 );
	}

	/**
	 * A REST `type` as JSON Schema: one of the seven type names, or a list of them,
	 * plus the format an alias such as `email` implies.
	 *
	 * `mixed` becomes every type; `object|string` and `array | string` are split;
	 * TYPE_ALIASES are resolved. For a response, the type also admits what PHP
	 * hands back: null for anything unset, [] for an empty object, and a number
	 * or boolean stored as text.
	 *
	 * @param string|string[] $type   The controller's type value.
	 * @param bool            $output Whether the schema describes a response.
	 * @return array{0: string|list<string>, 1: string|null} The type and an implied format.
	 */
	private function normalize_type( $type, bool $output ): array {
		$names  = array();
		$format = null;

		foreach ( (array) $type as $one ) {
			if ( ! is_string( $one ) ) {
				continue;
			}
			foreach ( preg_split( '/\s*\|\s*/', trim( $one ) ) as $name ) {
				$name = strtolower( $name );
				if ( isset( self::TYPE_ALIASES[ $name ] ) ) {
					$format = $format ?? self::TYPE_ALIASES[ $name ][1];
					$name   = self::TYPE_ALIASES[ $name ][0];
				}
				if ( 'mixed' === $name ) {
					$names = array_merge( $names, self::JSON_TYPES );
				} elseif ( '' !== $name ) {
					$names[] = $name;
				}
			}
		}

		if ( $output && $names ) {
			if ( in_array( 'object', $names, true ) ) {
				$names[] = 'array';
			}
			if ( array_intersect( $names, array( 'integer', 'number', 'boolean' ) ) ) {
				$names[] = 'string';
			}
			$names[] = 'null';
		}

		$names = array_values( array_unique( $names ) );

		return array( 1 === count( $names ) ? $names[0] : $names, $format );
	}

	// -------------------------------------------------------------------------
	// Input builders
	// -------------------------------------------------------------------------

	/**
	 * Dispatch to the correct input-schema builder for the given verb.
	 *
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
					'additionalProperties' => false,
				);
		}
	}

	/**
	 * Build input schema from collection params (for list verbs).
	 *
	 * @param \WP_REST_Controller $controller REST controller instance.
	 * @return array{type: 'object', additionalProperties: false, properties?: array<array-key, array<string, mixed>>, required?: list<array-key>} JSON Schema object.
	 */
	private function from_collection_params( \WP_REST_Controller $controller ): array {
		try {
			$params = $controller->get_collection_params();
		} catch ( \Throwable $e ) {
			return array(
				'type'                 => 'object',
				'additionalProperties' => false,
			);
		}

		$props    = array();
		$required = array();
		foreach ( $params as $key => $param ) {
			if ( ! is_array( $param ) ) {
				continue;
			}
			// A collection param's `required: true` is REST style; list it on the object.
			if ( isset( $param['required'] ) && is_bool( $param['required'] ) ) {
				if ( $param['required'] ) {
					$required[] = $key;
				}
				unset( $param['required'] );
			}
			$props[ $key ] = $this->clean_field( $param );
		}

		$result = array(
			'type'                 => 'object',
			'additionalProperties' => false,
		);
		if ( ! empty( $props ) ) {
			$result['properties'] = $props;
		}
		if ( ! empty( $required ) ) {
			$result['required'] = $required;
		}

		return $result;
	}

	/**
	 * Build input schema from item schema (for create/update verbs).
	 * Excludes readonly fields and fields not available in the 'edit' context.
	 *
	 * @param \WP_REST_Controller $controller REST controller instance.
	 * @param bool                $include_id  Whether to add a required 'id' property (update).
	 * @return array{type: 'object', additionalProperties: false, properties?: array<array-key, array<string, mixed>>, required?: list<array-key>} JSON Schema object.
	 */
	private function from_item_schema( \WP_REST_Controller $controller, bool $include_id ): array {
		try {
			$schema = $controller->get_item_schema();
		} catch ( \Throwable $e ) {
			return array(
				'type'                 => 'object',
				'additionalProperties' => false,
			);
		}

		$all_props = isset( $schema['properties'] ) ? $schema['properties'] : array();
		$props     = array();
		$required  = array();

		if ( $include_id ) {
			$props['id'] = array(
				'type'        => 'integer',
				'description' => 'Unique identifier for the resource.',
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

			// REST enforces `required` on create only.
			if ( ! $include_id && isset( $field['required'] ) && $field['required'] ) {
				$required[] = $key;
			}

			$props[ $key ] = $include_id ? $this->allow_null( $cleaned ) : $cleaned;
		}

		$result = array(
			'type'                 => 'object',
			'additionalProperties' => false,
		);
		if ( ! empty( $props ) ) {
			$result['properties'] = $props;
		}
		if ( ! empty( $required ) ) {
			$result['required'] = $required;
		}

		return $result;
	}

	/**
	 * Schema for single-ID operations (get, clone, restore).
	 *
	 * @return array
	 */
	private function id_only_schema(): array {
		return array(
			'type'                 => 'object',
			'required'             => array( 'id' ),
			'properties'           => array(
				'id' => array(
					'type'        => 'integer',
					'description' => 'Unique identifier for the resource.',
					'minimum'     => 1,
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Schema for delete operations.
	 *
	 * @return array
	 */
	private function delete_schema(): array {
		return array(
			'type'                 => 'object',
			'required'             => array( 'id' ),
			'properties'           => array(
				'id'    => array(
					'type'        => 'integer',
					'description' => 'Unique identifier for the resource.',
					'minimum'     => 1,
				),
				// No default: it differs by route, and each ability's description states it.
				'force' => array(
					'type'        => 'boolean',
					'description' => 'Whether to permanently delete rather than trash.',
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Let an update property take null. A record read back as an update carries
	 * null for an unset object; RestProxyAbility drops it before calling the route.
	 *
	 * @param array<string, mixed> $field Cleaned property schema.
	 * @return array<string, mixed>
	 */
	private function allow_null( array $field ): array {
		if ( ! isset( $field['type'] ) ) {
			return $field;
		}

		$types = (array) $field['type'];
		// Leave a type JSON Schema does not know (`mixed`, `object|string`) as it is.
		if ( in_array( 'null', $types, true ) || array_diff( $types, array( 'string', 'number', 'integer', 'boolean', 'object', 'array' ) ) ) {
			return $field;
		}

		$field['type'] = array_merge( $types, array( 'null' ) );
		if ( isset( $field['enum'] ) && is_array( $field['enum'] ) ) {
			$field['enum'][] = null;
		}

		return $field;
	}

	// -------------------------------------------------------------------------
	// Output builders
	// -------------------------------------------------------------------------

	/**
	 * Dispatch to the correct output-schema builder for the given verb.
	 *
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
	 * Build a JSON Schema object from a controller's get_item_schema(), describing
	 * the response through clean_output_field() and OUTPUT_SHAPES.
	 *
	 * @param \WP_REST_Controller $controller REST controller instance.
	 * @return array{type: 'object', properties?: array<array-key, array<string, mixed>>} JSON Schema object.
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
			if ( isset( self::OUTPUT_SHAPES[ $key ] ) && is_array( $field ) ) {
				$field = array_merge( array_diff_key( $field, array_flip( array( 'type', 'properties', 'items', 'format', 'pattern' ) ) ), self::OUTPUT_SHAPES[ $key ] );
			}
			$props[ $key ] = $this->clean_output_field( $field );
		}

		// An empty PHP array encodes as a JSON list, which `properties` may not be.
		return empty( $props )
			? array( 'type' => 'object' )
			: array(
				'type'       => 'object',
				'properties' => $props,
			);
	}
}
