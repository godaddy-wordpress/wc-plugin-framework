# WooCommerce Plugin Framework Tests

## Setup

1) Install dependencies with Composer:

    $ composer install

2) Verify that you've installed the dependencies correctly:

    $ vendor/bin/phpunit --version

3) Have a drink, you're done!

## Running Tests

In the root directory, run phpunit:

    $ vendor/bin/phpunit

The tests will execute and you'll be presented with a summary. Code coverage documentation is automatically generated as HTML in the `tmp/coverage` directory.

Note that unit tests are run by default, see the sections below for information on running the specific test suites.

You can run specific tests by providing the path and filename to the test class:

    $ phpunit tests/unit-tests/api/webhooks

A text code coverage summary can be displayed using the `--coverage-text` option:

    $ phpunit --coverage-text

## Unit Tests

We use [wpMock](https://github.com/10up/wp_mock) to unit test without loading WordPress or WooCommerce. It is a mocking framework specifically designed for use with WordPress. Unit tests are the default test suite run when executing `phpunit`.

## Integration Tests

Integration is a bit of a misnomer here, but our goal is to test the framework code with live WordPress/WooCommerce install. To run integration tests, run:

    $ vendor/bin/phpunit --testsuite integration

TODO: installing WP/WC, etc

## Writing Tests

* Each test file should roughly correspond to an associated source file, e.g. the `plugin.php` file covers the `class-sv-wc-helper.php` file
* Each test method should cover a single method or function with a single assertion. Make liberal use of data providers to reduce code duplication and enhance the range of data tested against the method.
* A single method or function can have multiple associated test methods if it's a large or complex method
* Use the test coverage HTML report (under `tmp/coverage/index.html`) to examine which lines your tests are covering and aim for 100% coverage
* For code that cannot be tested (e.g. conditional code for a certain PHP version or a missing extension), you can exclude the code from coverage using a comment: `// @codeCoverageIgnoreStart` and `// @codeCoverageIgnoreEnd`.
* In addition to covering each line of a method/function, make sure to test common input and edge cases.
* Prefer `assertsEquals()` where possible as it tests both type & equality
* Remember that only methods prefixed with `test` will be run so use helper methods liberally to keep test methods small and reduce code duplication. If there is a common helper method used in multiple test files, consider adding it to the `Test_Case` class so it can be shared.
* When writing integration tests, remember that filters persist between test cases so be sure to remove them in your test method or in the `tearDown()` method.

## Automated Tests

Tests are automatically run with [Travis-CI](https://travis-ci.org) for each commit and pull request.

## Code Coverage

Code coverage is available on [Coveralls](https://coveralls.io/) which receives updated data after each Travis build.

## TODO
 * Automatically test PHP 5.2/5.3 compat using [PHPCompatibility](https://github.com/wimg/PHPCompatibility)
