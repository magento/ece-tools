<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

/**
 * Sorts scenarios array by priority key value
 */
class Sorter
{
    /**
     * Sorts scenarios array by priority key value
     *
     * @param array $scenarios
     */
    public function sortScenarios(array &$scenarios): void
    {
        $sort = function (array $a, array $b) {
            return $a['priority'] <=> $b['priority'];
        };

        foreach ($scenarios as &$scenario) {
            if (isset($scenario['arguments'])) {
                foreach ($scenario['arguments'] as &$argument) {
                    if ($argument['name'] === 'steps') {
                        uasort($argument['items'], $sort);
                    }
                    if ($argument['name'] === 'validators') {
                        foreach ($argument['items'] as &$validatorLevel) {
                            uasort($validatorLevel['items'], $sort);
                        }
                    }
                }
            }
        }

        uasort($scenarios, $sort);
    }
}
