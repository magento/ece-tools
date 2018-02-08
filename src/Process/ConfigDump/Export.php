<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\ConfigDump;

use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * @inheritdoc
 */
class Export implements ProcessInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var File $file
     */
    private $file;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @param ShellInterface $shell
     * @param File $file
     * @param Reader $reader
     * @param Writer $writer
     */
    public function __construct(
        ShellInterface $shell,
        File $file,
        Reader $reader,
        Writer $writer
    ) {
        $this->shell = $shell;
        $this->file = $file;
        $this->reader = $reader;
        $this->writer = $writer;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function execute()
    {
        $envConfig = $this->reader->read();

        try {
            $this->shell->execute('php ./bin/magento app:config:dump');
        } finally {
            $this->writer->create($envConfig);
        }
    }
}
