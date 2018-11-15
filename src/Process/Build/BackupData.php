<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;

/**
 * Copies the data to the ./init/ directory
 *
 * {@inheritdoc}
 */
class BackupData implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProcessInterface
     */
    private $processes;

    /**
     * @param LoggerInterface $logger
     * @param ProcessInterface $processes
     */
    public function __construct(LoggerInterface $logger, ProcessInterface $processes)
    {
        $this->logger = $logger;
        $this->processes = $processes;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->notice('Copying data to the ./init directory');
        $this->processes->execute();
        $this->logger->notice('End of copying data to the ./init directory');
    }
}
