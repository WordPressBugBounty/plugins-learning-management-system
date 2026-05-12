<?php
/**
 * REST proxy ability base.
 *
 * For the ~95% of abilities that map 1-to-1 to a single REST controller method.
 * Subclasses declare three abstract methods; everything else is derived automatically.
 *
 * @package Masteriyo\Abilities\Support
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for abilities that proxy a single REST controller method.
 *
 * Concrete subclasses must implement:
 *   - controller_service(): string   DI container key, e.g. 'course.rest'
 *   - verb(): string                 list|get|create|update|delete|restore|clone
 *   - rest_base(): string            REST endpoint base, e.g. 'courses'
 *   - get_name(): string
 *   - get_label(): string
 *   - get_description(): string
 *
 * @since x.x.x
 */
abstract class RestProxyAbility extends AbstractAbility {

	// -------------------------------------------------------------------------
	// Abstract declaration — filled in by concrete ability classes
	// -------------------------------------------------------------------------

	/**
	 * DI container service key for the underlying REST controller.
	 * Example: 'course.rest', 'section.rest', 'course_builder.rest'
	 *
	 * @since x.x.x
	 * @return string
	 */
	abstract protected function controller_service(): string;

	/**
	 * The CRUD verb this ability represents.
	 * One of: list | get | create | update | delete | restore | clone
	 *
	 * @since x.x.x
	 * @return string
	 */
	abstract protected function verb(): string;

	/**
	 * The REST API base path for this resource, e.g. 'courses', 'sections'.
	 *
	 * @since x.x.x
	 * @return string
	 */
	abstract protected function rest_base(): string;

	// -------------------------------------------------------------------------
	// Defaults — override in concrete classes when needed
	// -------------------------------------------------------------------------

	/**
	 * REST namespace (without leading slash).
	 *
	 * @since x.x.x
	 * @return string
	 */
	protected function rest_namespace(): string {
		return 'masteriyo/v1';
	}

	/**
	 * Optional path segment appended after the resource ID in the REST route.
	 * Use for sub-resource endpoints: e.g. "/children" → /courses/{id}/children.
	 * Default empty string means standard verb-based routing.
	 *
	 * @since x.x.x
	 * @return string
	 */
	protected function route_suffix(): string {
		return '';
	}

	/**
	 * {@inheritdoc}
	 * Derived from verb: list/get are read-only.
	 */
	public function is_readonly(): bool {
		return in_array( $this->verb(), array( 'list', 'get' ), true );
	}

	/**
	 * {@inheritdoc}
	 * Derived from verb: delete is destructive.
	 */
	public function is_destructive(): bool {
		return 'delete' === $this->verb();
	}

	/**
	 * {@inheritdoc}
	 * Derived from verb: list/get/update are idempotent.
	 */
	public function is_idempotent(): bool {
		return in_array( $this->verb(), array( 'list', 'get', 'update' ), true );
	}

	// -------------------------------------------------------------------------
	// Schema — derived from controller introspection (lazy, cached via DI singleton)
	// -------------------------------------------------------------------------

	/**
	 * {@inheritdoc}
	 */
	public function get_input_schema(): array {
		return $this->get_schema_translator()->get_input( $this->get_controller(), $this->verb() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_output_schema(): array {
		return $this->get_schema_translator()->get_output( $this->get_controller(), $this->verb() );
	}

	// -------------------------------------------------------------------------
	// Permission — delegates to controller's *_permissions_check()
	// -------------------------------------------------------------------------

	/**
	 * {@inheritdoc}
	 */
	public function get_permission_callback(): callable {
		return array( $this, 'check_permission' );
	}

	/**
	 * Permission callback invoked by the WordPress Abilities API.
	 *
	 * Receives the same $input as execute_callback. Synthesizes a WP_REST_Request
	 * and delegates to the controller's own permission check, giving identical
	 * authorization behaviour to the REST endpoint.
	 *
	 * @since x.x.x
	 *
	 * @param mixed $input Ability input (array or null).
	 * @return bool
	 */
	public function check_permission( $input = null ): bool {
		$input      = is_array( $input ) ? $input : array();
		$controller = $this->get_controller();
		$request    = RequestSynthesizer::for_permission(
			$this->rest_namespace(),
			$this->rest_base(),
			$this->verb(),
			$input,
			$this->route_suffix()
		);

		return ( new PermissionResolver() )->resolve( $controller, $this->verb(), $request );
	}

	// -------------------------------------------------------------------------
	// Execute — dispatches an internal REST request
	// -------------------------------------------------------------------------

	/**
	 * {@inheritdoc}
	 */
	public function get_execute_callback(): callable {
		return array( $this, 'execute' );
	}

	/**
	 * Execute callback invoked by the WordPress Abilities API.
	 *
	 * Builds a WP_REST_Request and dispatches it through rest_do_request() so
	 * the full WP validation/sanitization/hook chain runs — no logic duplication.
	 *
	 * @since x.x.x
	 *
	 * @param mixed $input Ability input (array or null).
	 * @return mixed Response data, or WP_Error on failure.
	 */
	public function execute( $input = null ) {
		$input  = is_array( $input ) ? $input : array();
		$schema = $this->get_input_schema();

		$valid = rest_validate_value_from_schema( $input, $schema, 'input' );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$sanitized = rest_sanitize_value_from_schema( $input, $schema, 'input' );
		if ( ! is_wp_error( $sanitized ) && is_array( $sanitized ) ) {
			$input = $sanitized;
		}

		$request = RequestSynthesizer::for_execute(
			$this->rest_namespace(),
			$this->rest_base(),
			$this->verb(),
			$input,
			$this->route_suffix()
		);

		$response = rest_do_request( $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = $response->get_status();
		if ( $status >= 400 ) {
			$data = $response->get_data();
			return new \WP_Error(
				isset( $data['code'] ) ? $data['code'] : 'rest_error',
				isset( $data['message'] ) ? $data['message'] : __( 'REST request failed.', 'learning-management-system' ),
				array( 'status' => $status )
			);
		}

		if ( 'list' === $this->verb() ) {
			return ResponseEnvelope::wrap( $response, $input );
		}

		return $response->get_data();
	}

	// -------------------------------------------------------------------------
	// Internal helpers
	// -------------------------------------------------------------------------

	/**
	 * Resolve the REST controller from the DI container.
	 *
	 * @since x.x.x
	 * @return \WP_REST_Controller
	 */
	protected function get_controller(): \WP_REST_Controller {
		return masteriyo( $this->controller_service() );
	}

	/**
	 * Resolve the shared SchemaTranslator instance from the DI container.
	 *
	 * Using the DI-provided singleton means the per-instance cache is shared across
	 * all abilities within a request, avoiding redundant controller introspection.
	 *
	 * @since x.x.x
	 * @return SchemaTranslator
	 */
	protected function get_schema_translator(): SchemaTranslator {
		return masteriyo( 'abilities.schema_translator' );
	}
}
