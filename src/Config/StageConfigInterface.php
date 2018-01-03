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

    /**
     * Deployment variables.
     */
    const VAR_SCD_COMPRESSION_LEVEL = 'SCD_COMPRESSION_LEVEL';
    const VAR_SCD_STRATEGY = 'SCD_STRATEGY';
    const VAR_SCD_THREADS = 'SCD_THREADS';
    const VAR_SCD_EXCLUDE_THEMES = 'SCD_EXCLUDE_THEMES';
    const VAR_SKIP_SCD = 'SKIP_SCD';
    const VAR_VERBOSE_COMMANDS = 'VERBOSE_COMMANDS';

    /**
     * Retrieves environment configuration per stage.
     *
     * @param string $name The config name
     * @return string|bool|array|int The config value
     * @throws \RuntimeException If config value was not defined or can not be read
     */
    public function get(string $name);
}
