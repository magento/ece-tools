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

        ./bin/ece-tools docker:build:integration ${TRAVIS_PHP_VERSION} 10.0 latest
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
    docker-integration)
        ./bin/ece-tools docker:build:docker-integration ${TRAVIS_PHP_VERSION} 10.0 latest

        case $TRAVIS_PHP_VERSION in
            7.0)
                ./vendor/bin/phpunit --group php70 --verbose --configuration ./tests/docker-integration
                ;;
            7.1)
                ./vendor/bin/phpunit --group php71 --verbose --configuration ./tests/docker-integration
                ;;
            7.2)
                ./vendor/bin/phpunit --group php72 --verbose --configuration ./tests/docker-integration
                ;;
        esac
        ;;
esac
