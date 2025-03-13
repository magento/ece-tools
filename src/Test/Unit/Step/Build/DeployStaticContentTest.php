<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Build;

use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
use Magento\MagentoCloud\Config\Validator\Result;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Step\Build\DeployStaticContent;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DeployStaticContentTest extends TestCase
{
    /**
     * @var DeployStaticContent
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var StepInterface|MockObject
     */
    private $stepMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var ScdOnBuild|MockObject
     */
    private $scdOnBuildMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->stepMock = $this->getMockForAbstractClass(StepInterface::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->scdOnBuildMock = $this->createMock(ScdOnBuild::class);

        $this->step = new DeployStaticContent(
            $this->loggerMock,
            $this->flagManagerMock,
            $this->scdOnBuildMock,
            [$this->stepMock]
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
        $this->stepMock->expects($this->once())
            ->method('execute');
        $this->flagManagerMock->expects($this->once())
            ->method('set')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Result\Success());

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithError()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
        $this->stepMock->expects($this->never())
            ->method('execute');
        $this->flagManagerMock->expects($this->never())
            ->method('set');
        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Result\Error('Some error'));
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Skipping static content deploy: Some error');

        $this->step->execute();
    }

    public function testExecuteWithException()
    {
        $exceptionMsg = 'Error';
        $exceptionCode = 102;

        $this->expectException(StepException::class);
        $this->expectExceptionMessage($exceptionMsg);
        $this->expectExceptionCode($exceptionCode);

        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willThrowException(new \Exception($exceptionMsg, $exceptionCode));

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithStepException()
    {
        $e = new StepException('Error Message', 111);
        $this->expectExceptionObject($e);

        $this->flagManagerMock->expects($this->once())
            ->method('delete')
            ->with(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
        $this->scdOnBuildMock->expects($this->once())
            ->method('validate')
            ->willReturn(new Result\Success());
        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with('Generating fresh static content');
        $this->stepMock->expects($this->once())
            ->method('execute')
            ->willThrowException($e);

        $this->step->execute();
    }
}
