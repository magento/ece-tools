<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Resolver;

use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * @inheritdoc
 */
class SharedConfig implements ConfigResolverInterface
{
    /**
     * @var ConfigFileList
     */
    private $configFileList;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ConfigFileList $configFileList
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(ConfigFileList $configFileList, MagentoVersion $magentoVersion)
    {
        $this->configFileList = $configFileList;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * @inheritdoc
     */
    public function resolve(): string
    {
        return $this->magentoVersion->isGreaterOrEqual('2.2')
            ? $this->configFileList->getConfig()
            : $this->configFileList->getConfigLocal();
    }
}
