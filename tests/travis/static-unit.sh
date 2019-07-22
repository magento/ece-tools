#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

if [ $TRAVIS_PHP_VERSION = "7.0" ]; then
   ./vendor/bin/phpstan analyse -l 1 -c ./tests/static/phpstan7.0.neon ./src
else
   ./vendor/bin/phpstan analyse -c ./tests/static/phpstan.neon
fi

./vendor/bin/phpcs ./src --standard=./tests/static/phpcs-ruleset.xml -p -n
./vendor/bin/phpmd ./src xml ./tests/static/phpmd-ruleset.xml
./vendor/bin/phpunit --configuration ./tests/unit --coverage-clover ./tests/unit/tmp/clover.xml && php ./tests/unit/code-coverage.php ./tests/unit/tmp/clover.xml
./vendor/bin/phpunit --configuration ./tests/unit
