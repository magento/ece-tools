<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Util\BackgroundDirectoryCleaner;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DeployStaticContent implements ProcessInterface
{
    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var BackgroundDirectoryCleaner
     */
    private $cleaner;

    /**
     * @param ProcessInterface $process
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param BackgroundDirectoryCleaner $cleaner
     */
    public function __construct(
        ProcessInterface $process,
        Environment $environment,
        LoggerInterface $logger,
        DirectoryList $directoryList,
        BackgroundDirectoryCleaner $cleaner
    ) {
        $this->process = $process;
        $this->environment = $environment;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->cleaner = $cleaner;
    }

    /**
     * This function deploys the static content.
     * Moved this from processMagentoMode() to its own function because we changed the order to have
     * processMagentoMode called before the install.  Static content deployment still needs to happen after install.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $applicationMode = $this->environment->getApplicationMode();
        $this->logger->info('Application mode is ' . $applicationMode);

        if ($applicationMode !== Environment::MAGENTO_PRODUCTION_MODE) {
            return;
        }

        /* Workaround for MAGETWO-58594: disable redis cache before running static deploy, re-enable after */
        if (!$this->environment->isDeployStaticContent()) {
            $this->logger->info("Skipping static content deployment during deployment.");
            return;
        }

        // Clear old static content if necessary
        if ($this->environment->doCleanStaticFiles()) {
            $magentoRoot = $this->directoryList->getMagentoRoot();
            $this->cleaner->backgroundClearDirectory("$magentoRoot/pub/static");
            $this->cleaner->backgroundDeleteDirectory("$magentoRoot/var/view_preprocessed");
        }

        $this->logger->info('Generating fresh static content');
        $this->process->execute();
    }
}
