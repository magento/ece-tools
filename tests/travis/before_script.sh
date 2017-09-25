#!/usr/bin/env bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

case $TEST_SUITE in
    integration)
        cd tests/integration/etc

        touch composer.json && echo '{}' >> composer.json

        composer config -a -n github-oauth.github.com "$GH_TOKEN"
        composer config -a -n http-basic.repo.magento.com "$REPO_USERNAME" "$REPO_PASSWORD"
        composer config -a -n http-basic.connect20-qa01.magedevteam.com "$CONNECT20_USERNAME" "$CONNECT20_PASSWORD"

        cd ../../..
        ;;
esac
