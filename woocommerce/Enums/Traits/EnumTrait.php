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

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_9\Enums\Traits;

use ReflectionClass;

/**
 * Enables enum-like syntax pre PHP 8.1.
 *
 * @see https://www.php.net/manual/en/language.enumerations.backed.php
 *
 * @since 5.13.0
 */
trait EnumTrait
{
	/** @var array<string, static::*>|null */
	protected static ?array $enumReflectionCache = null;

	/**
	 * Maps a scalar to an enum value or null.
	 *
	 * @param int|string $value The scalar value to map to an enum case.
	 *
	 * @since 5.13.0
	 * @return static::*|null
	 */
	public static function tryFrom($value)
	{
		return in_array($value, static::values(), true) ? $value : null;
	}

	/**
	 * Fetches the values for this enum.
	 *
	 * @since 5.13.0
	 * @return array<static::*> An array of enum values.
	 */
	public static function values() : array
	{
		return array_values(static::cases());
	}

	/**
	 * Returns an associative array where the enum names are the keys and the enum values are the values.
	 *
	 * @since 5.13.0
	 * @return array<string, static::*>
	 */
	public static function cases() : array
	{
		if (null !== static::$enumReflectionCache) {
			return static::$enumReflectionCache;
		}

		/** @var array<string, static::*> $cases */
		$cases = (new ReflectionClass(static::class))->getConstants();

		static::$enumReflectionCache = $cases;

		return $cases;
	}
}
