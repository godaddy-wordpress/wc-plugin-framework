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
 * @copyright Copyright (c) 2013-2024, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_9\Helpers;

use ArrayAccess;

class ArrayHelper
{
	/**
	 * Determines if a given item is an accessible array.
	 *
	 * @param mixed $value
	 * @return bool
	 * @phpstan-return ($value is array ? true : false)
	 */
	public static function accessible($value) : bool
	{
		return is_array($value) || $value instanceof ArrayAccess;
	}

	/**
	 * Gets an array excluding the given keys.
	 *
	 * @param array $array
	 * @param array|string $keys
	 * @return array
	 */
	public static function except(array $array, $keys) : array
	{
		$temp = $array;

		static::remove($temp, static::wrap($keys));

		return $temp;
	}

	/**
	 * Removes a given key or keys from the original array.
	 *
	 * @param array $array
	 * @param array|string $keys
	 */
	public static function remove(array &$array, $keys) : void
	{
		$original = &$array;

		foreach (static::wrap($keys) as $key) {
			// if the key exists at this level unset and bail
			if (static::exists($array, $key)) {
				unset($array[$key]);

				continue;
			}

			$parts = explode('.', $key);

			// clean up before each pass
			$array = &$original;

			while (count($parts) > 1) {
				$part = array_shift($parts);

				if (isset($array[$part]) && is_array($array[$part])) {
					$array = &$array[$part];
				} else {
					continue 2;
				}
			}

			unset($array[array_shift($parts)]);
		}
	}

	/**
	 * Wraps a given item in an array if it is not an array.
	 *
	 * @param mixed $item
	 * @return array
	 */
	public static function wrap($item = null) : array
	{
		if (is_array($item)) {
			return $item;
		}

		return $item ? [$item] : [];
	}

	/**
	 * Determines if an array key exists.
	 *
	 * @param ArrayAccess|array<mixed> $array
	 * @param string|int $key
	 * @return bool
	 */
	public static function exists($array, $key) : bool
	{
		if ($array instanceof ArrayAccess) {
			return $array->offsetExists($key);
		}

		return array_key_exists($key, self::wrap($array));
	}

	/**
	 * Gets an array value from a dot notated key.
	 *
	 * @param mixed $array
	 * @param int|string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get($array, $key, $default = null)
	{
		if (! self::accessible($array)) {
			return $default;
		}

		if (self::exists($array, $key)) {
			return $array[$key];
		}

		foreach (explode('.', (string) $key) as $segment) {
			if (! self::exists($array, $segment)) {
				return $default;
			}

			$array = $array[$segment];
		}

		return $array;
	}
}
