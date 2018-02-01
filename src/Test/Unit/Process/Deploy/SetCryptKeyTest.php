<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\Deploy\SetCryptKey;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class CryptKeyTest extends TestCase
{
    /**
     * @var Environment|Mock
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|Mock
     */
    private $configWriterMock;

    /**
     * @var DbConnection
     */
    private $process;

    public function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->configWriterMock = $this->createMock(ConfigWriter::class);

        $this->process = new SetCryptKey(
            $this->environmentMock,
            $this->loggerMock,
            $this->configWriterMock
        );
    }

    public function testConfigUpdated()
    {
        $this->environmentMock->expects($this->once())
            ->method('getCryptKey')
            ->willReturn('TWFnZW50byBSb3g=');

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Setting encryption key');

        $this->configWriterMock->expects($this->once())
            ->method('update')
            ->with(['crypt' => ['key' => 'TWFnZW50byBSb3g=']]);

        $this->process->execute();
    }

    public function testUpdateSkipped()
    {
        $this->environmentMock->expects($this->once())
            ->method('getCryptKey')
            ->willReturn('');

        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->configWriterMock->expects($this->never())
            ->method('update');

        $this->process->execute();
    }
}
