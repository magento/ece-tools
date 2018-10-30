<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\State;
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
     * @var State
     */
    private $state;

    /**
     * Validates if database configured properly.
     *
     * @var DatabaseConfiguration
     */
    private $databaseConfiguration;

    /**
     * @param DatabaseConfiguration $databaseConfiguration
     * @param Environment $environment
     * @param ResultFactory $resultFactory
     * @param State $deploy
     */
    public function __construct(
        DatabaseConfiguration $databaseConfiguration,
        Environment $environment,
        ResultFactory $resultFactory,
        State $deploy
    ) {
        $this->databaseConfiguration = $databaseConfiguration;
        $this->environment = $environment;
        $this->resultFactory = $resultFactory;
        $this->state = $deploy;
    }

    /**
     * Validates that ADMIN_EMAIL variable was set and magento wasn't installed
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        if (!$this->environment->getAdminEmail()
            && $this->databaseConfiguration->validate() instanceof Validator\Result\Success
            && !$this->state->isInstalled()
        ) {
            return $this->resultFactory->create(
                Validator\ResultInterface::ERROR,
                [
                    'error' => 'The variable ADMIN_EMAIL was not set during the installation.',
                    'suggestion' => 'This variable is required to send the Admin password reset email.' .
                            ' Set an environment variable for ADMIN_EMAIL and retry deployment.'
                ]
            );
        }

        return $this->resultFactory->create(Validator\ResultInterface::SUCCESS);
    }
}
