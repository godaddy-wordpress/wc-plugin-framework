# Abilities Implementation Guide

Instructions and examples for adding WordPress Abilities API support to SkyVerge WooCommerce plugins.

> **Prerequisite:** Complete the steps in [COMMON-SETUP.md](./COMMON-SETUP.md) before starting this guide. You will need the resolved placeholders and codebase context from that document.

**When to use this guide:** You have a SkyVerge WooCommerce plugin and want to expose its domain operations through the WordPress Abilities API.

**Expected input:** The plugin name and which domain entities/operations to expose as abilities (e.g., "add get, list, delete, and search-by-address abilities for pickup locations").

**What this guide produces:**
- A Provider class that registers ability categories and classes
- One ability class per operation (get, list, delete, search, etc.)
- JSON serializers for domain objects
- Unit tests for all of the above
- A QA document with copy-and-pasteable verification snippets

---

## Table of Contents

1. [Overview](#overview)
2. [Directory Structure](#directory-structure)
3. [Framework Interfaces](#framework-interfaces)
4. [Step 1: JSON Serialization](#step-1-json-serialization)
5. [Step 2: Provider](#step-2-provider)
6. [Step 3: Individual Abilities](#step-3-individual-abilities)
7. [Step 4: Test Infrastructure](#step-4-test-infrastructure)
8. [Step 5: Unit Tests](#step-5-unit-tests)
9. [Step 6: QA Steps](#step-6-qa-steps)
10. [Annotations Reference](#annotations-reference)
11. [Checklist](#checklist)

---

## Overview

Each plugin exposes its domain operations as **abilities** — discrete, schema-documented actions that can be discovered and executed through the WordPress Abilities REST API. The implementation is built on the SkyVerge Plugin Framework's abilities layer.

**Key concepts:**
- **Provider** — a single class per plugin that registers all ability categories and ability classes.
- **Ability class** — one class per ability, implementing `MakesAbilityContract`. Each produces an `Ability` data object.
- **JSON serializer** — converts domain objects to arrays for ability output. Implements the framework's `JsonSerializable` contract so schemas stay co-located with serialization logic.
- **Annotations** — metadata flags (`readonly`, `destructive`, `idempotent`) that describe an ability's behavior to clients.

**Prerequisites:**
- Version 6.1+ of the `skyverge/wc-plugin-framework` Composer dependency. See [COMMON-SETUP.md](./COMMON-SETUP.md#framework-version-in-namespaces) for how to resolve the framework version in namespace imports used throughout this guide.

**Assumptions:**
- Using PHPUnit for testing. If not available, skip the unit tests.
- Using 10up/wp_mock dev dependency for testing - provides access to `mockStaticMethod()` test helper.

---

## Directory Structure

```
src/
├── Abilities/
│   └── Provider.php                          # Registers categories + ability classes
├── {DomainArea}/
│   ├── Abilities/
│   │   ├── Get{Entity}.php                   # Read-one ability
│   │   ├── List{Entities}.php                # Read-many ability
│   │   ├── Delete{Entity}.php                # Destructive ability
│   │   └── Search{Entities}By{Field}.php     # Search/filter ability
│   ├── Adapters/
│   │   └── JsonSerializers/
│   │       └── {Entity}Serializer.php        # Converts domain object → array + schema
│   └── Exceptions/                           # Domain-specific exceptions (optional)
tests/
├── bootstrap.php
├── TestCase.php
├── Mocks/
│   └── WP_Error.php
├── Unit/
│   ├── Abilities/
│   │   └── ProviderTest.php
│   ├── {DomainArea}/
│   │   ├── Abilities/
│   │   │   ├── Get{Entity}Test.php
│   │   │   ├── List{Entities}Test.php
│   │   │   ├── Delete{Entity}Test.php
│   │   │   └── Search{Entities}By{Field}Test.php
│   │   └── Adapters/
│   │       └── JsonSerializers/
│   │           └── {Entity}SerializerTest.php
│   └── Traits/
│       └── CanAssertAbilityPermissionCallbackTrait.php
```

---

## Framework Interfaces

The abilities system depends on these framework contracts from the `wc-plugin-framework` package:

### `MakesAbilityContract`

Each ability class implements this. It requires a single method:

```php
interface MakesAbilityContract
{
    public function makeAbility(): Ability;
}
```

### `AbstractAbilitiesProvider`

The Provider extends this. It requires a `$plugin` constructor arg (`SV_WC_Plugin`) and reads from a `$abilities` array property:

```php
abstract class AbstractAbilitiesProvider implements AbilitiesProviderContract
{
    protected SV_WC_Plugin $plugin;

    /** @var class-string<MakesAbilityContract>[] */
    protected array $abilities = [];

    public function getCategories(): array;   // Override to return AbilityCategory[]
    public function getAbilities(): array;    // Iterates $abilities, calls makeAbility()
}
```

### `JsonSerializable` (framework contract)

Extends PHP's native `\JsonSerializable` with a static schema method:

```php
interface JsonSerializable extends \JsonSerializable
{
    public static function getJsonSchema(): array;
}
```

### Data Objects

```php
// Ability constructor signature:
new Ability(
    string $name,                // 'plugin-slug/entity-action'
    string $label,               // Human-readable label
    string $description,         // What the ability does
    string $category,            // Category slug
    callable $executeCallback,   // The business logic
    callable $permissionCallback,// Authorization check
    array $inputSchema = [],     // JSON Schema for input
    array $outputSchema = [],    // JSON Schema for output
    ?AbilityAnnotations $annotations = null,
    bool $showInRest = true
);

// AbilityAnnotations constructor:
new AbilityAnnotations(
    bool $readonly = false,
    bool $destructive = false,
    bool $idempotent = false
);

// AbilityCategory constructor:
new AbilityCategory(
    string $slug,
    string $label,
    string $description,
    array $meta = []
);
```

---

## Step 1: JSON Serialization

Before writing abilities, make your domain objects serializable with co-located schemas. There are two approaches, depending on preference and whether or not you own the class.

An important note: if PHP 7.4 compatible be sure to add `#[\ReturnTypeWillChange]` to the `jsonSerialize()` method.

### Which option to use

Use **Option A** when all of the following are true:
- The domain class is in the plugin's own `src/` or `includes/` directory (not in `vendor/`)
- The class has 4 or fewer serializable properties
- The class has no nested objects that need their own serialization

Otherwise, use **Option B**.

### Option A: Implement on the domain class directly

Implement the framework `JsonSerializable` contract directly on the domain class:

```php
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\Contracts\JsonSerializable;

class WC_My_Plugin_Entity implements JsonSerializable
{
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id'   => $this->get_id(),
            'name' => $this->get_name(),
            // ... all fields
        ];
    }

    public static function getJsonSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'   => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                // ... all fields with types and descriptions
            ],
        ];
    }
}
```

### Option B: External serializer class

Use this when the domain class has more than 4 properties, has nested objects, or lives in `vendor/` and cannot be modified:

```php
class EntitySerializer
{
    public static function convert(WC_My_Plugin_Entity $entity): array
    {
        return [
            'id'      => $entity->get_id(),
            'name'    => $entity->get_name(),
            'address' => $entity->get_address()->jsonSerialize(),  // Nested serializable
            'adjustment' => ! $entity->get_adjustment()->is_null()
                ? $entity->get_adjustment()->jsonSerialize()
                : null,
        ];
    }

    public static function getJsonSchema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'      => ['type' => 'integer'],
                'name'    => ['type' => 'string'],
                'address' => WC_My_Plugin_Address::getJsonSchema(),  // Reference nested schema
                'adjustment' => [
                    'oneOf' => [
                        ['type' => 'null'],
                        WC_My_Plugin_Adjustment::getJsonSchema(),    // Nullable nested
                    ],
                ],
            ],
        ];
    }
}
```

Then delegate from the domain class:

```php
class WC_My_Plugin_Entity implements JsonSerializable
{
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return EntitySerializer::convert($this);
    }

    public static function getJsonSchema(): array
    {
        return EntitySerializer::getJsonSchema();
    }
}
```

### Schema guidelines

- Every property should have a `type` and ideally a `description`.
- Use `oneOf: [null, schema]` for nullable nested objects.
- Use `enum` for fixed sets of values.
- Use `minimum`, `minProperties`, `additionalProperties` where appropriate to enforce constraints. Arguments that accept any WP_Query arg should document "VIP" properties and then set `additionalProperties` to `true` to indicate any WP_Query arg can be used.
- Descriptions should be short, practical, and include format hints (e.g. `"Country code (e.g. \"US\")."`).

### Nested and sub-object serialization

When your primary serializable object references other objects (e.g. an entity has rules, items, or addresses), the first consideration should be making those sub-objects serializable as well. This keeps serialization logic co-located with the class that understands its own structure.

**Avoid** inline serialization of sub-objects:

```php
public function jsonSerialize()
{
    return [
        'id'    => $this->get_id(),
        'rules' => array_map(function ($rule) {
            return [
                'property' => $rule->get_property(),
                'operator' => $rule->get_operator(),
                'values'   => $rule->get_values(),
            ];
        }, $this->get_rules()),
    ];
}
```

**Instead**, make the sub-object serializable and delegate to it:

```php
public function jsonSerialize()
{
    return [
        'id'    => $this->get_id(),
        'rules' => array_map(function ($rule) {
            return $rule->jsonSerialize();
        }, $this->get_rules()),
    ];
}
```

This applies to both Option A and Option B. If the sub-object class has an abstract base class (e.g. a `Rule` base with `CartSubtotal`, `ProductOrCategory` concrete types), implement `JsonSerializable` on the base class with sensible defaults so that:

- **Concrete subclasses** override only when their structure differs from the default (e.g. a rule with min/max values instead of a generic property/operator/values shape).
- **Third-party subclasses** (from extensions or filters) get working serialization for free without needing to know about the contract.

The same principle applies to `getJsonSchema()` — put defaults on the base class and only override where the subclass has genuinely different behavior.

---

## Step 2: Provider

One Provider class per plugin. Registers categories and lists ability classes.

```php
namespace SkyVerge\WooCommerce\{Plugin_Namespace}\Abilities;

use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\AbstractAbilitiesProvider;
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\DataObjects\AbilityCategory;

defined('ABSPATH') or exit;

class Provider extends AbstractAbilitiesProvider
{
    // Define one constant per category
    const ENTITY_CATEGORY_SLUG = 'your-plugin-slug-entities';

    /** @inheritDoc */
    protected array $abilities = [
        GetEntity::class,
        ListEntities::class,
        DeleteEntity::class,
        // ... add each ability class here
    ];

    /** @inheritDoc */
    public function getCategories(): array
    {
        return [
            new AbilityCategory(
                static::ENTITY_CATEGORY_SLUG,
                __('Your Plugin Entities', 'your-text-domain'),
                __('Abilities related to your plugin entities.', 'your-text-domain'),
            ),
            // Add more categories if the plugin spans multiple domain areas
        ];
    }
}
```

**Conventions:**
- Category slug format: `{plugin-slug}-{domain-area}` (e.g. `woocommerce-local-pickup-plus-pickup-locations`)
- Categories map to logical groupings of your domain, not 1:1 with entities.
- Every new ability class must be added to the `$abilities` array.

### Wiring the Provider into the Plugin class

The plugin's main class (extending `SV_WC_Plugin`) must implement `HasAbilitiesContract` and return the Provider. The framework auto-detects this contract in its constructor and registers all abilities when `wp_register_ability` is available.

```php
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\Contracts\AbilitiesProviderContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\Contracts\HasAbilitiesContract;

class Plugin extends Framework\SV_WC_Plugin implements HasAbilitiesContract
{
    // ... existing code ...

    public function getAbilitiesProvider() : AbilitiesProviderContract
    {
        return new Provider($this);
    }
}
```

**How it works:** `SV_WC_Plugin::__construct()` checks `$this instanceof HasAbilitiesContract && function_exists('wp_register_ability')`. When both are true, it creates an `AbilitiesHandler` that calls `getAbilitiesProvider()` to register all categories and abilities. No additional hooks or manual registration are needed.

---

## Step 3: Individual Abilities

Each ability is a single class implementing `MakesAbilityContract`. Below are templates for common CRUD patterns.

### Ability name convention

```
{plugin-slug}/{domain-area}-{action}
```

Examples:
- `woocommerce-local-pickup-plus/pickup-locations-get`
- `woocommerce-local-pickup-plus/pickup-locations-list`
- `woocommerce-local-pickup-plus/pickup-locations-delete`
- `woocommerce-local-pickup-plus/pickup-locations-search-by-address`

### Get (read single entity by ID)

```php
namespace SkyVerge\WooCommerce\{Plugin_Namespace}\{DomainArea}\Abilities;

use SkyVerge\WooCommerce\{Plugin_Namespace}\Abilities\Provider;
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\Contracts\MakesAbilityContract;
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\DataObjects\Ability;
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\DataObjects\AbilityAnnotations;
use WP_Error;

defined('ABSPATH') or exit;

class GetEntity implements MakesAbilityContract
{
    const NAME = 'your-plugin-slug/entities-get';

    public function makeAbility(): Ability
    {
        return new Ability(
            static::NAME,
            __('Get Entity', 'your-text-domain'),
            __('Retrieves an entity by ID.', 'your-text-domain'),
            Provider::ENTITY_CATEGORY_SLUG,
            function (int $entityId) {
                $entity  = wc_my_plugin()->get_entities_handler()->get_entity($entityId);

                if (! $entity) {
                    return new WP_Error(
                        'entity_not_found',
                        __('Entity not found.', 'your-text-domain'),
                        ['status' => 404]
                    );
                }

                return $entity;
            },
            function () {
                return current_user_can('manage_woocommerce');
            },
            $this->getInputSchema(),
            WC_My_Plugin_Entity::getJsonSchema(),
            new AbilityAnnotations(true, false, true),   // readonly, not destructive, idempotent
            true
        );
    }

    protected function getInputSchema(): array
    {
        return [
            'type'        => 'integer',
            'description' => __('The entity ID.', 'your-text-domain'),
            'required'    => true,
            'minimum'     => 1,
        ];
    }
}
```

### List (read collection)

```php
class ListEntities implements MakesAbilityContract
{
    const NAME = 'your-plugin-slug/entities-list';

    public function makeAbility(): Ability
    {
        return new Ability(
            static::NAME,
            __('List Entities', 'your-text-domain'),
            __('Retrieves a collection of entities.', 'your-text-domain'),
            Provider::ENTITY_CATEGORY_SLUG,
            function (array $params = []) {
                return array_values(wc_my_plugin()->get_entities_handler()->get_entities($params));
            },
            function () {
                return current_user_can('manage_woocommerce');
            },
            $this->getInputSchema(),
            [
                'type'  => 'array',
                'items' => WC_My_Plugin_Entity::getJsonSchema(),
            ],
            new AbilityAnnotations(true, false, true),   // readonly, not destructive, idempotent
            true
        );
    }

    protected function getInputSchema(): array
    {
        return [
            'type'                 => 'object',
            'default'              => [],
            'additionalProperties' => true,
            'description'          => __('Query parameters.', 'your-text-domain'),
            'properties'           => [
                'posts_per_page' => [
                    'type'        => 'integer',
                    'description' => __('Number of results. Use -1 for all.', 'your-text-domain'),
                    'default'     => -1,
                ],
                'paged' => [
                    'type'        => 'integer',
                    'description' => __('Page number.', 'your-text-domain'),
                    'minimum'     => 1,
                ],
                // ... add domain-relevant query params
            ],
        ];
    }
}
```

### Delete (destructive)

```php
class DeleteEntity implements MakesAbilityContract
{
    const NAME = 'your-plugin-slug/entities-delete';

    public function makeAbility(): Ability
    {
        return new Ability(
            static::NAME,
            __('Delete Entity', 'your-text-domain'),
            __('Deletes an entity by ID.', 'your-text-domain'),
            Provider::ENTITY_CATEGORY_SLUG,
            function (int $entityId) {
                try {
                    return wc_my_plugin()->get_entities_handler()->delete_entity($entityId);
                } catch (EntityNotFoundException $e) {
                    return new WP_Error('entity_not_found', $e->getMessage(), ['status' => (int) $e->getCode()]);
                } catch (EntityDeleteFailedException $e) {
                    return new WP_Error('delete_failed', $e->getMessage(), ['status' => (int) $e->getCode()]);
                }
            },
            function () {
                return current_user_can('manage_woocommerce');
            },
            $this->getInputSchema(),
            WC_My_Plugin_Entity::getJsonSchema(),
            new AbilityAnnotations(false, true, false),  // NOT readonly, destructive, NOT idempotent
            true
        );
    }

    protected function getInputSchema(): array
    {
        return [
            'type'        => 'integer',
            'description' => __('The entity ID.', 'your-text-domain'),
            'required'    => true,
            'minimum'     => 1,
        ];
    }
}
```

### Search / filter (complex input)

```php
class SearchEntitiesByAddress implements MakesAbilityContract
{
    const NAME = 'your-plugin-slug/entities-search-by-address';

    public function makeAbility(): Ability
    {
        return new Ability(
            static::NAME,
            __('Search Entities by Address', 'your-text-domain'),
            __('Searches for entities near a given address.', 'your-text-domain'),
            Provider::ENTITY_CATEGORY_SLUG,
            function (array $params) {
                // Guard: ensure at least one non-empty field
                $searchableFields = array_filter($params, function ($value) {
                    return ! empty($value);
                });

                if (empty($searchableFields)) {
                    return new WP_Error(
                        'missing_search_fields',
                        __('At least one non-empty search field must be provided.', 'your-text-domain'),
                        ['status' => 422]
                    );
                }

                return array_values(wc_my_plugin()->get_entities_handler()->search_entities($searchableFields));
            },
            function () {
                return current_user_can('manage_woocommerce');
            },
            $this->getInputSchema(),
            [
                'type'  => 'array',
                'items' => WC_My_Plugin_Entity::getJsonSchema(),
            ],
            new AbilityAnnotations(true, false, true),
            true
        );
    }

    protected function getInputSchema(): array
    {
        // Derive from a shared schema and customize
        $schema = WC_My_Plugin_Address::getJsonSchema();

        unset($schema['properties']['address_2']);  // Remove irrelevant fields

        $schema['description']          = __('Address fields to search by. At least one field must be provided.', 'your-text-domain');
        $schema['minProperties']        = 1;
        $schema['additionalProperties'] = false;

        return $schema;
    }
}
```

### Key patterns

- **Execute callback:** Should not contain business logic. Delegate to the plugin's existing service/handler layer via the plugin singleton (e.g., `wc_my_plugin()->get_entities_handler()->get_entity($id)`). The ability class itself should have no data-access methods.
- **Error handling:** Return `WP_Error` for expected failures (not found, validation). Include `['status' => $httpCode]` in data.
- **Output normalization:** Use `array_values()` when returning results from methods that return associative arrays keyed by ID.
- **Permission callback:** Use `current_user_can('manage_woocommerce')` for admin abilities. Use more granular caps when appropriate.
- **Schema reuse:** Reference `Entity::getJsonSchema()` for both output schemas and as a base for input schemas.
- **Input validation guards:** When the schema allows empty input that would produce overly broad results, add a runtime guard that returns `WP_Error` with status `422`.

---

## Step 4: Test Infrastructure

### bootstrap.php

```php
<?php
const ABSPATH = 'foo/bar';
const MINUTE_IN_SECONDS = 60;   // Add any WP constants your code references

define('PLUGIN_ROOT_DIR', dirname(__DIR__));

require_once PLUGIN_ROOT_DIR.'/vendor/autoload.php';

// Framework files needed before Patchwork
require_once PLUGIN_ROOT_DIR.'/vendor/skyverge/wc-plugin-framework/woocommerce/class-sv-wc-plugin.php';
require_once PLUGIN_ROOT_DIR.'/vendor/skyverge/wc-plugin-framework/woocommerce/Abilities/Contracts/JsonSerializable.php';
require_once PLUGIN_ROOT_DIR.'/vendor/skyverge/wc-plugin-framework/woocommerce/class-sv-wc-helper.php';

WP_Mock::setUsePatchwork(true);
WP_Mock::bootstrap();

// Domain classes that use JsonSerializable must be loaded AFTER Patchwork init
// so that mockStaticMethod() works on their static methods
require_once PLUGIN_ROOT_DIR.'/src/path/to/domain-class.php';

// WP_Error mock (only if WP_Mock doesn't provide one)
if (! class_exists('WP_Error')) {
    require_once PLUGIN_ROOT_DIR.'/tests/Mocks/WP_Error.php';
}
```

**Important:** Classes whose static methods you need to mock with `mockStaticMethod()` must be loaded **after** `WP_Mock::bootstrap()` (which initializes Patchwork). If loaded before, Patchwork cannot intercept their static calls.

### TestCase.php

```php
namespace SkyVerge\WooCommerce\{PluginTests}\Tests;

class TestCase extends \GoDaddy\WordPress\MWC\Tests\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Require legacy class files that aren't PSR-4 autoloaded
        require_once PLUGIN_ROOT_DIR.'/class-wc-my-plugin.php';
        // ...
    }
}
```

### Mocks/WP_Error.php

A minimal mock for unit tests — avoids requiring WordPress core:

```php
<?php

class WP_Error
{
    public $code = '';
    public $message = '';
    public $data = '';

    public function __construct($code = '', $message = '', $data = '')
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
}
```

### Traits/CanAssertAbilityPermissionCallbackTrait.php

Reusable trait for asserting permission callbacks across all ability tests:

```php
namespace SkyVerge\WooCommerce\{PluginTests}\Tests\Unit\Traits;

use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\DataObjects\Ability;
use WP_Mock;

trait CanAssertAbilityPermissionCallbackTrait
{
    protected function assertAbilityRequiresManageWooCommerceCapability(Ability $ability): void
    {
        $this->assertAbilityRequiresCapability($ability, 'manage_woocommerce');
    }

    protected function assertAbilityRequiresCapability(Ability $ability, string $capabilityName): void
    {
        WP_Mock::userFunction('current_user_can')
            ->once()
            ->with($capabilityName)
            ->andReturn(false);

        $this->assertFalse(call_user_func($ability->permissionCallback));
    }
}
```

---

## Step 5: Unit Tests

Every ability needs three types of test methods, plus the Provider gets its own test.

### Provider test

```php
/**
 * @coversDefaultClass \SkyVerge\WooCommerce\{Plugin_Namespace}\Abilities\Provider
 */
final class ProviderTest extends TestCase
{
    /**
     * @covers ::getCategories
     */
    public function testGetCategoriesReturnsExpectedCategories(): void
    {
        $plugin = Mockery::mock(SV_WC_Plugin::class);
        $provider = new Provider($plugin);

        $categories = $provider->getCategories();

        $this->assertCount(1, $categories);
        $this->assertInstanceOf(AbilityCategory::class, $categories[0]);
        $this->assertSame(Provider::ENTITY_CATEGORY_SLUG, $categories[0]->slug);
    }

    /**
     * @covers ::getAbilities
     */
    public function testGetAbilitiesReturnsRegisteredAbilities(): void
    {
        $plugin = Mockery::mock(SV_WC_Plugin::class);
        $provider = new Provider($plugin);

        $abilities = $provider->getAbilities();

        $this->assertCount(3, $abilities);  // Match your actual count
        $this->assertInstanceOf(Ability::class, $abilities[0]);
        $this->assertSame(GetEntity::NAME, $abilities[0]->name);
        $this->assertSame(ListEntities::NAME, $abilities[1]->name);
        $this->assertSame(DeleteEntity::NAME, $abilities[2]->name);
    }
}
```

**Remember to update `assertCount` every time you add a new ability.**

### Ability test — `testCanMakeAbility`

Tests that `makeAbility()` returns a correctly configured `Ability` object. Mocks out `getInputSchema` and any static schema methods to isolate the test:

```php
/**
 * @coversDefaultClass \SkyVerge\WooCommerce\{Plugin_Namespace}\{DomainArea}\Abilities\GetEntity
 */
final class GetEntityTest extends TestCase
{
    use CanAssertAbilityPermissionCallbackTrait;

    /**
     * @covers ::makeAbility
     */
    public function testCanMakeAbility(): void
    {
        // Mock the output schema static method
        $this->mockStaticMethod(WC_My_Plugin_Entity::class, 'getJsonSchema')
            ->once()
            ->andReturn($outputSchema = ['key' => 'value']);

        // Partial mock to isolate getInputSchema
        $abilityClass = $this->createPartialMock(GetEntity::class, ['getInputSchema']);
        $abilityClass->expects($this->once())
            ->method('getInputSchema')
            ->willReturn($inputSchema = ['type' => 'integer']);

        $ability = $abilityClass->makeAbility();

        // Assert all Ability properties
        $this->assertSame(GetEntity::NAME, $ability->name);
        $this->assertSame('Get Entity', $ability->label);
        $this->assertSame('Retrieves an entity by ID.', $ability->description);
        $this->assertSame('your-plugin-slug-entities', $ability->category);
        $this->assertSame($inputSchema, $ability->inputSchema);
        $this->assertSame($outputSchema, $ability->outputSchema);
        $this->assertTrue($ability->showInRest);

        $this->assertEquals(
            new AbilityAnnotations(true, false, true),
            $ability->annotations
        );

        // Assert permission callback
        $this->assertAbilityRequiresManageWooCommerceCapability($ability);
    }
}
```

### Ability test — `testAbilityExecuteCallback`

Tests the happy path of the execute callback. Uses `call_user_func($ability->executeCallback, ...)`:

```php
    /**
     * @covers ::makeAbility
     */
    public function testAbilityExecuteCallback(): void
    {
        $this->mockStaticMethod(WC_My_Plugin_Entity::class, 'getJsonSchema')
            ->once()
            ->andReturn(['key' => 'value']);

        $ability = (new GetEntity())->makeAbility();

        WP_Mock::userFunction('wc_my_plugin')
            ->once()
            ->andReturn($plugin = Mockery::mock(WC_My_Plugin::class));

        $plugin->expects('get_entities_handler')
            ->once()
            ->andReturn($entities = Mockery::mock(WC_My_Plugin_Entities::class));

        $entity = Mockery::mock(WC_My_Plugin_Entity::class);
        $entities->expects('get_entity')
            ->once()
            ->with(123)
            ->andReturn($entity);

        $this->assertSame($entity, call_user_func($ability->executeCallback, 123));
    }
```

### Ability test — error paths

Test each `WP_Error` return path:

```php
    /**
     * @covers ::makeAbility
     */
    public function testAbilityExecuteCallbackWhenNotFound(): void
    {
        $this->mockStaticMethod(WC_My_Plugin_Entity::class, 'getJsonSchema')
            ->once()
            ->andReturn(['key' => 'value']);

        $ability = (new GetEntity())->makeAbility();

        // ... mock chain returning null ...

        $output = call_user_func($ability->executeCallback, 999);

        $this->assertInstanceOf(WP_Error::class, $output);
        $this->assertSame('entity_not_found', $output->code);
    }

    /**
     * @covers ::makeAbility
     */
    public function testAbilityExecuteCallbackReturnsWpErrorWhenNoSearchableFields(): void
    {
        // ... setup ...

        $ability = (new SearchEntitiesByAddress())->makeAbility();

        // Empty strings should trigger the guard
        $output = call_user_func($ability->executeCallback, ['city' => '', 'country' => '']);

        $this->assertInstanceOf(WP_Error::class, $output);
        $this->assertSame('missing_search_fields', $output->code);

        // Empty array should also trigger
        $output = call_user_func($ability->executeCallback, []);

        $this->assertInstanceOf(WP_Error::class, $output);
        $this->assertSame('missing_search_fields', $output->code);
    }
```

### Ability test — `testCanGetInputSchema`

Tests the schema returned by the protected `getInputSchema()` method using `invokeInaccessibleMethod`:

```php
    /**
     * @covers ::getInputSchema
     */
    public function testCanGetInputSchema(): void
    {
        $this->assertSame(
            [
                'type'        => 'integer',
                'description' => 'The entity ID.',
                'required'    => true,
                'minimum'     => 1,
            ],
            $this->invokeInaccessibleMethod(new GetEntity(), 'getInputSchema')
        );
    }
```

For schemas that depend on static methods (like `Address::getJsonSchema()`), you'll need to mock those before asserting.

### Serializer test

When testing the JSON schema, just use one single assertion on the entire array rather than separate assertions on each property.

```php
final class EntitySerializerTest extends TestCase
{
    public function testCanConvert(): void
    {
        $entity = Mockery::mock(WC_My_Plugin_Entity::class);
        $entity->expects('get_id')->once()->andReturn(1);
        $entity->expects('get_name')->once()->andReturn('Test');
        // ... mock all getters ...

        $result = EntitySerializer::convert($entity);

        $this->assertSame(1, $result['id']);
        $this->assertSame('Test', $result['name']);
        // ... assert all fields ...
    }

    public function testCanGetJsonSchema(): void
    {
        $schema = EntitySerializer::getJsonSchema();

        $this->assertSame(
            [], // expected schema in here
            $schema
        );
    }
}
```

---

## Step 6: QA Steps

Write a `QA.md` file in the plugin root. The purpose is to provide copy-and-pasteable PHP snippets that can be included in a GitHub PR for manual testing. Each ability section should cover a few logical scenarios (happy path, error paths, notable edge cases) without being excessive.

QA snippets use the `wp_get_ability()` / `->execute()` pattern so testers can paste them directly into a PHP execution context (e.g. WP-CLI `eval`, a mu-plugin, or a test harness).

### Template

Below is a template. Replace placeholders with your plugin's actual ability names, entity types, error codes, and field names. The output file should be named `QA.md` in the plugin root.

The file should start with a top-level heading:

`## QA - {Plugin Name} Abilities`

Then include one section per ability, following the patterns below.

#### Get ability

Section heading: `### Ability execution - get {entity}`

Execute this snippet with an existing {entity} ID:

```php
${entityVar}Id = 0; // <-- replace with a real {entity} ID
$ability = wp_get_ability('{plugin-slug}/{domain-area}-get');
${entityVar} = $ability->execute(${entityVar}Id);

var_dump(${entityVar});
```

- [ ] {Entity} object is returned

Follow up with:

```php
${entityVar}Id = 0; // <-- replace with a real {entity} ID
$ability = wp_get_ability('{plugin-slug}/{domain-area}-get');
${entityVar} = $ability->execute(${entityVar}Id);

echo json_encode(${entityVar}->jsonSerialize(), JSON_PRETTY_PRINT);
```

- [ ] Array of {entity} data is outputted

Now test with a non-existent ID:

```php
$ability = wp_get_ability('{plugin-slug}/{domain-area}-get');
$result = $ability->execute(999999);

var_dump($result);
```

- [ ] Returns a `WP_Error` with code `{entity}_not_found`

#### List ability

Section heading: `### Ability execution - list {entities}`

List all {entities}:

```php
$ability = wp_get_ability('{plugin-slug}/{domain-area}-list');
${entitiesVar} = $ability->execute();

var_dump(${entitiesVar});
```

- [ ] Returns an array of {entity} objects

With pagination:

```php
$ability = wp_get_ability('{plugin-slug}/{domain-area}-list');
${entitiesVar} = $ability->execute([
    'posts_per_page' => 2,
]);

var_dump(count(${entitiesVar}));
```

- [ ] Returns exactly 2 {entities}

#### Delete ability

Section heading: `### Ability execution - delete {entity}`

First, identify a {entity} you can safely delete (or create a throwaway one).

```php
${entityVar}Id = 0; // <-- replace with a disposable {entity} ID
$ability = wp_get_ability('{plugin-slug}/{domain-area}-delete');
$result = $ability->execute(${entityVar}Id);

var_dump($result);
```

- [ ] Returns the deleted {entity} object
- [ ] {Entity} is no longer visible in admin

Now try deleting a non-existent {entity}:

```php
$ability = wp_get_ability('{plugin-slug}/{domain-area}-delete');
$result = $ability->execute(999999);

var_dump($result);
```

- [ ] Returns a `WP_Error` with code `{entity}_not_found`

#### Search ability

Section heading: `### Ability execution - search {entities} by {field}`

Search with valid criteria:

```php
$ability = wp_get_ability('{plugin-slug}/{domain-area}-search-by-{field}');
$results = $ability->execute([
    'country' => 'US',
    'state'   => 'CA',
]);

foreach ($results as $r) {
    echo json_encode($r->jsonSerialize(), JSON_PRETTY_PRINT) . "\n";
}
```

- [ ] Returns an array of matching {entities}

Search with empty input:

```php
$ability = wp_get_ability('{plugin-slug}/{domain-area}-search-by-{field}');
$result = $ability->execute([]);

var_dump($result);
```

- [ ] Returns a `WP_Error` with code `missing_{field}_fields`

### Guidelines

- Each ability section should be a `### Ability execution - {action} {entity}` heading.
- Use ` ```php ` fenced code blocks for all snippets.
- Include `// <-- replace with ...` comments for values the tester needs to fill in.
- End each snippet with `var_dump()` or `json_encode()` so the output is visible.
- Use `- [ ]` checkboxes for assertions — testers check them off as they verify.
- Cover: happy path, serialization output, and each distinct error code.
- For destructive abilities (delete), guide the tester to create or identify a safe-to-delete record first.
- Keep it concise — 2-4 snippets per ability is usually enough.

---

## Annotations Reference

| Ability type | readonly | destructive | idempotent |
|---|---|---|---|
| Get (read one) | `true` | `false` | `true` |
| List (read many) | `true` | `false` | `true` |
| Search / filter | `true` | `false` | `true` |
| Create | `false` | `false` | `false` |
| Update | `false` | `false` | `true` |
| Delete | `false` | `true` | `false` |

---

## Checklist

When adding abilities to a plugin, verify each item:

- [ ] Domain objects implement `JsonSerializable` (or have an external serializer)
- [ ] `getJsonSchema()` covers all serialized fields with types and descriptions
- [ ] Provider class extends `AbstractAbilitiesProvider`
- [ ] Provider lists all ability classes in `$abilities` array
- [ ] Provider defines at least one `AbilityCategory`
- [ ] Plugin class implements `HasAbilitiesContract` and returns the Provider from `getAbilitiesProvider()`
- [ ] Each ability class implements `MakesAbilityContract`
- [ ] Ability names follow `{plugin-slug}/{domain-area}-{action}` format
- [ ] Each ability has correct `AbilityAnnotations` (see table above)
- [ ] Execute callbacks return `WP_Error` for expected failures with appropriate status codes
- [ ] All abilities use `current_user_can()` in their permission callback
- [ ] Unless explicitly otherwise specified, permission callback should require `manage_woocommerce` capability
- [ ] `showInRest` is `true` for all abilities exposed to the REST API
- [ ] `bootstrap.php` loads domain classes with `mockStaticMethod`-able statics **after** Patchwork init
- [ ] `WP_Error` mock exists in `tests/Mocks/`
- [ ] `CanAssertAbilityPermissionCallbackTrait` exists and is used
- [ ] Provider test asserts category count/slugs and ability count/names
- [ ] Each ability has tests for: `makeAbility`, execute callback (happy path), execute callback (error paths), `getInputSchema`
- [ ] Serializer has tests for `convert` and `getJsonSchema`
- [ ] Provider test `assertCount` is updated when abilities are added/removed
- [ ] `QA.md` written with copy-and-pasteable PHP snippets using `wp_get_ability()->execute()` for each ability
- [ ] All tests pass: `./vendor/bin/phpunit`
