<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Build;

use Magento\MagentoCloud\Scenario\Exception\ProcessorException;
use Magento\MagentoCloud\Scenario\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command that used as part of build hook.
 * Responsible for patches applying, validating configuration, preparing the codebase, etc.
 *
 * @api
 */
class Generate extends Command
{
    const NAME = 'build:generate';

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
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Generates all necessary files for build stage.');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ProcessorException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->processor->execute([
            'scenario/build/generate.xml'
        ]);
    }
}
