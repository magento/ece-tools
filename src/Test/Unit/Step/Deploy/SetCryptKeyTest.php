<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Step\Deploy;

use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface as ConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Step\Deploy\SetCryptKey;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class SetCryptKeyTest extends TestCase
{
    /**
     * @var Environment|MockObject
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigReader|MockObject
     */
    private $configReaderMock;

    /**
     * @var ConfigWriter|MockObject
     */
    private $configWriterMock;

    /**
     * @var SetCryptKey
     */
    private $step;

    public function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->configReaderMock = $this->createMock(ConfigReader::class);
        $this->configWriterMock = $this->createMock(ConfigWriter::class);

        $this->step = new SetCryptKey(
            $this->environmentMock,
            $this->loggerMock,
            $this->configReaderMock,
            $this->configWriterMock
        );
    }

    public function testConfigUpdated()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getCryptKey')
            ->willReturn('TWFnZW50byBSb3g=');
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Setting encryption key');
        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with(['crypt' => ['key' => 'TWFnZW50byBSb3g=']]);

        $this->step->execute();
    }

    public function testEnvironmentVariableNotSet()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->environmentMock->expects($this->once())
            ->method('getCryptKey')
            ->willReturn('');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->configWriterMock->expects($this->never())
            ->method('update');

        $this->step->execute();
    }

    public function testKeyAlreadySet()
    {
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn(['crypt' => ['key' => 'QmVuIHd1eiBoZXJl']]);
        $this->environmentMock->expects($this->never())
            ->method('getCryptKey');
        $this->loggerMock->expects($this->never())
            ->method('info');
        $this->configWriterMock->expects($this->never())
            ->method('update');

        $this->step->execute();
    }
}
