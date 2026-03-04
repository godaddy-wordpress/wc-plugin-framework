<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\Contracts;

use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\DataObjects\Ability;
use SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\DataObjects\AbilityCategory;

interface AbilitiesProviderContract
{
	/**
	 * @return AbilityCategory[]
	 */
	public function getCategories() : array;

	/**
	 * @return Ability[]
	 */
	public function getAbilities() : array;
}
