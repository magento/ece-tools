<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Process\Build;

use Magento\MagentoCloud\Patch\Manager;
use Magento\MagentoCloud\Process\Build\ApplyPatches;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @inheritdoc
 */
class ApplyPatchesTest extends TestCase
{
    /**
     * @var ApplyPatches
     */
    private $process;

    /**
     * @var LoggerInterface|Mock
     */
    private $loggerMock;

    /**
     * @var Manager|Mock
     */
    private $managerMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->managerMock = $this->createMock(Manager::class);

        $this->process = new ApplyPatches(
            $this->loggerMock,
            $this->managerMock
        );

        parent::setUp();
    }

    public function testExecute()
    {
        $this->loggerMock->expects($this->exactly(2))
            ->method('notice')
            ->withConsecutive(
                ['Applying patches.'],
                ['End of applying patches.']
            );
        $this->managerMock->expects($this->once())
            ->method('applyAll');

        $this->process->execute();
    }
}
