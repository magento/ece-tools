<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\StaticContentCleaner;
use Psr\Log\LoggerInterface;

class CleanStaticContent implements ProcessInterface
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
     * @var StaticContentCleaner
     */
    private $staticContentCleaner;

    /**
     * @param LoggerInterface $logger
     * @param Environment $env
     * @param StaticContentCleaner $staticContentCleaner
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $env,
        StaticContentCleaner $staticContentCleaner
    ) {
        $this->logger = $logger;
        $this->env = $env;
        $this->staticContentCleaner = $staticContentCleaner;
    }

    /**
     * Clean static files if static content deploy was performed during build phase.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        if (!$this->env->isStaticDeployInBuild()) {
            return;
        }

        $this->logger->info('Static content deployment was performed during build hook, cleaning old content.');
        $this->staticContentCleaner->cleanPubStatic();
        $this->staticContentCleaner->cleanViewPreprocessed();
    }
}
