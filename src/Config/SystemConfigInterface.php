<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

/**
 * Provides access to configuration of deployment stages.
 */
interface SystemConfigInterface
{
    /**
     * Section of configuration file.
     */
    public const SECTION_SYSTEM = 'system';

    /**
     * System sections.
     */
    public const SYSTEM_VARIABLES = 'variables';

    /**
     * Environment variables.
     */
    public const VAR_ENV_RELATIONSHIPS = 'ENV_RELATIONSHIPS';
    public const VAR_ENV_ROUTES = 'ENV_ROUTES';
    public const VAR_ENV_VARIABLES = 'ENV_VARIABLES';
    public const VAR_ENV_APPLICATION = 'ENV_APPLICATION';
    public const VAR_ENV_ENVIRONMENT = 'ENV_ENVIRONMENT';

    /**
     * Retrieves environment configuration per stage.
     *
     * @param string $name The config name
     * @return string|bool|array|int The config value
     * @throws ConfigException If config value was not defined or can not be read
     */
    public function get(string $name);
}
