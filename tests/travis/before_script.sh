#!/usr/bin/env bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

case $TEST_SUITE in
    integration)
        cd tests/integration/etc

        echo "==> creating mock of composer.json"
        touch composer.json && echo '{}' >> composer.json

        echo "==> setting github token"
        test -n "$GH_TOKEN" && composer config -a -n github-oauth.github.com "$GH_TOKEN" || true

        echo "==> setting repo token"
        composer config -a -n http-basic.repo.magento.com "$REPO_USERNAME" "$REPO_PASSWORD"

        echo "==> setting connect20 token"
        composer config -a -n http-basic.connect20-qa01.magedevteam.com "$CONNECT20_USERNAME" "$CONNECT20_PASSWORD"

        cd ../../..
        ;;
esac
