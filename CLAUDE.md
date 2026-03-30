# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

The SkyVerge WooCommerce Plugin Framework is a PHP library for building WooCommerce extensions. It provides base classes for plugins, payment gateways, REST APIs, background jobs, and admin UI. Plugins extend `SV_WC_Plugin` (or `SV_WC_Payment_Gateway_Plugin` for payment gateways) and inherit lifecycle management, dependency checking, settings, and WooCommerce integration.

## Build Commands

```bash
# Install dependencies
composer install
npm install

# Full build (Grunt compiles CoffeeScript/SCSS, then Parcel bundles payment gateway assets)
npm run build

# Clean payment gateway dist directory
npm run clean

# Grunt only (CoffeeScript, SCSS, translations)
grunt default
grunt build           # includes translation updates
```

## Testing

```bash
# Unit tests (uses WP_Mock, no database needed)
vendor/bin/phpunit --order-by=random

# Run a single test file
vendor/bin/phpunit tests/unit/HelperTest.php

# Run a single test method
vendor/bin/phpunit --filter testMethodName
```

Unit tests bootstrap via `tests/bootstrap.php` using WP_Mock. Integration tests require a WordPress/WooCommerce environment configured via `.env` (copy from `.dist.env`).

## Linting

```bash
# PHP compatibility check (validates PHP 7.4+ compatibility)
vendor/bin/phpcs
```

The only phpcs rule configured is `PHPCompatibility` targeting PHP 7.4+.

## Architecture

### Versioned Namespaces

The framework uses versioned namespaces (`SkyVerge\WooCommerce\PluginFramework\v6_1_2\`) so multiple framework versions can coexist. `SV_WC_Framework_Bootstrap` loads only the highest available version and initializes compatible plugins.

### Core Class Hierarchy

- **`SV_WC_Plugin`** (`woocommerce/class-sv-wc-plugin.php`) — abstract base for all plugins. Singleton pattern. Manages lifecycle, logging, REST API, blocks, abilities, admin notices.
- **`SV_WC_Payment_Gateway_Plugin`** (`woocommerce/payment-gateway/class-sv-wc-payment-gateway-plugin.php`) — extends `SV_WC_Plugin` for payment gateway plugins.
- **`SV_WC_Payment_Gateway`** (`woocommerce/payment-gateway/class-sv-wc-payment-gateway.php`) — abstract payment gateway with direct and hosted subclasses.

### Key Subsystems

- **Abilities** (`woocommerce/Abilities/`) — contract-based plugin capability system with `AbilitiesHandler` and providers.
- **API** (`woocommerce/api/`) — base API client (`SV_WC_API_Base`) with JSON/XML request/response handling and caching support.
- **Payment Gateway** (`woocommerce/payment-gateway/`) — largest subsystem. Payment forms, tokens, external checkout (Apple Pay/Google Pay), subscriptions, pre-orders, blocks integration.
- **Background Jobs** (`woocommerce/utilities/`) — async request and batch job processing via WordPress cron.
- **Lifecycle** (`woocommerce/Plugin/Lifecycle.php`) — install/upgrade/activation/deactivation routines with version migration support.
- **Blocks** (`woocommerce/Blocks/`) — WooCommerce block checkout integration.
- **Helpers** (`woocommerce/Helpers/`) — PSR-4 utility classes (Array, Number, Order, Page, Script helpers).

### Autoloading

Uses both classmap (legacy classes in `woocommerce/`) and PSR-4 (namespaced classes under `SkyVerge\WooCommerce\PluginFramework\v6_1_2\`). See `composer.json` for the full mapping.

### Asset Pipeline

CoffeeScript and SCSS source files in `woocommerce/payment-gateway/assets/` and `woocommerce/assets/` are compiled by Grunt, then Parcel bundles payment gateway JS to `woocommerce/payment-gateway/assets/dist/`.

## Conventions

- PHP 7.4 minimum, supports through PHP 8.3. Code must pass `PHPCompatibility` phpcs checks.
- CI runs PHPUnit on PHP 7.4, 8.0, 8.1, 8.2, and 8.3.
- **Legacy code** uses WordPress coding standards (tab indentation, `snake_case` methods, `class-sv-wc-*.php` file naming). Do not refactor existing legacy code to new conventions.
- **New files and new methods** should use modern PHP conventions: PascalCase class names, camelCase methods, PSR-4 autoloading, type declarations on parameters and return types, short closures where appropriate. See `woocommerce/Helpers/` and `woocommerce/Abilities/` for examples of the modern style.
