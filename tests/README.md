# WooCommerce Plugin Framework Tests

## Setup

1) Install dependencies with `composer install`
1) Copy the `.dist.env` file to `.env` and fill in your local environment variables. Note: be sure to use a local environment that is separate from your usual development environment, and point the acceptance and integration variables to two separate databases.
1) Copy the `codeception.local.yml` file to `codeception.yml`. You can use this file to add any custom Codeception configurations.

## Running Tests

From the root directory, run:
- `vendor/bin/codecept run unit` for unit tests
- `vendor/bin/codecept run integration` for integration tests

// TODO: acceptance test instructions.
