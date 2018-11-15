<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Validator\GlobalStage\ScdOnBuild;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Filesystem\Flag\Manager as FlagManager;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DeployStaticContent implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var ScdOnBuild
     */
    private $scdOnBuild;

    /**
     * @param LoggerInterface $logger
     * @param ProcessInterface $process
     * @param FlagManager $flagManager
     * @param ScdOnBuild $scdOnBuild
     */
    public function __construct(
        LoggerInterface $logger,
        ProcessInterface $process,
        FlagManager $flagManager,
        ScdOnBuild $scdOnBuild
    ) {
        $this->logger = $logger;
        $this->process = $process;
        $this->flagManager = $flagManager;
        $this->scdOnBuild = $scdOnBuild;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->flagManager->delete(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);

        $result = $this->scdOnBuild->validate();

        if ($result instanceof Error) {
            $this->logger->notice('Skipping static content deploy: ' . $result->getError());

            return;
        }

        $this->logger->notice('Generating fresh static content');
        $this->process->execute();
        $this->flagManager->set(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
        $this->logger->notice('End of generating fresh static content');
    }
}
