<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Lock;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Step\Deploy\InstallUpdate\ConfigUpdate\Lock\Config as LockConfig;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Step\StepException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class LockTest extends TestCase
{
    /**
     * @var Lock
     */
    private $lock;

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
     * @var LockConfig|MockObject
     */
    private $lockConfigMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->lockConfigMock = $this->createMock(LockConfig::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->lock = new Lock(
            $this->configReaderMock,
            $this->configWriterMock,
            $this->lockConfigMock,
            $this->magentoVersionMock,
            $this->loggerMock
        );
    }

    /**
     * @throws StepException
     */
    public function testExecuteMagento225OrGreater(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.5')
            ->willReturn(true);
        $this->lockConfigMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'provider' => 'lock-provider',
                'config' => [
                    'some-config' => 'some-value',
                ],
            ]);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'db' => 'some-config'
            ]);
        $this->configWriterMock->expects($this->once())
            ->method('create')
            ->with([
                'db' => 'some-config',
                'lock' => [
                    'provider' => 'lock-provider',
                    'config' => [
                        'some-config' => 'some-value',
                    ],
                ],
            ]);
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('The lock provider "lock-provider" was set.');

        $this->lock->execute();
    }

    /**
     * @throws StepException
     */
    public function testExecuteMagentoLess225(): void
    {
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2.5')
            ->willReturn(false);
        $this->lockConfigMock->expects($this->never())->method('get');
        $this->configReaderMock->expects($this->never())->method('read');
        $this->configWriterMock->expects($this->never())->method('create');
        $this->loggerMock->expects($this->never())->method('info');

        $this->lock->execute();
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

        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willThrowException(new GenericException($exceptionMsg, $exceptionCode));

        $this->lock->execute();
    }
}
