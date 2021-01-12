<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\Config\Schema;
use Magento\MagentoCloud\Config\Schema\FormatterInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dist schema generator.
 *
 * @api
 */
class GenerateSchema extends Command
{
    public const NAME = 'schema:generate';

    /**
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var Schema
     */
    private $schema;

    /**
     * @param FormatterInterface $formatter
     * @param File $file
     * @param FileList $fileList
     * @param Schema $schema
     */
    public function __construct(
        FormatterInterface $formatter,
        File $file,
        FileList $fileList,
        Schema $schema
    ) {
        $this->formatter = $formatter;
        $this->file = $file;
        $this->fileList = $fileList;
        $this->schema = $schema;

        parent::__construct(self::NAME);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Generates the schema *.dist file.');

        parent::configure();
    }

    /**
     * {@inheritDoc}
     *
     * @throws FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting schema dist file generation');

        $data = $this->schema->getVariables();

        $this->file->filePutContents(
            $this->fileList->getEnvDistConfig(),
            $this->formatter->format($data) . PHP_EOL
            . $this->file->fileGetContents($this->fileList->getLogDistConfig())
        );

        $output->writeln(sprintf('Dist file was successfully generated: %s', $this->fileList->getEnvDistConfig()));

        return Cli::SUCCESS;
    }
}
