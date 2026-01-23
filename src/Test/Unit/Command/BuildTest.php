<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\Build;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @see Build
 */
#[AllowMockObjectsWithoutExpectations]
class BuildTest extends TestCase
{
    /**
     * @var Build
     */
    private $command;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->command = new Build();
    }

    public function testExecute(): void
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->createStub(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createStub(OutputInterface::class);
        /** @var Application|MockObject $applicationMock */
        $applicationMock = $this->createMock(Application::class);

        $applicationMock->method('getHelperSet')
            ->willReturn($this->createMock(HelperSet::class));
        $applicationMock->expects(self::exactly(2))
            ->method('find')
            ->willReturnMap([
                [Build\Generate::NAME, $this->createMock(Build\Generate::class)],
                [Build\Transfer::NAME, $this->createMock(Build\Transfer::class)],
            ]);

        $this->command->setApplication($applicationMock);
        $this->command->execute($inputMock, $outputMock);
    }

    public function testExecuteException(): void
    {
        $this->expectExceptionMessage('Application is not defined');
        $this->expectException(RuntimeException::class);

        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->createStub(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->createStub(OutputInterface::class);
        /** @var Application|MockObject $applicationMock */
        $applicationMock = null;

        $this->command->setApplication($applicationMock);
        $this->command->execute($inputMock, $outputMock);
    }
}
