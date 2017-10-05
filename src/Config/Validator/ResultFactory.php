<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator;

/**
 * Creates instance of Result object
 */
class ResultFactory
{
    /**
     * Creates instance of Result object
     *
     * @return Result
     */
    public function create()
    {
        return new Result();
    }
}
