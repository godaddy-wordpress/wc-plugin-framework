# WooCommerce Plugin Framework Tests

## Setup

1) Install dependencies with Composer:

    $ composer install

2) Copy the `/.dist.env` file to `/.env` and fill in your local environment variables. Note: be sure to use a local environment that is separate from your usual development environment.
2) Copy the `/codeception.local.yml` file to `/codeception.yml`. You can use this file to add any custom Codeception configurations

## Running Tests

From the root directory, run:
- `vendor/bin/codecept run integration` for integration tests
- `vendor/bin/codecept run unit` for unit tests
