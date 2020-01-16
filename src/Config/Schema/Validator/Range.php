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
 * Validates the range value
 */
class Range implements ValidatorInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var integer
     */
    private $from;

    /**
     * @var integer
     */
    private $to;

    /**
     * @param ResultFactory $resultFactory
     * @param int $from
     * @param int $to
     */
    public function __construct(ResultFactory $resultFactory, int $from, int $to)
    {
        $this->resultFactory = $resultFactory;
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @inheritDoc
     */
    public function validate(string $key, $value): ResultInterface
    {
        if ($value < $this->from || $value > $this->to) {
            return $this->resultFactory->error(sprintf(
                'The %s variable contains an invalid value %d. ' .
                'Use an integer value from %d to %d.',
                $key,
                $value,
                $this->from,
                $this->to
            ));
        }

        return $this->resultFactory->success();
    }
}
