<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Command\ConfigDump;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @inheritdoc
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var ReaderInterface|MockObject
     */
    private $readerMock;

    /**
     * @var WriterInterface|MockObject
     */
    private $writerMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var PostDeployInterface|MockObject
     */
    private $stageConfigMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->shellFactoryMock = $this->createMock(ShellFactory::class);
        $this->shellMock = $this->createMock(MagentoShell::class);
        $this->generateMock = $this->createMock(ConfigDump\Generate::class);
        $this->readerMock = $this->getMockForAbstractClass(ReaderInterface::class);
        $this->writerMock = $this->getMockForAbstractClass(WriterInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->stageConfigMock = $this->createMock(PostDeployInterface::class);

        $this->shellFactoryMock->method('createMagento')
            ->willReturn($this->shellMock);

        $this->command = new ConfigDump(
            $this->loggerMock,
            $this->shellFactoryMock,
            $this->generateMock,
            $this->readerMock,
            $this->writerMock,
            $this->magentoVersionMock,
            $this->stageConfigMock
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
        $this->stageConfigMock->method('get')
            ->with(PostDeployInterface::VAR_VERBOSE_COMMANDS)
            ->willReturn('-v');
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
            ->with('app:config:dump', ['-v']);

        $tester = new CommandTester(
            $this->command
        );
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testExecuteWithException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Some error');

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
