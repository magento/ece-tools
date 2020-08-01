#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

./vendor/bin/codecept run -g parallel_"${TRAVIS_PHP_VERSION//./}"_"$FUNCTIONAL_INDEX" --steps
