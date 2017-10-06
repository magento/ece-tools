<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\EmailChecker;

/**
 * @inheritdoc
 */
class EmailCheckerTest extends TestCase
{
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
        $this->environmentMock = $this->createMock(Environment::class);
        $this->emailChecker = new EmailChecker($this->environmentMock);
    }

    /**
     * @inheritdoc
     */
    public function testExecuteWithoutException()
    {
        $this->environmentMock->expects($this->once())
            ->method('getAdminEmail')
            ->willReturn('admin@example.com');

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

        $this->emailChecker->execute();
    }
}
