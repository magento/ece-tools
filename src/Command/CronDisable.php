<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Cron\Switcher;
use Magento\MagentoCloud\Step\Deploy\BackgroundProcessKill;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for disabling Magento cron processes
 */
class CronDisable extends Command
{
    public const NAME = 'cron:disable';

    /**
     * @var Switcher
     */
    private $cronSwitcher;

    /**
     * @var BackgroundProcessKill
     */
    private $backgroundProcessKill;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Switcher $cronSwitcher
     * @param BackgroundProcessKill $backgroundProcessKill
     * @param LoggerInterface $logger
     */
    public function __construct(
        Switcher $cronSwitcher,
        BackgroundProcessKill $backgroundProcessKill,
        LoggerInterface $logger
    ) {
        $this->cronSwitcher = $cronSwitcher;
        $this->backgroundProcessKill = $backgroundProcessKill;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Disable all Magento cron processes');

        parent::configure();
    }

    /**
     * Runs process which finds all running Magento cron processes and kills them
     *
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->logger->info('Disable cron');
            $this->cronSwitcher->disable();
            $this->backgroundProcessKill->execute();
        } catch (GenericException $e) {
            $this->logger->critical($e->getMessage());

            throw $e;
        }
    }
}
