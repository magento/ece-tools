<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Deploy;

use Magento\MagentoCloud\Util\BackgroundProcess;
use Magento\MagentoCloud\Process\Deploy\BackgroundProcessKill;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @inheritDoc
 */
class BackgroundProcessKillTest extends TestCase
{
    /**
     * @var BackgroundProcessKill
     */
    private $process;

    /**
     * @var BackgroundProcess|MockObject
     */
    private $backgroundProcessMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->backgroundProcessMock = $this->createMock(BackgroundProcess::class);

        $this->process = new BackgroundProcessKill(
            $this->backgroundProcessMock
        );
    }

    public function testExecute()
    {
        $this->backgroundProcessMock->expects($this->once())
            ->method('kill');

        $this->process->execute();
    }
}
