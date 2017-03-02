<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MagentoCloud\Utils;

class ArrayManipulator
{
    public function flatten($array, $prefix='')
    {
        $result = [];
        foreach($array as $key=>$value) {
            if(is_array($value)) {
                $result = $result + $this->flatten($value, $prefix . $key . '/');
            }
            else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    public function filter($array, $pattern)
    {
        $filteredResult = [];
        $length = strlen($pattern);
        foreach ($array as $key => $value) {
            if (substr($key, -$length) === $pattern) {
                $filteredResult[$key] = $value;
            }
        }
        return array_values($filteredResult);
    }
}