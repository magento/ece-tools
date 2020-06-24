<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Dev;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GenerateSchemaError extends Command
{
    public const NAME = 'dev:generate:schema-error';

    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     *
     * @param File $file
     * @param FileList $fileList
     */
    public function __construct(File $file, FileList $fileList)
    {
        $this->file = $file;
        $this->fileList = $fileList;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(static::NAME)
            ->setDescription('Generates dist/error-codes.md file from schema.error.yaml');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errors = Yaml::parse(
            $this->file->fileGetContents($this->fileList->getErrorSchema()),
            Yaml::PARSE_CONSTANT
        );

        $errors = $this->groupErrors($errors);

        $docs = $this->generateDocs($errors);

        $this->file->filePutContents($this->fileList->getErrorDistConfig(), $docs);

        $output->writeln(sprintf('File %s was generated', $this->fileList->getErrorDistConfig()));
    }

    /**
     * Groups errors by type and stage
     *
     * @param array $errors
     * @return array
     */
    private function groupErrors(array $errors): array
    {
        $groupedErrors = [];

        foreach ($errors as $errorCode => $errorData) {
            $groupedErrors[$errorData['type']][$errorData['stage']][$errorCode] = $errorData;
        }

        return $groupedErrors;
    }

    /**
     * @param array $errors
     * @return string
     */
    private function generateDocs(array $errors): string
    {
        $result = '';

        foreach ($errors as $type => $typeErrors) {
            $result .= sprintf("\n\n## %s Errors\n\n", ucfirst($type));

            foreach ($typeErrors as $stage => $stageErrors) {
                $result .= sprintf("\n### %s%s\n\n", ucfirst($stage), $stage === 'general' ? '' : ' stage');

                $table = "{:.error-table}\n";
                $table .= sprintf(
                    "| Error code | %s step | Error description | Suggested action |\n",
                    ucfirst($stage)
                );
                foreach ($stageErrors as $errorCode => $errorData) {
                    $table .= sprintf(
                        "| %d | %s | %s | %s |\n",
                        $errorCode,
                        $errorData['step'] ?? '',
                        $errorData['title'] ?? '',
                        $errorData['suggestion'] ?? ''
                    );
                }

                $result .= $table;
            }
        }

        return $result;
    }
}
