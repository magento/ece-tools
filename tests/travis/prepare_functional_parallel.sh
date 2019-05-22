#!/bin/bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

test_set_list=$(grep -Rc 'php70\|php71' src/Test/Functional/Acceptance | grep ':0$' | sed 's/..$//' | sort)
test_set_count=$(printf "$test_set_list" | wc -l)
let "test_set_count++"

test_set_size[1]=$(printf "%.0f" $(echo "$test_set_count*0.20" | bc))
test_set_size[2]=$(printf "%.0f" $(echo "$test_set_count*0.40" | bc))
test_set_size[3]=$((test_set_count-test_set_size[1]-test_set_size[2]))
echo "Total = ${test_set_count}; Batch #1 = ${test_set_size[1]}; Batch #2 = ${test_set_size[2]}; Batch #3 = ${test_set_size[3]};";

cp codeception.dist.yml codeception.yml
echo "groups:" >> codeception.yml
echo "  php72parallel_*: tests/functional/_data/php72parallel_*" >> codeception.yml

group_id=1
i=1
for test_file in $test_set_list
do
    group_file="tests/functional/_data/php72parallel_$group_id.yml"
    echo "$test_file" >> "$group_file"

    if [ $i -lt ${test_set_size[$group_id]} ]
    then
        let "i++"
    else
        i=1
        let "group_id++"
    fi
done
