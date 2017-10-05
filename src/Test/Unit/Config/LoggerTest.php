<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Config;

use Magento\MagentoCloud\Config\Logger;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    /**
     * @var DirectoryList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $directoryListMock;

    /**
     * @var Logger
     */
    private $loggerConfig;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->loggerConfig = new Logger($this->directoryListMock);
    }

    public function testGetLineFormat()
    {
        $this->assertSame(
            "[%datetime%] %level_name%: %message% %context% %extra%\n",
            $this->loggerConfig->getLineFormat()
        );
    }

    public function testAllowInlineLineBreaks()
    {
        $this->assertTrue($this->loggerConfig->allowInlineLineBreaks());
    }

    public function testIgnoreEmptyContextAndExtra()
    {
        $this->assertTrue($this->loggerConfig->ignoreEmptyContextAndExtra());
    }

    public function testDateFormat()
    {
        $this->assertNull($this->loggerConfig->dateFormat());
    }

    public function testGetDeployLogPath()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->assertSame(
            'magento_root/var/log/cloud.log',
            $this->loggerConfig->getDeployLogPath()
        );
    }

    public function testGetBackupBuildLogPath()
    {
        $this->directoryListMock->expects($this->once())
            ->method('getMagentoRoot')
            ->willReturn('magento_root');
        $this->assertSame(
            'magento_root/init/var/log/cloud.log',
            $this->loggerConfig->getBackupBuildLogPath()
        );
    }
}
