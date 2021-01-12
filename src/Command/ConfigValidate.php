<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\Config\Validator\Build\StageConfig;
use Magento\MagentoCloud\Config\Validator\Result\Error;
use Magento\MagentoCloud\Config\ValidatorException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command for validating configuration in .magento.env.yaml file
 */
class ConfigValidate extends Command
{
    const NAME = 'cloud:config:validate';

    /**
     * @var StageConfig
     */
    private $stageConfig;

    /**
     * @param StageConfig $stageConfig
     */
    public function __construct(StageConfig $stageConfig)
    {
        $this->stageConfig = $stageConfig;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(static::NAME)
            ->setDescription('Validates `.magento.env.yaml` configuration file');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            if (($result = $this->stageConfig->validate()) instanceof Error) {
                $output->writeln($result->getError());
                $output->writeln($result->getSuggestion());

                return Cli::FAILURE;
            }
        } catch (ValidatorException $e) {
            $output->writeln('Command execution failed:');
            $output->writeln($e->getMessage());

            return Cli::FAILURE;
        }

        $output->writeln('The configuration file .magento.env.yaml is valid.');

        return Cli::SUCCESS;
    }
}
