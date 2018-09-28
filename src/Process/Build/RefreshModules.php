<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Module;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
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
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Reconciling installed modules with shared config.');

        try {
            $this->config->refresh();
        } catch (ShellException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
