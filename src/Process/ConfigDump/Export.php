<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\ConfigDump;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Export implements ProcessInterface
{
    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File $file
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param ProcessInterface $process
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ProcessInterface $process,
        ShellInterface $shell,
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->process = $process;
        $this->shell = $shell;
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function execute()
    {
        $configFile = $this->directoryList->getMagentoRoot() . '/app/etc/config.php';
        $this->shell->execute('php ./bin/magento app:config:dump');

        if (!$this->file->isExists($configFile)) {
            throw new \Exception('Config file was not found.');
        }

        $this->process->execute();
        $this->shell->execute('php ./bin/magento app:config:import -n');
    }
}
