<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Config\Schema\Generator;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;

/**
 * Dist schema generator
 */
class GenerateSchema extends Command
{
    public const NAME = 'schema:generate';

    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var File
     */
    private $file;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var Dumper
     */
    private $dumper;

    /**
     * @param Generator $generator
     * @param File $file
     * @param FileList $fileList
     * @param Dumper $dumper
     */
    public function __construct(Generator $generator, File $file, FileList $fileList, Dumper $dumper)
    {
        $this->generator = $generator;
        $this->file = $file;
        $this->fileList = $fileList;
        $this->dumper = $dumper;

        parent::__construct(self::NAME);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Generate the schema dist file');

        parent::configure();
    }

    /**
     * {@inheritDoc}
     *
     * @throws FileSystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $text = '';
        $newline = "\n\n";

        foreach ($this->generator->generate() as $key => $data) {
            $text .= '# ' . $key . $newline;
            $text .= $data['description'];
            $text .= $newline;

            $text .= '## Magento version' . $newline;
            $text .= '`' . $data['magento_version'] . '`' . $newline;

            if (!empty($data['stages'])) {
                $text .= '## Stages' . $newline;
                $text .= implode(', ', $data['stages']);
                $text .= $newline;
            }

            if (!empty($data['examples'])) {
                $text .= '## Examples' . $newline;

                foreach ($data['examples'] as $example) {
                    $text .= $this->wrapCode($this->dumper->dump($example, 4, 2), 'yaml');
                }
            }
        }

        $this->file->filePutContents(
            $this->fileList->getSchemaDist(),
            $text
        );
    }

    /**
     * @param string $code
     * @param string|null $lang
     * @return string
     */
    private function wrapCode(string $code, string $lang = null): string
    {
        return '```' . ($lang ?: '') . "\n" . $code . "\n" . '```' . "\n\n";
    }
}
