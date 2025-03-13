<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

/**
 * Provides access to configuration of deployment stages.
 *
 * @api
 */
interface StageConfigInterface
{
    /**
     * Section of configuration file.
     */
    public const SECTION_STAGE = 'stage';

    /**
     * Deployment stages.
     */
    public const STAGE_GLOBAL = 'global';
    public const STAGE_BUILD = 'build';
    public const STAGE_DEPLOY = 'deploy';
    public const STAGE_POST_DEPLOY = 'post-deploy';

    /**
     * Deployment variables.
     */
    public const VAR_SCD_COMPRESSION_LEVEL = 'SCD_COMPRESSION_LEVEL';
    public const VAR_SCD_COMPRESSION_TIMEOUT = 'SCD_COMPRESSION_TIMEOUT';
    public const VAR_SCD_STRATEGY = 'SCD_STRATEGY';
    public const VAR_SCD_THREADS = 'SCD_THREADS';
    public const VAR_SCD_MAX_EXEC_TIME = 'SCD_MAX_EXECUTION_TIME';
    public const VAR_SKIP_SCD = 'SKIP_SCD';
    public const VAR_VERBOSE_COMMANDS = 'VERBOSE_COMMANDS';
    public const VAR_SCD_ON_DEMAND = 'SCD_ON_DEMAND';
    public const VAR_SKIP_HTML_MINIFICATION = 'SKIP_HTML_MINIFICATION';
    public const VAR_SCD_MATRIX = 'SCD_MATRIX';
    public const VAR_SCD_NO_PARENT = 'SCD_NO_PARENT';
    public const VAR_X_FRAME_CONFIGURATION = 'X_FRAME_CONFIGURATION';
    public const VAR_ENABLE_EVENTING = 'ENABLE_EVENTING';
    public const VAR_ENABLE_WEBHOOKS = 'ENABLE_WEBHOOKS';

    /**
     * Settings for deployment from git.
     * @deprecated
     */
    public const VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT = 'DEPLOYED_MAGENTO_VERSION_FROM_GIT';
    public const VAR_DEPLOY_FROM_GIT_OPTIONS = 'DEPLOY_FROM_GIT_OPTIONS';

    /**
     * Default minimum logging level.
     */
    public const VAR_MIN_LOGGING_LEVEL = 'MIN_LOGGING_LEVEL';

    /**
     * Option for enabling merging given configuration with default configuration
     */
    public const OPTION_MERGE = '_merge';

    /**
     * Default value of SCD_THREADS variable.
     */
    public const VAR_SCD_THREADS_DEFAULT_VALUE = -1;

    /**
     * Retrieves environment configuration per stage.
     *
     * @param string $name The config name
     * @return string|bool|array|int The config value
     * @throws ConfigException If config value was not defined or can not be read
     */
    public function get(string $name);
}
