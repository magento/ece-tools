<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

/**
 * @inheritDoc
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
     * @inheritDoc
     */
    public function getLocale(): string
    {
        return $this->environmentData->getVariables()['ADMIN_LOCALE'] ?? 'en_US';
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): string
    {
        return $this->environmentData->getVariables()['ADMIN_USERNAME'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getFirstName(): string
    {
        return $this->environmentData->getVariables()['ADMIN_FIRSTNAME'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getLastName(): string
    {
        return $this->environmentData->getVariables()['ADMIN_LASTNAME'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getEmail(): string
    {
        return $this->environmentData->getVariables()['ADMIN_EMAIL'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): string
    {
        return $this->environmentData->getVariables()['ADMIN_PASSWORD'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): string
    {
        return $this->environmentData->getVariables()['ADMIN_URL'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getDefaultCurrency(): string
    {
        return 'USD';
    }
}
