<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator;

use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\Validator\Result\Success;
use Magento\MagentoCloud\Filesystem\FileSystemException;

/**
 * Creates instance of ResultInterface object
 */
class ResultFactory
{
    /**
     * @var ErrorInfo
     */
    private $errorInfo;

    /**
     * @param ErrorInfo $errorInfo
     */
    public function __construct(ErrorInfo $errorInfo)
    {
        $this->errorInfo = $errorInfo;
    }

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
                $arguments['suggestion'] ?? '',
                $arguments['errorCode'] ?? null
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
     * @param int|null $code
     * @return Error
     */
    public function error(string $message, string $suggestion = '', int $code = null): Error
    {
        return new Error($message, $suggestion, $code);
    }

    /**
     * Creates Error object, fetches info about error from .schema.error.yaml file by code.
     * Uses for errors with static error message and suggestion.
     *
     * @param int $code
     * @return Error
     * @throws FileSystemException
     */
    public function errorByCode(int $code): Error
    {
        $errorData = $this->errorInfo->get($code);

        return new Error(
            $errorData['title'] ?? '',
            $errorData['suggestion'] ?? '',
            $code
        );
    }
}
