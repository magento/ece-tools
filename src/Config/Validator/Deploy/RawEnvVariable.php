<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates variables configured through raw ENV variables.
 *
 * At this moment there is only one possible variable configuring through raw ENV - STATIC_CONTENT_THREADS.
 * This is deprecated flow, but should be validated as wrong value can crash deploy process.
 */
class RawEnvVariable implements ValidatorInterface
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
     * Validates variables configured through raw ENV variables.
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        if (isset($_ENV[DeployInterface::VAR_STATIC_CONTENT_THREADS])
            && !ctype_digit($_ENV[DeployInterface::VAR_STATIC_CONTENT_THREADS])
        ) {
            return $this->resultFactory->create(
                Validator\ResultInterface::ERROR,
                [
                    'error' => sprintf(
                        'Environment variable STATIC_CONTENT_THREADS has wrong value: "%s"',
                        $_ENV[DeployInterface::VAR_STATIC_CONTENT_THREADS]
                    ),
                    'suggestion' => 'Please use only integer values for STATIC_CONTENT_THREADS'
                ]
            );
        }

        return $this->resultFactory->create(Validator\ResultInterface::SUCCESS);
    }
}
