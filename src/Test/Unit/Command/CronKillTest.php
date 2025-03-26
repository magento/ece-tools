<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\CronKill;
use Magento\MagentoCloud\Util\BackgroundProcess;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class CronKillTest extends TestCase
{
    /**
     * @var CronKill
     */
    private $command;

    /**
     * @var BackgroundProcess|MockObject
     */
    private $backgroundProcessMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->backgroundProcessMock = $this->createMock(BackgroundProcess::class);

        $this->command = new CronKill(
            $this->backgroundProcessMock
        );
    }

    public function testExecute()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->createMock(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createMock(OutputInterface::class);

        $this->backgroundProcessMock->expects($this->once())
            ->method('kill');

        $this->command->execute($inputMock, $outputMock);
    }
}
