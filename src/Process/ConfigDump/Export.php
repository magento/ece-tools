<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\ConfigDump;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Package\MagentoVersion;
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
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ShellInterface $shell
     * @param File $file
     * @param FileList $directoryList
     * @param MagentoVersion $magentoVersion;
     */
    public function __construct(
        ShellInterface $shell,
        File $file,
        FileList $directoryList,
        MagentoVersion $magentoVersion
    ) {
        $this->shell = $shell;
        $this->file = $file;
        $this->fileList = $directoryList;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function execute()
    {
        $this->shell->execute('php ./bin/magento app:config:dump');
        if ($this->magentoVersion->isGreaterOrEqual('2.2')) {
            $configFile = $this->fileList->getConfig();
        } else { // In 2.0 and 2.1, we use config.local.php instead
            $configFile = $this->fileList->getConfigLocal();
        }
        if (!$this->file->isExists($configFile)) {
            throw new \Exception('Config file was not found.');
        }
    }
}
