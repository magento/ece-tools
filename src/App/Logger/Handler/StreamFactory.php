<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger\Handler;

use Monolog\Handler\StreamHandler;

/**
 * The factory for StreamHandler
 */
class StreamFactory
{
    /**
     * @param array $configuration
     * @return StreamHandler
     */
    public function create(array $configuration = []): StreamHandler
    {
        return new StreamHandler($configuration['stream'] ?? 'php://stdout');
    }
}
