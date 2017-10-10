<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorInterface;

/**
 * Validates that ADMIN_EMAIL variable was set
 */
class AdminEmail implements ValidatorInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param Environment $environment
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        Environment $environment,
        ResultFactory $resultFactory
    ) {
        $this->environment = $environment;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Validates that ADMIN_EMAIL variable was set
     *
     * @return Validator\Result
     */
    public function validate(): Validator\Result
    {
        if (!$this->environment->getAdminEmail()) {
            return $this->resultFactory->create(
                'The variable ADMIN_EMAIL was not set during the installation.' .
                ' This variable is required to send the Admin password reset email.',
                'Set an environment variable for ADMIN_EMAIL and retry deployment.'
            );
        }

        return $this->resultFactory->create();
    }
}
