#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

readarray -t test_set_list <<< "$(grep -RL 'edition-ce' --exclude='*AcceptanceCest.php' --exclude='*AcceptanceCeCest.php' --exclude='*AbstractCest.php' src/Test/Functional/Acceptance | sort)"
group_count=6

if [ $(( ${#test_set_list[@]} % group_count )) -eq 0 ]; then
  element_in_group=$(printf "%.0f" "$(echo "scale=2;(${#test_set_list[@]})/${group_count}" | bc)")
else
  element_in_group=$(printf "%.0f" "$(echo "scale=2;(${#test_set_list[@]} + ${group_count} - 1)/${group_count}" | bc)")
fi

cp codeception.dist.yml codeception.yml
echo "groups:" >> codeception.yml
echo "  parallel_*: tests/functional/_data/parallel_*" >> codeception.yml

echo "Total = ${#test_set_list[@]};"
echo "Batch #1 = Acceptance"
echo "src/Test/Functional/Acceptance/AcceptanceCest.php" >> "tests/functional/_data/parallel_1.yml"

for((i=0, group_id=2; i < ${#test_set_list[@]}; i+=element_in_group, group_id++))
do
  test_file_group=( "${test_set_list[@]:i:element_in_group}" )
  echo "Batch #${group_id} = ${#test_file_group[@]}"

  group_file="tests/functional/_data/parallel_$group_id.yml"

  for test_file in "${test_file_group[@]}"
  do
    echo "$test_file" >> "$group_file"
  done
done
