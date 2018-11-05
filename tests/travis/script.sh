#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

BASH="docker-compose run cli bash"

case $TEST_SUITE in
    static-unit)
        $BASH -c "${DIR_TOOLS}/vendor/bin/phpcs ${DIR_TOOLS}/src --standard=${DIR_TOOLS}/tests/static/phpcs-ruleset.xml -p -n"
        $BASH -c "${DIR_TOOLS}/vendor/bin/phpmd ${DIR_TOOLS}/src xml ${DIR_TOOLS}/tests/static/phpmd-ruleset.xml"
        $BASH -c "${DIR_TOOLS}/vendor/bin/phpunit --configuration ${DIR_TOOLS}/tests/unit --coverage-clover ${DIR_TOOLS}/tests/unit/tmp/clover.xml && php ${DIR_TOOLS}/tests/unit/code-coverage.php ${DIR_TOOLS}/tests/unit/tmp/clover.xml"
        $BASH -c "${DIR_TOOLS}/vendor/bin/phpunit --configuration ${DIR_TOOLS}/tests/unit"
        ;;
    integration)
        case $TRAVIS_PHP_VERSION in
            7.0)
                $BASH -c "${DIR_TOOLS}/vendor/bin/phpunit --group php70 --verbose --configuration ${DIR_TOOLS}/tests/integration"
                ;;
            7.1)
                $BASH -c "${DIR_TOOLS}/vendor/bin/phpunit --exclude-group php70,php72 --verbose --configuration ${DIR_TOOLS}/tests/integration"
                ;;
            7.2)
                $BASH -c "${DIR_TOOLS}/vendor/bin/phpunit --group php72 --verbose --configuration ${DIR_TOOLS}/tests/integration"
                ;;
        esac
        ;;
esac
