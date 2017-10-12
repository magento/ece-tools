<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\CacheHandler;

/**
 * @inheritdoc
 */
class ClearEnvironmentCaches implements ProcessInterface
{
    /***
     * @var CacheHandler
     */
    private $cacheHandler;

    /**
     * @param CacheHandler $cacheHandler
     */
    public function __construct(CacheHandler $cacheHandler)
    {
        $this->cacheHandler = $cacheHandler;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->cacheHandler->clearRedisCache();
        $this->cacheHandler->clearFilesCache();
    }
}
