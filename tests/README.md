# Running tests

## Test suites

This way will run next tests:

- Static tests, including PHPMD and PHPCS
- All unit tests

This is the best way to run tests locally.

1. Navigate to working directory
2. Run `composer test` and verify results

## Unit tests

To run unit tests, specify the configuration file in the following command:

```
./vendor/bin/phpunit --configuration tests/unit/phpunit.xml.dist
```

## Static tests

1. Run PHPCS test with following command:
```
./vendor/bin/phpcs src --standard=tests/static/phpcs-ruleset.xml -p -n
```
2. Run PHPMD tests withfollowing command:
```
./vendor/bin/phpmd src xml tests/static/phpmd-ruleset.xml
```

## Integration tests

Integration tests require database access and existed database. To run, you will need to add your custom configuration to a copied environment.php file. After completing the steps, integration tests will run.

1. Create MySQL database. For example, `ece_integration_tests`
2. Navigate to `tests/integration/etc` and copy `environment.php` file:
```
cp environment.php.dist environemnt.php
```
3. Edit this file with your custom configuration, including section `relationships` and `skip_front_check => true`
4. Run integration tests with the following command:
```
./vendor/bin/phpunit --configuration tests/integration/phpunit.xml.dist
```

It will create a snapshot folder under `tests/integration/tmp` and clone the project.
Then, all available tests will be executed.

## Code coverage report

This test will generate a pretty report for unit test coverage.

1. Run the command `composer test-coverage`
2. Navigate to `tests/unit/tmp/coverage` and open `index.html` file in browser
 - Be sure to enable [xDebug](http://devdocs.magento.com/guides/v2.2/cloud/howtos/debug.html) for this test

## Best practices

- After you setup PhpStorm with PhpUnit and PHPCS, etc, it sometimes runs really slow. But, there is an icon in the bottom right corner of PhpStorm you can click on (it looks like Travis) that will let you temporarily disable inspections.

