<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_0\Tests\Unit\Abilities;

use Exception;
use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\AbstractAbilitiesProvider;
use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\Contracts\MakesAbilityContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\DataObjects\Ability;
use SkyVerge\WooCommerce\PluginFramework\v6_1_0\SV_WC_Plugin;
use SkyVerge\WooCommerce\PluginFramework\v6_1_0\Tests\TestCase;
use WP_Mock;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_1_0\Abilities\AbstractAbilitiesProvider
 */
final class AbstractAbilitiesProviderTest extends TestCase
{
	/**
	 * @covers ::__construct
	 * @throws Exception
	 */
	public function testCanConstruct() : void
	{
		$plugin = Mockery::mock(SV_WC_Plugin::class);
		$concrete = new class($plugin) extends AbstractAbilitiesProvider {};

		$this->assertSame($plugin, $this->getInaccessiblePropertyValue($concrete, 'plugin'));
	}

	/**
	 * @covers ::getCategories
	 * @throws Exception
	 */
	public function testCanGetCategories() : void
	{
		$this->assertSame(
			[],
			$this->invokeInaccessibleMethod(
				$this->getMockForAbstractClass(AbstractAbilitiesProvider::class, [], '', false),
				'getCategories'
			)
		);
	}

	/**
	 * @covers ::getAbilities
	 * @throws Exception
	 */
	public function testCanGetAbilities() : void
	{
		$abilityMaker = Mockery::mock(MakesAbilityContract::class);
		$abilityMakerClassName = get_class($abilityMaker);

		$abilityMaker->expects('makeAbility')
			->once()
			->andReturn($ability = Mockery::mock(Ability::class));

		$abstractProvider = $this->getMockForAbstractClass(
			AbstractAbilitiesProvider::class,
			[],
			'',
			false,
			false,
			true,
			['instantiateAbilityClass']
		);

		$this->setInaccessiblePropertyValue($abstractProvider, 'abilities', [$abilityMakerClassName]);

		$abstractProvider->expects($this->once())
			->method('instantiateAbilityClass')
			->with(get_class($abilityMaker))
			->willReturn($abilityMaker);

		$this->assertSame(
			[$ability],
			$this->invokeInaccessibleMethod($abstractProvider, 'getAbilities')
		);
	}

	/**
	 * @covers ::getAbilities
	 * @throws Exception
	 */
	public function testCanGetAbilitiesWithInvalidAbilityMaker() : void
	{
		$abilityMaker = new \stdClass();
		$abilityMakerClassName = get_class($abilityMaker);

		$abstractProvider = $this->getMockForAbstractClass(
			AbstractAbilitiesProvider::class,
			[],
			'',
			false,
			false,
			true,
			['instantiateAbilityClass']
		);

		$this->setInaccessiblePropertyValue($abstractProvider, 'abilities', [$abilityMakerClassName]);

		$abstractProvider->expects($this->never())
			->method('instantiateAbilityClass');

		WP_Mock::userFunction('wc_doing_it_wrong')
			->once()
			->with(
				sprintf('%s::getAbilities', AbstractAbilitiesProvider::class),
				'Ability class "stdClass" must implement '.MakesAbilityContract::class.'.',
				'6.1.0'
			);

		$this->assertSame(
			[],
			$this->invokeInaccessibleMethod($abstractProvider, 'getAbilities')
		);
	}
}
