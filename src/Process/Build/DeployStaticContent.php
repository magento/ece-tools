<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var ScdOnBuild
     */
    private $scdOnBuild;

    /**
     * @var ProcessInterface[]
     */
    private $processes;

    /**
     * @param LoggerInterface $logger
     * @param FlagManager $flagManager
     * @param ScdOnBuild $scdOnBuild
     * @param ProcessInterface[] $processes
     */
    public function __construct(
        LoggerInterface $logger,
        FlagManager $flagManager,
        ScdOnBuild $scdOnBuild,
        array $processes
    ) {
        $this->logger = $logger;
        $this->flagManager = $flagManager;
        $this->scdOnBuild = $scdOnBuild;
        $this->processes = $processes;
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

        foreach ($this->processes as $process) {
            $process->execute();
        }

        $this->flagManager->set(FlagManager::FLAG_STATIC_CONTENT_DEPLOY_IN_BUILD);
        $this->logger->notice('End of generating fresh static content');
    }
}
