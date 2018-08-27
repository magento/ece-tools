<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates variables configured through raw ENV variables.
 *
 * At this moment there is only one possible variable configuring through raw ENV - STATIC_CONTENT_THREADS.
 * STATIC_CONTENT_THREADS - will be ignored if have non-integer value.
 */
class RawEnvVariable implements ValidatorInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param ResultFactory $resultFactory
     * @param Environment $environment
     */
    public function __construct(
        ResultFactory $resultFactory,
        Environment $environment
    ) {
        $this->resultFactory = $resultFactory;
        $this->environment = $environment;
    }

    /**
     * Validates variables configured through raw ENV variables.
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $staticContentThreads = $this->environment->getEnv(DeployInterface::VAR_STATIC_CONTENT_THREADS);

        if (!empty($staticContentThreads) && !ctype_digit($staticContentThreads)) {
            return $this->resultFactory->error(
                sprintf(
                    'The environment variable %s has wrong value "%s" and will be ignored',
                    DeployInterface::VAR_STATIC_CONTENT_THREADS,
                    $staticContentThreads
                ),
                'Use an integer value'
            );
        }

        return $this->resultFactory->success();
    }
}
