<?php

namespace SkyVerge\WooCommerce\PluginFramework\v6_2_0\Tests\Unit\Abilities\DataObjects;

use SkyVerge\WooCommerce\PluginFramework\v6_2_0\Abilities\Contracts\RestInputAdapterContract;
use SkyVerge\WooCommerce\PluginFramework\v6_2_0\Abilities\Contracts\RestOutputAdapterContract;
use SkyVerge\WooCommerce\PluginFramework\v6_2_0\Abilities\DataObjects\RestConfig;
use SkyVerge\WooCommerce\PluginFramework\v6_2_0\Tests\TestCase;

/**
 * @coversDefaultClass \SkyVerge\WooCommerce\PluginFramework\v6_2_0\Abilities\DataObjects\RestConfig
 */
final class RestConfigTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testCanConstructWithDefaults() : void
    {
        $config = new RestConfig('/teams');

        $this->assertSame('/teams', $config->path);
        $this->assertNull($config->namespace);
        $this->assertSame('v1', $config->version);
        $this->assertNull($config->method);
        $this->assertNull($config->inputAdapter);
        $this->assertNull($config->outputAdapter);
    }

    /**
     * @covers ::__construct
     */
    public function testCanConstructWithAllParameters() : void
    {
        $inputAdapter = get_class(new class implements RestInputAdapterContract {
            public function adapt(\WP_REST_Request $request) { return []; }
        });

        $outputAdapter = get_class(new class implements RestOutputAdapterContract {
            public function adapt($result) { return []; }
        });

        $config = new RestConfig(
            '/teams/(?P<team_id>\d+)',
            'my-plugin',
            'v2',
            'GET',
            $inputAdapter,
            $outputAdapter
        );

        $this->assertSame('/teams/(?P<team_id>\d+)', $config->path);
        $this->assertSame('my-plugin', $config->namespace);
        $this->assertSame('v2', $config->version);
        $this->assertSame('GET', $config->method);
        $this->assertSame($inputAdapter, $config->inputAdapter);
        $this->assertSame($outputAdapter, $config->outputAdapter);
    }
}
