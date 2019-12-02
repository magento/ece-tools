<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

/**
 * Using for get magento admin user data
 *
 * @api
 */
interface AdminDataInterface
{
    public const DEFAULT_ADMIN_URL = 'admin';
    public const DEFAULT_ADMIN_NAME = 'admin';
    public const DEFAULT_ADMIN_FIRST_NAME = 'Admin';
    public const DEFAULT_ADMIN_LAST_NAME = 'Username';

    /**
     * Returns admin locale.
     *
     * @return string
     */
    public function getLocale(): string;

    /**
     * Returns admin username.
     *
     * @return string
     */
    public function getUsername(): string;

    /**
     * Returns admin first name.
     *
     * @return string
     */
    public function getFirstName(): string;

    /**
     * Returns admin last name.
     *
     * @return string
     */
    public function getLastName(): string;

    /**
     * Returns admin email.
     *
     * @return string
     */
    public function getEmail(): string;

    /**
     * Returns admin password.
     *
     * @return string
     */
    public function getPassword(): string;

    /**
     * Returns backend url.
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Returns default currency.
     *
     * @return string
     */
    public function getDefaultCurrency(): string;
}
