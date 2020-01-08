<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\Wizard;

use Magento\MagentoCloud\Command\Wizard\Util\OutputFormatter;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Magento\Env\ReaderInterface as ConfigReader;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
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
    const NAME = 'wizard:split-db-state';

    /**
     * @var OutputFormatter
     */
    private $outputFormatter;

    /**
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
     * SplitDb constructor.
     * @param OutputFormatter $outputFormatter
     * @param ConfigReader $configReader
     * @param RelationshipConnectionFactory $connectionDataFactory
     */
    public function __construct(
        OutputFormatter $outputFormatter,
        ConfigReader $configReader,
        RelationshipConnectionFactory $connectionDataFactory
    ){
        $this->outputFormatter = $outputFormatter;
        $this->configReader = $configReader;
        $this->connectionDataFactory = $connectionDataFactory;

        parent::__construct();
    }


    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Verifies ability to split DB and whether DB was already split or not.');
    }

    /**
     * @inheritdoc
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $info = [];
        $mageConfig = $this->configReader->read();
        $envDBConfig = [];

        foreach (DbConfig::SPLIT_CONNECTIONS as $mageConnectionName) {
            if (isset($mageConfig[DbConfig::KEY_DB][DbConfig::KEY_CONNECTION][$mageConnectionName])) {
                $existedSplits[] = DbConfig::MAIN_CONNECTION_MAP[$mageConnectionName];
            }
            $envDBConfig[$mageConnectionName] = $this->connectionDataFactory
                ->create(DbConfig::MAIN_CONNECTION_MAP[$mageConnectionName]);
        }

        if (!$existedSplits) {
            if ($envDBConfig) {
                $info[] = sprintf(
                    'You may split DB using %s variable in .magento.env.yaml file',
                    DeployInterface::VAR_SPLIT_DB
                );
                $info[] = 'Once you split DB you won\'t be able to change the state back';
            } else {
                $info[] = 'DB cannot be split on this environment';
            }
        }

        foreach ($info as $msg) {
            $this->outputFormatter->writeItem($output, $msg);
        }

        $message = $existedSplits
            ? sprintf('DB is already split with type(s): %s', implode(', ', $existedSplits))
            : 'DB is not split';

        $this->outputFormatter->writeResult($output, true, $message);
    }
}
