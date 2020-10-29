<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Util\BackgroundProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command for killing Magento cron processes
 *
 * @api
 */
class CronKill extends Command
{
    public const NAME = 'cron:kill';

    /**
     * @var BackgroundProcess
     */
    private $backgroundProcess;

    /**
     * @param BackgroundProcess $backgroundProcess
     */
    public function __construct(BackgroundProcess $backgroundProcess)
    {
        $this->backgroundProcess = $backgroundProcess;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Terminates all Magento cron processes.');

        parent::configure();
    }

    /**
     * Runs process which finds all running Magento cron processes and kills them
     *
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->backgroundProcess->kill();
    }
}
