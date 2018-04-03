#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

BASH="docker-compose -f ./docker-compose-${PHP}.yml run cli bash"
ECE_DIR="/var/www/magento"

case $TEST_SUITE in
    static-unit)
        $BASH -c "${ECE_DIR}/vendor/bin/phpcs ${ECE_DIR}/src --standard=${ECE_DIR}/tests/static/phpcs-ruleset.xml -p -n"
        $BASH -c "${ECE_DIR}/vendor/bin/phpmd ${ECE_DIR}/src xml ${ECE_DIR}/tests/static/phpmd-ruleset.xml"
        $BASH -c "${ECE_DIR}/vendor/bin/phpunit --configuration ${ECE_DIR}/tests/unit --coverage-clover ${ECE_DIR}/tests/unit/tmp/clover.xml && php ${ECE_DIR}/tests/unit/code-coverage.php ${ECE_DIR}/tests/unit/tmp/clover.xml"
        $BASH -c "${ECE_DIR}/vendor/bin/phpunit --configuration ${ECE_DIR}/tests/unit"
        ;;
    integration)
        case $PHP in
            7.0)
                $BASH -c "${ECE_DIR}/vendor/bin/phpunit --group php70 --verbose --configuration ${ECE_DIR}/tests/integration"
                ;;
            7.1)
                $BASH -c "${ECE_DIR}/vendor/bin/phpunit --exclude-group php70 --verbose --configuration ${ECE_DIR}/tests/integration"
                ;;
        esac
        ;;
esac
