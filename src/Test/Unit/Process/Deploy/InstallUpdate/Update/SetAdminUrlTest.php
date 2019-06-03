<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Update\SetAdminUrl;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetAdminUrlTest extends TestCase
{
    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configWriterMock;

    /**
     * @var SetAdminUrl
     */
    private $setAdminUrl;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->configWriterMock = $this->createMock(ConfigWriter::class);

        $this->setAdminUrl = new SetAdminUrl(
            $this->environmentMock,
            $this->configWriterMock,
            $this->loggerMock
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $frontName = 'admino4ka';
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating env.php backend front name.');
        $this->environmentMock->expects($this->once())
            ->method('getAdminUrl')
            ->willReturn($frontName);
        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with(['backend' => ['frontName' => $frontName]]);

        $this->setAdminUrl->execute();
    }

    /**
     * @return void
     */
    public function testExecuteNoChange()
    {
        $this->environmentMock->expects($this->once())
            ->method('getAdminUrl')
            ->willReturn('');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Not updating env.php backend front name. (ADMIN_URL not set)');
        $this->configWriterMock->expects($this->never())
            ->method('update');

        $this->setAdminUrl->execute();
    }
}
