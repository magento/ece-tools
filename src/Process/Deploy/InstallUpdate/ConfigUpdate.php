<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate;

use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Updates application configs.
 */
class ConfigUpdate implements ProcessInterface
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
     * @param LoggerInterface $logger
     * @param ProcessInterface $process
     */
    public function __construct(
        LoggerInterface $logger,
        ProcessInterface $process
    ) {
        $this->logger = $logger;
        $this->process = $process;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Updating configuration from environment variables.');
        $this->process->execute();
    }
}
