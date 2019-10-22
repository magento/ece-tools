<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario\Collector;

use Magento\MagentoCloud\Scenario\Exception\ValidationException;

/**
 * Collects step data
 */
class Step
{
    public const DEFAULT_PRIORITY = 10000;
    public const NODE_VALUE = '#';
    public const XSI_TYPE_STRING = 'string';
    public const XSI_TYPE_OBJECT = 'object';
    public const XSI_TYPE_ARRAY = 'array';

    /**
     * @var array
     */
    private static $argumentRequiredArgs = [
        '@name',
        '@xsi:type',
    ];

    /**
     * @var array
     */
    private static $itemRequiredArgs = [
        '@name',
        '@xsi:type',
    ];

    /**
     * Collect step data including child items
     *
     * @param array $step
     * @return array
     * @throws ValidationException
     */
    public function collect(array $step): array
    {
        $stepName = $step['@name'];

        $arguments = [];

        foreach ($step['arguments']['argument'] ?? [] as $argument) {
            if ($missedArgs = array_diff(self::$argumentRequiredArgs, array_keys($argument))) {
                throw new ValidationException(sprintf(
                    'Argument(s) "%s" are missed from argument in step "%s"',
                    implode(', ', $missedArgs),
                    $stepName
                ));
            }

            $argumentName = $argument['@name'];
            $argumentType = $argument['@xsi:type'];

            switch ($argumentType) {
                case self::XSI_TYPE_ARRAY:
                    $arguments[] = [
                        'name' => $argumentName,
                        'xsi:type' => $argumentType,
                        'items' => $this->collectItems($this->prepareItem($argument['item'])),
                    ];
                    break;
                case self::XSI_TYPE_OBJECT:
                case self::XSI_TYPE_STRING:
                    $arguments[] = [
                        'name' => $argumentName,
                        'xsi:type' => $argumentType,
                        self::NODE_VALUE => $argument[self::NODE_VALUE],
                    ];
                    break;
                default:
                    throw new ValidationException(sprintf(
                        'xsi:type "%s" not allowed in argument "%s"',
                        $argumentType,
                        $argumentName
                    ));
            }
        }

        $stepData = [
            'name' => $stepName,
            'type' => $step['@type'] ?? '',
            'arguments' => $arguments,
            'skip' => isset($step['@skip']) && $step['@skip'] === 'true'
        ];

        if (isset($step['@priority'])) {
            $stepData['priority'] = intval($step['@priority']);
        }

        return $stepData;
    }

    /**
     * Recursively collect items
     *
     * @param array $items
     * @return array
     * @throws ValidationException
     */
    private function collectItems(array $items): array
    {
        $newItems = [];

        foreach ($items as $item) {
            $itemName = $item['@name'] ?? '';

            if ($missedArgs = array_diff(self::$itemRequiredArgs, array_keys($item))) {
                throw new ValidationException(sprintf(
                    'Argument(s) "%s" are missed from item "%s"',
                    implode(', ', $missedArgs),
                    $itemName
                ));
            }

            $newItem = [
                'name' => $itemName,
                'xsi:type' => $item['@xsi:type'],
                'priority' => $item['@priority'] ?? self::DEFAULT_PRIORITY,
                'skip' => isset($item['@skip']) && $item['@skip'] === 'true'
            ];

            if (isset($item[self::NODE_VALUE])) {
                $newItem[self::NODE_VALUE] = $item[self::NODE_VALUE];
            } elseif (isset($item['item'])) {
                $newItem['items'] = $this->collectItems($this->prepareItem($item['item']));
            }

            $newItems[$itemName] = $newItem;
        }

        return $newItems;
    }

    /**
     * Convert item to array of item
     *
     * @param array $item
     * @return array
     */
    private function prepareItem(array $item): array
    {
        return is_array(reset($item)) ? $item : [$item];
    }
}
