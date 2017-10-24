<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\ConfigDump;

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
     * @param ShellInterface $shell
     * @param File $file
     * @param FileList $directoryList
     */
    public function __construct(
        ShellInterface $shell,
        File $file,
        FileList $directoryList
    ) {
        $this->shell = $shell;
        $this->file = $file;
        $this->fileList = $directoryList;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function execute()
    {
        $this->shell->execute('php ./bin/magento app:config:dump');

        $configFile = $this->fileList->getConfig();

        if (!$this->file->isExists($configFile)) {
            throw new \Exception('Config file was not found.');
        }
    }
}
