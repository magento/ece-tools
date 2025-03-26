<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\Scenario\Exception\ProcessorException;
use Magento\MagentoCloud\Scenario\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Execute given scenarios.
 *
 * @api
 */
class RunCommand extends Command
{
    public const NAME = 'run';
    public const ARG_SCENARIO = 'scenario';

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @param Processor $processor
     */
    public function __construct(Processor $processor)
    {
        $this->processor = $processor;

        parent::__construct(self::NAME);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Execute scenario(s).')
            ->addArgument(
                self::ARG_SCENARIO,
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Scenario(s)'
            );

        parent::configure();
    }

    /**
     * {@inheritDoc}
     *
     * @throws ProcessorException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processor->execute(
            (array)$input->getArgument(self::ARG_SCENARIO)
        );

        return Cli::SUCCESS;
    }
}
