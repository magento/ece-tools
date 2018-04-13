<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem\Resolver;

use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * @inheritdoc
 */
class SharedConfig implements ConfigResolverInterface
{
    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param SystemList $systemList
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(SystemList $systemList, MagentoVersion $magentoVersion)
    {
        $this->systemList = $systemList;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * @inheritdoc
     */
    public function resolve(): string
    {
        return $this->magentoVersion->isGreaterOrEqual('2.2')
            ? $this->systemList->getMagentoRoot() . '/app/etc/config.php'
            : $this->systemList->getMagentoRoot() . '/app/etc/config.local.php';
    }
}
