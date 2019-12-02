<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy;

use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Config\Magento\Env\WriterInterface;
use Psr\Log\LoggerInterface;

/**
 * Set flag for disabling Magento cron jobs and kills all existed Magento cron processes
 */
class DisableCron implements StepInterface
{
    /**
     * @var BackgroundProcessKill
     */
    private $backgroundProcessKill;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param BackgroundProcessKill $backgroundProcessKill
     * @param LoggerInterface $logger
     * @param WriterInterface $deployConfigWriter
     */
    public function __construct(
        BackgroundProcessKill $backgroundProcessKill,
        LoggerInterface $logger,
        WriterInterface $deployConfigWriter
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
