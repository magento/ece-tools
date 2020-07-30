#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

case $TRAVIS_PHP_VERSION in
    7.1)
        ./vendor/bin/codecept run -g parallel_71_"$FUNCTIONAL_INDEX" --steps
        ;;
    7.2)
        ./vendor/bin/codecept run -g parallel_72_"$FUNCTIONAL_INDEX" --steps
        ;;
    7.3)
        ./vendor/bin/codecept run -g parallel_73_"$FUNCTIONAL_INDEX" --steps
        ;;
    7.4)
        ./vendor/bin/codecept run -g parallel_74_"$FUNCTIONAL_INDEX" --steps
        ;;
esac
