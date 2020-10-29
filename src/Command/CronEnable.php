<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Cron\Switcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for enabling Magento cron processes
 */
class CronEnable extends Command
{
    public const NAME = 'cron:enable';

    /**
     * @var Switcher
     */
    private $cronSwitcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Switcher $cronSwitcher
     * @param LoggerInterface $logger
     */
    public function __construct(Switcher $cronSwitcher, LoggerInterface $logger)
    {
        $this->cronSwitcher = $cronSwitcher;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Enables Magento cron processes.');

        parent::configure();
    }

    /**
     * Enables Magento cron
     *
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->logger->info('Enable cron');
            $this->cronSwitcher->enable();
        } catch (GenericException $e) {
            $this->logger->critical($e->getMessage());

            throw $e;
        }
    }
}
