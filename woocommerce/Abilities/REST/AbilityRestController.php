<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\REST;

use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\DataObjects\Ability;

/**
 * Generic REST controller that auto-registers an endpoint for an Ability.
 *
 * Bridges WP_REST_Request parameters to the ability's executeCallback using
 * smart param detection: scalar inputSchema passes the single URL param value
 * directly, while object inputSchema passes the full params array.
 *
 * @since 6.1.0
 */
class AbilityRestController extends \WP_REST_Controller
{
	protected Ability $ability;
	protected ?\WP_Ability $wpAbility;

	public function __construct(Ability $ability)
	{
		$this->ability = $ability;
		$this->namespace = $ability->restApiConfig->namespace;
		$this->rest_base = $ability->restApiConfig->apiRoute;
	}

	protected function getWpAbility() : ?\WP_Ability
	{
		if (! isset($this->wpAbility)) {
			$this->wpAbility = wp_get_ability($this->ability->name);
		}

		return $this->wpAbility;
	}

	/**
	 * Registers the REST route derived from the ability's RestApiConfig.
	 *
	 * @since 6.1.0
	 */
	public function register_routes() : void
	{
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => $this->ability->restApiConfig->method,
					'callback'            => [$this, 'handle_request'],
					'permission_callback' => [$this, 'check_permission'],
					'args'                => $this->build_route_args(),
				],
				'schema' => [$this, 'get_public_item_schema'],
			]
		);
	}

	/**
	 * Delegates permission check to the ability's permissionCallback.
	 *
	 * @since 6.1.0
	 *
	 * @param \WP_REST_Request $request
	 * @return bool|\WP_Error
	 */
	public function check_permission(\WP_REST_Request $request)
	{
		$wpAbility = $this->getWpAbility();
		if (! $wpAbility) {
			return false;
		}

		return $wpAbility->check_permissions($this->getNormalizedAbilityInput($request));
	}

	/**
	 * Formats the incoming request input as per the expected ability schema.
	 *
	 * @param \WP_REST_Request $request
	 * @return mixed|null
	 */
	protected function getNormalizedAbilityInput(\WP_REST_Request $request)
	{
		$params = $input = $request->get_params();
		$inputSchema = $this->ability->inputSchema;

		if ($this->isScalarSchema($inputSchema)) {
			$paramName = $this->extractUrlParamName();
			$input = $params[$paramName] ?? null;
		}

		return $input;
	}

	/**
	 * Handles the REST request by delegating to the ability's executeCallback.
	 *
	 * Uses smart param bridging:
	 * - Scalar inputSchema: extracts the single URL param value and passes it directly
	 * - Object inputSchema: passes the full params array
	 *
	 * @since 6.1.0
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_request(\WP_REST_Request $request)
	{
		try {
			$ability = $this->getWpAbility();
			if (! $ability) {
				return new \WP_Error( 'rest_no_ability', __( 'The ability is not registered.', 'wc-plugin-framework' ), ['status' => 500] );
			}

			$result = $ability->execute($this->getNormalizedAbilityInput($request));

			return rest_ensure_response($result);
		} catch (\Exception $e) {
			return new \WP_Error(
				'ability_execution_error',
				$e->getMessage(),
				['status' => $e->getCode() ?: 500]
			);
		}
	}

	/**
	 * Builds WP REST route arg definitions from the ability's inputSchema.
	 *
	 * - Object schema with properties: each property becomes a route arg
	 * - Scalar schema: extracts param name from URL regex pattern, creates single arg
	 *
	 * @since 6.1.0
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function build_route_args() : array
	{
		$inputSchema = $this->ability->inputSchema;

		if (empty($inputSchema)) {
			return [];
		}

		$schemaType = $inputSchema['type'] ?? null;

		if ($schemaType === 'object' && ! empty($inputSchema['properties'])) {
			return $inputSchema['properties'];
		}

		if ($this->isScalarSchema($inputSchema)) {
			$paramName = $this->extractUrlParamName();

			if ($paramName) {
				return [
					$paramName => [
						'type'     => $schemaType,
						'required' => true,
					],
				];
			}
		}

		return [];
	}

	/**
	 * Returns the JSON Schema for this endpoint's response.
	 *
	 * @since 6.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_item_schema() : array
	{
		if ($this->schema) {
			return $this->add_additional_fields_schema($this->schema);
		}

		$outputSchema = $this->ability->outputSchema;

		$this->schema = [
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => $this->ability->name,
			'type'    => $outputSchema['type'] ?? 'object',
		];

		if (! empty($outputSchema['properties'])) {
			$this->schema['properties'] = $outputSchema['properties'];
		}

		return $this->add_additional_fields_schema($this->schema);
	}

	/**
	 * Determines whether the inputSchema describes a scalar (non-object) type.
	 *
	 * @since 6.1.0
	 *
	 * @param array<string, mixed> $schema
	 * @return bool
	 */
	protected function isScalarSchema(array $schema) : bool
	{
		$type = $schema['type'] ?? null;

		return $type !== null && $type !== 'object' && $type !== 'array';
	}

	/**
	 * Extracts the first named capture group from the route's URL regex pattern.
	 *
	 * For example, from "memberships/plans/(?P<plan_id>\d+)" extracts "plan_id".
	 *
	 * @since 6.1.0
	 *
	 * @return string|null
	 */
	protected function extractUrlParamName() : ?string
	{
		if (preg_match('/\(\?P<(\w+)>/', $this->rest_base, $matches)) {
			return $matches[1];
		}

		return null;
	}
}
