#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

case $TRAVIS_PHP_VERSION in
    7.0)
        ./vendor/bin/codecept run -g php70 --steps
        ;;
    7.1)
        ./vendor/bin/codecept run -g php71 --steps
        ;;
    7.2)
        ./vendor/bin/codecept run -g php72parallel_"$FUNCTIONAL_INDEX" --steps
        ;;
esac
