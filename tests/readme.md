# WooCommerce Plugin Framework Tests

The SkyVerge WooCommerce Plugin Framework uses [Codeception](https://codeception.com/) and [WP Browser](https://wpbrowser.wptestkit.dev/) to run acceptance and integration tests.

## Setup

1) Install dependencies with `composer install`
1) Copy the `.dist.env` file to `.env` and fill in your local environment variables. Note: be sure to use a local environment that is separate from your usual development environment, and point the acceptance and integration variables to two separate databases.
1) Copy the `codeception.local.yml` file to `codeception.yml`. You can use this file to add any custom Codeception configurations.

#### Integration tests

Make sure you have a database available on the machine where you'd like to run integration tests.

You can add one with:

```shell script
sudo mysql -e "CREATE DATABASE IF NOT EXISTS framework_tests"  -uroot
``` 

You may also want to have a copy of WooCommerce active in such environment. If WP CLI is available, you can run

```shell script
wp plugin install woocommerce --activate
```

#### Integration tests

Not particular setup should be required to run standard unit tests.

#### Acceptance tests

TODO

## Running Tests

From the root directory of the framework project, _on the machine where the test database lives_, run:

- `vendor/bin/codecept run acceptance` for acceptance tests
- `vendor/bin/codecept run integration` for integration tests
- `vendor/bin/codecept run unit` for unit tests

You can also specify individual tests to run. See [Codeception documentation](https://codeception.com/docs/reference/Commands).

## Adding tests

#### Unit tests

Follow the same directory structure as the framework project.

Name test files after the classes you're going to test, for example `SV_WC_Plugin` may become `SV_WC_Plugin_Test`. The public methods being tested should also match this pattern. E.g. `get_id()` becomes `test_get_id()`. 

#### Integration tests

Follow the same naming and directory structure as with unit tests.

#### Acceptance tests

TODO