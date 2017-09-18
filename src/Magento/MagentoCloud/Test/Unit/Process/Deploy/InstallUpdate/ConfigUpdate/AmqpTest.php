<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy\InstallUpdate\ConfigUpdate;

use PHPUnit\Framework\TestCase;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\Config\Deploy\Writer as ConfigWriter;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\Amqp;

/**
 * @inheritdoc
 */
class AmqpTest extends TestCase
{
    /**
     * @var Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $environmentMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ConfigWriter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configWriterMock;

    /**
     * @var ConfigReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configReaderMock;

    /**
     * @var Amqp
     */
    private $amqp;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->environmentMock = $this->createMock(Environment::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->configWriterMock = $this->createMock(ConfigWriter::class);
        $this->configReaderMock = $this->createMock(ConfigReader::class);

        $this->amqp = new Amqp(
            $this->environmentMock,
            $this->configReaderMock,
            $this->configWriterMock,
            $this->loggerMock
        );
    }

    public function testExecuteWithoutAmqp()
    {
        $config = ['some config'];
        $this->environmentMock->expects($this->once())
            ->method('getRelationship')
            ->with('mq')
            ->willReturn([]);
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($config);
        $this->configWriterMock->expects($this->once())
            ->method('write')
            ->with($config);
        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->amqp->execute();
    }
}
