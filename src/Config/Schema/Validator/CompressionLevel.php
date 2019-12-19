<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Schema\Validator;

use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;

/**
 * Validates the compression level value
 */
class CompressionLevel implements ValidatorInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param ResultFactory $resultFactory
     */
    public function __construct(ResultFactory $resultFactory)
    {
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritDoc
     */
    public function validate(string $key, $value): ResultInterface
    {
        if (!in_array($value, range(0, 9), false)) {
            return $this->resultFactory->error(sprintf(
                'The %s variable contains an invalid value %d. ' .
                'Use an integer value from 0 to 9.',
                $key,
                $value
            ));
        }

        return $this->resultFactory->success();
    }
}
