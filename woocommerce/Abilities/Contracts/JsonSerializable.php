<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\Contracts;

/**
 * Contract for objects that provide both JSON serialization and a JSON Schema describing their output.
 *
 * Extends PHP's native {@see \JsonSerializable} with a static method that returns
 * the JSON Schema for the shape produced by {@see \JsonSerializable::jsonSerialize()}.
 * This allows abilities to reference the schema directly from the serializable object
 * rather than duplicating it in each ability definition.
 *
 * @since 6.1.0
 */
interface JsonSerializable extends \JsonSerializable
{
	/**
	 * Returns the JSON Schema describing the shape of {@see \JsonSerializable::jsonSerialize()} output.
	 *
	 * @since 6.1.0
	 *
	 * @return array JSON Schema array (e.g. with 'type', 'properties', etc.)
	 */
	public static function getJsonSchema() : array;
}
