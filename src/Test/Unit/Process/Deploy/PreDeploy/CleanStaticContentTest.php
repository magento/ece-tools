<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\PreDeploy\CleanStaticContent;
use Magento\MagentoCloud\Util\StaticContentCleaner;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class CleanStaticContentTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var StaticContentCleaner|Mock
     */
    private $staticContentCleanerMock;

    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var CleanStaticContent
     */
    private $process;

    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->environmentMock = $this->createMock(Environment::class);
        $this->staticContentCleanerMock = $this->createMock(StaticContentCleaner::class);

        $this->process = new CleanStaticContent(
            $this->loggerMock,
            $this->environmentMock,
            $this->staticContentCleanerMock
        );
    }

    public function testExecute()
    {
        $this->environmentMock->expects($this->once())
            ->method('isStaticDeployInBuild')
            ->willReturn(true);
        $this->staticContentCleanerMock->expects($this->once())
            ->method('cleanPubStatic');
        $this->staticContentCleanerMock->expects($this->once())
            ->method('cleanViewPreprocessed');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Static content deployment was performed during build hook, cleaning old content.');

        $this->process->execute();
    }
}
