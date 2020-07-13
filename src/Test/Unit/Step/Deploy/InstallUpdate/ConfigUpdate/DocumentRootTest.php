<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\DocumentRoot;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DocumentRootTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var DocumentRoot
     */
    private $step;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);

        $this->step = new DocumentRoot(
            $this->loggerMock,
            $this->configWriterMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('The value of the property \'directories/document_root_is_pub\' set as \'true\'');
        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with(['directories' => ['document_root_is_pub' => true]]);

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithException()
    {
        $exceptionMsg = 'Error';
        $exceptionCode = 111;

        $this->expectException(StepException::class);
        $this->expectExceptionMessage($exceptionMsg);
        $this->expectExceptionCode($exceptionCode);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('The value of the property \'directories/document_root_is_pub\' set as \'true\'');
        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->willThrowException(new GenericException($exceptionMsg, $exceptionCode));

        $this->step->execute();
    }
}
