<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\ProcessStaticContent;
use Magento\MagentoCloud\Util\BuildDirCopier;
use Magento\MagentoCloud\Util\StaticContentCleaner;
use Magento\MagentoCloud\Util\StaticContentSymlink;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;

/**
 * @inheritdoc
 */
class ProcessStaticContentTest extends TestCase
{
    /**
     * @var ProcessStaticContent
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var StaticContentCleaner|Mock
     */
    private $staticContentCleanerMock;

    /**
     * @var StaticContentSymlink|Mock
     */
    private $staticContentSymlinkMock;

    /**
     * @var BuildDirCopier|Mock
     */
    private $buildDirCopierMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var BuildConfig|Mock
     */
    private $buildConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->buildDirCopierMock = $this->createMock(BuildDirCopier::class);
        $this->staticContentCleanerMock = $this->createMock(StaticContentCleaner::class);
        $this->staticContentSymlinkMock = $this->createMock(StaticContentSymlink::class);
        $this->buildConfigMock = $this->createMock(BuildConfig::class);

        $this->process = new ProcessStaticContent(
            $this->loggerMock,
            $this->environmentMock,
            $this->staticContentCleanerMock,
            $this->staticContentSymlinkMock,
            $this->buildDirCopierMock,
            $this->buildConfigMock
        );
    }

    public function testExecute()
    {
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('isStaticContentSymlinkOn')
            ->willReturn(true);
        $this->staticContentCleanerMock->expects($this->once())
            ->method('cleanPubStatic');
        $this->staticContentSymlinkMock->expects($this->once())
            ->method('create');
        $this->buildDirCopierMock->expects($this->never())
            ->method('copy');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Static content deployment was performed during build hook'],
                ['Symlinking static content from pub/static to init/pub/static']
            );
        $this->buildConfigMock->method('isSkipStaticMount')
            ->willReturn(false);

        $this->process->execute();
    }

    public function testExecuteWithoutSymlink()
    {
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(true);
        $this->environmentMock->expects($this->once())
            ->method('isStaticContentSymlinkOn')
            ->willReturn(false);
        $this->staticContentCleanerMock->expects($this->once())
            ->method('cleanPubStatic');
        $this->buildDirCopierMock->expects($this->once())
            ->method('copy');
        $this->staticContentSymlinkMock->expects($this->never())
            ->method('create');
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Static content deployment was performed during build hook'],
                ['Copying static content from init/pub/static to pub/static']
            );
        $this->buildConfigMock->method('isSkipStaticMount')
            ->willReturn(false);

        $this->process->execute();
    }

    public function testExecuteStaticDeployNotInBuild()
    {
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(false);
        $this->environmentMock->expects($this->never())
            ->method('isStaticContentSymlinkOn');
        $this->staticContentCleanerMock->expects($this->never())
            ->method('cleanPubStatic');
        $this->buildDirCopierMock->expects($this->never())
            ->method('copy');
        $this->staticContentSymlinkMock->expects($this->never())
            ->method('create');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->buildConfigMock->method('isSkipStaticMount')
            ->willReturn(false);

        $this->process->execute();
    }

    public function testWithSkippedMount()
    {
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(true);
        $this->buildConfigMock->method('isSkipStaticMount')
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Static content folder was not mounted. Read-only mode used.');
        $this->environmentMock->expects($this->never())
            ->method('isStaticContentSymlinkOn');
        $this->staticContentCleanerMock->expects($this->never())
            ->method('cleanPubStatic');
        $this->buildDirCopierMock->expects($this->never())
            ->method('copy');
        $this->staticContentSymlinkMock->expects($this->never())
            ->method('create');

        $this->process->execute();
    }
}
