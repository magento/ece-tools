<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellFactory;

/**
 * Cleans all cache by tags.
 */
class CleanCache implements ProcessInterface
{
    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param ShellFactory $shellFactory
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        ShellFactory $shellFactory,
        DeployInterface $stageConfig
    ) {
        $this->magentoShell = $shellFactory->createMagento();
        $this->stageConfig = $stageConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->magentoShell->execute(
                'cache:flush',
                [$this->stageConfig->get(PostDeployInterface::VAR_VERBOSE_COMMANDS)]
            );
        } catch (ShellException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
