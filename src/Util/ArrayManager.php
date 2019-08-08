<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

/**
 * Provides additional functionality for array manipulations.
 */
class ArrayManager
{
    /**
     * @param array $array
     * @param string $prefix
     * @return array
     */
    public function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (empty($value)) {
                    $result[$prefix . $key] = [];
                } else {
                    $result = $result + $this->flatten($value, $prefix . $key . '/');
                }
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param array $array
     * @param string $pattern
     * @param bool $ending
     * @return array
     */
    public function filter(array $array, string $pattern, $ending = true): array
    {
        $filteredResult = [];
        $length = strlen($pattern);
        foreach ($array as $key => $value) {
            if ($ending) {
                if (substr($key, -$length) === $pattern) {
                    $filteredResult[$key] = $value;
                }
            } else {
                if (substr($key, 0, strlen($pattern)) === $pattern) {
                    $filteredResult[$key] = $value;
                }
            }
        }

        return array_unique(array_filter(array_values($filteredResult)));
    }

    /**
     * This method expands original array in next way.
     * Having original array as
     * ```php
     * [
     *  'test_old' => 'two'
     * ]
     * ```
     *
     * And keys
     * ```php
     * [
     *  'test',
     *  'test2'
     *  ]
     * ```
     *
     * And value
     *
     * ```php
     * one
     * ```
     *
     * Will be resulted into array
     *
     * ```php
     * [
     *  'test_old' => 'two',
     *  'test' => [
     *      'test2' => 'one'
     *      ]
     *  ]
     * ```
     *
     * @param array $original
     * @param array $keys
     * @param string|int $val
     * @return array
     */
    public function nest(array $original, array $keys, $val): array
    {
        $data = &$original;

        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                $data[$key] = [];
            }
            $data = &$data[$key];
        }

        $data = $val;

        return $original;
    }
}
