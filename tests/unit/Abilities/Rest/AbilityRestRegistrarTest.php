<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_5\Tests\Unit\Abilities\Rest;

use Exception;
use Generator;
use InvalidArgumentException;
use JsonSerializable;
use Mockery;
use SkyVerge\WooCommerce\PluginFramework\v6_1_5\Abilities\Contracts\AbilitiesProviderContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_5\Abilities\Contracts\RestInputAdapterContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_5\Abilities\Contracts\RestOutputAdapterContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_5\Abilities\DataObjects\Ability;
use SkyVerge\WooCommerce\PluginFramework\v6_1_5\Abilities\DataObjects\AbilityAnnotations;
use SkyVerge\WooCommerce\PluginFramework\v6_1_5\Abilities\DataObjects\RestConfig;
use SkyVerge\WooCommerce\PluginFramework\v6_1_5\Abilities\Rest\AbilityRestRegistrar;
use SkyVerge\WooCommerce\PluginFramework\v6_1_5\Tests\TestCase;
use stdClass;
use WP_Error;
use WP_Mock;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_1_5\Abilities\Rest\AbilityRestRegistrar
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

	/**
	 * @covers ::resolveNamespace
	 * @covers ::deriveNamespacePrefix
	 * @dataProvider providerCanResolveNamespace
	 * @throws Exception
	 */
	public function testCanResolveNamespace(Ability $ability, string $expectedNamespace) : void
	{
		$this->assertSame(
			$expectedNamespace,
			$this->invokeInaccessibleMethod(new AbilityRestRegistrar(Mockery::mock(AbilitiesProviderContract::class)), 'resolveNamespace', $ability)
		);
	}

	/** @see testCanResolveNamespace */
	public function providerCanResolveNamespace() : Generator
	{
		yield 'ability with specified namespace & version' => [
			'ability' => new Ability(
				'woocommerce-memberships-for-teams/get-team',
				'Get Team',
				'',
				'',
				fn() => true,
				fn() => true,
				[],
				[],
				null,
				true,
				new RestConfig('', 'memberships-teams', 'v2')
			),
			'expectedNamespace' => 'memberships-teams/v2',
		];

		yield 'ability with specified namespace & default version' => [
			'ability' => new Ability(
				'woocommerce-memberships-for-teams/get-team',
				'Get Team',
				'',
				'',
				fn() => true,
				fn() => true,
				[],
				[],
				null,
				true,
				new RestConfig('', 'memberships-teams')
			),
			'expectedNamespace' => 'memberships-teams/v1',
		];

		yield 'ability without specified namespace' => [
			'ability' => new Ability(
				'woocommerce-memberships-for-teams/get-team',
				'Get Team',
				'',
				'',
				fn() => true,
				fn() => true,
				[],
				[],
				null,
				true,
				new RestConfig('', null)
			),
			'expectedNamespace' => 'wc-memberships-for-teams/v1',
		];
	}

	/**
	 * @covers ::inferMethod
	 * @dataProvider providerCanInferMethod
	 * @throws Exception
	 */
	public function testCanInferMethod(?AbilityAnnotations $abilityAnnotations, string $expectedMethod) : void
	{
		$this->assertSame(
			$expectedMethod,
			$this->invokeInaccessibleMethod(new AbilityRestRegistrar(Mockery::mock(AbilitiesProviderContract::class)), 'inferMethod', $abilityAnnotations)
		);
	}

	/** @see testCanInferMethod */
	public function providerCanInferMethod() : Generator
	{
		yield 'no annotations' => [
			'abilityAnnotations' => null,
			'expectedMethod' => 'POST',
		];

		yield 'readonly' => [
			'abilityAnnotations' => new AbilityAnnotations(true),
			'expectedMethod' => 'GET',
		];

		yield 'destructive' => [
			'abilityAnnotations' => new AbilityAnnotations(false, true),
			'expectedMethod' => 'DELETE',
		];

		yield 'not readonly, not destructive' => [
			'abilityAnnotations' => new AbilityAnnotations(false, false),
			'expectedMethod' => 'POST',
		];
	}

	/**
	 * @covers ::makeCallback
	 * @throws Exception
	 */
	public function testCanMakeCallbackWhenAbilityDoesNotExist() : void
	{
		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['adaptInput', 'adaptOutput']);
		$request = Mockery::mock('WP_REST_Request');
		$ability = new Ability('name', '', '', '', fn() => true, fn() => true);
		$config = Mockery::mock(RestConfig::class);

		$registrar->expects($this->once())
			->method('adaptInput')
			->with($request, $config, $ability)
			->willReturn([]);

		WP_Mock::userFunction('wp_get_ability')
			->once()
			->with('name')
			->andReturnFalse();

		$registrar->expects($this->never())->method('adaptOutput');

		$callback = $this->invokeInaccessibleMethod($registrar, 'makeCallback', $ability, $config);

		$this->assertIsCallable($callback);

		$output = $callback($request);

		$this->assertInstanceOf(WP_Error::class, $output);
		$this->assertSame('ability_not_found', $output->get_error_code());
		$this->assertSame('Ability "name" is not registered.', $output->get_error_message());
		$this->assertSame(['status' => 500], $output->data);
	}

	/**
	 * @covers ::makeCallback
	 * @throws Exception
	 */
	public function testCanMakeCallbackWhenExecuteReturnsWpError() : void
	{
		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['adaptInput', 'adaptOutput']);
		$request = Mockery::mock('WP_REST_Request');
		$ability = new Ability('name', '', '', '', fn() => true, fn() => true);
		$config = Mockery::mock(RestConfig::class);

		$registrar->expects($this->once())
			->method('adaptInput')
			->with($request, $config, $ability)
			->willReturn($input = ['key' => 'value']);

		WP_Mock::userFunction('wp_get_ability')
			->once()
			->with('name')
			->andReturn($wpAbility = Mockery::mock('WP_Ability'));

		$wpAbility->expects('execute')
			->once()
			->with($input)
			->andReturn($wpError = Mockery::mock('WP_Error'));

		$registrar->expects($this->never())->method('adaptOutput');

		$callback = $this->invokeInaccessibleMethod($registrar, 'makeCallback', $ability, $config);

		$this->assertIsCallable($callback);

		$output = $callback($request);

		$this->assertSame($wpError, $output);
	}

	/**
	 * @covers ::makeCallback
	 * @throws Exception
	 */
	public function testCanMakeCallbackWhenExecuteSucceeds() : void
	{
		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['adaptInput', 'adaptOutput']);
		$request = Mockery::mock('WP_REST_Request');
		$ability = new Ability('name', '', '', '', fn() => true, fn() => true);
		$config = Mockery::mock(RestConfig::class);

		$registrar->expects($this->once())
			->method('adaptInput')
			->with($request, $config, $ability)
			->willReturn($input = ['key' => 'value']);

		WP_Mock::userFunction('wp_get_ability')
			->once()
			->with('name')
			->andReturn($wpAbility = Mockery::mock('WP_Ability'));

		$wpAbility->expects('execute')
			->once()
			->with($input)
			->andReturn($output = ['outputKey' => 'outputValue']);

		$registrar->expects($this->once())
			->method('adaptOutput')
			->with($output, $config)
			->willReturn($adaptedOutput = ['adaptedOutputKey' => 'adaptedOutputValue']);

		WP_Mock::userFunction('rest_ensure_response')
			->once()
			->with($adaptedOutput)
			->andReturn($finalOutput = ['finalOutputKey' => 'finalOutputValue']);

		$callback = $this->invokeInaccessibleMethod($registrar, 'makeCallback', $ability, $config);

		$this->assertIsCallable($callback);

		$output = $callback($request);

		$this->assertSame($finalOutput, $output);
	}

	/**
	 * @covers ::adaptInput
	 * @throws Exception
	 */
	public function testCanAdaptInputWhenNoInputAdapter() : void
	{
		$request = Mockery::mock('WP_REST_Request');
		$config = new RestConfig('');
		$ability = new Ability('name', '', '', '', fn() => true, fn() => true, ['key' => 'value']);

		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['extractDefaultInput']);

		$registrar->expects($this->once())
			->method('extractDefaultInput')
			->with($request, ['key' => 'value'])
			->willReturn($adapted = ['adaptedKey' => 'adaptedValue']);

		$this->assertSame($adapted, $this->invokeInaccessibleMethod($registrar, 'adaptInput', $request, $config, $ability));
	}

	/**
	 * @covers ::adaptInput
	 * @throws Exception
	 */
	public function testCanAdaptInputWhenHasInputAdapter() : void
	{
		$request = Mockery::mock('WP_REST_Request');
		$inputAdapter = Mockery::mock(RestInputAdapterContract::class);
		$config = new RestConfig('', null, 'v1', 'POST', RestInputAdapterContract::class);
		$ability = new Ability('name', '', '', '', fn() => true, fn() => true, ['key' => 'value']);

		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['instantiateInputAdapter']);

		$registrar->expects($this->once())
			->method('instantiateInputAdapter')
			->with(RestInputAdapterContract::class)
			->willReturn($inputAdapter);

		$inputAdapter->expects('adapt')
			->once()
			->with($request)
			->andReturn($adapted = ['key' => 'value']);

		$this->assertSame($adapted, $this->invokeInaccessibleMethod($registrar, 'adaptInput', $request, $config, $ability));
	}

	/**
	 * @covers ::instantiateInputAdapter
	 * @throws Exception
	 */
	public function testCanInstantiateInputAdapterWhenWrongClassType() : void
	{
		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['instantiateAdapterClass']);

		$className = 'MyTestClass';

		$registrar->expects($this->once())
			->method('instantiateAdapterClass')
			->with($className)
			->willReturn(new stdClass());

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Input adapter "MyTestClass" must implement '.RestInputAdapterContract::class);

		$this->invokeInaccessibleMethod($registrar, 'instantiateInputAdapter', $className);
	}

	/**
	 * @covers ::instantiateInputAdapter
	 * @throws Exception
	 */
	public function testCanInstantiateInputAdapter() : void
	{
		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['instantiateAdapterClass']);

		$className = 'MyTestClass';

		$registrar->expects($this->once())
			->method('instantiateAdapterClass')
			->with($className)
			->willReturn($adapter = Mockery::mock(RestInputAdapterContract::class));

		$this->assertSame(
			$adapter,
			$this->invokeInaccessibleMethod($registrar, 'instantiateInputAdapter', $className)
		);
	}

	/**
	 * @covers ::extractDefaultInput
	 */
	public function testCanExtractDefaultInput() : void
	{
		$this->markTestIncomplete('TODO');
	}

	/**
	 * @covers ::adaptOutput
	 * @throws Exception
	 */
	public function testCanAdaptOutputWhenNoOutputAdapter() : void
	{
		$config = new RestConfig('');
		$result = ['key' => 'value'];

		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['serializeDefaultOutput']);

		$registrar->expects($this->once())
			->method('serializeDefaultOutput')
			->with($result)
			->willReturn($serialized = ['serializedKey' => 'serializedValue']);

		$this->assertSame($serialized, $this->invokeInaccessibleMethod($registrar, 'adaptOutput', $result, $config));
	}

	/**
	 * @covers ::adaptOutput
	 * @throws Exception
	 */
	public function testCanAdaptOutputWhenHasOutputAdapter() : void
	{
		$outputAdapter = Mockery::mock(RestOutputAdapterContract::class);
		$config = new RestConfig('', null, 'v1', 'POST', null, RestOutputAdapterContract::class);
		$result = ['key' => 'value'];

		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['instantiateOutputAdapter']);

		$registrar->expects($this->once())
			->method('instantiateOutputAdapter')
			->with(RestOutputAdapterContract::class)
			->willReturn($outputAdapter);

		$outputAdapter->expects('adapt')
			->once()
			->with($result)
			->andReturn($adapted = ['adaptedKey' => 'adaptedValue']);

		$this->assertSame($adapted, $this->invokeInaccessibleMethod($registrar, 'adaptOutput', $result, $config));
	}

	/**
	 * @covers ::instantiateOutputAdapter
	 * @throws Exception
	 */
	public function testCanInstantiateOutputAdapterWhenWrongClassType() : void
	{
		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['instantiateAdapterClass']);

		$className = 'MyTestClass';

		$registrar->expects($this->once())
			->method('instantiateAdapterClass')
			->with($className)
			->willReturn(new stdClass());

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Output adapter "MyTestClass" must implement '.RestOutputAdapterContract::class);

		$this->invokeInaccessibleMethod($registrar, 'instantiateOutputAdapter', $className);
	}

	/**
	 * @covers ::instantiateOutputAdapter
	 * @throws Exception
	 */
	public function testCanInstantiateOutputAdapter() : void
	{
		$registrar = $this->createPartialMock(AbilityRestRegistrar::class, ['instantiateAdapterClass']);

		$className = 'MyTestClass';

		$registrar->expects($this->once())
			->method('instantiateAdapterClass')
			->with($className)
			->willReturn($adapter = Mockery::mock(RestOutputAdapterContract::class));

		$this->assertSame(
			$adapter,
			$this->invokeInaccessibleMethod($registrar, 'instantiateOutputAdapter', $className)
		);
	}

	/**
	 * @covers ::serializeDefaultOutput
	 * @throws Exception
	 */
	public function testCanSerializeDefaultOutputWhenSerializable() : void
	{
		$result = Mockery::mock(JsonSerializable::class);
		$result->expects('jsonSerialize')
			->once()
			->andReturn($output = ['key' => 'value']);

		$this->assertSame(
			$output,
			$this->invokeInaccessibleMethod(
				$this->createPartialMock(AbilityRestRegistrar::class, []),
				'serializeDefaultOutput',
				$result
			)
		);
	}

	/**
	 * @covers ::serializeDefaultOutput
	 * @throws Exception
	 */
	public function testCanSerializeDefaultOutputWhenIsArray() : void
	{
		$item1 = Mockery::mock(JsonSerializable::class);
		$item1->expects('jsonSerialize')
			->once()
			->andReturn(['first' => 'value']);

		$item2 = Mockery::mock(JsonSerializable::class);
		$item2->expects('jsonSerialize')
			->once()
			->andReturn(['second' => 'other value']);

		$this->assertSame(
			[
				['first' => 'value'],
				['second' => 'other value'],
			],
			$this->invokeInaccessibleMethod(
				$this->createPartialMock(AbilityRestRegistrar::class, []),
				'serializeDefaultOutput',
				[$item1, $item2]
			)
		);
	}

	/**
	 * @covers ::buildArgs
	 * @dataProvider providerCanBuildArgsWhenReturnsEmptyArray
	 * @throws Exception
	 */
	public function testCanBuildArgsWhenReturnsEmptyArray(array $inputSchema) : void
	{
		$this->assertSame(
			[],
			$this->invokeInaccessibleMethod(
				$this->createPartialMock(AbilityRestRegistrar::class, []),
				'buildArgs',
				$inputSchema,
				'POST'
			)
		);
	}

	/** @see testCanBuildArgsWhenReturnsEmptyArray */
	public function providerCanBuildArgsWhenReturnsEmptyArray() : Generator
	{
		yield 'empty' => [
			'inputSchema' => [],
		];

		yield 'not an object' => [
			'inputSchema' => [
				'type' => 'integer',
			],
		];
	}

	/**
	 * @covers ::buildArgs
	 * @dataProvider providerCanBuildArgs
	 * @throws Exception
	 */
	public function testCanBuildArgs(array $inputSchema, array $expectedArgs) : void
	{
		$this->assertSame(
			$expectedArgs,
			$this->invokeInaccessibleMethod(
				$this->createPartialMock(AbilityRestRegistrar::class, []),
				'buildArgs',
				$inputSchema,
				'POST'
			)
		);
	}

	/** @see testCanBuildArgs */
	public function providerCanBuildArgs() : Generator
	{
		yield 'object with empty properties' => [
			'inputSchema' => [
				'type'       => 'object',
				'properties' => [],
			],
			'expectedArgs' => [],
		];

		yield 'simple property with type and description' => [
			'inputSchema' => [
				'type'       => 'object',
				'properties' => [
					'name' => [
						'type'        => 'string',
						'description' => 'The team name',
					],
				],
			],
			'expectedArgs' => [
				'name' => [
					'description' => 'The team name',
					'type'        => 'string',
				],
			],
		];

		yield 'property with required flag' => [
			'inputSchema' => [
				'type'       => 'object',
				'properties' => [
					'team_id' => [
						'type'        => 'integer',
						'description' => 'The team ID',
						'required'    => true,
					],
				],
			],
			'expectedArgs' => [
				'team_id' => [
					'description' => 'The team ID',
					'type'        => 'integer',
					'required'    => true,
				],
			],
		];

		yield 'property with default value' => [
			'inputSchema' => [
				'type'       => 'object',
				'properties' => [
					'status' => [
						'type'        => 'string',
						'description' => 'The status',
						'default'     => 'active',
					],
				],
			],
			'expectedArgs' => [
				'status' => [
					'description' => 'The status',
					'type'        => 'string',
					'default'     => 'active',
				],
			],
		];

		yield 'property with enum constraint' => [
			'inputSchema' => [
				'type'       => 'object',
				'properties' => [
					'role' => [
						'type'        => 'string',
						'description' => 'The member role',
						'enum'        => ['member', 'manager', 'owner'],
					],
				],
			],
			'expectedArgs' => [
				'role' => [
					'description' => 'The member role',
					'type'        => 'string',
					'enum'        => ['member', 'manager', 'owner'],
				],
			],
		];

		yield 'property with minimum constraint' => [
			'inputSchema' => [
				'type'       => 'object',
				'properties' => [
					'seats' => [
						'type'        => 'integer',
						'description' => 'Number of seats',
						'minimum'     => 1,
					],
				],
			],
			'expectedArgs' => [
				'seats' => [
					'description' => 'Number of seats',
					'type'        => 'integer',
					'minimum'     => 1,
				],
			],
		];

		yield 'multiple properties with all supported attributes' => [
			'inputSchema' => [
				'type'       => 'object',
				'properties' => [
					'name' => [
						'type'        => 'string',
						'description' => 'The team name',
						'required'    => true,
					],
					'seats' => [
						'type'        => 'integer',
						'description' => 'Number of seats',
						'required'    => true,
						'minimum'     => 1,
						'default'     => 5,
					],
					'role' => [
						'type'        => 'string',
						'description' => 'Default member role',
						'enum'        => ['member', 'manager'],
						'default'     => 'member',
					],
				],
			],
			'expectedArgs' => [
				'name' => [
					'description' => 'The team name',
					'type'        => 'string',
					'required'    => true,
				],
				'seats' => [
					'description' => 'Number of seats',
					'type'        => 'integer',
					'required'    => true,
					'default'     => 5,
					'minimum'     => 1,
				],
				'role' => [
					'description' => 'Default member role',
					'type'        => 'string',
					'default'     => 'member',
					'enum'        => ['member', 'manager'],
				],
			],
		];

		yield 'property with no type defaults to string' => [
			'inputSchema' => [
				'type'       => 'object',
				'properties' => [
					'label' => [
						'description' => 'A label',
					],
				],
			],
			'expectedArgs' => [
				'label' => [
					'description' => 'A label',
					'type'        => 'string',
				],
			],
		];
	}

	/**
	 * @covers ::buildResponseSchema
	 * @throws Exception
	 */
	public function testCanBuildResponseSchema() : void
	{
		$outputSchema = ['key' => 'value'];
		$ability = new Ability('get-team', '', '', '', fn() => true, fn() => true, [], $outputSchema);

		$this->assertSame(
			[
				'$schema' => 'http://json-schema.org/draft-04/schema#',
				'title'   => 'get-team',
				'key'     => 'value',
			],
			$this->invokeInaccessibleMethod(
				$this->createPartialMock(AbilityRestRegistrar::class, []),
				'buildResponseSchema',
				$ability
			)
		);
	}

}
