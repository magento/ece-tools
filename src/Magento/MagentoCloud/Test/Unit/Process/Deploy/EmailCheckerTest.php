<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Environment;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Process\Deploy\EmailChecker;

/**
 * @inheritdoc
 */
class EmailCheckerTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var EmailChecker
     */
    private $emailChecker;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);

        $this->emailChecker = new EmailChecker($this->loggerMock, $this->environmentMock);
    }

    /**
     * @inheritdoc
     */
    public function testExecuteWithoutException()
    {
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn('admin@example.com');
        $this->loggerMock->expects($this->never())
            ->method('error');

        $this->emailChecker->execute();
    }

    /**
     * @inheritdoc
     * @expectedException \RuntimeException
     */
    public function testExecuteWithException()
    {
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn('');
        $this->loggerMock->expects($this->once())
            ->method('error');

        $this->emailChecker->execute();
    }
}
