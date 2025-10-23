<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Dev;

use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

/**
 * @codeCoverageIgnore
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
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
            ->setDescription('Generates the dist/error-codes.md file from the schema.error.yaml file.');

        parent::configure();
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $parseFlags = 0;
        if (defined(Yaml::class . '::PARSE_CONSTANT')) {
            $parseFlags |= Yaml::PARSE_CONSTANT;
        }
        if (defined(Yaml::class . '::PARSE_CUSTOM_TAGS')) {
            $parseFlags |= Yaml::PARSE_CUSTOM_TAGS;
        }
        $errors = (array) Yaml::parse(
            $this->file->fileGetContents($this->fileList->getErrorSchema()),
            $parseFlags
        );

        $errors = $this->normalizeYamlData($errors);
        $errors = $this->groupErrors($errors);
        $docs   = $this->generateDocs($errors);

        $this->file->filePutContents($this->fileList->getErrorDistConfig(), $docs);
        $output->writeln(sprintf('File %s was generated', $this->fileList->getErrorDistConfig()));

        return Cli::SUCCESS;
    }

    /**
     * Recursively normalizes Symfony YAML TaggedValue objects into PHP-native values.
     *
     * Handles the following YAML tags:
     *  - !env: resolves environment variables.
     *  - !include: parses and normalizes included YAML files.
     *  - !php/const: resolves PHP constants (e.g. !php/const:\PDO::ATTR_ERRMODE).
     *  - Other or unknown tags: recursively normalize their values.
     *
     * Ensures all YAML data is converted to scalars or arrays suitable for safe merging.
     *
     * @param mixed $data The parsed YAML data (array, scalar, or TaggedValue).
     * @return mixed The normalized data structure.
     *
     * @SuppressWarnings("PHPMD.NPathComplexity")
     * @SuppressWarnings("PHPMD.CyclomaticComplexity") Method is intentionally complex due to tag resolution logic.
     */
    private function normalizeYamlData(mixed $data): mixed
    {
        if ($data instanceof TaggedValue) {
            $tag   = $data->getTag();   // e.g. "php/const:\PDO::MYSQL_ATTR_LOCAL_INFILE"
            $value = $data->getValue();

            // Handle php/const tags (Symfony strips leading '!')
            if (str_starts_with($tag, 'php/const:')) {
                // Extract the constant name
                $constName = substr($tag, strlen('php/const:'));
                $constName = ltrim($constName, '\\');

                // Resolve the constant name to its value if defined
                $constKey = defined($constName) ? constant($constName) : $constName;

                // Handle YAML quirk where ": 1" is parsed literally
                $raw = is_string($value) ? $value : (string)$value;
                $cleanVal = str_replace([':', ' '], '', $raw);
                $constVal = is_numeric($cleanVal) ? (int)$cleanVal : $cleanVal;

                return [$constKey => $constVal];
            }

            // Handle !env
            if ($tag === 'env') {
                $envValue = getenv((string)$value);
                return $envValue !== false ? $envValue : null;
            }

            // Handle !include
            if ($tag === 'include') {
                if (file_exists((string)$value)) {
                    $included = Yaml::parseFile((string)$value);
                    return $this->normalizeYamlData($included);
                }
                return null;
            }

            // Default — recursively normalize nested tagged structures
            $normalized = $this->normalizeYamlData($value);
            return is_array($normalized) ? $normalized : [$normalized];
        }

        // Recursively normalize arrays
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->normalizeYamlData($value);
            }
        }

        return $data;
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
        $result .= "\n";

        foreach ($errors as $type => $typeErrors) {
            $result .= sprintf("\n## %s Errors\n", ucfirst($type));
            $result .= sprintf("\n%s\n", $this->getErrorTypeDescription()[$type]);

            foreach ($typeErrors as $stage => $stageErrors) {
                $result .= sprintf("\n### %s%s\n", ucfirst($stage), $stage === 'general' ? '' : ' stage');

                $table = sprintf(
                    "\n| Error code | %s step | Error description (Title) | Suggested action |\n",
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

        return $result;
    }

    /**
     * Returns an array of error types description (warning and critical)
     *
     * @return array
     */
    public function getErrorTypeDescription(): array
    {
        return [
            'critical' => 'Critical errors indicate a problem with the Commerce on cloud infrastructure project ' .
                'configuration that causes deployment failure, for example incorrect, unsupported, or missing ' .
                'configuration for required settings. Before you can deploy, you must update the configuration ' .
                'to resolve these errors.',
            'warning' => 'Warning errors indicate a problem with the Commerce on cloud infrastructure project ' .
                'configuration such as incorrect, deprecated, unsupported, or missing configuration settings for ' .
                'optional features that can affect site operation. Although a warning does not cause deployment ' .
                'failure, you should review warning messages and update the configuration to resolve them.',
        ];
    }
}
