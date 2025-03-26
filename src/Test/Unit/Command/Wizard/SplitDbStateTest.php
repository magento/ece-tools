<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\SplitDbState;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\DB\Data\RelationshipConnection;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class SplitDbStateTest extends TestCase
{
    /**
     * @var SplitDbState
     */
    private $command;

    /**
     * @var OutputFormatter|MockObject
     */
    private $outputFormatterMock;

    /**
     * @var ReaderInterface|MockObject
     */
    private $configReaderMock;

    /**
     * @var RelationshipConnectionFactory|MockObject
     */
    private $connectionDataFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->outputFormatterMock = $this->createMock(OutputFormatter::class);
        $this->configReaderMock = $this->getMockForAbstractClass(ReaderInterface::class);
        $this->connectionDataFactoryMock = $this->createMock(RelationshipConnectionFactory::class);

        $this->command = new SplitDbState(
            $this->outputFormatterMock,
            $this->configReaderMock,
            $this->connectionDataFactoryMock
        );
    }

    /**
     * @param $mageConf
     * @param $message
     *
     * @dataProvider executeWithSplitDataProvider
     */
    public function testExecuteWithSplit($mageConf, $message)
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($mageConf);

        $this->connectionDataFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap(
                [
                    [RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN, new RelationshipConnection([])],
                    [RelationshipConnectionFactory::CONNECTION_SALES_MAIN, new RelationshipConnection([])],
                ]
            );

        $this->outputFormatterMock->expects($this->never())
            ->method('writeItem');
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, true, $message);

        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * @param $quoteConnection
     * @param $salesConnection
     * @param $message
     *
     * @dataProvider executeNoSplitDataProvider
     */
    public function testExecuteNoSplit($quoteConnection, $salesConnection, $message)
    {
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $mageConf = [
            'db' => [
                'connection' => [
                    'main' => ['host' => 'localhost'],
                ]
            ]
        ];
        $this->configReaderMock->expects($this->once())
            ->method('read')
            ->willReturn($mageConf);

        $this->connectionDataFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnMap([
                [RelationshipConnectionFactory::CONNECTION_QUOTE_MAIN, new RelationshipConnection($quoteConnection)],
                [RelationshipConnectionFactory::CONNECTION_SALES_MAIN, new RelationshipConnection($salesConnection)],
            ]);

        $this->outputFormatterMock->expects($this->any())
            ->method('writeItem')
            ->with($outputMock, $message);
        $this->outputFormatterMock->expects($this->once())
            ->method('writeResult')
            ->with($outputMock, true, 'DB is not split');

        $this->command->execute($inputMock, $outputMock);
    }

    /**
     * Data provider for executeWithSplit
     * @return array
     */
    public function executeWithSplitDataProvider(): array
    {
        $mageConfQuote = [
            'db' => [
                'connection' => [
                    'default' => ['host' => 'localhost'],
                    'checkout' => ['host' => 'localhost'],
                ]
            ]
        ];
        $mageConfSales = [
            'db' => [
                'connection' => [
                    'sales' => ['host' => 'localhost'],
                ]
            ]
        ];
        $mageConfSalesQuote = array_merge_recursive($mageConfQuote, $mageConfSales);

        return [
            [$mageConfQuote, 'DB is already split with type(s): quote'],
            [$mageConfSales, 'DB is already split with type(s): sales'],
            [$mageConfSalesQuote, 'DB is already split with type(s): quote, sales'],
        ];
    }

    /**
     * Data provider for testExecuteNoSplit
     * @return array
     */
    public function executeNoSplitDataProvider(): array
    {
        $connection = [
            'host' => '120.0.0.1',
            'dbname' => 'dbname'
        ];

        return [
            [[], [], 'DB cannot be split on this environment'],
            [$connection, [], 'You may split DB using SPLIT_DB variable in .magento.env.yaml file'],
            [[], $connection, 'You may split DB using SPLIT_DB variable in .magento.env.yaml file'],
            [$connection, $connection, 'You may split DB using SPLIT_DB variable in .magento.env.yaml file'],
        ];
    }
}
