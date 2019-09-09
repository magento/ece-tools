<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Psr\Log\LoggerInterface;

/**
 * Set flag for disabling Magento cron jobs and kills all existed Magento cron processes
 */
class DisableCron implements ProcessInterface
{
    /**
     * @var BackgroundProcessKill
     */
    private $backgroundProcessKill;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param BackgroundProcessKill $backgroundProcessKill
     * @param LoggerInterface $logger
     * @param Writer $deployConfigWriter
     */
    public function __construct(
        BackgroundProcessKill $backgroundProcessKill,
        LoggerInterface $logger,
        Writer $deployConfigWriter
    ) {
        $this->backgroundProcessKill = $backgroundProcessKill;
        $this->logger = $logger;
        $this->writer = $deployConfigWriter;
    }

    /**
     * Process set Magento flag for disabling running cron jobs
     * and kill all existed Magento cron processes.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->logger->info('Disable cron');
        $this->writer->update(['cron' => ['enabled' => 0]]);

        $this->backgroundProcessKill->execute();
    }
}
