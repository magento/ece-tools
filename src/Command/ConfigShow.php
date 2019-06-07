<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Command\ConfigShow\Renderer as ConfigRenderer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to friendly display the encoded cloud configuration environment variables
 */
class ConfigShow extends Command
{
    const NAME = 'env:config:show';

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
     * @var ConfigRenderer
     */
    private $renderer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ConfigRenderer $renderer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ConfigRenderer $renderer,
        LoggerInterface $logger
    ) {
        $this->renderer = $renderer;
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
                'Environment variables to display, possible options: ' . implode(',', $this->allowedVariables),
                []
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
        $variables = $input->getArgument('variable') ?? [];
        $unknownVariables = array_diff($variables, $this->allowedVariables);

        if ($variables && $unknownVariables) {
            $output->writeln('<error>Unknown variable(s): ' . implode(',', $unknownVariables) . '</error>');
        }
        $this->printVariables($output, $variables);
    }

    /**
     * @param OutputInterface $output
     * @param array $variables
     */
    protected function printVariables(OutputInterface $output, array $variables = [])
    {
        if (in_array(self::RELATIONSHIPS, $variables) || empty($variables)) {
            $this->renderer->printRelationships($output);
        }
        if (in_array(self::ROUTES, $variables) || empty($variables)) {
            $this->renderer->printRoutes($output);
        }
        if (in_array(self::VARIABLES, $variables) || empty($variables)) {
            $this->renderer->printVariables($output);
        }
    }
}
