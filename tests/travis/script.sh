#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

case $TEST_SUITE in
    static-unit)
        ./vendor/bin/phpcs ./src --standard=./tests/static/phpcs-ruleset.xml -p -n
        ./vendor/bin/phpmd ./src xml ./tests/static/phpmd-ruleset.xml
        ./vendor/bin/phpunit --configuration ./tests/unit --coverage-clover ./tests/unit/tmp/clover.xml && php ./tests/unit/code-coverage.php ./tests/unit/tmp/clover.xml
        ./vendor/bin/phpunit --configuration ./tests/unit
        ;;
    integration)
       BASH="docker-compose run cli bash"
       DIR_TOOLS="/var/www/ece-tools"

        ./bin/ece-tools docker:build:integration test-v1 --php ${TRAVIS_PHP_VERSION}
        docker-compose up -d

        case $TRAVIS_PHP_VERSION in
            7.0)
                $BASH -c "${DIR_TOOLS}/vendor/bin/phpunit --group php70 --verbose --configuration ${DIR_TOOLS}/tests/integration"
                ;;
            7.1)
                $BASH -c "${DIR_TOOLS}/vendor/bin/phpunit --group php71 --verbose --configuration ${DIR_TOOLS}/tests/integration"
                ;;
            7.2)
                $BASH -c "${DIR_TOOLS}/vendor/bin/phpunit --group php72 --verbose --configuration ${DIR_TOOLS}/tests/integration"
                ;;
        esac

        docker-compose down -v
        ;;
    functional)
        ./bin/ece-tools docker:build:integration test-v2 --php ${TRAVIS_PHP_VERSION}

        case $TRAVIS_PHP_VERSION in
            7.0)
                ./vendor/bin/codecept run -g php70 --steps
                ;;
            7.1)
                ./vendor/bin/codecept run -g php71 --steps
                ;;
            7.2)
                ./vendor/bin/codecept run -g php72 --steps
                ;;
        esac
        ;;
esac
