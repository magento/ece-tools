<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\Deploy\PreDeploy;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\ComponentInfo;
use Magento\MagentoCloud\Util\StaticContentCleaner;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class PreDeployTest extends TestCase
{
    /**
     * @var ShellInterface|Mock
     */
    private $shellMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var File|Mock
     */
    private $fileMock;

    /**
     * @var ComponentInfo|Mock
     */
    private $componentInfoMock;

    /**
     * @var StaticContentCleaner|Mock
     */
    private $staticContentCleanerMock;

    /**
     * @var PreDeploy
     */
    private $process;

    protected function setUp()
    {
        $this->shellMock = $this->getMockBuilder(ShellInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->fileMock = $this->createMock(File::class);
        $this->componentInfoMock = $this->createMock(ComponentInfo::class);
        $this->staticContentCleanerMock = $this->createMock(StaticContentCleaner::class);

        $this->process = new PreDeploy(
            $this->environmentMock,
            $this->loggerMock,
            $this->shellMock,
            $this->fileMock,
            $this->componentInfoMock,
            $this->staticContentCleanerMock
        );
    }

    public function testExecute()
    {
        $this->markTestIncomplete();
    }
}
