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
     * CSS style which appends in the bottom of the file
     */
    private const FOOTER_SCRIPTS = <<<EOT

<!--Custom css-->

<!--
  This is a style declaration so that first column does not wrap
-->

<style>
table.error-table td:nth-child(1) {
  width: 100px;
}
table.error-table td:nth-child(2) {
  width: 200px;
}
</style>
EOT;

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
        $result = '<!-Note: The error code tables in this file are auto-generated from source code. ' .
            'To request changes to error code descriptions or suggestions, ' .
            'submit a GitHub issue to the magento/ece-tools repository.->';

        foreach ($errors as $type => $typeErrors) {
            $result .= sprintf("\n### %s Errors\n", ucfirst($type));

            foreach ($typeErrors as $stage => $stageErrors) {
                $result .= sprintf("\n### %s%s\n", ucfirst($stage), $stage === 'general' ? '' : ' stage');

                $table = "\n{:.error-table}\n";
                $table .= sprintf(
                    "| Error code | %s step | Error description (Title) | Suggested action |\n",
                    ucfirst($stage)
                );
                $table .= "| - | - | - | - |\n";
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

        return $result . self::FOOTER_SCRIPTS;
    }
}
