<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\PreDeploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\EnvironmentDataInterface;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

/**
 * Cleans static content.
 */
class CleanStaticContent implements StepInterface
{
    /**
     * @var Environment
     */
    private $env;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var EnvironmentDataInterface
     */
    private $environmentData;

    /**
     * @param LoggerInterface $logger
     * @param Environment $env
     * @param File $file
     * @param DirectoryList $directoryList
     * @param FlagManager $flagManager
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $env,
        File $file,
        DirectoryList $directoryList,
        FlagManager $flagManager,
        DeployInterface $stageConfig,
        EnvironmentDataInterface $environmentData
    ) {
        $this->logger = $logger;
        $this->env = $env;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->flagManager = $flagManager;
        $this->stageConfig = $stageConfig;
        $this->environmentData = $environmentData;
    }

    /**
     * Clean static files if static content deploy was performed during build phase.
     *
     * {@inheritdoc}
     */
    public function execute(): void
    {
        try {
            if (!$this->flagManager->exists(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD)
                || !$this->stageConfig->get(DeployInterface::VAR_CLEAN_STATIC_FILES)
                || !$this->environmentData->hasMount(EnvironmentDataInterface::MOUNT_PUB_STATIC)
            ) {
                return;
            }

            $this->logger->info('Static content deployment was performed during build hook, cleaning old content.');
            $magentoRoot = $this->directoryList->getMagentoRoot();
            $this->logger->info('Clearing pub/static');
            $this->file->backgroundClearDirectory($magentoRoot . '/pub/static');
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_SCD_CLEAN_FAILED, $e);
        } catch (GenericException $e) {
            throw new StepException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
