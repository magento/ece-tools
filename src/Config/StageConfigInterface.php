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
     * Deployment stages.
     */
    const STAGE_BUILD = 'build';
    const STAGE_DEPLOY = 'deploy';
    /**
     * Default, unified stage.
     */
    const STAGE_GLOBAL = 'global';

    /**
     * Deployment variables.
     */
    const VAR_SCD_COMPRESSION_LEVEL = 'SCD_COMPRESSION_LEVEL';
    const VAR_SCD_STRATEGY = 'SCD_STRATEGY';
    const VAR_SKIP_SCD = 'SKIP_SCD';

    /**
     * Retrieves environment configuration per stage.
     *
     * @param string $name The config name
     * @return string|bool|array|int The config value
     */
    public function get(string $name);
}
