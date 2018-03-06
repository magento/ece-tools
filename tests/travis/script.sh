#!/usr/bin/env bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

case $TEST_SUITE in
    static)
        phpcs src --standard=tests/static/phpcs-ruleset.xml -p -n
        phpmd src xml tests/static/phpmd-ruleset.xml
        ;;
    unit)
        phpunit --configuration tests/unit/phpunit.xml.dist --coverage-clover tests/unit/tmp/clover.xml && php tests/unit/code-coverage.php tests/unit/tmp/clover.xml ${MIN_CODE_COVERAGE}
        phpunit --configuration tests/unit/phpunit.xml.dist
        ;;
    integration)
        phpunit --verbose --configuration tests/integration/phpunit.xml.dist
        ;;
esac
