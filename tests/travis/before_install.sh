#!/usr/bin/env bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

case $TEST_SUITE in
    integration)
        export SANDBOX_KEY="$SANDBOX_KEY"
        export MAGENTO_HOST_NAME="$MAGENTO_HOST_NAME"

        mysql -e 'CREATE DATABASE IF NOT EXISTS integration_tests;'

        composer config -a -n -g github-oauth.github.com ${GH_TOKEN}
        composer config -a -n -g http-basic.repo.magento.com ${REPO_USERNAME} ${REPO_PASSWORD}
        composer config -a -n -g http-basic.connect20-qa01.magedevteam.com ${CONNECT20_USERNAME} ${CONNECT20_PASSWORD}

        # Install apache
        sudo apt-get update
        mkdir -p ${TRAVIS_BUILD_DIR}/tests/integration/tmp/sandbox-${SANDBOX_KEY}
        sudo apt-get install apache2 libapache2-mod-fastcgi
        sudo cp ${TRAVIS_BUILD_DIR}/tests/travis/config/www.conf ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.d/

        # Enable php-fpm
        sudo cp ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf.default ~/.phpenv/versions/$(phpenv version-name)/etc/php-fpm.conf
        sudo a2enmod rewrite actions fastcgi alias
        echo "cgi.fix_pathinfo = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
        ~/.phpenv/versions/$(phpenv version-name)/sbin/php-fpm

        # Configure apache virtual hosts
        sudo cp -f ${TRAVIS_BUILD_DIR}/tests/travis/config/apache_virtual_host /etc/apache2/sites-available/000-default.conf
        sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/000-default.conf
        sudo sed -e "s?%MAGENTO_HOST_NAME%?${MAGENTO_HOST_NAME}?g" --in-place /etc/apache2/sites-available/000-default.conf

        sudo usermod -a -G www-data travis
        sudo usermod -a -G travis www-data

        phpenv config-rm xdebug.ini
        sudo service apache2 restart
        ;;
esac
