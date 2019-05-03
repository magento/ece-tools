<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

/**
 * Provides access to configuration of deployment stages.
 */
interface StageConfigInterface
{
    /**
     * Section of configuration file.
     */
    const SECTION_STAGE = 'stage';

    /**
     * Deployment stages.
     */
    const STAGE_GLOBAL = 'global';
    const STAGE_BUILD = 'build';
    const STAGE_DEPLOY = 'deploy';
    const STAGE_POST_DEPLOY = 'post-deploy';

    /**
     * Deployment variables.
     */
    const VAR_SCD_COMPRESSION_LEVEL = 'SCD_COMPRESSION_LEVEL';
    const VAR_SCD_COMPRESSION_TIMEOUT = 'SCD_COMPRESSION_TIMEOUT';
    const VAR_SCD_STRATEGY = 'SCD_STRATEGY';
    const VAR_SCD_THREADS = 'SCD_THREADS';
    const VAR_SCD_MAX_EXEC_TIME = 'SCD_MAX_EXECUTION_TIME';
    const VAR_SKIP_SCD = 'SKIP_SCD';
    const VAR_VERBOSE_COMMANDS = 'VERBOSE_COMMANDS';
    const VAR_SCD_ON_DEMAND = 'SCD_ON_DEMAND';
    const VAR_SKIP_HTML_MINIFICATION = 'SKIP_HTML_MINIFICATION';
    const VAR_SCD_MATRIX = 'SCD_MATRIX';
    const VAR_X_FRAME_CONFIGURATION = 'X_FRAME_CONFIGURATION';

    /**
     * @deprecated use SCD_MATRIX instead.
     */
    const VAR_SCD_EXCLUDE_THEMES = 'SCD_EXCLUDE_THEMES';

    /**
     * Environment variables.
     */
    const VAR_ENV_RELATIONSHIPS = 'ENV_RELATIONSHIPS';
    const VAR_ENV_ROUTES = 'ENV_ROUTES';
    const VAR_ENV_VARIABLES = 'ENV_VARIABLES';
    const VAR_ENV_APPLICATION = 'ENV_APPLICATION';

    /**
     * Settings for deployment from git.
     */
    const VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT = 'DEPLOYED_MAGENTO_VERSION_FROM_GIT';
    const VAR_DEPLOY_FROM_GIT_OPTIONS = 'DEPLOY_FROM_GIT_OPTIONS';

    /**
     * Default minimum logging level.
     */
    const VAR_MIN_LOGGING_LEVEL = 'MIN_LOGGING_LEVEL';

    /**
     * Option for enabling merging given configuration with default configuration
     */
    const OPTION_MERGE = '_merge';

    /**
     * Default value of SCD_THREADS variable.
     */
    const VAR_SCD_THREADS_DEFAULT_VALUE = -1;

    /**
     * Retrieves environment configuration per stage.
     *
     * @param string $name The config name
     * @return string|bool|array|int The config value
     * @throws \RuntimeException If config value was not defined or can not be read
     */
    public function get(string $name);
}
