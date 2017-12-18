<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Prestart;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\FlagFile\Flag\StaticContentDeployPending;
use Magento\MagentoCloud\Filesystem\FlagFile\Manager;
use Magento\MagentoCloud\Process\Prestart\CompressStaticContent;
use Magento\MagentoCloud\Util\StaticContentCompressor;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

/**
 * Unit test for deploy-time static content compressor.
 */
class CompressStaticContentTest extends TestCase
{
    /**
     * @var CompressStaticContent
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var StaticContentCompressor|Mock
     */
    private $compressorMock;

    /**
     * @var Manager|Mock
     */
    private $flagFileManagerMock;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->compressorMock = $this->createMock(StaticContentCompressor::class);
        $this->flagFileManagerMock = $this->createMock(Manager::class);

        $this->process = new CompressStaticContent(
            $this->loggerMock,
            $this->environmentMock,
            $this->compressorMock,
            $this->flagFileManagerMock
        );
    }

    public function testExecuteCompressInPrestart()
    {
        $this->environmentMock
            ->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->flagFileManagerMock->expects($this->once())
            ->method('exists')
            ->with(StaticContentDeployPending::KEY)
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('getVerbosityLevel')
            ->willReturn('');
        $this->compressorMock->expects($this->once())
            ->method('process')
            ->with(StaticContentCompressor::DEFAULT_COMPRESSION_LEVEL);

        $this->process->execute();
    }

    public function testExecuteSCDInBuild()
    {
        $this->environmentMock
            ->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Static content deployment was performed during the build phase or disabled. Skipping prestart phase'
            . ' static content compression.');
        $this->flagFileManagerMock->expects($this->never())
            ->method('exists');
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }

    public function testExecuteSCDInDeploy()
    {
        $this->environmentMock
            ->expects($this->once())
            ->method('isDeployStaticContent')
            ->willReturn(true);
        $this->flagFileManagerMock->expects($this->once())
            ->method('exists')
            ->with(StaticContentDeployPending::KEY)
            ->willReturn(false);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Static content deployment was performed during the build phase or disabled. Skipping prestart phase'
                . ' static content compression.');
        $this->compressorMock
            ->expects($this->never())
            ->method('process');

        $this->process->execute();
    }
}
