<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Docker;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Docker\BuilderFactory;
use Magento\MagentoCloud\Docker\Config\DistGenerator;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\Config;
use Magento\MagentoCloud\Docker\Service\Version\Validator as VersionValidator;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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

    /**
     * @var BuilderFactory
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
     * @var Config
     */
    private $serviceConfig;

    /**
     * @var VersionValidator
     */
    private $versionValidator;

    /**
     * @param BuilderFactory $builderFactory
     * @param File $file
     * @param Environment $environment
     * @param RepositoryFactory $configFactory
     * @param Config $serviceConfig
     * @param VersionValidator $versionValidator
     * @param DistGenerator $distGenerator
     */
    public function __construct(
        BuilderFactory $builderFactory,
        File $file,
        Environment $environment,
        RepositoryFactory $configFactory,
        Config $serviceConfig,
        VersionValidator $versionValidator,
        DistGenerator $distGenerator
    ) {
        $this->builderFactory = $builderFactory;
        $this->file = $file;
        $this->environment = $environment;
        $this->configFactory = $configFactory;
        $this->serviceConfig = $serviceConfig;
        $this->versionValidator = $versionValidator;
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
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConfigurationMismatchException
     * @throws FileSystemException
     * @throws \Magento\MagentoCloud\Package\UndefinedPackageException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = $this->builderFactory->create(BuilderFactory::BUILDER_DEV);
        $config = $this->configFactory->create();

        $map = [
            self::OPTION_PHP => Config::KEY_PHP,
            self::OPTION_DB => Config::KEY_DB,
            self::OPTION_NGINX => Config::KEY_NGINX,
            self::OPTION_REDIS => Config::KEY_REDIS,
            self::OPTION_ES => Config::KEY_ELASTICSEARCH,
            self::OPTION_RABBIT_MQ => Config::KEY_RABBITMQ,
        ];

        array_walk($map, function ($key, $option) use ($config, $input) {
            if ($value = $input->getOption($option)) {
                $config->set($key, $value);
            }
        });

        $versionList = $this->serviceConfig->getAllServiceVersions($config);

        $unsupportedErrorMsg = $this->versionValidator->validateVersions($versionList);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('There are some service versions which are not supported'
            . ' by current Magento version:' . "\n" . implode("\n", $unsupportedErrorMsg) . "\n"
            . 'Do you want to continue?[y/N]',
            false);

        if ($unsupportedErrorMsg && !$helper->ask($input, $output, $question) && $input->isInteractive()) {
            return null;
        }

        $this->file->filePutContents(
            $builder->getConfigPath(),
            Yaml::dump($builder->build($config), 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
        );

        $this->distGenerator->generate();

        $this->getApplication()
            ->find(ConfigConvert::NAME)
            ->run($input, $output);

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
