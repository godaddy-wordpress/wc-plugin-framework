<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_0_1\Abilities\Contracts;

/**
 * Contract for plugins that register abilities.
 *
 * Plugins implementing this contract must return an abilities provider instance
 * that owns all registration logic for the plugin's abilities and categories.
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
