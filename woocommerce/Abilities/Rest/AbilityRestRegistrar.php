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

namespace SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Rest;

use InvalidArgumentException;
use JsonSerializable;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Contracts\AbilitiesProviderContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Contracts\RestInputAdapterContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\Contracts\RestOutputAdapterContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects\Ability;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects\AbilityAnnotations;
use SkyVerge\WooCommerce\PluginFramework\v6_1_4\Abilities\DataObjects\RestConfig;
use WP_Error;
use WP_REST_Request;

/**
 * Registers WordPress REST API routes for abilities that opt in via {@see RestConfig}.
 *
 * For each ability with a non-null {@see Ability::$restConfig}, this class registers
 * a REST route that executes the ability, applying optional input/output adapters.
 *
 * @since 6.2.0
 */
class AbilityRestRegistrar
{
    protected AbilitiesProviderContract $provider;

    /**
     * Constructor.
     *
     * @since 6.2.0
     */
    public function __construct(AbilitiesProviderContract $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Registers REST routes for all abilities that have a RestConfig.
     *
     * Should be called during the {@see 'rest_api_init'} action.
     *
     * @since 6.2.0
     */
    public function registerRoutes() : void
    {
        foreach ($this->provider->getAbilities() as $ability) {
            if ($ability->restConfig !== null) {
                $this->registerRouteForAbility($ability);
            }
        }
    }

    /**
     * Registers a single REST route for the given ability.
     *
     * @since 6.2.0
     */
    protected function registerRouteForAbility(Ability $ability) : void
    {
        $config = $ability->restConfig;
        $namespace = $this->resolveNamespace($ability);
        $method = $config->method ?? $this->inferMethod($ability->annotations);

        register_rest_route($namespace, $config->path, [
            'methods'             => $method,
            'callback'            => $this->makeCallback($ability, $config),
            'permission_callback' => $ability->permissionCallback,
            'args'                => $this->buildArgs($ability->inputSchema, $method),
            'schema'              => fn() => $this->buildResponseSchema($ability),
        ]);
    }

    /**
     * Resolves the full REST namespace (prefix + version) for an ability.
     *
     * @since 6.2.0
     */
    protected function resolveNamespace(Ability $ability) : string
    {
        $config = $ability->restConfig;
        $prefix = $config->namespace ?? $this->deriveNamespacePrefix($ability->name);

        return $prefix . '/' . $config->version;
    }

    /**
     * Derives a REST namespace prefix from the ability name.
     *
     * Extracts the segment before the first `/` and shortens the `woocommerce-` prefix to `wc-`.
     *
     * Example: `"woocommerce-memberships-for-teams/teams-create"` becomes `"wc-memberships-for-teams"`.
     *
     * @since 6.2.0
     */
    protected function deriveNamespacePrefix(string $abilityName) : string
    {
        $parts = explode('/', $abilityName, 2);
        $prefix = $parts[0];

        if (strpos($prefix, 'woocommerce-') === 0) {
            $prefix = 'wc-' . substr($prefix, strlen('woocommerce-'));
        }

        return $prefix;
    }

    /**
     * Infers the HTTP method from ability annotations.
     *
     * @since 6.2.0
     */
    protected function inferMethod(?AbilityAnnotations $annotations) : string
    {
        if ($annotations === null) {
            return 'POST';
        }

        if ($annotations->readonly) {
            return 'GET';
        }

        if ($annotations->destructive) {
            return 'DELETE';
        }

        return 'POST';
    }

    /**
     * Creates the REST callback closure for the ability.
     *
     * Flow: request -> input adapter -> wp_get_ability -> WP_Ability::execute() -> output adapter -> response.
     *
     * Uses the WordPress Abilities API to execute the ability so that WP core handles
     * input normalization, input/output schema validation, permission checks, and lifecycle hooks.
     *
     * @since 6.2.0
     */
    protected function makeCallback(Ability $ability, RestConfig $config) : callable
    {
        return function (WP_REST_Request $request) use ($ability, $config) {
            $input = $this->adaptInput($request, $config, $ability);

            $wpAbility = wp_get_ability($ability->name);

            if (! $wpAbility) {
                return new WP_Error(
                    'ability_not_found',
                    sprintf('Ability "%s" is not registered.', $ability->name),
                    ['status' => 500]
                );
            }

            $result = $wpAbility->execute($input);

            if ($result instanceof WP_Error) {
                return $result;
            }

            $output = $this->adaptOutput($result, $config);

            return rest_ensure_response($output);
        };
    }

    /**
     * Adapts the REST request into the format the ability's execute callback expects.
     *
     * @since 6.2.0
     *
     * @return mixed
     */
    protected function adaptInput(WP_REST_Request $request, RestConfig $config, Ability $ability)
    {
        if ($config->inputAdapter !== null) {
            $adapter = $this->instantiateInputAdapter($config->inputAdapter);

            return $adapter->adapt($request);
        }

        return $this->extractDefaultInput($request, $ability->inputSchema);
    }

    /**
     * Instantiates the input adapter class and validates it implements the contract.
     *
     * @since 6.2.0
     *
     * @param class-string<RestInputAdapterContract> $className
     */
    protected function instantiateInputAdapter(string $className) : RestInputAdapterContract
    {
        $adapter = new $className();

        if (! $adapter instanceof RestInputAdapterContract) {
            throw new InvalidArgumentException(
                sprintf('Input adapter "%s" must implement %s.', $className, RestInputAdapterContract::class)
            );
        }

        return $adapter;
    }

    /**
     * Extracts input from the request when no adapter is provided.
     *
     * For object-type schemas, returns all request params as an array.
     * For scalar-type schemas (e.g. integer), extracts the first URL param and casts it.
     *
     * @since 6.2.0
     *
     * @return mixed
     */
    protected function extractDefaultInput(WP_REST_Request $request, array $inputSchema)
    {
        $type = $inputSchema['type'] ?? 'object';

        if ($type === 'object') {
            return $request->get_params();
        }

        $urlParams = $request->get_url_params();

        if (count($urlParams) === 1) {
            $value = reset($urlParams);

            if ($type === 'integer') {
                return (int) $value;
            }

            return $value;
        }

        return $request->get_params();
    }

    /**
     * Adapts the ability result into REST response format.
     *
     * @since 6.2.0
     *
     * @param mixed $result
     * @return mixed
     */
    protected function adaptOutput($result, RestConfig $config)
    {
        if ($config->outputAdapter !== null) {
            $adapter = $this->instantiateOutputAdapter($config->outputAdapter);

            return $adapter->adapt($result);
        }

        return $this->serializeDefaultOutput($result);
    }

    /**
     * Instantiates the output adapter class and validates it implements the contract.
     *
     * @since 6.2.0
     *
     * @param class-string<RestOutputAdapterContract> $className
     */
    protected function instantiateOutputAdapter(string $className) : RestOutputAdapterContract
    {
        $adapter = new $className();

        if (! $adapter instanceof RestOutputAdapterContract) {
            throw new InvalidArgumentException(
                sprintf('Output adapter "%s" must implement %s.', $className, RestOutputAdapterContract::class)
            );
        }

        return $adapter;
    }

    /**
     * Default output serialization when no adapter is provided.
     *
     * Handles {@see JsonSerializable} objects and arrays of them.
     *
     * @since 6.2.0
     *
     * @param mixed $result
     * @return mixed
     */
    protected function serializeDefaultOutput($result)
    {
        if ($result instanceof JsonSerializable) {
            return $result->jsonSerialize();
        }

        if (is_array($result)) {
            return array_map(function ($item) {
                return $item instanceof JsonSerializable ? $item->jsonSerialize() : $item;
            }, $result);
        }

        return $result;
    }

    /**
     * Builds REST route args from the ability's input schema.
     *
     * Converts JSON Schema properties to WordPress REST API argument definitions.
     *
     * @since 6.2.0
     */
    protected function buildArgs(array $inputSchema, string $method) : array
    {
        if (empty($inputSchema) || ($inputSchema['type'] ?? null) !== 'object') {
            return [];
        }

        $args = [];
        $properties = $inputSchema['properties'] ?? [];

        foreach ($properties as $name => $schema) {
            $arg = [
                'description' => $schema['description'] ?? '',
                'type'        => $schema['type'] ?? 'string',
            ];

            if (! empty($schema['required'])) {
                $arg['required'] = true;
            }

            if (array_key_exists('default', $schema)) {
                $arg['default'] = $schema['default'];
            }

            if (isset($schema['enum'])) {
                $arg['enum'] = $schema['enum'];
            }

            if (isset($schema['minimum'])) {
                $arg['minimum'] = $schema['minimum'];
            }

            $args[$name] = $arg;
        }

        return $args;
    }

    /**
     * Builds the response schema from the ability's output schema.
     *
     * @since 6.2.0
     */
    protected function buildResponseSchema(Ability $ability) : array
    {
        return array_merge(
            [
                '$schema' => 'http://json-schema.org/draft-04/schema#',
                'title'   => $ability->name,
            ],
            $ability->outputSchema
        );
    }
}
