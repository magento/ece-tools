#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

readarray -t test_set_list <<< "$(grep -RL 'php71' src/Test/Functional/Acceptance | sort -r)"
group_count=4

element_in_group=$(printf "%.0f" "$(echo "scale=2;(${#test_set_list[@]} + ${group_count} - 1)/${group_count}" | bc)")

echo "Total = ${#test_set_list[@]};"

cp codeception.dist.yml codeception.yml
echo "groups:" >> codeception.yml
echo "  parallel_*: tests/functional/_data/parallel_*" >> codeception.yml

for((i=0, group_id=1; i < ${#test_set_list[@]}; i+=element_in_group, group_id++))
do
  part=( "${test_set_list[@]:i:element_in_group}" )
  echo "Batch #${group_id} = ${#part[@]}"

  group_file="tests/functional/_data/parallel_$group_id.yml"

  for test_file in "${part[@]}"
  do
    echo "$test_file" >> "$group_file"
  done
done
exit;