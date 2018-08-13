<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ExecBinMagento;
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
     * @var ExecBinMagento
     */
    private $shell;

    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @param LoggerInterface $logger
     * @param ExecBinMagento $shell
     * @param BuildInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        ExecBinMagento $shell,
        BuildInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->stageConfig = $stageConfig;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function execute()
    {
        $verbosityLevel = $this->stageConfig->get(BuildInterface::VAR_VERBOSE_COMMANDS);

        $this->logger->info('Running DI compilation');
        $this->shell->execute('setup:di:compile', $verbosityLevel);
    }
}
