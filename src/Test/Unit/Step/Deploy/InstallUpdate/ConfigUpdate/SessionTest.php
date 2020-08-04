<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Session;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SessionTest extends TestCase
{
    /**
     * @var Session
     */
    private $step;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var Session\Config|MockObject
     */
    private $sessionConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->sessionConfigMock = $this->createMock(Session\Config::class);

        $this->step = new Session(
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock,
            $this->sessionConfigMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecute(): void
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->sessionConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'save' => 'redis',
                'redis' => [
                    'host' => 'redis_host'
                ]
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'session' => [
                    'save' => 'redis',
                    'redis' => [
                        'host' => 'redis_host'
                    ]
                ]
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Updating session configuration.');

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteEmptyConfig(): void
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['session' => ['save' => 'redis']]);
        $this->sessionConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with(['session' => ['save' => 'db']]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Removing session configuration from env.php.');

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

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willThrowException(new GenericException($exceptionMsg, $exceptionCode));

        $this->step->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteWithExceptionInCreate()
    {
        $exceptionMsg = 'Some error';
        $exceptionCode = 11111;
        $this->expectException(StepException::class);
        $this->expectExceptionCode(Error::DEPLOY_ENV_PHP_IS_NOT_WRITABLE);
        $this->expectExceptionMessage($exceptionMsg);

        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->willThrowException(new FileSystemException($exceptionMsg, $exceptionCode));

        $this->step->execute();
    }
}
