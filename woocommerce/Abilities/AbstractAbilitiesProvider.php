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

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_1\Abilities;

use SkyVerge\WooCommerce\PluginFramework\v6_1_1\Abilities\Contracts\AbilitiesProviderContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_1\Abilities\Contracts\MakesAbilityContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_1\Abilities\DataObjects\Ability;
use SkyVerge\WooCommerce\PluginFramework\v6_1_1\Abilities\DataObjects\AbilityCategory;
use SkyVerge\WooCommerce\PluginFramework\v6_1_1\SV_WC_Plugin;

/**
 * Base class for plugin abilities providers.
 *
 * Consumer plugins extend this class to encapsulate all abilities registration
 * logic in a dedicated provider, keeping the main plugin class clean.
 *
 * Subclasses list ability class names in the {@see $abilities} property and
 * optionally override {@see getCategories()} to register categories.
 *
 * @since 6.1.0
 */
abstract class AbstractAbilitiesProvider implements AbilitiesProviderContract
{
	/** @var SV_WC_Plugin the plugin instance */
	protected SV_WC_Plugin $plugin;

	/** @var class-string<MakesAbilityContract>[] FQCNs of classes implementing MakesAbilityContract */
	protected array $abilities = [];

	/**
	 * Constructor.
	 *
	 * @since 6.1.0
	 */
	public function __construct(SV_WC_Plugin $plugin)
	{
		$this->plugin = $plugin;
	}

	/** @inheritDoc */
	public function getCategories() : array
	{
		return [];
	}

	/** @inheritDoc */
	public function getAbilities() : array
	{
		$abilities = [];

		foreach ($this->abilities as $className) {
			if (
				! is_string($className) ||
				! class_exists($className) ||
				! in_array(MakesAbilityContract::class, class_implements($className) ?: [], true)
			) {
				wc_doing_it_wrong(
					__METHOD__,
					sprintf('Ability class "%s" must implement %s.', $className, MakesAbilityContract::class),
					'6.1.0'
				);
				continue;
			}

			$abilities[] = $this->instantiateAbilityClass($className)->makeAbility();
		}

		return $abilities;
	}

	/**
	 * Instantiates the provided {@see MakesAbilityContract} class name.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param class-string<MakesAbilityContract> $className
	 * @return MakesAbilityContract
	 */
	protected function instantiateAbilityClass(string $className) : MakesAbilityContract
	{
		return new $className();
	}
}
