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
     * @var Environment
     */
    private $environment;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param BuildDirCopier $buildDirCopier
     * @param Environment $environment
     */
    public function __construct(
        LoggerInterface $logger,
        File $file,
        BuildDirCopier $buildDirCopier,
        Environment $environment
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->buildDirCopier = $buildDirCopier;
        $this->environment = $environment;
    }

    /**
     * Executes the process.
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->environment->getRecoverableDirectories() as $dir) {
            $this->buildDirCopier->copy($dir);
        }

        // Restore mounted directories
        $this->logger->info('Recoverable directories were copied back.');

        if ($this->file->isExists(Environment::REGENERATE_FLAG)) {
            $this->logger->info('Removing var/.regenerate flag');
            $this->file->deleteFile(Environment::REGENERATE_FLAG);
        }
    }
}
