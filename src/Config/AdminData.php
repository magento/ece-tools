<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

/**
 */
class AdminData implements AdminDataInterface
{
    /**
     * @var EnvironmentDataInterface
     */
    private $environmentData;

    /**
     * @param EnvironmentDataInterface $environmentData
     */
    public function __construct(EnvironmentDataInterface $environmentData)
    {
        $this->environmentData = $environmentData;
    }

    /**
     */
    public function getLocale(): string
    {
        return $this->environmentData->getVariables()['ADMIN_LOCALE'] ?? 'en_US';
    }

    /**
     */
    public function getUsername(): string
    {
        return $this->environmentData->getVariables()['ADMIN_USERNAME'] ?? '';
    }

    /**
     */
    public function getFirstName(): string
    {
        return $this->environmentData->getVariables()['ADMIN_FIRSTNAME'] ?? '';
    }

    /**
     */
    public function getLastName(): string
    {
        return $this->environmentData->getVariables()['ADMIN_LASTNAME'] ?? '';
    }

    /**
     */
    public function getEmail(): string
    {
        return $this->environmentData->getVariables()['ADMIN_EMAIL'] ?? '';
    }

    /**
     */
    public function getPassword(): string
    {
        return $this->environmentData->getVariables()['ADMIN_PASSWORD'] ?? '';
    }

    /**
     */
    public function getUrl(): string
    {
        return $this->environmentData->getVariables()['ADMIN_URL'] ?? '';
    }

    /**
     */
    public function getDefaultCurrency(): string
    {
        return 'USD';
    }
}
