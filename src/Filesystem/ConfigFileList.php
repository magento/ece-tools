<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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
     * @deprecated File build_options.ini is unsupported, this method only uses in the validator class
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

    /**
     * @return string
     */
    public function getErrorReportConfig(): string
    {
        return $this->systemList->getMagentoRoot() . '/pub/errors/local.xml';
    }

    /**
     * @return string
     */
    public function getPhpIni(): string
    {
        return $this->systemList->getMagentoRoot() . '/php.ini';
    }

    /**
     * @return string
     */
    public function getOpCacheExcludeList(): string
    {
        return $this->systemList->getMagentoRoot() . '/op-exclude.txt';
    }
}
