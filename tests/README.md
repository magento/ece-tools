# Running tests

## Test suites

This way will run next tests:

- Static tests, including PHPMD and PHPCS
- All unit tests

This is the best way to run tests locally.

1. Navigate to working directory
2. Run `composer test` and verify results

## Unit tests

To run unit tests, you need to specify configuration file within command

```
./vendor/bin/phpunit --configuration tests/unit/phpunit.xml.dist
```

## Integration tests

These tests requires database access and created database itself.

1. Create MySQL database, for example `ece_integration_tests`
2. Navigate to `tests/integration/etc` and copy `environment.php` file:
```
cp environment.php.dist environemnt.php
```
3. Edit this file with your custom configuration, including section `relationships` and `skip_front_check`
4. Run integration tests with command
```
./vendor/bin/phpunit --configuration tests/integration/phpunit.xml.dist
```

It will create snapshot folder under `tests/integration/tmp` and clone project.
Then, all available tests will be executed.

## Code coverage

This test will show you pretty report for unit tests coverage.

1. Run `composer test-coverage`
2. Navigate to `tests/unit/tmp/coverage` and open `index.html` file in browser

! Be sure to enable xDebug for this test

