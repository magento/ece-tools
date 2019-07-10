<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command\Docker;

use Magento\MagentoCloud\Command\Docker\GenerateDist;
use Magento\MagentoCloud\Docker\Config\Dist\Generator;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class GenerateDistTest extends TestCase
{
    /**
     * @var GenerateDist
     */
    private $command;
    /**
     * @var Generator|MockObject
     */
    private $distGeneratorMock;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->distGeneratorMock = $this->createMock(Generator::class);

        $this->command = new GenerateDist($this->distGeneratorMock);
    }

    /**
     * @throws FileSystemException
     * @throws ConfigurationMismatchException
     */
    public function testExecute()
    {
        /** @var InputInterface|MockObject $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $this->distGeneratorMock->expects($this->once())
            ->method('generate');

        $this->command->execute($inputMock, $outputMock);
    }
}
