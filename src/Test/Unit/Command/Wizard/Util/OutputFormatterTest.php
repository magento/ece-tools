<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Wizard\Util;

use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
     * @var OutputInterface|MockObject
     */
    private $outputMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->outputMock = $this->createMock(OutputInterface::class);

        $this->outputFormatter = new OutputFormatter();
    }

    public function testWriteResult(): void
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

    public function testWriteItem(): void
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
