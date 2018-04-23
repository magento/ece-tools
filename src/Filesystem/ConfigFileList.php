<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

/**
 * Resolver of file configurations.
 */
class ConfigFileList
{
    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @param SystemList $systemList
     */
    public function __construct(SystemList $systemList)
    {
        $this->systemList = $systemList;
    }

    /**
     * @return string
     */
    public function getConfig(): string
    {
        return $this->systemList->getMagentoRoot() . '/app/etc/config.php';
    }

    /**
     * @return string
     */
    public function getConfigLocal(): string
    {
        return $this->systemList->getMagentoRoot() . '/app/etc/config.local.php';
    }

    /**
     * @return string
     */
    public function getEnv(): string
    {
        return $this->systemList->getMagentoRoot() . '/app/etc/env.php';
    }

    /**
     * @return string
     */
    public function getBuildConfig(): string
    {
        return $this->systemList->getMagentoRoot() . '/build_options.ini';
    }

    /**
     * @return string
     */
    public function getEnvConfig(): string
    {
        return $this->systemList->getMagentoRoot() . '/.magento.env.yaml';
    }
}
