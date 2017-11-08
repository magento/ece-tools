<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\PreDeploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\BuildDirCopier;
use Magento\MagentoCloud\Util\StaticContentCleaner;
use Magento\MagentoCloud\Util\StaticContentSymlink;
use Psr\Log\LoggerInterface;

class ProcessStaticContent implements ProcessInterface
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
     * @var BuildDirCopier
     */
    private $buildDirCopier;

    /**
     * @var StaticContentSymlink
     */
    private $staticContentSymlink;

    /**
     * @param LoggerInterface $logger
     * @param Environment $env
     * @param StaticContentCleaner $staticContentCleaner
     * @param StaticContentSymlink $staticContentSymlink
     * @param BuildDirCopier $buildDirCopier
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $env,
        StaticContentCleaner $staticContentCleaner,
        StaticContentSymlink $staticContentSymlink,
        BuildDirCopier $buildDirCopier
    ) {
        $this->logger = $logger;
        $this->env = $env;
        $this->staticContentCleaner = $staticContentCleaner;
        $this->buildDirCopier = $buildDirCopier;
        $this->staticContentSymlink = $staticContentSymlink;
    }

    /**
     * Handle case where static content is deployed during build hook:
     *  1. set a flag to be read by magento-cloud:deploy
     *  2. Either copy or symlink files from init/ directory, depending on strategy
     *
     * @inheritdoc
     */
    public function execute()
    {
//        if (!$this->env->isStaticDeployInBuild()) {
//            return;
//        }

//        $this->logger->info('Static content deployment was performed during build hook');
//        $this->staticContentCleaner->cleanPubStatic();
//
//        if ($this->env->isStaticContentSymlinkOn()) {
//            $this->logger->info('Symlinking static content from pub/static to init/pub/static');
//            $this->staticContentSymlink->create();
//        } else {
//            $this->logger->info('Copying static content from init/pub/static to pub/static');
//            $this->buildDirCopier->copy('pub/static');
//        }
    }
}
