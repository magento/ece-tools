<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Config\Deploy\Writer;
use Psr\Log\LoggerInterface;

/**
 * Set flag for disabling Magento cron jobs and kills all existed Magento cron processes
 */
class DisableCron implements StepInterface
{
    /**
     * @var CronStepKill
     */
    private $cronProcessKill;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CronStepKill $cronProcessKill
     * @param LoggerInterface $logger
     * @param Writer $deployConfigWriter
     */
    public function __construct(
        CronStepKill $cronProcessKill,
        LoggerInterface $logger,
        Writer $deployConfigWriter
    ) {
        $this->cronProcessKill = $cronProcessKill;
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

        $this->cronProcessKill->execute();
    }
}
