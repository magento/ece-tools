#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

DIR_TOOLS="/var/www/ece-tools"

case $TRAVIS_PHP_VERSION in
    7.2)
        $BASH -c "${DIR_TOOLS}/vendor/bin/phpunit --verbose --configuration ${DIR_TOOLS}/tests/integration"
        ;;
esac
