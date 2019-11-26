<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\Update;

use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\Update\SetAdminUrl;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetAdminUrlTest extends TestCase
{
    /**
     * @var AdminDataInterface|MockObject
     */
    private $adminDataMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var WriterInterface|MockObject
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
        $this->adminDataMock = $this->getMockForAbstractClass(AdminDataInterface::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->configWriterMock = $this->getMockForAbstractClass(WriterInterface::class);

        $this->setAdminUrl = new SetAdminUrl(
            $this->adminDataMock,
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
        $this->adminDataMock->expects($this->once())
            ->method('getUrl')
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
        $this->adminDataMock->expects($this->once())
            ->method('getUrl')
            ->willReturn('');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Not updating env.php backend front name. (ADMIN_URL not set)');
        $this->configWriterMock->expects($this->never())
            ->method('update');

        $this->setAdminUrl->execute();
    }
}
