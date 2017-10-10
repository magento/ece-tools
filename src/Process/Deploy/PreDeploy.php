<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Package\Manager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class PreDeploy implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Manager
     */
    private $packageManager;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param LoggerInterface $logger
     * @param ProcessInterface $process
     * @param Manager $packageManager
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        ProcessInterface $process,
        Manager $packageManager,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->process = $process;
        $this->packageManager = $packageManager;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->prepareLog();
        $this->logger->info('Starting deploy.');
        $this->logger->info('Starting pre-deploy. ' . $this->packageManager->getPrettyInfo());
        $this->process->execute();
    }


    /**
     * Prepares the deploy log for further use.
     *
     * @return void
     */
    private function prepareLog()
    {
        $magentoRoot = $this->directoryList->getMagentoRoot();
        $deployLogPath = $magentoRoot . '/' . Logger::DEPLOY_LOG_PATH;
        $buildPhaseLogPath = $magentoRoot . '/' . Logger::BACKUP_BUILD_PHASE_LOG_PATH;
        $buildPhaseLogContent = $this->file->fileGetContents($buildPhaseLogPath);

        $deployLogFileIsExists = $this->file->isExists($deployLogPath);

        if ($deployLogFileIsExists && !$this->buildLogIsApplied($deployLogPath, $buildPhaseLogContent)) {
            $this->file->filePutContents($deployLogPath, $buildPhaseLogContent, FILE_APPEND);
        } elseif (!$deployLogFileIsExists) {
            $this->file->createDirectory($magentoRoot . '/' . Logger::LOG_DIR);
            $this->file->copy($buildPhaseLogPath, $deployLogPath);
        }
    }

    /**
     * Checks if the log contains the content of the build phase.
     *
     * @param string $deployLogPath deploy log path
     * @param string $buildPhaseLogContent build log content
     * @return bool
     */
    private function buildLogIsApplied(string $deployLogPath, string $buildPhaseLogContent): bool
    {
        return false !== strpos($this->file->fileGetContents($deployLogPath), $buildPhaseLogContent);
    }
}
