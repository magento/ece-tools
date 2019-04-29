<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Docker;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Docker\ComposeManagerFactory;
use Magento\MagentoCloud\Docker\ComposeManagerInterface;
use Magento\MagentoCloud\Docker\Config\DistGenerator;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds Docker configuration for Magento project.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
    const OPTION_MODE = 'mode';

    /**
     * @var ComposeManagerFactory
     */
    private $builderFactory;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var RepositoryFactory
     */
    private $configFactory;

    /**
     * @var DistGenerator
     */
    private $distGenerator;

    /**
     * @param ComposeManagerFactory $builderFactory
     * @param File $file
     * @param Environment $environment
     * @param RepositoryFactory $configFactory
     * @param DistGenerator $distGenerator
     */
    public function __construct(
        ComposeManagerFactory $builderFactory,
        File $file,
        Environment $environment,
        RepositoryFactory $configFactory,
        DistGenerator $distGenerator
    ) {
        $this->builderFactory = $builderFactory;
        $this->file = $file;
        $this->environment = $environment;
        $this->configFactory = $configFactory;
        $this->distGenerator = $distGenerator;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Build docker configuration')
            ->addOption(
                self::OPTION_PHP,
                null,
                InputOption::VALUE_OPTIONAL,
                'PHP version'
            )->addOption(
                self::OPTION_NGINX,
                null,
                InputOption::VALUE_OPTIONAL,
                'Nginx version'
            )->addOption(
                self::OPTION_DB,
                null,
                InputOption::VALUE_OPTIONAL,
                'DB version'
            )->addOption(
                self::OPTION_REDIS,
                null,
                InputOption::VALUE_OPTIONAL,
                'Redis version'
            )->addOption(
                self::OPTION_ES,
                null,
                InputOption::VALUE_OPTIONAL,
                'Elasticsearch version'
            )->addOption(
                self::OPTION_RABBIT_MQ,
                null,
                InputOption::VALUE_OPTIONAL,
                'RabbitMQ version'
            )->addOption(
                self::OPTION_MODE,
                'm',
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Mode of environment (%s)',
                    implode(', ', [ComposeManagerFactory::COMPOSE_DEVELOPER, ComposeManagerFactory::COMPOSE_PRODUCTION])
                ),
                ComposeManagerFactory::COMPOSE_PRODUCTION
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws FileSystemException
     * @throws ConfigurationMismatchException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption(self::OPTION_MODE);

        $builder = $this->builderFactory->create($type);
        $config = $this->configFactory->create();

        $map = [
            self::OPTION_PHP => ComposeManagerInterface::PHP_VERSION,
            self::OPTION_DB => ComposeManagerInterface::DB_VERSION,
            self::OPTION_NGINX => ComposeManagerInterface::NGINX_VERSION,
            self::OPTION_REDIS => ComposeManagerInterface::REDIS_VERSION,
            self::OPTION_ES => ComposeManagerInterface::ES_VERSION,
            self::OPTION_RABBIT_MQ => ComposeManagerInterface::RABBIT_MQ_VERSION,
        ];

        array_walk($map, static function ($key, $option) use ($config, $input) {
            if ($value = $input->getOption($option)) {
                $config->set($key, $value);
            }
        });

        $this->file->filePutContents(
            $builder->getConfigPath(),
            Yaml::dump($builder->build($config), 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
        );

        $this->distGenerator->generate();

        try {
            $this->getApplication()
                ->find(ConfigConvert::NAME)
                ->run(new ArrayInput([]), $output);
        } catch (\Exception $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $output->writeln('<info>Configuration was built.</info>');
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return !$this->environment->isMasterBranch();
    }
}
