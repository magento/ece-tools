<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Unit\Command;

use Magento\MagentoCloud\Command\GenerateSchema;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
class GenerateSchemaTest extends TestCase
{
    /**
     * @var GenerateSchema
     */
    private $command;

    /**
     * @var Schema\Formatter|MockObject
     */
    private $formatterMock;

    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var Schema|MockObject
     */
    private $schemaMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->formatterMock = $this->createMock(Schema\Formatter::class);
        $this->fileMock = $this->createMock(File::class);
        $this->fileListMock = $this->createMock(FileList::class);
        $this->schemaMock = $this->createMock(Schema::class);

        $this->command = new GenerateSchema(
            $this->formatterMock,
            $this->fileMock,
            $this->fileListMock,
            $this->schemaMock
        );
    }

    public function testExecute(): void
    {
        /** @var InputInterface|MockObject $input */
        $input = $this->getMockForAbstractClass(InputInterface::class);
        /** @var OutputInterface|MockObject $output */
        $output = $this->getMockForAbstractClass(OutputInterface::class);
        $output->expects($this->exactly(2))
            ->method('writeln');

        $this->schemaMock->method('getSchema')
            ->willReturn(['some' => 'schema']);
        $this->fileListMock->method('getEnvDistConfig')
            ->willReturn('.magento.env.md');
        $this->formatterMock->method('format')
            ->with(['some' => 'schema'])
            ->willReturn('some schema');
        $this->fileMock->method('filePutContents')
            ->with('.magento.env.md', 'some schema');

        $this->command->execute($input, $output);
    }
}
