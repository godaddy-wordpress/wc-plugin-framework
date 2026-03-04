<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\DataObjects;

/**
 * Data object for future REST API endpoint configuration.
 *
 * This is structural scaffolding for a future iteration where abilities can
 * optionally expose dedicated REST API endpoints beyond the default WP Abilities API.
 * Not acted upon by the handler in this PoC.
 *
 * @since 6.1.0
 */
class RestApiConfig
{
	/** @var bool whether to add a REST API endpoint for this ability */
	public bool $addRestApiEndpoint;

	/** @var string the API route path */
	public string $apiRoute;

	/** @var ?string fully qualified class name for a custom controller */
	public ?string $controllerOverride;

	/** @var ?string fully qualified class name for an input adapter */
	public ?string $inputAdapter;

	/** @var ?string fully qualified class name for an output adapter */
	public ?string $outputAdapter;

	public function __construct(
		bool $addRestApiEndpoint = false,
		string $apiRoute = '',
		?string $controllerOverride = null,
		?string $inputAdapter = null,
		?string $outputAdapter = null
	)
	{
		$this->addRestApiEndpoint = $addRestApiEndpoint;
		$this->apiRoute = $apiRoute;
		$this->controllerOverride = $controllerOverride;
		$this->inputAdapter = $inputAdapter;
		$this->outputAdapter = $outputAdapter;
	}
}
