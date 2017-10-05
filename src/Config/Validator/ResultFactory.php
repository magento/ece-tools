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
     * @param array $errors
     * @param string $suggestion
     * @return Result
     */
    public function create(array $errors = [], string $suggestion = '')
    {
        return new Result($errors, $suggestion);
    }
}
