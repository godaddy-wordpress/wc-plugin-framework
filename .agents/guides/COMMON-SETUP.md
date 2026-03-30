# Common Setup

Shared discovery and preparation steps for all implementation guides. Complete these steps before following any specific guide.

---

## Step 1: Codebase Discovery

Before writing any code, read the plugin's existing source to establish context.

1. **Main plugin class** — Find the file that extends `SV_WC_Plugin` (or `SV_WC_Payment_Gateway_Plugin`). This is typically in the plugin root or `src/`. Read it to identify:
   - The plugin's PHP namespace (e.g., `SkyVerge\WooCommerce\LocalPickupPlus`)
   - The plugin slug (usually defined as a class constant, e.g., `const PLUGIN_ID = 'woocommerce-local-pickup-plus'`)
   - The text domain (look for existing `__()` or `esc_html__()` calls, or the `Text Domain:` header in the main plugin file)

2. **Domain objects** — Identify the classes that represent the plugin's core data (e.g., `WC_Local_Pickup_Plus_Pickup_Location`). Look in `src/` or `includes/` for classes with getters like `get_id()`, `get_name()`, etc. These are candidates for serialization and ability exposure.

3. **Service/manager classes** — Find the classes responsible for CRUD operations on domain objects (e.g., a handler that queries, creates, or deletes entities). These are what ability execute callbacks will delegate to. Look for classes with methods like `get_*()`, `find_*()`, `delete_*()`, or classes that interact with `WP_Query`, `$wpdb`, or custom post types.

4. **Plugin instance accessor** — Identify the global function that returns the plugin singleton (e.g., `wc_local_pickup_plus()`). Ability execute callbacks use this to access the plugin's service layer.

5. **Framework version** — Check the framework version in `composer.json` or `composer.lock` under `skyverge/wc-plugin-framework`. Note the version number for use in namespace imports (see [Framework Version in Namespaces](#framework-version-in-namespaces) below).

6. **Existing patterns** — Check if the plugin already has any abilities, serializers, or test infrastructure. If so, follow the established patterns rather than starting from scratch.

---

## Step 2: Placeholder Resolution

Implementation guides use placeholders wrapped in `{}`. Resolve all of them before writing code, using what you discovered in Step 1.

| Placeholder | How to resolve | Example |
|---|---|---|
| `{Plugin_Namespace}` | The PHP namespace from the main plugin class | `LocalPickupPlus` |
| `{plugin-slug}` | The plugin slug constant or text domain | `woocommerce-local-pickup-plus` |
| `{text-domain}` | The WordPress text domain from existing `__()` calls or plugin header | `woocommerce-local-pickup-plus` |
| `{DomainArea}` | PascalCase name for the domain area being implemented | `PickupLocations` |
| `{domain-area}` | Kebab-case version of the domain area, used in ability names | `pickup-locations` |
| `{Entity}` | Singular PascalCase name of the domain object | `PickupLocation` |
| `{Entities}` | Plural PascalCase name | `PickupLocations` |
| `{entityVar}` | Camel case variable name for a single entity | `$pickupLocation` |
| `{entitiesVar}` | Camel case variable name for a collection | `$pickupLocations` |

If the plugin has multiple domain areas (e.g., a shipping plugin with both "pickup locations" and "shipping zones"), resolve these placeholders separately for each area.

---

## Framework Version in Namespaces

Code examples across all guides reference the framework with a specific version in the namespace, for example:

```php
use SkyVerge\WooCommerce\PluginFramework\v6_1_2\Abilities\Contracts\JsonSerializable;
```

The version segment (`v6_1_2`) must match the actual framework version installed in the plugin. To find it:

1. Check `composer.lock` for the installed version of `skyverge/wc-plugin-framework`
2. Or look at existing `use` statements in the plugin's source code — they will already reference the correct version

Convert the version to underscore format: `6.1.2` becomes `v6_1_2`.

Do **not** copy version numbers from guide examples verbatim — always verify against the plugin's actual dependency.

---

## Conventions

These apply across all implementation guides:

- **Method visibility** — Declare helper methods as `protected` unless there is a reason to make them `public`. Test protected methods using `invokeInaccessibleMethod()` from the base `TestCase`.
- **File naming** — Class files are named after the class: `GetPickupLocation.php` for `class GetPickupLocation`.
- **Coding style** — Follow the existing plugin's style. If the plugin uses WordPress coding standards (snake_case functions, tabs for indentation), match that in non-framework code. Framework-facing code (ability classes, providers) uses PSR-style (camelCase methods, 4-space indentation) as established by the framework interfaces.

---

## Verification Pattern

After completing each step in an implementation guide, verify your work before proceeding:

- **After writing classes** — Run `./vendor/bin/phpunit` to confirm no syntax errors or autoloading issues.
- **After writing tests** — Run `./vendor/bin/phpunit` and confirm the new tests pass.
- **After all steps** — Run the full test suite and review the checklist at the end of the guide.

Do not write all files in one pass and test at the end. Incremental verification catches issues early.
