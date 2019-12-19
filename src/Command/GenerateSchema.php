<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Config\Schema\FormatterInterface;
use Magento\MagentoCloud\Config\Schema\Generator;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\SystemList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var FormatterInterface
     */
    private $formatter;

    /**
     * @var File
     */
    private $file;

    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @param FormatterInterface $formatter
     * @param Generator $generator
     * @param File $file
     * @param SystemList $systemList
     */
    public function __construct(
        FormatterInterface $formatter,
        Generator $generator,
        File $file,
        SystemList $systemList
    ) {
        $this->formatter = $formatter;
        $this->generator = $generator;
        $this->file = $file;
        $this->systemList = $systemList;

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
        $data = $this->generator->generate();
        $text = $this->formatter->format($data);

        $this->file->filePutContents(
            $this->systemList->getMagentoRoot() . '/.magento.env.yaml.md',
            $text
        );
    }

}
