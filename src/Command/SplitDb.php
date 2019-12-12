<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command;

use Magento\MagentoCloud\Config\Deploy\Reader as ConfigReader;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Util\MaintenanceModeSwitcher;
use Symfony\Component\Console\Command\Command;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\MagentoCloud\Config\Database\MergedConfig as DbConfig;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * CLI command for splitting database
 */
class SplitDb extends Command
{
    /**
     * Command name
     */
    const NAME = 'db:split';

    /**
     * Argument name of types
     */
    const ARGUMENT_TYPES = 'types';

    /**
     * Quote type
     */
    const TYPE_QUOTE = 'quote';

    /**
     * Sales type
     */
    const TYPE_SALES = 'sales';

    /**
     * Types and connection for splitting database
     */
    const TYPE_MAP = [
        self::TYPE_QUOTE => [
            DbConfig::KEY_CONNECTION => DbConfig::CONNECTION_CHECKOUT,
            DbConfig::KEY_RESOURCE => DbConfig::RESOURCE_CHECKOUT
        ],
        self::TYPE_SALES => [
            DbConfig::KEY_CONNECTION => DbConfig::CONNECTION_SALES,
            DbConfig::KEY_RESOURCE => DbConfig::RESOURCE_SALES
        ],
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Final database and resource configurations.
     *
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * Final configuration for deploy phase
     *
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * Class for configuration merging
     *
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * Factory for creation database configurations
     *
     * @var RelationshipConnectionFactory
     */
    private $connectionDataFactory;

    /**
     * Reader for app/etc/env.php file
     *
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var MaintenanceModeSwitcher
     */
    private $maintenanceMode;

    /**
     *  * ./bin/magento shell wrapper.
     *
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @param LoggerInterface $logger
     * @param DbConfig $dbConfig
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     * @param RelationshipConnectionFactory $connectionDataFactory
     * @param ConfigReader $configReader
     * @param MaintenanceModeSwitcher $maintenanceModeSwitcher
     * @param MagentoShell $magentoShell
     */
    public function __construct(
        LoggerInterface $logger,
        DbConfig $dbConfig,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger,
        RelationshipConnectionFactory $connectionDataFactory,
        ConfigReader $configReader,
        MaintenanceModeSwitcher $maintenanceModeSwitcher,
        MagentoShell $magentoShell
    ) {
        $this->logger = $logger;
        $this->dbConfig = $dbConfig;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
        $this->connectionDataFactory = $connectionDataFactory;
        $this->configReader = $configReader;
        $this->maintenanceMode = $maintenanceModeSwitcher;
        $this->magentoShell = $magentoShell;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $types = array_keys(self::TYPE_MAP);
        $this->setName(self::NAME)
            ->setDescription(
                'Move sales and/or checkout quote related tables to a separate DB servers.'
                . ' This procedure is not allowed if you do not have prepared DBs'
            );
        $this->addArgument(
            self::ARGUMENT_TYPES,
            InputArgument::IS_ARRAY,
            sprintf(
                'Space-separated list of table types for migrating to appropriate DB or omit to apply to all'
                . ' table types (possible types: %s).',
                implode(',', $types)
            ),
            $types
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $types = $input->getArgument(self::ARGUMENT_TYPES);
            $configFormEnvFile = $this->configReader->read();
            $envDbConfig = $this->getDatabaseConfig();

            $breakExecution = false;

            foreach ($types as $type) {
                if (!isset(self::TYPE_MAP[$type])) {
                    $this->logger->error('Incorrect the argument type value: ' . $type);
                    $breakExecution = true;
                    continue;
                }
                $connectionName = self::TYPE_MAP[$type][DbConfig::KEY_CONNECTION];
                if (isset($configFormEnvFile['db']['connection'][$connectionName])) {
                    $this->logger->notice(sprintf('Database for %s has been split.', $type));
                    $breakExecution = true;
                    continue;
                }
                if (!isset($envDbConfig[DbConfig::KEY_CONNECTION][$connectionName])) {
                    $this->logger->error(sprintf(
                        'There is not connections to additional DBs for %s splitting.',
                        $type
                    ));
                    $breakExecution = true;
                }
            }

            if ($breakExecution) {
                return null;
            }

            if (!$this->maintenanceMode->isEnabled()) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    'We recommend to run this command on the project in maintenance mode.' .
                    ' For this type N and run maintenance:enable CLI command.' . PHP_EOL
                    . 'Do you want to continue without maintenance mode [y|N]?',
                    false
                );
                if (!$helper->ask($input, $output, $question) && $input->isInteractive()) {
                    return null;
                }
            }
            $useSlave = $this->stageConfig->get(DeployInterface::VAR_MYSQL_USE_SLAVE_CONNECTION);
            foreach ($types as $type) {
                $connectionName = self::TYPE_MAP[$type][DbConfig::KEY_CONNECTION];
                $splitDbConfig = $envDbConfig[DbConfig::KEY_CONNECTION][$connectionName];
                $cmd = sprintf(
                    'setup:db-schema:split-%s --host="%s" --dbname="%s" --username="%s" --password="%s"',
                    $type,
                    $splitDbConfig['host'],
                    $splitDbConfig['dbname'],
                    $splitDbConfig['username'],
                    $splitDbConfig['password']
                );
                $outputCmd = $this->magentoShell->execute($cmd)->getOutput();
                $this->logger->debug($outputCmd);
                $this->logger->info(sprintf(
                    'Quote tables were split to DB %s in %s',
                    $splitDbConfig['dbname'],
                    $splitDbConfig['host']
                ));

                if ($useSlave) {
                    $splitDbConfigSlave = $envDbConfig[DbConfig::KEY_SLAVE_CONNECTION][$connectionName];
                    $resourceName = self::TYPE_MAP[$type][DbConfig::KEY_RESOURCE];
                    $cmd = sprintf(
                        'setup:db-schema:add-slave --host="%s" --dbname="%s" --username="%s" --password="%s"'
                        . ' --connection="%s" --resource="%s"',
                        $splitDbConfigSlave['host'],
                        $splitDbConfigSlave['dbname'],
                        $splitDbConfigSlave['username'],
                        $splitDbConfigSlave['password'],
                        $connectionName,
                        $resourceName
                    );
                    $outputCmd = $this->magentoShell->execute($cmd)->getOutput();
                    $this->logger->debug($outputCmd);
                }
            }
            return null;
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @return array
     */
    private function getDatabaseConfig(): array
    {
        $envDbConfig = $this->stageConfig->get(DeployInterface::VAR_DATABASE_CONFIGURATION);
        if (!$this->configMerger->isEmpty($envDbConfig) && !$this->configMerger->isMergeRequired($envDbConfig)) {
            return $this->configMerger->clear($envDbConfig);
        }
        $config = [];
        foreach (DbConfig::CONNECTION_MAP as $key => $connections) {
            foreach ($connections as $type => $connection) {
                $connectionData = $this->connectionDataFactory->create($connection);
                if ($connectionData->getHost()) {
                    $config[$type][$key] = $this->dbConfig->getConnectionConfig(
                        $connectionData,
                        DbConfig::KEY_SLAVE_CONNECTION === $type
                    );
                }
            }
        }

        return $this->configMerger->merge($config, $envDbConfig);
    }
}
