<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\DataObjects;

/**
 * Data object representing a single ability registration.
 *
 * Encapsulates all data needed to register an ability with the WordPress Abilities API,
 * including callbacks, schemas, annotations, and optional REST API configuration.
 *
 * @since 6.1.0
 */
class Ability
{
	/** @var string unique ability name (e.g. "plugin-slug/action-name") */
	public string $name;

	/** @var string human-readable label */
	public string $label;

	/** @var string description of what the ability does */
	public string $description;

	/** @var string category slug this ability belongs to */
	public string $category;

	/** @var callable callback that executes the ability */
	public $executeCallback;

	/** @var callable callback that checks if the current user has permission */
	public $permissionCallback;

	/** @var array<string, mixed> JSON Schema describing the expected input */
	public array $inputSchema;

	/** @var array<string, mixed> JSON Schema describing the output */
	public array $outputSchema;

	/** @var ?AbilityAnnotations behavioral annotations (readonly, destructive, idempotent) */
	public ?AbilityAnnotations $annotations;

	/** @var bool whether this ability should be exposed in the REST API */
	public bool $showInRest;

	/** @var ?RestApiConfig future REST API configuration (not acted on in this PoC) */
	public ?RestApiConfig $restApiConfig;

	public function __construct(
		string $name,
		string $label,
		string $description,
		string $category,
		callable $executeCallback,
		callable $permissionCallback,
		array $inputSchema = [],
		array $outputSchema = [],
		?AbilityAnnotations $annotations = null,
		bool $showInRest = true,
		?RestApiConfig $restApiConfig = null
	)
	{
		$this->name = $name;
		$this->label = $label;
		$this->description = $description;
		$this->category = $category;
		$this->executeCallback = $executeCallback;
		$this->permissionCallback = $permissionCallback;
		$this->inputSchema = $inputSchema;
		$this->outputSchema = $outputSchema;
		$this->annotations = $annotations;
		$this->showInRest = $showInRest;
		$this->restApiConfig = $restApiConfig;
	}

	/**
	 * Maps properties to the structure expected by wp_register_ability(),
	 * including the nested meta.annotations and meta.show_in_rest format.
	 *
	 * @since 6.1.0
	 */
	public function toArray() : array
	{
		$args = [
			'label'               => $this->label,
			'description'         => $this->description,
			'category'            => $this->category,
			'execute_callback'    => $this->executeCallback,
			'permission_callback' => $this->permissionCallback,
			'input_schema'        => $this->inputSchema,
			'output_schema'       => $this->outputSchema,
			'meta'                => [
				'show_in_rest' => $this->showInRest,
			],
		];

		if ($this->annotations) {
			$args['meta']['annotations'] = $this->annotations->toArray();
		}

		return $args;
	}
}
