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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Performs post-deploy operations.
 *
 * @api
 */
class PostDeploy extends Command
{
    public const NAME = 'post-deploy';

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

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(static::NAME)
            ->setDescription('Performs after deploy operations.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ProcessorException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processor->execute([
            'scenario/post-deploy.xml'
        ]);

        return Cli::SUCCESS;
    }
}
