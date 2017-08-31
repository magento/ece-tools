<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\BuildDirCopier;
use Psr\Log\LoggerInterface;

class RestoreWritableDirectories implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @var BuildDirCopier
     */
    private $buildDirCopier;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param BuildDirCopier $buildDirCopier
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        BuildDirCopier $buildDirCopier
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->buildDirCopier = $buildDirCopier;
    }

    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        // Restore mounted directories
        $this->logger->info('Copying writable directories back.');
        $mountedDirectories = ['app/etc', 'pub/media'];
        foreach ($mountedDirectories as $dir) {
            $this->buildDirCopier->copy($dir);
        }

        if ($this->file->isExists(Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing var/.regenerate flag');
            $this->file->deleteFile(Environment::REGENERATE_FLAG);
        }
    }
}
