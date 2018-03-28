#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

BASH="docker-compose -f docker-compose-$PHP.yml run cli bash"

case $TEST_SUITE in
    static-unit)
        $BASH -c "/var/www/magento/vendor/bin/phpcs /var/www/magento/src --standard=/var/www/magento/tests/static/phpcs-ruleset.xml -p -n"
        $BASH -c "/var/www/magento/vendor/bin/phpmd /var/www/magento/src xml /var/www/magento/tests/static/phpmd-ruleset.xml"
        $BASH -c "/var/www/magento/vendor/bin/phpunit --configuration /var/www/magento/tests/unit --coverage-clover /var/www/magento/tests/unit/tmp/clover.xml && php /var/www/magento/tests/unit/code-coverage.php /var/www/magento/tests/unit/tmp/clover.xml"
        $BASH -c "/var/www/magento/vendor/bin/phpunit --configuration /var/www/magento/tests/unit"
        ;;
    integration)
        case $PHP in
            7.0)
                $BASH -c "/var/www/magento/vendor/bin/phpunit --group php70 --verbose --configuration /var/www/magento/tests/integration"
                ;;
            7.1)
                $BASH -c "/var/www/magento/vendor/bin/phpunit --exclude-group php70 --verbose --configuration /var/www/magento/tests/integration"
                ;;
        esac
        ;;
esac
