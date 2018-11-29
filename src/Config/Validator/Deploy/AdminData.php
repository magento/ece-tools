<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\State;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Config\Validator\Result\Success;

/**
 * Validates data to create an admin.
 */
class AdminData implements ValidatorInterface
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * Validates if database configured properly.
     *
     * @var DatabaseConfiguration
     */
    private $databaseConfiguration;

    /**
     * @param State $state
     * @param Environment $environment
     * @param DatabaseConfiguration $databaseConfiguration
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        State $state,
        Environment $environment,
        DatabaseConfiguration $databaseConfiguration,
        ResultFactory $resultFactory
    ) {
        $this->state = $state;
        $this->environment = $environment;
        $this->databaseConfiguration = $databaseConfiguration;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Validates data to create an admin.
     *
     * @return ResultInterface
     */
    public function validate(): ResultInterface
    {
        $data = $this->getAdminData();

        if ($this->databaseConfiguration->validate() instanceof Success) {
            if ($this->state->isInstalled() && $data) {
                return $this->resultFactory->error(
                    'The following admin data is required to create an admin user during initial installation'
                    . ' only and is ignored during upgrade process: ' . implode(', ', $data)
                );
            }

            if (!$this->environment->getAdminEmail() && $data) {
                return $this->resultFactory->error(
                    'The following admin data was ignored and an admin was not created because admin email is not set: '
                    . implode(', ', $data),
                    'Create an admin user via ssh manually: bin/magento admin:user:create'
                );
            }
        }

        return $this->resultFactory->success();
    }

    /**
     * Returns titles of set admin data.
     *
     * @return array
     */
    private function getAdminData(): array
    {
        $data = [];

        if ($this->environment->getAdminEmail()) {
            $data[] = 'admin email';
        }

        if ($this->environment->getAdminUsername()) {
            $data[] = 'admin login';
        }

        if ($this->environment->getAdminFirstname()) {
            $data[] = 'admin first name';
        }

        if ($this->environment->getAdminLastname()) {
            $data[] = 'admin last name';
        }

        if ($this->environment->getAdminPassword()) {
            $data[] = 'admin password';
        }

        return $data;
    }
}
