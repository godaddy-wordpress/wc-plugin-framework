<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_4\Tests\Unit\Abilities\Rest;

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
     * @covers ::registerRoutes
     */
    public function testSkipsAbilitiesWithoutRestConfig() : void
    {
        $ability = $this->makeAbility();

        $provider = Mockery::mock(AbilitiesProviderContract::class);
        $provider->expects('getAbilities')
            ->once()
            ->andReturn([$ability]);

        WP_Mock::userFunction('register_rest_route')->never();

        $registrar = new AbilityRestRegistrar($provider);
        $registrar->registerRoutes();

        $this->assertConditionsMet();
    }

    /**
     * @covers ::registerRoutes
     * @covers ::registerRouteForAbility
     */
    public function testRegistersRouteForAbilityWithRestConfig() : void
    {
        $restConfig = new RestConfig('/teams', 'my-plugin', 'v1', 'POST');
        $ability = $this->makeAbility('test-plugin/test-action', [], [], null, $restConfig);

        $provider = Mockery::mock(AbilitiesProviderContract::class);
        $provider->expects('getAbilities')
            ->once()
            ->andReturn([$ability]);

        WP_Mock::userFunction('register_rest_route')
            ->once()
            ->withArgs(function (string $namespace, string $path, array $args) use ($ability) {
                return $namespace === 'my-plugin/v1'
                    && $path === '/teams'
                    && $args['methods'] === 'POST'
                    && $args['permission_callback'] === $ability->permissionCallback
                    && is_callable($args['callback']);
            });

        $registrar = new AbilityRestRegistrar($provider);
        $registrar->registerRoutes();

        $this->assertConditionsMet();
    }

    /**
     * @covers ::deriveNamespacePrefix
     * @dataProvider providerDeriveNamespace
     */
    public function testDeriveNamespace(string $abilityName, string $version, string $expected) : void
    {
        $restConfig = new RestConfig('/test', null, $version, 'GET');
        $ability = $this->makeAbility($abilityName, [], [], null, $restConfig);

        $provider = Mockery::mock(AbilitiesProviderContract::class);
        $provider->expects('getAbilities')
            ->once()
            ->andReturn([$ability]);

        WP_Mock::userFunction('register_rest_route')
            ->once()
            ->withArgs(function (string $namespace) use ($expected) {
                return $namespace === $expected;
            });

        $registrar = new AbilityRestRegistrar($provider);
        $registrar->registerRoutes();

        $this->assertConditionsMet();
    }

    /** @see testDeriveNamespace */
    public function providerDeriveNamespace() : Generator
    {
        yield 'woocommerce prefix shortened' => [
            'woocommerce-memberships-for-teams/teams-create',
            'v1',
            'wc-memberships-for-teams/v1',
        ];

        yield 'non-woocommerce prefix unchanged' => [
            'my-plugin/do-something',
            'v1',
            'my-plugin/v1',
        ];

        yield 'custom version' => [
            'woocommerce-test/action',
            'v2',
            'wc-test/v2',
        ];
    }

    /**
     * @covers ::inferMethod
     * @dataProvider providerInferMethod
     */
    public function testInferMethod(?AbilityAnnotations $annotations, string $expectedMethod) : void
    {
        $restConfig = new RestConfig('/test', 'ns', 'v1');
        $ability = $this->makeAbility('test-plugin/test-action', [], [], $annotations, $restConfig);

        $provider = Mockery::mock(AbilitiesProviderContract::class);
        $provider->expects('getAbilities')
            ->once()
            ->andReturn([$ability]);

        WP_Mock::userFunction('register_rest_route')
            ->once()
            ->withArgs(function (string $namespace, string $path, array $args) use ($expectedMethod) {
                return $args['methods'] === $expectedMethod;
            });

        $registrar = new AbilityRestRegistrar($provider);
        $registrar->registerRoutes();

        $this->assertConditionsMet();
    }

    /** @see testInferMethod */
    public function providerInferMethod() : Generator
    {
        yield 'null annotations defaults to POST' => [
            null,
            'POST',
        ];

        yield 'readonly maps to GET' => [
            new AbilityAnnotations(true, false, true),
            'GET',
        ];

        yield 'destructive maps to DELETE' => [
            new AbilityAnnotations(false, true, false),
            'DELETE',
        ];

        yield 'non-readonly non-destructive maps to POST' => [
            new AbilityAnnotations(false, false, false),
            'POST',
        ];
    }

    /**
     * @covers ::buildArgs
     */
    public function testBuildArgsFromObjectSchema() : void
    {
        $inputSchema = [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'The name.',
                    'required' => true,
                ],
                'count' => [
                    'type' => 'integer',
                    'description' => 'The count.',
                    'minimum' => 1,
                    'default' => 10,
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'The status.',
                    'enum' => ['active', 'inactive'],
                ],
            ],
        ];

        $restConfig = new RestConfig('/test', 'ns', 'v1', 'POST');
        $ability = $this->makeAbility('test-plugin/test-action', $inputSchema, [], null, $restConfig);

        $provider = Mockery::mock(AbilitiesProviderContract::class);
        $provider->expects('getAbilities')->once()->andReturn([$ability]);

        $capturedArgs = null;

        WP_Mock::userFunction('register_rest_route')
            ->once()
            ->withArgs(function (string $namespace, string $path, array $args) use (&$capturedArgs) {
                $capturedArgs = $args['args'];
                return true;
            });

        $registrar = new AbilityRestRegistrar($provider);
        $registrar->registerRoutes();

        $this->assertSame([
            'name' => [
                'description' => 'The name.',
                'type' => 'string',
                'required' => true,
            ],
            'count' => [
                'description' => 'The count.',
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
            ],
            'status' => [
                'description' => 'The status.',
                'type' => 'string',
                'enum' => ['active', 'inactive'],
            ],
        ], $capturedArgs);
    }

    /**
     * @covers ::buildArgs
     */
    public function testBuildArgsReturnsEmptyForScalarSchema() : void
    {
        $restConfig = new RestConfig('/test/(?P<id>\d+)', 'ns', 'v1', 'GET');
        $ability = $this->makeAbility('test-plugin/test-action', ['type' => 'integer'], [], null, $restConfig);

        $provider = Mockery::mock(AbilitiesProviderContract::class);
        $provider->expects('getAbilities')->once()->andReturn([$ability]);

        $capturedArgs = null;

        WP_Mock::userFunction('register_rest_route')
            ->once()
            ->withArgs(function (string $namespace, string $path, array $args) use (&$capturedArgs) {
                $capturedArgs = $args['args'];
                return true;
            });

        $registrar = new AbilityRestRegistrar($provider);
        $registrar->registerRoutes();

        $this->assertSame([], $capturedArgs);
    }

    /**
     * @covers ::makeCallback
     * @covers ::adaptInput
     * @covers ::extractDefaultInput
     * @covers ::adaptOutput
     * @covers ::serializeDefaultOutput
     */
    public function testCallbackFlowWithObjectSchemaAndNoAdapters() : void
    {
        $inputSchema = ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]];
        $restConfig = new RestConfig('/test', 'ns', 'v1', 'POST');
        $ability = $this->makeAbility('test-plugin/test-action', $inputSchema, [], null, $restConfig);

        $capturedCallback = $this->registerAndCaptureCallback($ability);

        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')
            ->once()
            ->andReturn(['name' => 'Test Team']);

        $wpAbility = Mockery::mock('WP_Ability');
        $wpAbility->expects('execute')
            ->once()
            ->with(['name' => 'Test Team'])
            ->andReturn(['id' => 1, 'name' => 'Test Team']);

        WP_Mock::userFunction('wp_get_ability')
            ->once()
            ->with('test-plugin/test-action')
            ->andReturn($wpAbility);

        WP_Mock::userFunction('rest_ensure_response')
            ->once()
            ->with(['id' => 1, 'name' => 'Test Team'])
            ->andReturnArg(0);

        $result = $capturedCallback($request);

        $this->assertSame(['id' => 1, 'name' => 'Test Team'], $result);
    }

    /**
     * @covers ::makeCallback
     * @covers ::adaptInput
     * @covers ::extractDefaultInput
     */
    public function testCallbackFlowWithScalarSchemaExtractsUrlParam() : void
    {
        $restConfig = new RestConfig('/test/(?P<id>\d+)', 'ns', 'v1', 'GET');
        $ability = $this->makeAbility('test-plugin/test-action', ['type' => 'integer'], [], null, $restConfig);

        $capturedCallback = $this->registerAndCaptureCallback($ability);

        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_url_params')
            ->once()
            ->andReturn(['id' => '42']);

        $wpAbility = Mockery::mock('WP_Ability');
        $wpAbility->expects('execute')
            ->once()
            ->with(42)
            ->andReturn(['id' => 42]);

        WP_Mock::userFunction('wp_get_ability')
            ->once()
            ->with('test-plugin/test-action')
            ->andReturn($wpAbility);

        WP_Mock::userFunction('rest_ensure_response')
            ->once()
            ->andReturnArg(0);

        $capturedCallback($request);

        $this->assertConditionsMet();
    }

    /**
     * @covers ::makeCallback
     */
    public function testCallbackReturnsWpErrorFromExecute() : void
    {
        $wpError = Mockery::mock('WP_Error');

        $restConfig = new RestConfig('/test', 'ns', 'v1', 'POST');
        $ability = $this->makeAbility('test-plugin/test-action', [], [], null, $restConfig);

        $capturedCallback = $this->registerAndCaptureCallback($ability);

        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')->andReturn([]);

        $wpAbility = Mockery::mock('WP_Ability');
        $wpAbility->expects('execute')
            ->once()
            ->andReturn($wpError);

        WP_Mock::userFunction('wp_get_ability')
            ->once()
            ->with('test-plugin/test-action')
            ->andReturn($wpAbility);

        WP_Mock::userFunction('rest_ensure_response')->never();

        $result = $capturedCallback($request);

        $this->assertSame($wpError, $result);
    }

    /**
     * @covers ::makeCallback
     */
    public function testCallbackReturnsErrorWhenAbilityNotFound() : void
    {
        $restConfig = new RestConfig('/test', 'ns', 'v1', 'POST');
        $ability = $this->makeAbility('test-plugin/missing-action', [], [], null, $restConfig);

        $capturedCallback = $this->registerAndCaptureCallback($ability);

        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')->andReturn([]);

        WP_Mock::userFunction('wp_get_ability')
            ->once()
            ->with('test-plugin/missing-action')
            ->andReturn(null);

        WP_Mock::userFunction('rest_ensure_response')->never();

        $result = $capturedCallback($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    /**
     * @covers ::makeCallback
     * @covers ::adaptInput
     * @covers ::instantiateInputAdapter
     */
    public function testCallbackUsesInputAdapter() : void
    {
        $adapterClass = get_class(new class implements RestInputAdapterContract {
            public function adapt(\WP_REST_Request $request) {
                return ['adapted' => true];
            }
        });

        $restConfig = new RestConfig('/test', 'ns', 'v1', 'POST', $adapterClass);
        $ability = $this->makeAbility('test-plugin/test-action', [], [], null, $restConfig);

        $capturedCallback = $this->registerAndCaptureCallback($ability);

        $request = Mockery::mock('WP_REST_Request');

        $wpAbility = Mockery::mock('WP_Ability');
        $wpAbility->expects('execute')
            ->once()
            ->with(['adapted' => true])
            ->andReturn('ok');

        WP_Mock::userFunction('wp_get_ability')
            ->once()
            ->andReturn($wpAbility);

        WP_Mock::userFunction('rest_ensure_response')->once()->andReturnArg(0);

        $capturedCallback($request);

        $this->assertConditionsMet();
    }

    /**
     * @covers ::makeCallback
     * @covers ::adaptOutput
     * @covers ::instantiateOutputAdapter
     */
    public function testCallbackUsesOutputAdapter() : void
    {
        $adapterClass = get_class(new class implements RestOutputAdapterContract {
            public function adapt($result) {
                return ['transformed' => $result];
            }
        });

        $restConfig = new RestConfig('/test', 'ns', 'v1', 'POST', null, $adapterClass);
        $ability = $this->makeAbility('test-plugin/test-action', [], [], null, $restConfig);

        $capturedCallback = $this->registerAndCaptureCallback($ability);

        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')->andReturn([]);

        $wpAbility = Mockery::mock('WP_Ability');
        $wpAbility->expects('execute')
            ->once()
            ->andReturn('raw-result');

        WP_Mock::userFunction('wp_get_ability')
            ->once()
            ->andReturn($wpAbility);

        $capturedOutput = null;

        WP_Mock::userFunction('rest_ensure_response')
            ->once()
            ->withArgs(function ($output) use (&$capturedOutput) {
                $capturedOutput = $output;
                return true;
            })
            ->andReturnArg(0);

        $capturedCallback($request);

        $this->assertSame(['transformed' => 'raw-result'], $capturedOutput);
    }

    /**
     * @covers ::instantiateInputAdapter
     */
    public function testThrowsForInvalidInputAdapter() : void
    {
        $invalidClass = get_class(new class {});

        $restConfig = new RestConfig('/test', 'ns', 'v1', 'POST', $invalidClass);
        $ability = $this->makeAbility('test-plugin/test-action', [], [], null, $restConfig);

        $capturedCallback = $this->registerAndCaptureCallback($ability);

        $request = Mockery::mock('WP_REST_Request');

        $this->expectException(\InvalidArgumentException::class);
        $capturedCallback($request);
    }

    /**
     * @covers ::instantiateOutputAdapter
     */
    public function testThrowsForInvalidOutputAdapter() : void
    {
        $invalidClass = get_class(new class {});

        $restConfig = new RestConfig('/test', 'ns', 'v1', 'POST', null, $invalidClass);
        $ability = $this->makeAbility('test-plugin/test-action', [], [], null, $restConfig);

        $capturedCallback = $this->registerAndCaptureCallback($ability);

        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')->andReturn([]);

        $wpAbility = Mockery::mock('WP_Ability');
        $wpAbility->expects('execute')
            ->once()
            ->andReturn('result');

        WP_Mock::userFunction('wp_get_ability')
            ->once()
            ->andReturn($wpAbility);

        $this->expectException(\InvalidArgumentException::class);
        $capturedCallback($request);
    }

    /**
     * @covers ::serializeDefaultOutput
     */
    public function testDefaultOutputSerializesJsonSerializable() : void
    {
        $jsonSerializable = new class implements \JsonSerializable {
            public function jsonSerialize() : array {
                return ['serialized' => true];
            }
        };

        $restConfig = new RestConfig('/test', 'ns', 'v1', 'GET');
        $ability = $this->makeAbility('test-plugin/test-action', [], [], null, $restConfig);

        $capturedCallback = $this->registerAndCaptureCallback($ability);

        $request = Mockery::mock('WP_REST_Request');
        $request->expects('get_params')->andReturn([]);

        $wpAbility = Mockery::mock('WP_Ability');
        $wpAbility->expects('execute')
            ->once()
            ->andReturn($jsonSerializable);

        WP_Mock::userFunction('wp_get_ability')
            ->once()
            ->andReturn($wpAbility);

        $capturedOutput = null;

        WP_Mock::userFunction('rest_ensure_response')
            ->once()
            ->withArgs(function ($output) use (&$capturedOutput) {
                $capturedOutput = $output;
                return true;
            })
            ->andReturnArg(0);

        $capturedCallback($request);

        $this->assertSame(['serialized' => true], $capturedOutput);
    }

    /**
     * @covers ::buildResponseSchema
     */
    public function testBuildResponseSchema() : void
    {
        $outputSchema = ['type' => 'object', 'properties' => ['id' => ['type' => 'integer']]];
        $restConfig = new RestConfig('/test', 'ns', 'v1', 'GET');
        $ability = $this->makeAbility('my-plugin/get-thing', [], $outputSchema, null, $restConfig);

        $provider = Mockery::mock(AbilitiesProviderContract::class);
        $provider->expects('getAbilities')->once()->andReturn([$ability]);

        $capturedSchemaFn = null;

        WP_Mock::userFunction('register_rest_route')
            ->once()
            ->withArgs(function (string $namespace, string $path, array $args) use (&$capturedSchemaFn) {
                $capturedSchemaFn = $args['schema'];
                return true;
            });

        $registrar = new AbilityRestRegistrar($provider);
        $registrar->registerRoutes();

        $schema = $capturedSchemaFn();

        $this->assertSame('http://json-schema.org/draft-04/schema#', $schema['$schema']);
        $this->assertSame('my-plugin/get-thing', $schema['title']);
        $this->assertSame('object', $schema['type']);
        $this->assertSame(['id' => ['type' => 'integer']], $schema['properties']);
    }

    /**
     * Registers a route for the ability and returns the captured callback.
     */
    protected function registerAndCaptureCallback(Ability $ability) : callable
    {
        $provider = Mockery::mock(AbilitiesProviderContract::class);
        $provider->expects('getAbilities')->once()->andReturn([$ability]);

        $capturedCallback = null;

        WP_Mock::userFunction('register_rest_route')
            ->once()
            ->withArgs(function (string $namespace, string $path, array $args) use (&$capturedCallback) {
                $capturedCallback = $args['callback'];
                return true;
            });

        $registrar = new AbilityRestRegistrar($provider);
        $registrar->registerRoutes();

        return $capturedCallback;
    }

    /**
     * Helper to create an Ability with sensible defaults.
     */
    protected function makeAbility(
        string $name = 'test-plugin/test-action',
        array $inputSchema = [],
        array $outputSchema = [],
        ?AbilityAnnotations $annotations = null,
        ?RestConfig $restConfig = null
    ) : Ability {
        return new Ability(
            $name,
            'Test',
            'Test ability.',
            'test-category',
            function ($input) { return $input; },
            function () { return true; },
            $inputSchema,
            $outputSchema,
            $annotations,
            true,
            $restConfig
        );
    }
}
