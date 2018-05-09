#!/usr/bin/env bash

# Copyright © Magento, Inc. All rights reserved.
# See COPYING.txt for license details.

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

# prepare for test suite
if [[ $TEST_SUITE = "integration" ]] && [[ -n $INTEGRATION_INDEX ]]
then
    cd tests/integration

    test_set_list=$(find ../../src/Test/Integration -name "*Test.php" -type f | sort)
    test_set_count=$(printf "$test_set_list" | wc -l)
    test_set_size[1]=$(printf "%.0f" $(echo "$test_set_count*0.33" | bc))  #33%
    test_set_size[2]=$(printf "%.0f" $(echo "$test_set_count*0.33" | bc))  #33%
    test_set_size[3]=$((test_set_count-test_set_size[1]-test_set_size[2])) #34%
    echo "Total = ${test_set_count}; Batch #1 = ${test_set_size[1]}; Batch #2 = ${test_set_size[2]}; Batch #3 = ${test_set_size[3]};";

    echo "==> preparing integration testsuite on index $INTEGRATION_INDEX with set size of ${test_set_size[$INTEGRATION_INDEX]}"
    cp phpunit.xml.dist phpunit.xml

    # divide test sets up by indexed testsuites
    i=0; j=1; dirIndex=1; testIndex=1;
    for test_set in $test_set_list; do
        test_xml[j]+="            <file>$test_set</file>\n"

        if [[ $j -eq $INTEGRATION_INDEX ]]; then
            echo "$dirIndex: Batch #$j($testIndex of ${test_set_size[$j]}): + including $test_set"
        else
            echo "$dirIndex: Batch #$j($testIndex of ${test_set_size[$j]}): + excluding $test_set"
        fi

        testIndex=$((testIndex+1))
        dirIndex=$((dirIndex+1))
        i=$((i+1))
        if [ $i -eq ${test_set_size[$j]} ] && [ $j -lt $INTEGRATION_SETS ]; then
            j=$((j+1))
            i=0
            testIndex=1
        fi
    done

    # replace test sets for current index into testsuite
    perl -pi -e "s#\s+<directory.*>*</directory>#${test_xml[INTEGRATION_INDEX]}#g" phpunit.xml
    cat phpunit.xml

    echo "==> testsuite preparation complete"

    cd ../..
fi
