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
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Util\YamlNormalizer;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritDoc
 */
#[AllowMockObjectsWithoutExpectations]
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
     * @var YamlNormalizer|MockObject
     */
    private YamlNormalizer $yamlNormalizerMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->formatterMock      = $this->createMock(Schema\Formatter::class);
        $this->fileMock           = $this->createMock(File::class);
        $this->fileListMock       = $this->createMock(FileList::class);
        $this->schemaMock         = $this->createMock(Schema::class);
        $this->yamlNormalizerMock = $this->createMock(YamlNormalizer::class);

        $this->command = new GenerateSchema(
            $this->formatterMock,
            $this->fileMock,
            $this->fileListMock,
            $this->schemaMock,
            $this->yamlNormalizerMock
        );
    }

    /**
     * Test execute method.
     *
     * @return void
     * @throws FileSystemException
     */
    public function testExecute(): void
    {
        /** @var InputInterface|MockObject $input */
        $input = $this->createStub(InputInterface::class);
        /** @var OutputInterface|MockObject $output */
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->exactly(2))
            ->method('writeln');

        $this->schemaMock->method('getVariables')
            ->willReturn(['some' => 'schema']);
        $this->fileListMock->method('getEnvDistConfig')
            ->willReturn('.magento.env.md');
        $this->fileListMock->method('getLogDistConfig')
            ->willReturn('/dist/.log.env.md');
        $this->formatterMock->method('format')
            ->with(['some' => 'schema'])
            ->willReturn('some schema');
        $this->fileMock->expects($this->once())
            ->method('fileGetContents')
            ->willReturn('some additional text');
        $this->fileMock->method('filePutContents')
            ->with('.magento.env.md', 'some schema' . PHP_EOL . 'some additional text');

        // Mock YAML normalization
        $this->yamlNormalizerMock = $this->createMock(YamlNormalizer::class);
        $this->yamlNormalizerMock->expects($this->any())
            ->method('normalize')
            ->with($this->callback(fn($arg) => is_array($arg)))
            ->willReturn([
                1001 => ['message' => 'Test error'],
            ]);

        $this->command->execute($input, $output);
    }
}
