<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\DataObjects;

/**
 * Data object for REST API endpoint configuration.
 *
 * When an ability provides a RestApiConfig, the framework auto-registers
 * a WP_REST_Controller-based endpoint for it.
 *
 * @since 6.1.0
 */
class RestApiConfig
{
	/** @var string REST API namespace (e.g. "wc/v5") */
	public string $namespace;

	/** @var string the API route path */
	public string $apiRoute;

	/** @var string HTTP method */
	public string $method;

	public function __construct(
		string $namespace,
		string $apiRoute,
		string $method
	)
	{
		$this->namespace = $namespace;
		$this->apiRoute = $apiRoute;
		$this->method = $method;
	}
}
