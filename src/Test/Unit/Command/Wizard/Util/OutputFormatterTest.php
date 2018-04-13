<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command\Wizard\Util;

use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class OutputFormatterTest extends TestCase
{
    /**
     * @var OutputFormatter
     */
    private $outputFormatter;

    /**
     * @var OutputInterface|Mock
     */
    private $outputMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->outputMock = $this->createMock(OutputInterface::class);

        $this->outputFormatter = new OutputFormatter();
    }

    public function testWriteResult()
    {
        $this->outputMock->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                ['<info>some item</info>'],
                ['<error>some item</error>']
            );

        $this->outputFormatter->writeResult(
            $this->outputMock,
            true,
            'some item'
        );
        $this->outputFormatter->writeResult(
            $this->outputMock,
            false,
            'some item'
        );
    }

    public function testWriteItem()
    {
        $this->outputMock->expects($this->once())
            ->method('writeln')
            ->with(' - some item');

        $this->outputFormatter->writeItem(
            $this->outputMock,
            'some item'
        );
    }
}
