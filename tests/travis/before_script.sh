#!/usr/bin/env bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

case $TEST_SUITE in
    integration-docker)
        echo "COMPOSER_MAGENTO_USERNAME=${REPO_USERNAME}" >> ./docker/composer.env
        echo "COMPOSER_MAGENTO_PASSWORD=${REPO_PASSWORD}" >> ./docker/composer.env

        docker-compose up --build --abort-on-container-exit
        docker-compose run cli bash -c "composer install -d /var/www/magento"
        ;;
esac
