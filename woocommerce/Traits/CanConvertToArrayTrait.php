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

namespace SkyVerge\WooCommerce\PluginFramework\v5_15_9\Traits;

use ReflectionClass;
use ReflectionProperty;
use SkyVerge\WooCommerce\PluginFramework\v5_15_9\Helpers\ArrayHelper;

/**
 * A trait that allows a given class/object to convert its state to an array.
 */
trait CanConvertToArrayTrait
{
	/** @var bool convert Private Properties to Array Output */
	protected bool $toArrayIncludePrivate = false;

	/** @var bool convert Protected Properties to Array Output */
	protected bool $toArrayIncludeProtected = true;

	/** @var bool convert Public Properties to Array Output */
	protected bool $toArrayIncludePublic = true;

	/** @var bool prevents infinite recursive calls */
	private bool $bailIfInRecursiveCall = false;

	/**
	 * Converts all class data properties to an array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray() : array
	{
		if ($this->bailIfInRecursiveCall) {
			return [];
		}

		$this->bailIfInRecursiveCall = true;

		$array = [];

		foreach ((new ReflectionClass(static::class))->getProperties() as $property) {
			if ($this->toArrayShouldPropertyBeAccessible($property)) {
				$property->setAccessible(true);

				$propertyValue = $property->getValue($this);

				$value = $propertyValue;

				if ($this->canConvertItemToArray($propertyValue)) {
					/** @phpstan-ignore-next-line PhpStan might not understand that the check above means the object has a toArray() method */
					$value = $propertyValue->toArray();
				} elseif (ArrayHelper::accessible($value)) {
					$trait = $this;
					/* @phpstan-ignore-next-line */
					array_walk($value, static function (&$item) use ($trait) {
						$item = $trait->canConvertItemToArray($item) ? $item->toArray() : $item;
					});
				}

				$array[$property->getName()] = $value;
			}
		}

		$this->bailIfInRecursiveCall = false;

		return ArrayHelper::except($array, [
			'bailIfInRecursiveCall',
			'toArrayIncludePrivate',
			'toArrayIncludeProtected',
			'toArrayIncludePublic',
		]);
	}

	/**
	 * Determines if an item can be converted to an array.
	 *
	 * @param mixed $item
	 * @return bool
	 */
	protected function canConvertItemToArray($item) : bool
	{
		return is_object($item) && is_callable([$item, 'toArray']);
	}

	/**
	 * Checks if the property is accessible for {@see toArray()} conversion.
	 *
	 * @param ReflectionProperty $property
	 * @return bool
	 */
	private function toArrayShouldPropertyBeAccessible(ReflectionProperty $property) : bool
	{
		// Force accessible for typed properties in PHP <8.1 to avoid an exception from isInitialized()
		$property->setAccessible(true);

		if (! $property->isInitialized($this)) {
			return false;
		}

		if ($this->toArrayIncludePublic && $property->isPublic()) {
			return true;
		}

		if ($this->toArrayIncludeProtected && $property->isProtected()) {
			return true;
		}

		if ($this->toArrayIncludePrivate && $property->isPrivate()) {
			return true;
		}

		return false;
	}
}
