<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\Shell\ExecBinMagento;
use Magento\MagentoCloud\Shell\ShellInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * {@inheritdoc}
 */
class ExecBinMagentoTest extends TestCase
{
    /**
     * @var ExecBinMagento
     */
    private $binMagento;

    /**
     * @var ShellInterface|MockObject
     */
    private $shellMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->shellMock = $this->createMock(ShellInterface::class);

        $this->binMagento = new ExecBinMagento($this->shellMock);
    }

    public function testExecuteNoArgs()
    {
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with("php ./bin/magento 'some:command' '--ansi' '--no-interaction'")
            ->willReturn('bin magento result');

        $this->assertSame('bin magento result', $this->binMagento->execute('some:command'));
    }

    public function testExecuteStringArgs()
    {
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with("php ./bin/magento 'some:command' '--ansi' '--no-interaction' 'extra-arg'")
            ->willReturn('bin magento result');

        $this->assertSame('bin magento result', $this->binMagento->execute('some:command', 'extra-arg'));
    }

    public function testExecuteArrayArgs()
    {
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with("php ./bin/magento 'some:command' '--ansi' '--no-interaction' 'arg1' 'arg2'")
            ->willReturn('bin magento result');

        $this->assertSame('bin magento result', $this->binMagento->execute('some:command', ['arg1', 'arg2']));
    }
}
