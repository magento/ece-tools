<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\Build;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class BuildTest extends TestCase
{
    /**
     * @var Build
     */
    private $command;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->command = new Build();
    }

    /**
     * @inheritdoc
     */
    public function testExecute()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->createMock(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createMock(OutputInterface::class);
        /** @var Application|MockObject $applicationMock */
        $applicationMock = $this->createMock(Application::class);

        $applicationMock->method('getHelperSet')
            ->willReturn($this->createMock(HelperSet::class));
        $applicationMock->expects($this->exactly(2))
            ->method('find')
            ->willReturnMap([
                [Build\Generate::NAME, $this->createMock(Build\Generate::class)],
                [Build\Transfer::NAME, $this->createMock(Build\Transfer::class)],
            ]);

        $this->command->setApplication($applicationMock);
        $this->command->execute($inputMock, $outputMock);
    }
}
