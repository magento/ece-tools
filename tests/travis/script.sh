#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

BASH="docker-compose run cli bash"

case $TEST_SUITE in
    static-unit)
        composer test
        ;;
    integration)
        case $TRAVIS_PHP_VERSION in
            7.0)
                $BASH -c "${DIR_TOOLS}/vendor/bin/phpunit --group php70 --verbose --configuration ${DIR_TOOLS}/tests/integration"
                ;;
            7.1)
                $BASH -c "${DIR_TOOLS}/vendor/bin/phpunit --exclude-group php70 --verbose --configuration ${DIR_TOOLS}/tests/integration"
                ;;
        esac
        ;;
esac
