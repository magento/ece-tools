<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\PostDeploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Stage\PostDeployInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ExecBinMagento;

/**
 * Cleans all cache by tags.
 */
class CleanCache implements ProcessInterface
{
    /**
     * @var ExecBinMagento
     */
    private $shell;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param ExecBinMagento $shell
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        ExecBinMagento $shell,
        DeployInterface $stageConfig
    ) {
        $this->shell = $shell;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->shell->execute('cache:flush', $this->stageConfig->get(PostDeployInterface::VAR_VERBOSE_COMMANDS));
    }
}
