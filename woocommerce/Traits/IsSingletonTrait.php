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

defined('ABSPATH') or exit;

if (trait_exists('\\SkyVerge\\WooCommerce\\PluginFramework\\v5_15_9\\Traits\\IsSingletonTrait')) {
	return;
}

trait IsSingletonTrait
{
	/** @var ?static holds the current singleton instance */
	protected static $instance;

	/**
	 * Determines if the current instance is loaded.
	 *
	 * @return bool
	 */
	public static function isLoaded() : bool
	{
		return (bool) static::$instance;
	}

	/**
	 * Gets the singleton instance.
	 *
	 * @return static
	 */
	public static function getInstance(...$args)
	{
		return static::$instance ??= new static(...$args);
	}

	/**
	 * Resets the singleton instance.
	 *
	 * @return void
	 */
	public static function reset() : void
	{
		static::$instance = null;
	}
}
