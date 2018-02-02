<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\ConfigDump;

use Magento\MagentoCloud\Config\Deploy\Reader;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
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
     * @var FileList
     */
    private $fileList;

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
     * @param FileList $directoryList
     * @param Reader $reader
     * @param Writer $writer
     */
    public function __construct(
        ShellInterface $shell,
        File $file,
        FileList $directoryList,
        Reader $reader,
        Writer $writer
    ) {
        $this->shell = $shell;
        $this->file = $file;
        $this->fileList = $directoryList;
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
        $configFile = $this->fileList->getConfig();

        try {
            $this->shell->execute('php ./bin/magento app:config:dump');
        } finally {
            $this->writer->create($envConfig);
        }

        if (!$this->file->isExists($configFile)) {
            throw new \Exception('Config file was not found.');
        }
    }
}
