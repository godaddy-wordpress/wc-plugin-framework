<?php
/**
 * WooCommerce Plugin Framework
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the plugin to newer
 * versions in the future. If you wish to customize the plugin for your
 * needs please refer to http://www.skyverge.com
 *
 * @package   SkyVerge/WooCommerce/Plugin/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2013-2026, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\Contracts;

/**
 * Contract for objects that provide both JSON serialization and a JSON Schema describing their output.
 *
 * Extends PHP's native {@see \JsonSerializable} with a static method that returns the JSON Schema for the shape
 * produced by {@see \JsonSerializable::jsonSerialize()}. This allows abilities to reference the schema directly from
 * the serializable object rather than duplicating it in each ability definition.
 *
 * @since 6.1.0
 */
interface JsonSerializable extends \JsonSerializable
{
	/**
	 * Returns the JSON Schema describing the shape of {@see \JsonSerializable::jsonSerialize()} output.
	 * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
	 *
	 * @since 6.1.0
	 *
	 * @return array<string, mixed> JSON Schema array (e.g. with 'type', 'properties', etc.)
	 */
	public static function getJsonSchema() : array;
}
