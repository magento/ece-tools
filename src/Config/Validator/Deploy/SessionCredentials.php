<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Session\Config;

/**
 * Validates session configuration.
 */
class SessionCredentials implements ValidatorInterface
{
    /**
     * @var Config
     */
    private $sessionConfig;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @param Validator\ResultFactory $resultFactory
     * @param Config $sessionConfig
     */
    public function __construct(
        Validator\ResultFactory $resultFactory,
        Config $sessionConfig
    ) {
        $this->resultFactory = $resultFactory;
        $this->sessionConfig = $sessionConfig;
    }

    /**
     * Validates that session configuration contain required 'save' option.
     * If session save into redis then checks that host option presents.
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $sessionConfig = $this->sessionConfig->get();
        if (empty($sessionConfig)) {
            return $this->resultFactory->create(Validator\ResultInterface::SUCCESS);
        }

        if (!isset($sessionConfig['save'])) {
            return $this->resultFactory->create(Validator\ResultInterface::ERROR, [
                'error' => 'Missed required parameter \'save\' in session configuration'
            ]);
        }

        if ($sessionConfig['save'] === 'redis') {
            if (!isset($sessionConfig['redis'])) {
                return $this->resultFactory->create(Validator\ResultInterface::ERROR, [
                    'error' => 'Missed redis options in session configuration'
                ]);
            }

            if (!isset($sessionConfig['redis']['host'])) {
                return $this->resultFactory->create(Validator\ResultInterface::ERROR, [
                    'error' => 'Missed host option for redis in session configuration'
                ]);
            }
        }

        return $this->resultFactory->create(Validator\ResultInterface::SUCCESS);
    }
}
