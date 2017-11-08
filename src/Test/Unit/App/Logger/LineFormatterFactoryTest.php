<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\App\Logger;

use Magento\MagentoCloud\App\Logger\LineFormatterFactory;
use Monolog\Formatter\LineFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class LineFormatterFactoryTest extends TestCase
{
    public function testCreate()
    {
        $this->assertInstanceOf(LineFormatter::class, (new LineFormatterFactory())->create());
    }
}
