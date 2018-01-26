#!/bin/bash

 # This script generates files of checksums of patches for each version of Magento that magento-cloud-configuration supports from 2.1.4 through 2.1.11
 # These files are later used to validate that ece-tools is applying the same patches for each of those versions

set -e
MCC_PATH=/home/jacob/Projects/magento-cloud-configuration
ECE_TOOLS_PATH=/home/jacob/Projects/ece-tools
cd ${MCC_PATH}/patches
START="4"
STOP="11"
for x in `seq $START $STOP`
do
  git checkout magento/mcc-101.${x};
  sha1sum * | grep -Po '^([^ ]+)' | sort > ${ECE_TOOLS_PATH}/tests/integration-patches/mcc-101.${x}.patches.sha1
done
