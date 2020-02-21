<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Patch;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;

/**
 * Wrapper form applying required patches.
 */
class Manager
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var GlobalSection
     */
    private $globalSection;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param File $file
     * @param DirectoryList $directoryList
     * @param GlobalSection $globalSection
     * @param FileList $fileList
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        File $file,
        DirectoryList $directoryList,
        GlobalSection $globalSection,
        FileList $fileList
    ) {

        $this->logger = $logger;
        $this->shell = $shell;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->globalSection = $globalSection;
        $this->fileList = $fileList;
    }

    /**
     * Applies all needed patches.
     *
     * @throws ShellException
     */
    public function apply()
    {
        $this->file->copy(
            $this->fileList->getFrontStaticDist(),
            $this->directoryList->getMagentoRoot() . '/pub/front-static.php'
        );
        $this->logger->info('File "front-static.php" was copied');

        $this->logger->notice('Applying patches');

        $command = 'php ./vendor/bin/ece-patches apply';

        if ($this->globalSection->get(GlobalSection::VAR_DEPLOYED_MAGENTO_VERSION_FROM_GIT)) {
            $command .= ' --git-installation 1';
        }

        try {
            $this->shell->execute($command)->getOutput();
        } catch (ShellException $exception) {
            $this->logger->error($exception->getMessage());

            throw  $exception;
        }

        $this->logger->notice('End of applying patches');
    }
}
