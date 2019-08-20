<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Docker;

use Magento\MagentoCloud\Command\Docker\Build\Writer;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\RepositoryFactory;
use Magento\MagentoCloud\Docker\Compose\DeveloperCompose;
use Magento\MagentoCloud\Docker\ComposeFactory;
use Magento\MagentoCloud\Docker\Config\Dist\Generator;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Docker\Service\Config;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Service\ServiceMismatchException;
use Magento\MagentoCloud\Service\Validator;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
    const OPTION_NODE = 'node';
    const OPTION_MODE = 'mode';
    const OPTION_SYNC_ENGINE = 'sync-engine';

    /**
     * Option key to service name map.
     *
     * @var array
     */
    private static $optionsMap = [
        self::OPTION_PHP => ServiceInterface::NAME_PHP,
        self::OPTION_DB => ServiceInterface::NAME_DB,
        self::OPTION_NGINX => ServiceInterface::NAME_NGINX,
        self::OPTION_REDIS => ServiceInterface::NAME_REDIS,
        self::OPTION_ES => ServiceInterface::NAME_ELASTICSEARCH,
        self::OPTION_NODE => ServiceInterface::NAME_NODE,
        self::OPTION_RABBIT_MQ => ServiceInterface::NAME_RABBITMQ,
    ];

    /**
     * @var ComposeFactory
     */
    private $composeFactory;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var RepositoryFactory
     */
    private $configFactory;

    /**
     * @var Config
     */
    private $serviceConfig;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var Generator
     */
    private $distGenerator;

    /**
     * @param ComposeFactory $composeFactory
     * @param Environment $environment
     * @param RepositoryFactory $configFactory
     * @param Config $serviceConfig
     * @param Validator $versionValidator
     * @param Writer $writer
     * @param Generator $distGenerator
     */
    public function __construct(
        ComposeFactory $composeFactory,
        Environment $environment,
        RepositoryFactory $configFactory,
        Config $serviceConfig,
        Validator $versionValidator,
        Writer $writer,
        Generator $distGenerator
    ) {
        $this->composeFactory = $composeFactory;
        $this->environment = $environment;
        $this->configFactory = $configFactory;
        $this->serviceConfig = $serviceConfig;
        $this->validator = $versionValidator;
        $this->writer = $writer;
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
                sprintf(
                    'Mode of environment (%s)',
                    implode(
                        ', ',
                        [
                            ComposeFactory::COMPOSE_DEVELOPER,
                            ComposeFactory::COMPOSE_PRODUCTION,
                            ComposeFactory::COMPOSE_FUNCTIONAL,
                        ]
                    )
                ),
                ComposeFactory::COMPOSE_PRODUCTION
            )->addOption(
                self::OPTION_SYNC_ENGINE,
                null,
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'File sync engine. Works only with developer mode. Available: (%s)',
                    implode(', ', DeveloperCompose::SYNC_ENGINES_LIST)
                ),
                DeveloperCompose::SYNC_ENGINE_DOCKER_SYNC
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConfigurationMismatchException
     * @throws FileSystemException
     * @throws UndefinedPackageException
     * @throws ServiceMismatchException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption(self::OPTION_MODE);
        $syncEngine = $input->getOption(self::OPTION_SYNC_ENGINE);

        $compose = $this->composeFactory->create($type);
        $config = $this->configFactory->create();

        if (ComposeFactory::COMPOSE_DEVELOPER === $type
            && !in_array($syncEngine, DeveloperCompose::SYNC_ENGINES_LIST, true)
        ) {
            throw new ConfigurationMismatchException(sprintf(
                "File sync engine '%s' is not supported. Available: %s",
                $syncEngine,
                implode(', ', DeveloperCompose::SYNC_ENGINES_LIST)
            ));
        }

        array_walk(self::$optionsMap, static function ($key, $option) use ($config, $input) {
            if ($value = $input->getOption($option)) {
                $config->set($key, $value);
            }
        });

        $config->set(DeveloperCompose::SYNC_ENGINE, $syncEngine);

        if (in_array(
            $input->getOption(self::OPTION_MODE),
            [ComposeFactory::COMPOSE_DEVELOPER, ComposeFactory::COMPOSE_PRODUCTION],
            false
        )) {
            $versionList = $this->serviceConfig->getAllServiceVersions($config);
            $errorList = $this->validator->validateVersions($versionList);

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'There are some service versions which are not supported'
                . ' by current Magento version:' . "\n" . implode("\n", $errorList) . "\n"
                . 'Do you want to continue?[y/N]',
                false
            );

            if ($errorList && !$helper->ask($input, $output, $question) && $input->isInteractive()) {
                return 1;
            }

            $this->distGenerator->generate();
        }

        $this->writer->write($compose, $config);
        $output->writeln('<info>Configuration was built.</info>');

        return 0;
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return !$this->environment->isMasterBranch();
    }
}
