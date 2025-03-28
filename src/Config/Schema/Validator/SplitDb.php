<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Schema\Validator;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;

/**
 * Validates the value of split types
 */
class SplitDb implements ValidatorInterface
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
        if (array_diff($value, DeployInterface::SPLIT_DB_VALUES)) {
            return $this->resultFactory->error(sprintf(
                'The %s variable contains the invalid value. '
                . 'It should be an array with following values: [%s].',
                $key,
                implode(', ', DeployInterface::SPLIT_DB_VALUES)
            ));
        }
        return $this->resultFactory->success();
    }
}
