<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator;

/**
 * Creates instance of ResultInterface object
 */
class ResultFactory
{
    /**
     * Creates instance of ResultInterface object
     *
     * @param string $type
     * @param array $arguments
     * @return ResultInterface
     */
    public function create(string $type, array $arguments = []): ResultInterface
    {
        if ($type === ResultInterface::ERROR) {
            $suggestion = $arguments['suggestion'] ?? '';
            $result = new Result\Error($arguments['error'], $suggestion);
        } else {
            $result = new Result\Success();
        }

        return $result;
    }
}
