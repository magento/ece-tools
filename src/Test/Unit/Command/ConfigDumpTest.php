<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Process\ConfigDump\Export;
use Magento\MagentoCloud\Process\ConfigDump\Generate;
use Magento\MagentoCloud\Process\ConfigDump\Import;
use Magento\MagentoCloud\Package\MagentoVersion;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 */
class ConfigDumpTest extends TestCase
{
    /**
     * @var ConfigDump
     */
    private $command;

    /**
     * @var Export|\PHPUnit_Framework_MockObject_MockObject
     */
    private $exportMock;

    /**
     * @var Generate|\PHPUnit_Framework_MockObject_MockObject
     */
    private $generateMock;

    /**
     * @var Import|\PHPUnit_Framework_MockObject_MockObject
     */
    private $importMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var MagentoVersion|PHPUnit_Framework_MockObject_MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->exportMock = $this->createMock(Export::class);
        $this->generateMock = $this->createMock(Generate::class);
        $this->importMock = $this->createMock(Import::class);
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->command = new ConfigDump(
            $this->exportMock,
            $this->generateMock,
            $this->importMock,
            $this->loggerMock,
            $this->magentoVersionMock
        );
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting dump.'],
                ['Dump completed.']
            );
        $this->exportMock->expects($this->once())->method('execute');
        $this->generateMock->expects($this->once())->method('execute');
        $this->importMock->expects($this->once())->method('execute');
        $tester = new CommandTester(
            $this->command
        );
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->willReturn(true);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Some error
     */
    public function testExecuteWithException()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Starting dump.');
        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with('Some error');
        $this->exportMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Some error'));

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }
}
