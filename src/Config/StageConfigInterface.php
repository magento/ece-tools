<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

/**
 * Interface StageConfigInterface
 */
interface StageConfigInterface
{
    /**
     * Deployment stages.
     */
    const STAGE_BUILD = 'build';
    const STAGE_DEPLOY = 'deploy';

    /**
     * Deployment variables.
     */
    const VAR_SCD_COMPRESSION_LEVEL = 'SCD_COMPRESSION_LEVEL';
    const VAR_SCD_STRATEGY = 'SCD_STRATEGY';
    const VAR_SKIP_SCD = 'SKIP_SCD';

    /**
     * Retrieves environment configuration per stage.
     *
     * @param string $stage The stage name
     * @param string $name The config name
     * @return mixed The config value
     */
    public function get(string $stage, string $name);

    /**
     * Retrieves environment configuration for build stage.
     *
     * @param string $name
     * @return mixed
     */
    public function getBuild(string $name);

    /**
     * Retrieves environment configuration for deploy stage.
     *
     * @param string $name
     * @return mixed
     */
    public function getDeploy(string $name);
}
