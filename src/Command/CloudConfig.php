<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Process\CloudConfig as CloudConfigProcess;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to friendly display the encoded cloud configuration environment variables
 */
class CloudConfig extends Command
{
    const NAME = 'cloud:config';

    const RELATIONSHIPS = 'services';
    const ROUTES = 'routes';
    const VARIABLES = 'variables';

    /**
     * Allowed environment variable options
     * @var array
     */
    private $allowedVariables = [
        self::RELATIONSHIPS,
        self::ROUTES,
        self::VARIABLES,
    ];
    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ProcessInterface $process
     * @param LoggerInterface $logger
     */
    public function __construct(
        CloudConfigProcess $process,
        LoggerInterface $logger
    ) {
        $this->process = $process;
        $this->logger = $logger;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(static::NAME)
            ->setDescription('Display encoded cloud configuration environment variables')
            ->addArgument(
                'variable',
                InputArgument::IS_ARRAY,
                "Environment variables to display, possible options: services, routes and/or variables"
            );

        parent::configure();
    }

    /**
     * Runs process to display the encoded environment variables
     *
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $printVariables = $input->getArgument('variable');
        if (!empty($printVariables)
            && !empty($unknownVariables = array_diff($printVariables, $this->allowedVariables))
        ) {
            $output->writeln('<error>Unknown variable(s): ' . implode(',', $unknownVariables) . '</error>');
        }
        $this->printVariables($output, $printVariables);
    }

    /**
     * @param OutputInterface $output
     * @param array $vars
     */
    protected function printVariables(OutputInterface $output, array $vars = [])
    {
        if (in_array(self::RELATIONSHIPS, $vars) || empty($vars)) {
            $this->process->printRelationships($output);
        }
        if (in_array(self::ROUTES, $vars) || empty($vars)) {
            $this->process->printRoutes($output);
        }
        if (in_array(self::VARIABLES, $vars) || empty($vars)) {
            $this->process->printVariables($output);
        }
    }
}
