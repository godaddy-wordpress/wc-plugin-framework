<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities;

use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\Contracts\AbilitiesProviderContract;
use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\Contracts\MakesAbilityContract;
use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\DataObjects\Ability;
use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\DataObjects\AbilityCategory;
use SkyVerge\WooCommerce\PluginFramework\v6_0_1\SV_WC_Plugin;

/**
 * Base class for plugin abilities providers.
 *
 * Consumer plugins extend this class to encapsulate all abilities registration
 * logic in a dedicated provider, keeping the main plugin class clean.
 *
 * Subclasses list ability class names in the {@see $abilities} property and
 * optionally override {@see registerCategories()} to register categories.
 *
 * @since 6.1.0
 */
abstract class AbstractAbilitiesProvider implements AbilitiesProviderContract
{
	/** @var SV_WC_Plugin the plugin instance */
	protected SV_WC_Plugin $plugin;

	/** @var string[] FQCNs of classes implementing MakesAbilityContract */
	protected array $abilities = [];

	/**
	 * Constructor.
	 *
	 * @since 6.1.0
	 *
	 * @param SV_WC_Plugin $plugin
	 */
	public function __construct(SV_WC_Plugin $plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * @return AbilityCategory[]
	 */
	public function getCategories() : array
	{
		return [];
	}

	/**
	 * @return Ability[]
	 */
	public function getAbilities() : array
	{
		$abilities = [];

		foreach ($this->abilities as $className) {
			if (! is_string($className) || ! in_array(MakesAbilityContract::class, class_implements($className) ?: [], true)) {
				_doing_it_wrong(
					__METHOD__,
					sprintf('Ability class "%s" must implement %s.', $className, MakesAbilityContract::class),
					'6.1.0'
				);
				continue;
			}

			$abilities[] = (new $className)->makeAbility();
		}

		return $abilities;
	}
}
