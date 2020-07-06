<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Flag\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\CacheType;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class CacheTypeTest extends TestCase
{
    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var DeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @var MagentoShell|MockObject
     */
    private $magentoShellMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var CacheType
     */
    private $cacheType;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->stageConfigMock = $this->getMockForAbstractClass(DeployInterface::class);
        $this->magentoShellMock = $this->createMock(MagentoShell::class);
        /** @var ShellFactory|MockObject $shellFactoryMock */
        $shellFactoryMock = $this->createMock(ShellFactory::class);
        $shellFactoryMock->expects($this->once())
            ->method('createMagento')
            ->willReturn($this->magentoShellMock);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);

        $this->cacheType = new CacheType(
            $this->flagManagerMock,
            $this->stageConfigMock,
            $shellFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * Test that shell won't be run if file existed
     */
    public function testExecuteWithFile()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_ENV_FILE_ABSENCE)
            ->willReturn(false);

        $this->magentoShellMock->expects($this->never())
            ->method('execute');

        $this->cacheType->execute();
    }

    /**
     * Test that shell command runs when file did not exist
     */
    public function testExecuteWhenFileWasAbsent()
    {
        $verbosity = '-vv';
        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_ENV_FILE_ABSENCE)
            ->willReturn(true);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Run cache:enable to restore all cache types');
        $this->stageConfigMock->expects($this->once())
            ->method('get')
            ->with(DeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn($verbosity);
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->with('cache:enable', [$verbosity]);

        $this->cacheType->execute();
    }

    /**
     * @throws StepException
     */
    public function testShellException()
    {
        $exceptionCode = 111;
        $exceptionMsg = 'Exception message';
        $exception = new ShellException($exceptionMsg, $exceptionCode);

        $this->expectExceptionObject(new StepException($exceptionMsg, Error::DEPLOY_CACHE_ENABLE_FAILED, $exception));

        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_ENV_FILE_ABSENCE)
            ->willReturn(true);
        $this->magentoShellMock->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->cacheType->execute();
    }

    /**
     * @throws StepException
     */
    public function testGenericException()
    {
        $exceptionCode = 222;
        $exceptionMsg = 'Exception message';
        $exception = new ConfigurationMismatchException($exceptionMsg, $exceptionCode);

        $this->expectExceptionObject(new StepException($exceptionMsg, $exceptionCode, $exception));

        $this->flagManagerMock->expects($this->once())
            ->method('exists')
            ->with(FlagManager::FLAG_ENV_FILE_ABSENCE)
            ->willThrowException($exception);

        $this->cacheType->execute();
    }
}
