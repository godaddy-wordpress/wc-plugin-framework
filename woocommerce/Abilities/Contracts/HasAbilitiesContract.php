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

use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\AbstractAbilitiesProvider;

/**
 * Contract for plugins that register abilities.
 *
 * Plugins implementing this contract must return an abilities provider instance ({@see AbilitiesProviderContract}
 * and {@see AbstractAbilitiesProvider}) that owns all registration logic for the plugin's abilities and categories.
 *
 * @since 6.1.0
 */
interface HasAbilitiesContract
{
	/**
	 * Returns the plugin's abilities provider instance.
	 *
	 * @since 6.1.0
	 *
	 * @return AbilitiesProviderContract
	 */
	public function getAbilitiesProvider() : AbilitiesProviderContract;
}
