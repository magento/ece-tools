<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\App;

use Magento\MagentoCloud\App\NullLogger;
use Monolog\Handler\NullHandler;
use PHPUnit\Framework\TestCase;

class NullLoggerTest extends TestCase
{
    public function testHasNullHandler()
    {
        $logger = new NullLogger(new NullHandler());

        $handlers = $logger->getHandlers();

        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(NullHandler::class, $handlers[0]);
    }
}
