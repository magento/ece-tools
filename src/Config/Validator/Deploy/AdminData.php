<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Config\State;
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
     * @var AdminDataInterface
     */
    private $adminData;

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
     * @param AdminDataInterface $adminData
     * @param DatabaseConfiguration $databaseConfiguration
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        State $state,
        AdminDataInterface $adminData,
        DatabaseConfiguration $databaseConfiguration,
        ResultFactory $resultFactory
    ) {
        $this->state = $state;
        $this->adminData = $adminData;
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
        try {
            $data = $this->getAdminData();

            if ($this->databaseConfiguration->validate() instanceof Success) {
                if ($this->state->isInstalled() && $data) {
                    return $this->resultFactory->error(
                        'The following admin data is required to create an admin user during initial installation'
                        . ' only and is ignored during upgrade process: ' . implode(', ', $data),
                        '',
                        Error::WARN_ADMIN_DATA_IGNORED
                    );
                }

                if (!$this->adminData->getEmail() && $data) {
                    return $this->resultFactory->error(
                        'The following admin data was ignored and an admin was not created '
                        . 'because admin email is not set: ' . implode(', ', $data),
                        'Create an admin user via ssh manually: bin/magento admin:user:create',
                        Error::WARN_ADMIN_EMAIL_NOT_SET
                    );
                }
            }

            return $this->resultFactory->success();
        } catch (\Exception $e) {
            // Exception on this step is not critical and can be only logged without interruption of the process
            return $this->resultFactory->error($e->getMessage());
        }
    }

    /**
     * Returns titles of set admin data.
     *
     * @return array
     */
    private function getAdminData(): array
    {
        $data = [];

        if ($this->adminData->getEmail()) {
            $data[] = 'admin email';
        }

        if ($this->adminData->getUsername()) {
            $data[] = 'admin login';
        }

        if ($this->adminData->getFirstName()) {
            $data[] = 'admin first name';
        }

        if ($this->adminData->getLastName()) {
            $data[] = 'admin last name';
        }

        if ($this->adminData->getPassword()) {
            $data[] = 'admin password';
        }

        return $data;
    }
}
