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

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities;

use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\Contracts\AbilitiesProviderContract;

/**
 * Central handler for the WordPress Abilities API.
 *
 * Orchestrates ability registration by collecting abilities from the plugin (via HasAbilitiesContract) and
 * extensibility hooks, then registering them with WordPress when the appropriate hooks fire.
 *
 * @since 6.1.0
 */
class AbilitiesHandler
{
	protected AbilitiesProviderContract $abilitiesProvider;

	/**
	 * Constructor.
	 *
	 * @param AbilitiesProviderContract $abilitiesProvider
	 * @since 6.1.0
	 *
	 */
	public function __construct(AbilitiesProviderContract $abilitiesProvider)
	{
		$this->abilitiesProvider = $abilitiesProvider;
	}

	/**
	 * Hooks into WordPress Abilities API initialization actions.
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	public function addHooks() : void
	{
		add_action('wp_abilities_api_categories_init', [$this, 'handleCategoriesInit']);
		add_action('wp_abilities_api_init', [$this, 'handleAbilitiesInit']);
	}

	/**
	 * Handles the categories init hook by registering ability categories with WordPress.
	 *
	 * @internal
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	public function handleCategoriesInit() : void
	{
		if (! function_exists('wp_register_ability_category')) {
			return;
		}

		foreach ($this->abilitiesProvider->getCategories() as $category) {
			wp_register_ability_category($category->slug, $category->toArray());
		}
	}

	/**
	 * Handles the abilities init hook by registering the abilities with WordPress.
	 *
	 * @internal
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	public function handleAbilitiesInit() : void
	{
		if (! function_exists('wp_register_ability')) {
			return;
		}

		foreach ($this->abilitiesProvider->getAbilities() as $ability) {
			wp_register_ability($ability->name, $ability->toArray());
		}
	}
}
