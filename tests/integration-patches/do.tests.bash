#/bin/bash

 # This script tests that the patches applied by ece-tools are the same patches that were applied in magento-cloud-configuration for versions 2.1.4 through 2.1.11 of Magento.
 # Note that this script doesn't support spaces (and probably other characters) in directory paths, but neither does ece-tools anyways.
 # You should set ECE_TOOLS_DIRECTORY to where your ece-tools is checked out with the version you want to test.

set -e
export ECE_TOOLS_DIRECTORY=`pwd`
mkdir /tmp/patch-tests

START="4"
STOP="11"
for x in `seq $START $STOP`
do
  cd /tmp/patch-tests
  git clone  --branch 2.1.${x} --depth 1 git@github.com:magento/magento-cloud.git 2.1.${x}
  cd 2.1.${x}
  composer install
  composer config repositories.ece-tools "{\"type\": \"path\", \"url\": \"$ECE_TOOLS_DIRECTORY\",   \"options\": { \"symlink\": false } }"
  composer require magento/ece-tools
  vendor/bin/ece-tools patch | grep -Po '(?<=git apply )(.*)$' | xargs sha1sum  | grep -Po '^([^ ]+)' | sort > patches.sha1
  diff patches.sha1 ${ECE_TOOLS_DIRECTORY}/tests/integration-patches/mcc-101.${x}.patches.sha1
done
