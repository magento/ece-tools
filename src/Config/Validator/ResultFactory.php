<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;

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
            return $this->error(
                $arguments['error'],
                $arguments['suggestion'] ?? ''
            );
        }

        return $this->success();
    }

    /**
     * @return Success
     */
    public function success(): Success
    {
        return new Success();
    }

    /**
     * @param string $message
     * @param string $suggestion
     * @return Error
     */
    public function error(string $message, string $suggestion = ''): Error
    {
        return new Error($message, $suggestion);
    }
}
