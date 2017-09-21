<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\SecureAdmin;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\PackageManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SecureAdminTest extends TestCase
{
    /**
     * @var SecureAdmin
     */
    private $process;

    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var PackageManager|Mock
     */
    private $packageManagerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->process = new SecureAdmin(
            $this->loggerMock,
            $this->environmentMock,
            $this->shellMock,
            $this->packageManagerMock
        );
    }

    public function testExecute()
    {
        $this->environmentMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn(' -v');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Setting secure admin');
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento config:set web/secure/use_in_adminhtml 1 -v');
        $this->packageManagerMock->method('hasMagentoVersion')
            ->with('2.2')
            ->willReturn(true);

        $this->process->execute();
    }
}
