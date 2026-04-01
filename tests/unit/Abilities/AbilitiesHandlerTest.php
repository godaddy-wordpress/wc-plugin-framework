<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_4\Tests\Unit\Abilities;

use Exception;
use Generator;
use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\AbilitiesHandler;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Contracts\AbilitiesProviderContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects\Ability;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects\AbilityAnnotations;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects\AbilityCategory;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Tests\TestCase;
use WP_Mock;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\AbilitiesHandler
 */
final class AbilitiesHandlerTest extends TestCase
{
	/**
	 * @covers ::__construct
	 * @throws Exception
	 */
	public function testCanConstruct() : void
	{
		$provider = Mockery::mock(AbilitiesProviderContract::class);
		$handler = new AbilitiesHandler($provider);

		$this->assertSame($provider, $this->getInaccessiblePropertyValue($handler, 'abilitiesProvider'));
	}

	/**
	 * @covers ::addHooks
	 * @dataProvider providerCanAddHooks
	 */
	public function testCanAddHooks(bool $canUseApi) : void
	{
		$handler = $this->createPartialMock(AbilitiesHandler::class, ['canUseAbilitiesApi']);

		$handler->expects($this->once())
			->method('canUseAbilitiesApi')
			->willReturn($canUseApi);

		if ($canUseApi) {
			WP_Mock::expectActionAdded('wp_abilities_api_categories_init', [$handler, 'handleCategoriesInit']);
			WP_Mock::expectActionAdded('wp_abilities_api_init', [$handler, 'handleAbilitiesInit']);
		} else {
			WP_Mock::expectActionNotAdded('wp_abilities_api_categories_init', [$handler, 'handleCategoriesInit']);
			WP_Mock::expectActionNotAdded('wp_abilities_api_init', [$handler, 'handleAbilitiesInit']);
		}

		$handler->addHooks();

		$this->assertConditionsMet();
	}

	/** @see testCanAddHooks */
	public function providerCanAddHooks() : Generator
	{
		yield 'can use api' => [true];
		yield 'cannot use api' => [false];
	}

	/**
	 * @covers ::handleCategoriesInit
	 */
	public function testCanHandleCategoriesInit() : void
	{
		$category = new AbilityCategory('memberships', 'WooCommerce Memberships', 'Description');

		$provider = Mockery::mock(AbilitiesProviderContract::class);
		$provider->expects('getCategories')
			->once()
			->andReturn([$category]);

		$handler = new AbilitiesHandler($provider);

		WP_Mock::userFunction('wp_register_ability_category')
			->once()
			->with('memberships', [
				'label' => 'WooCommerce Memberships',
				'description' => 'Description',
				'meta' => [],
			]);

		$handler->handleCategoriesInit();

		$this->assertConditionsMet();
	}

	/**
	 * @covers ::handleAbilitiesInit
	 */
	public function testCanHandleAbilitiesInit(): void
	{
		$ability = new Ability(
			'woocommerce-memberships/create-plan',
			'Create Membership Plan',
			'Create a new plan.',
			'woocommerce-memberships',
			fn() => true,
			fn() => false,
			['type' => 'integer'],
			['type' => 'boolean'],
			new AbilityAnnotations(false, false, false)
		);

		$provider = Mockery::mock(AbilitiesProviderContract::class);
		$provider->expects('getAbilities')
			->once()
			->andReturn([$ability]);

		$handler = new AbilitiesHandler($provider);

		WP_Mock::userFunction('wp_register_ability')
			->once()
			->with('woocommerce-memberships/create-plan', [
				'label' => $ability->label,
				'description' => $ability->description,
				'category' => $ability->category,
				'execute_callback' => $ability->executeCallback,
				'permission_callback' => $ability->permissionCallback,
				'input_schema' => $ability->inputSchema,
				'output_schema' => $ability->outputSchema,
				'meta' => [
					'show_in_rest' => true,
					'annotations' => [
						'readonly' => false,
						'destructive' => false,
						'idempotent' => false,
					],
				],
			]);

		$handler->handleAbilitiesInit();

		$this->assertConditionsMet();
	}
}
