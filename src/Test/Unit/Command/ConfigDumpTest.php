<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var ShellFactory|MockObject
     */
    private $shellFactoryMock;

    /**
     * @var MagentoShell|MockObject
     */
    private $shellMock;

    /**
     * @var ConfigDump\Generate|MockObject
     */
    private $generateMock;

    /**
     * @var Reader|MockObject
     */
    private $readerMock;

    /**
     * @var Writer|MockObject
     */
    private $writerMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellFactoryMock = $this->createMock(ShellFactory::class);
        $this->shellMock = $this->createMock(MagentoShell::class);
        $this->generateMock = $this->createMock(ConfigDump\Generate::class);
        $this->readerMock = $this->createMock(Reader::class);
        $this->writerMock = $this->createMock(Writer::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);

        $this->shellFactoryMock->method('createMagento')
            ->willReturn($this->shellMock);

        $this->command = new ConfigDump(
            $this->loggerMock,
            $this->shellFactoryMock,
            $this->generateMock,
            $this->readerMock,
            $this->writerMock,
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
        $this->generateMock->expects($this->once())
            ->method('execute');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(true);
        $this->shellMock->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(['app:config:dump'], ['app:config:import']);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testExecute21Version()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Starting dump.'],
                ['Dump completed.']
            );
        $this->generateMock->expects($this->once())
            ->method('execute');
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->magentoVersionMock->expects($this->once())
            ->method('isGreaterOrEqual')
            ->with('2.2')
            ->willReturn(false);
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->withConsecutive(['app:config:dump']);

        $tester = new CommandTester(
            $this->command
        );
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
        $this->generateMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new GenericException('Some error'));

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);
    }
}
