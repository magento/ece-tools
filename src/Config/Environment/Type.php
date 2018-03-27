<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Environment;

/**
 * Gets environment type depending on system user.
 */
class Type
{
    const PRODUCTION = 'production';
    const STAGING = 'staging';
    const INTEGRATION = 'integration';

    /**
     * Gets environment type depending on system user.
     *
     * - if user is 'web' environment is integration,
     * - if user name ends with '_stg' environment is staging,
     * - in other cases environment is production.
     *
     * @return string
     */
    public function get(): string
    {
        $systemUser = get_current_user();

        if ($systemUser === 'web') {
            return self::INTEGRATION;
        }

        if (substr($systemUser, -4) === '_stg') {
            return self::STAGING;
        }

        return self::PRODUCTION;
    }
}
