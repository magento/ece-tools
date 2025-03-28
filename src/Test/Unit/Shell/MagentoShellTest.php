<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Shell;

use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\Shell;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class MagentoShellTest extends TestCase
{
    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var Shell|MockObject
     */
    private $shellMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->shellMock = $this->createMock(Shell::class);

        $this->magentoShell = new MagentoShell(
            $this->shellMock
        );
    }

    public function testExecute()
    {
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento some:command --ansi --no-interaction', ['arg1']);

        $this->magentoShell->execute(
            'some:command',
            ['arg1']
        );
    }

    public function testExecuteWithEmptyArgument()
    {
        $this->shellMock->expects($this->once())
            ->method('execute')
            ->with('php ./bin/magento some:command --ansi --no-interaction', ['arg1']);

        $this->magentoShell->execute(
            'some:command',
            ['arg1', '', null]
        );
    }
}
