<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_4\Tests\Unit\Abilities\Rest;

use Exception;
use Generator;
use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Contracts\AbilitiesProviderContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Contracts\RestInputAdapterContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Contracts\RestOutputAdapterContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects\Ability;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects\AbilityAnnotations;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects\RestConfig;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Rest\AbilityRestRegistrar;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Tests\TestCase;
use WP_Mock;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Rest\AbilityRestRegistrar
 */
final class AbilityRestRegistrarTest extends TestCase
{
	/**
	 * @covers ::__construct
	 * @throws Exception
	 */
    public function testCanConstruct(): void
	{
		$provider = Mockery::mock(AbilitiesProviderContract::class);
		$registrar = new AbilityRestRegistrar($provider);

		$this->assertSame($provider, $this->getInaccessiblePropertyValue($registrar, 'provider'));
	}

	/**
	 * @covers ::registerRoutes
	 * @dataProvider providerCanRegisterRoutes
	 */
	public function testCanRegisterRoutes(bool $hasRestConfig) : void
	{
		$ability = Mockery::mock(Ability::class);
		$ability->restConfig = $hasRestConfig ? Mockery::mock(RestConfig::class) : null;

		$provider = Mockery::mock(AbilitiesProviderContract::class);
		$provider->expects('getAbilities')
			->once()
			->andReturn([$ability]);

		$registrar = $this->getMockBuilder(AbilityRestRegistrar::class)
			->setConstructorArgs([$provider])
			->onlyMethods(['registerRouteForAbility'])
			->getMock();

		$registrar->expects($hasRestConfig ? $this->once() : $this->never())
			->method('registerRouteForAbility')
			->with($ability);

		$registrar->registerRoutes();
	}

	/** @see testCanRegisterRoutes */
	public function providerCanRegisterRoutes() : Generator
	{
		yield 'has rest config' => [true];
		yield 'no rest config' => [false];
	}

	/**
	 * @covers ::registerRouteForAbility
	 * @throws Exception
	 */
	public function testCanRegisterRouteForAbility() : void
	{
		$config = new RestConfig(
			'teams',
			'woocommerce-memberships-for-teams',
			'v1',
			'GET',
			null,
			null
		);

		$ability = new Ability(
			'woocommerce-memberships-for-teams/get-team',
			'Get Team',
			'',
			'',
			fn() => true,
			fn() => true,
			[],
			[],
			new AbilityAnnotations(),
			true,
			$config
		);

		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['resolveNamespace', 'inferMethod', 'makeCallback', 'buildArgs', 'buildResponseSchema']);
		$registrar->expects($this->once())->method('resolveNamespace')->with($ability)->willReturn($namespace = 'namespace');
		$registrar->expects($this->never())->method('inferMethod');

		$registrar->expects($this->once())
			->method('makeCallback')
			->with($ability, $config)
			->willReturn($callback = fn() => true);

		$registrar->expects($this->once())
			->method('buildArgs')
			->with($ability->inputSchema, $config->method)
			->willReturn($args = ['key' => 'value']);

		$registrar->expects($this->once())
			->method('buildResponseSchema')
			->with($ability)
			->willReturn($schema = ['type' => 'object']);

		WP_Mock::userFunction('register_rest_route')
			->once()
			->withArgs(function($namespaceArg, $pathArg, $argsArg) use($namespace, $config, $callback, $ability, $args, $schema) {
				$this->assertSame($namespace, $namespaceArg);
				$this->assertSame($config->path, $pathArg);

				$this->assertSame($schema, call_user_func($argsArg['schema']));

				unset($argsArg['schema']);

				$this->assertSame(
					[
						'methods' => $config->method,
						'callback' => $callback,
						'permission_callback' => $ability->permissionCallback,
						'args' => $args,
					],
					$argsArg
				);

				return true;
			});

		$this->invokeInaccessibleMethod($registrar, 'registerRouteForAbility', $ability);
	}

	public function
}
