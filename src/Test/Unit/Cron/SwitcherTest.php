<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Cron;

use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Magento\MagentoCloud\Cron\Switcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class SwitcherTest extends TestCase
{
    /**
     * @var Switcher
     */
    private $switcher;

    /**
     * @var ReaderInterface|MockObject
     */
    private $readerMock;

    /**
     * @var WriterInterface|MockObject
     */
    private $writerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->readerMock = $this->getMockForAbstractClass(ReaderInterface::class);
        $this->writerMock = $this->getMockForAbstractClass(WriterInterface::class);

        $this->switcher = new Switcher($this->writerMock, $this->readerMock);
    }

    /**
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function testDisable()
    {
        $this->writerMock->expects($this->once())
            ->method('update')
            ->with(['cron' => ['enabled' => 0]]);

        $this->switcher->disable();
    }

    /**
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    public function testEnable()
    {
        $this->readerMock->expects($this->once())
            ->method('read')
            ->willReturn([
                'option1' => 'value1',
                'option2' => 'value2',
                'cron' => ['enabled' => 0, 'some-option' => 'some-value']
            ]);
        $this->writerMock->expects($this->once())
            ->method('create')
            ->with([
                'option1' => 'value1',
                'option2' => 'value2',
                'cron' => ['some-option' => 'some-value']
            ]);

        $this->switcher->enable();
    }
}
