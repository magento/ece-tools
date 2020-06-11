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
use Symfony\Component\Console\Input\InputArgument;
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
            ->setHidden(true)
            ->setDescription('Generate schema.error.yaml file')
            ->addArgument(
                'doc-error-path',
                InputArgument::OPTIONAL,
                'Path to documentation md file',
                'https://raw.githubusercontent.com/magento/devdocs/Cloud-Docker-1.1.0/src/cloud/reference/error-codes.md'
            )->addArgument(
                'doc-error-suggestion-path',
                InputArgument::OPTIONAL,
                'Path to suggestion md file',
                'https://raw.githubusercontent.com/magento/devdocs/Cloud-Docker-1.1.0/src/_data/cloud-error-messages.yml'
            );

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \Magento\MagentoCloud\Filesystem\FileSystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pathToErrorDoc = $input->getArgument('doc-error-path');
        $pathToErrorSuggestionDoc = $input->getArgument('doc-error-suggestion-path');

        $errors = $this->getErrorsFromDoc(file_get_contents($pathToErrorDoc));
        $suggestions = $this->getSuggestionFromDoc(file_get_contents($pathToErrorSuggestionDoc));

        foreach ($errors as &$errorData) {
            unset($errorData['code']);

            if (empty($errorData['step'])) {
                unset($errorData['step']);
            }

            if (!empty($errorData['suggestion'])
                && preg_match('/\.(?P<errorCode>\w+)}}$/', $errorData['suggestion'], $matches)
            ) {
                if (isset($suggestions[$matches['errorCode']])) {
                    $errorData['suggestion'] = $suggestions[$matches['errorCode']];
                } else {
                    $errorData['suggestion'] = '';
                }
            }

            $errorData['type'] = 'critical';
        }

        $otherErrors = Yaml::parse(
            $this->file->fileGetContents(__DIR__ . '/GenerateSchemaError/schema.error.base.yaml'),
            Yaml::PARSE_CONSTANT
        );

        $errors = array_replace_recursive($errors, $otherErrors);

        $this->file->filePutContents($this->fileList->getErrorSchema(), Yaml::dump($errors));

        return;
    }

    /**
     * Fetches list of errors from the documentation
     *
     * @param string $documentation
     * @return array
     */
    private function getErrorsFromDoc(string $documentation): array
    {
        $result = [];
        foreach (explode(PHP_EOL, $documentation) as $row) {
            if (substr($row, 0, 2) === '##') {
                $stage = strtolower(str_replace(' stage', '', substr($row, 3)));
            }
            if (!empty($stage) && substr_count($row, '|') == 5) {
                $errorData = array_map('trim', explode('|', trim($row, '|')));

                if (in_array(substr($errorData[0], 0, 3), ['---', 'Err'])) {
                    continue;
                }

                $errorData[] = $stage;
                $result[$errorData[0]] = array_combine(['code', 'step', 'title', 'suggestion', 'stage'], $errorData);
            }
        }

        return $result;
    }

    /**
     * Fetches suggestion for ece-tools error
     *
     * @param string $documentation
     * @return array
     */
    private function getSuggestionFromDoc(string $documentation): array
    {
        $result = [];
        foreach (explode(PHP_EOL, $documentation) as $row) {
            if (strpos($row, '#') === 0 || empty($row)) {
                continue;
            }

            if (preg_match('/^(?P<code>\w+):\s(?P<suggestion>.*)/', $row, $matches)) {
                $code = $matches['code'];
                $result[$code] = trim($matches['suggestion'], ' "');
            } elseif (isset($code)) {
                $result[$code] .= PHP_EOL . $row;
            }
        }

        return $result;
    }
}
