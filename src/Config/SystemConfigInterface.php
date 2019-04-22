<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

/**
 * Provides access to configuration of deployment stages.
 */
interface SystemConfigInterface
{
    /**
     * Section of configuration file.
     */
    const SECTION_SYSTEM = 'system';

    /**
     * System sections.
     */
    const SYSTEM_VARIABLES = 'variables';

    /**
     * Environment variables.
     */
    const VAR_ENV_RELATIONSHIPS = 'ENV_RELATIONSHIPS';
    const VAR_ENV_ROUTES = 'ENV_ROUTES';
    const VAR_ENV_VARIABLES = 'ENV_VARIABLES';
    const VAR_ENV_APPLICATION = 'ENV_APPLICATION';
    const VAR_ENV_ENVIRONMENT = 'ENV_ENVIRONMENT';

    /**
     * Retrieves environment configuration per stage.
     *
     * @param string $name The config name
     * @return string|bool|array|int The config value
     * @throws \RuntimeException If config value was not defined or can not be read
     */
    public function get(string $name);
}
