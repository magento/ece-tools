<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Module;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class RefreshModules implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Module
     */
    private $config;

    /**
     * @param LoggerInterface $logger
     * @param Module $config
     */
    public function __construct(
        LoggerInterface $logger,
        Module $config
    ) {
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException
     */
    public function execute()
    {
        $this->logger->info('Reconciling installed modules with shared config.');
        $this->config->refresh();
    }
}
