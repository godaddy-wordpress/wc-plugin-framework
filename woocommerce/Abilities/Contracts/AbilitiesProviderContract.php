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

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Contracts;

use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects\Ability;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects\AbilityCategory;

/**
 * Contract for classes that provide ability and ability category definitions.
 *
 * @since 6.1.0
 */
interface AbilitiesProviderContract
{
	/**
	 * Makes and returns an array of {@see AbilityCategory} objects.
	 * This method does not register the categories; it simply makes and returns the objects.
	 *
	 * @since 6.1.0
	 *
	 * @return AbilityCategory[]
	 */
	public function getCategories() : array;

	/**
	 * Makes and returns an array of {@see Ability} objects.
	 * This method does not register the abilities; it simply makes and returns the objects.
	 *
	 * @since 6.1.0
	 *
	 * @return Ability[]
	 */
	public function getAbilities() : array;
}
