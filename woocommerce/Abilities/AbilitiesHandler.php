<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities;

use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\Contracts\AbilitiesProviderContract;
use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\REST\AbilityRestController;

/**
 * Central handler for the WordPress Abilities API.
 *
 * Orchestrates ability registration by collecting abilities from the plugin
 * (via HasAbilitiesContract) and extensibility hooks, then registering them
 * with WordPress when the appropriate hooks fire.
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

		$this->addHooks();
	}

	/**
	 * Hooks into WordPress Abilities API initialization actions.
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	protected function addHooks() : void
	{
		add_action('wp_abilities_api_categories_init', [$this, 'handleCategoriesInit']);
		add_action('wp_abilities_api_init', [$this, 'handleAbilitiesInit']);
		add_action('rest_api_init', [$this, 'handleRestApiInit']);
	}

	/**
	 * Handles the categories init hook.
	 *
	 * Lazily collects abilities from the plugin, then registers categories with WordPress.
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
			wp_register_ability($ability->getName(), $ability->toArray());
		}
	}

	/**
	 * Handles the REST API init hook by auto-registering endpoints for abilities that have a RestApiConfig.
	 *
	 * @internal
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	public function handleRestApiInit() : void
	{
		foreach ($this->abilitiesProvider->getAbilities() as $ability) {
			if ($ability->restApiConfig === null) {
				continue;
			}

			$controller = new AbilityRestController($ability);
			$controller->register_routes();
		}
	}
}
