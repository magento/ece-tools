#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

php_version="${TRAVIS_PHP_VERSION//./}"

readarray -t test_set_list <<< "$(grep -Rl php${php_version} --exclude='*AcceptanceCest.php' --exclude='*AcceptanceCeCest.php' --exclude='*AcceptanceCe71Cest.php' --exclude='*AcceptanceCe72Cest.php' --exclude='*AcceptanceCe73Cest.php' --exclude='*AbstractCest.php' src/Test/Functional/Acceptance | sort)"
group_count=6

if [ $(( ${#test_set_list[@]} % group_count )) -eq 0 ]; then
  element_in_group=$(printf "%.0f" "$(echo "scale=2;(${#test_set_list[@]})/${group_count}" | bc)")
else
  element_in_group=$(printf "%.0f" "$(echo "scale=2;(${#test_set_list[@]} + ${group_count} - 1)/${group_count}" | bc)")
fi

cp codeception.dist.yml codeception.yml
echo "groups:" >> codeception.yml
echo "  parallel_*: tests/functional/_data/parallel_*" >> codeception.yml

if [ $php_version == "74" ]; then
  echo "Total = ${#test_set_list[@]};"
  echo "Batch #1 = Acceptance"
  echo "src/Test/Functional/Acceptance/AcceptanceCest.php" >> "tests/functional/_data/parallel_${php_version}_1.yml"
  start_group_id=2
else
  start_group_id=1
fi

for((i=0, group_id=start_group_id; i < ${#test_set_list[@]}; i+=element_in_group, group_id++))
do
  test_file_group=( "${test_set_list[@]:i:element_in_group}" )
  echo "Batch #${group_id} = ${#test_file_group[@]}"

  group_file="tests/functional/_data/parallel_${php_version}_$group_id.yml"

  for test_file in "${test_file_group[@]}"
  do
    echo "$test_file" >> "$group_file"
  done
done