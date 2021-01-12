<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Wizard;

use Magento\MagentoCloud\Cli;
use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Step\Deploy\SplitDbConnection;
use Magento\MagentoCloud\DB\Data\RelationshipConnectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Verifies whether DB was split or not
 *
 * @api
 */
class SplitDbState extends Command
{
    public const NAME = 'wizard:split-db-state';

    /**
     * Console output formatter
     *
     * @var OutputFormatter
     */
    private $outputFormatter;

    /**
     * Reader of Magento environment configuration file
     *
     * @var ConfigReader
     */
    private $configReader;

    /**
     * Factory for creation database configurations
     *
     * @var RelationshipConnectionFactory
     */
    private $connectionDataFactory;

    /**
     * @param OutputFormatter $outputFormatter
     * @param ConfigReader $configReader
     * @param RelationshipConnectionFactory $connectionDataFactory
     */
    public function __construct(
        OutputFormatter $outputFormatter,
        ConfigReader $configReader,
        RelationshipConnectionFactory $connectionDataFactory
    ) {
        $this->outputFormatter = $outputFormatter;
        $this->configReader = $configReader;
        $this->connectionDataFactory = $connectionDataFactory;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Verifies ability to split DB and whether DB was already split or not.');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $info = [];
        $mageConfig = $this->configReader->read();
        $envDBConfig = [];
        $existedSplits = [];

        foreach (DbConfig::SPLIT_CONNECTIONS as $mageConnectionName) {
            if (isset($mageConfig[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION][$mageConnectionName])) {
                $existedSplits[] = SplitDbConnection::SPLIT_CONNECTION_MAP[$mageConnectionName];
            }
            $connection = $this->connectionDataFactory->create(DbConfig::MAIN_CONNECTION_MAP[$mageConnectionName]);
            if ($connection->getHost()) {
                $envDBConfig[$mageConnectionName] = $connection;
            }
        }

        if (!$existedSplits) {
            if ($envDBConfig) {
                $info[] = sprintf(
                    'You may split DB using %s variable in .magento.env.yaml file',
                    DeployInterface::VAR_SPLIT_DB
                );
            } else {
                $info[] = 'DB cannot be split on this environment';
            }
        }

        $message = $existedSplits
            ? sprintf('DB is already split with type(s): %s', implode(', ', $existedSplits))
            : 'DB is not split';

        $this->outputFormatter->writeResult($output, true, $message);

        foreach ($info as $msg) {
            $this->outputFormatter->writeItem($output, $msg);
        }

        return Cli::SUCCESS;
    }
}
