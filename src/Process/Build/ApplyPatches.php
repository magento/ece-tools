<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ApplyPatches implements ProcessInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Applying patches.');

        $patchScript = $this->directoryList->getMagentoRoot() . '/vendor/bin/m2-apply-patches';

        var_dump($patchScript);

        if (!$this->file->isExists($patchScript)) {
            $this->logger->warning('Package with patches was not found.');

            return;
        }

        $this->shell->execute('php ' . $patchScript);
    }
}
