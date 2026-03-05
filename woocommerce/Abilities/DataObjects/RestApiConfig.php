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
	/** @var string the API route path */
	public string $apiRoute;

	public function __construct(
		string $apiRoute
	)
	{
		$this->apiRoute = $apiRoute;
	}
}
