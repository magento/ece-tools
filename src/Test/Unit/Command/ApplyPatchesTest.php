<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\ApplyPatches;
use Magento\MagentoCloud\Patch\Manager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ApplyPatchesTest.
 */
class ApplyPatchesTest extends TestCase
{
    /**
     * @var ApplyPatches
     */
    private $command;

    /**
     * @var Manager|MockObject
     */
    private $managerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->managerMock = $this->createMock(Manager::class);

        $this->command = new ApplyPatches(
            $this->managerMock
        );
    }

    public function testExecute(): void
    {
        $this->managerMock->expects($this->once())
            ->method('apply');

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->command->execute($inputMock, $outputMock);
    }
}
