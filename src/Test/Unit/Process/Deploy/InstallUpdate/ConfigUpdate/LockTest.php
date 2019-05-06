<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Lock;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Lock\Config as LockConfig;
use Magento\MagentoCloud\Package\MagentoVersion;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class LockTest extends TestCase
{
    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|Mock
     */
    private $configReaderMock;

    /**
     * @var LockConfig|Mock
     */
    private $lockConfigMock;

    /**
     * @var MagentoVersion|Mock
     */
    private $magentoVersionMock;

    /**
     * @var Lock
     */
    private $lock;

    /**
     * @inheritdoc
     */
    protected function setUp()
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
     * @return void
     */
    public function testExecuteMagento225OrGreater()
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
     * return void
     */
    public function testExecuteMagentoLess225()
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
}
