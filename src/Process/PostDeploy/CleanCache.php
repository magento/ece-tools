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
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * Cleans all cache by tags.
 */
class CleanCache implements ProcessInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @param ShellInterface $shell
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        ShellInterface $shell,
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
        try {
            $this->shell->execute(sprintf(
                'php ./bin/magento cache:flush --ansi --no-interaction %s',
                $this->stageConfig->get(PostDeployInterface::VAR_VERBOSE_COMMANDS)
            ));
        } catch (ShellException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
