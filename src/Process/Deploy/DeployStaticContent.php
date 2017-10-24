<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\StaticContentCleaner;
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
     * @var StaticContentCleaner
     */
    private $staticContentCleaner;

    /**
     * @param ProcessInterface $process
     * @param Environment $environment
     * @param LoggerInterface $logger
     * @param StaticContentCleaner $staticContentCleaner
     */
    public function __construct(
        ProcessInterface $process,
        Environment $environment,
        LoggerInterface $logger,
        StaticContentCleaner $staticContentCleaner
    ) {
        $this->process = $process;
        $this->environment = $environment;
        $this->logger = $logger;
        $this->staticContentCleaner = $staticContentCleaner;
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
            return;
        }

        // Clear old static content if necessary
        if ($this->environment->doCleanStaticFiles()) {
            $this->staticContentCleaner->cleanPubStatic();
            $this->staticContentCleaner->cleanViewPreprocessed();
        }

        $this->logger->info('Generating fresh static content');
        $this->process->execute();
    }
}
