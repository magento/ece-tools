#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

php_version="${TRAVIS_PHP_VERSION//./}"

test_set_list=($(grep -Rl php${php_version} --exclude='*AcceptanceCest.php' --exclude='*AcceptanceCeCest.php' --exclude='*AcceptanceCe71Cest.php' --exclude='*AcceptanceCe72Cest.php' --exclude='*AcceptanceCe73Cest.php' --exclude='*AbstractCest.php' src/Test/Functional/Acceptance | sort))
group_count=6

cp codeception.dist.yml codeception.yml
echo "groups:" >> codeception.yml
echo "  parallel_${php_version}_*: tests/functional/_data/parallel_${php_version}_*" >> codeception.yml

if [ $php_version == "74" ]; then
  echo "Total = ${#test_set_list[@]};"
  echo "Batch #1 = Acceptance"
  echo "src/Test/Functional/Acceptance/AcceptanceCest.php" >> "tests/functional/_data/parallel_${php_version}_1.yml"
  start_group_id=2
else
  start_group_id=1
fi

for((i=0, group_id=start_group_id; i < ${#test_set_list[@]}; i+=1, group_id++))
do
  if [ $group_id -gt $group_count ]; then
      group_id=$start_group_id
  fi
  group_file="tests/functional/_data/parallel_${php_version}_$group_id.yml"
  echo "${test_set_list[i]}" >> "$group_file"
done
