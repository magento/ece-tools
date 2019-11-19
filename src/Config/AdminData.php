<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

/**
 * Used for getting magento admin user data
 */
class AdminData implements AdminDataInterface
{
    /**
     * @var EnvironmentDataInterface
     */
    private $environmentData;

    /**
     * Environment constructor.
     *
     * @param EnvironmentDataInterface $environmentData
     */
    public function __construct(EnvironmentDataInterface $environmentData)
    {
        $this->environmentData = $environmentData;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->environmentData->getVariables()['ADMIN_LOCALE'] ?? 'en_US';
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->environmentData->getVariables()['ADMIN_USERNAME'] ?? '';
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->environmentData->getVariables()['ADMIN_FIRSTNAME'] ?? '';
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->environmentData->getVariables()['ADMIN_LASTNAME'] ?? '';
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->environmentData->getVariables()['ADMIN_EMAIL'] ?? '';
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->environmentData->getVariables()['ADMIN_PASSWORD'] ?? '';
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->environmentData->getVariables()['ADMIN_URL'] ?? '';
    }

    /**
     * @return string
     */
    public function getDefaultCurrency(): string
    {
        return 'USD';
    }
}
