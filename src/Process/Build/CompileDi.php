<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Stage\BuildInterface;

/**
 * @inheritdoc
 */
class CompileDi implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param MagentoShell $magentoShell
     * @param BuildInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        MagentoShell $magentoShell,
        BuildInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->magentoShell = $magentoShell;
        $this->stageConfig = $stageConfig;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function execute()
    {
        $this->logger->notice('Running DI compilation');

        try {
            $this->magentoShell->execute(
                'setup:di:compile',
                array_filter([
                    $this->stageConfig->get(BuildInterface::VAR_VERBOSE_COMMANDS)
                ])
            );
        } catch (ShellException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
        $this->logger->notice('End of running DI compilation');
    }
}
