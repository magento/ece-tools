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
    private $cronStepKill;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CronStepKill $cronStepKill
     * @param LoggerInterface $logger
     * @param Writer $deployConfigWriter
     */
    public function __construct(
        CronStepKill $cronStepKill,
        LoggerInterface $logger,
        Writer $deployConfigWriter
    ) {
        $this->cronStepKill = $cronStepKill;
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

        $this->cronStepKill->execute();
    }
}
