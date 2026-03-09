<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\Contracts;

use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\DataObjects\Ability;

/**
 * Contract for classes that produce an Ability registration.
 *
 * Each implementing class encapsulates the configuration for a single ability,
 * keeping providers slim and focused on listing ability classes.
 *
 * @since 6.1.0
 */
interface MakesAbilityContract
{
	/**
	 * Creates and returns the Ability data object for registration.
	 *
	 * @since 6.1.0
	 *
	 * @return Ability
	 */
	public function makeAbility() : Ability;
}
