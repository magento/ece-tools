<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario\Collector;

/**
 * Collects action data
 */
class Action
{
    /**
     * Collect action data
     *
     * @param array $action
     * @return array
     */
    public function collect(array $action): array
    {
        $actionData = [
            'name' => $action['@name'],
            'type' => $action['@type'] ?? '',
            'skip' => isset($action['@skip']) && $action['@skip'] === 'true',
        ];

        if (isset($action['@priority'])) {
            $actionData['priority'] = (int)$action['@priority'];
        }

        return $actionData;
    }
}
