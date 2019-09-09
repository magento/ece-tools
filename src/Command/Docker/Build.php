<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Docker;

use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Builds Docker configuration for Magento project.
 *
 * @codeCoverageIgnore
 */
class Build extends Command
{
    const NAME = 'docker:build';

    const OPTION_PHP = 'php';
    const OPTION_NGINX = 'nginx';
    const OPTION_DB = 'db';
    const OPTION_REDIS = 'redis';
    const OPTION_ES = 'es';
    const OPTION_RABBIT_MQ = 'rmq';
    const OPTION_NODE = 'node';
    const OPTION_MODE = 'mode';
    const OPTION_SYNC_ENGINE = 'sync-engine';

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param ShellInterface $shell
     */
    public function __construct(
        ShellInterface $shell
    ) {
        $this->shell = $shell;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('(deprecated) Build docker configuration')
            ->addOption(
                self::OPTION_PHP,
                null,
                InputOption::VALUE_REQUIRED,
                'PHP version'
            )->addOption(
                self::OPTION_NGINX,
                null,
                InputOption::VALUE_REQUIRED,
                'Nginx version'
            )->addOption(
                self::OPTION_DB,
                null,
                InputOption::VALUE_REQUIRED,
                'DB version'
            )->addOption(
                self::OPTION_REDIS,
                null,
                InputOption::VALUE_REQUIRED,
                'Redis version'
            )->addOption(
                self::OPTION_ES,
                null,
                InputOption::VALUE_REQUIRED,
                'Elasticsearch version'
            )->addOption(
                self::OPTION_RABBIT_MQ,
                null,
                InputOption::VALUE_REQUIRED,
                'RabbitMQ version'
            )->addOption(
                self::OPTION_NODE,
                null,
                InputOption::VALUE_REQUIRED,
                'Node.js version'
            )->addOption(
                self::OPTION_MODE,
                'm',
                InputOption::VALUE_REQUIRED,
                'Mode of environment'
            )->addOption(
                self::OPTION_SYNC_ENGINE,
                null,
                InputOption::VALUE_REQUIRED,
                'File sync engine. Works only with developer mode'
            );

        parent::configure();
    }

    /**
     * {@inheritDoc}
     *
     * @throws ShellException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<error>This command is deprecated.</error>');

        $command = str_replace(self::NAME, 'build:compose', (string)$input);
        $process = $this->shell->execute('./vendor/bin/ece-docker ' . $command);

        $output->writeln($process->getOutput());

        return $process->getExitCode();
    }
}
