<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Illuminate\Database\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

/**
 * Class Deploy.
 */
class Deploy
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param LoggerInterface $logger
     * @param ConnectionInterface $connection
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        ConnectionInterface $connection,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * Verifies is Magento installed based on install date in env.php
     *
     * 1. from environment variables check if db exists and has tables
     * 2. check if core_config_data and setup_module tables exist
     * 3. check install date
     *
     * @return bool
     * @throws \Exception
     */
    public function isInstalled(): bool
    {
        $this->logger->info('Checking if db exists and has tables');

        /** @var \PDO $pdo */
        $pdo = $this->connection->getPdo();
        $output = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        if (!is_array($output) || count($output) <= 1) {
            return false;
        }

        if (!in_array('core_config_data', $output) || !in_array('setup_module', $output)) {
            throw new \Exception('Missing either core_config_data or setup_module table', 5);
        }

        $configPath = $this->getConfigFilePath();

        if (!$this->file->isExists($configPath)) {
            $this->updateConfig($configPath);

            return true;
        }

        $data = include $configPath;
        if (isset($data['install']['date'])) {
            $this->logger->info('Magento was installed on ' . $data['install']['date']);

            return true;
        }

        $this->updateConfig($configPath);

        return true;
    }

    private function updateConfig(string $configFile)
    {
        $config['install']['date'] = date('r');
        $updatedConfig = '<?php' . "\n" . 'return ' . var_export($config, true) . ';';
        $this->file->filePutContents($configFile, $updatedConfig);
    }

    /**
     * Return full path to environment configuration file.
     *
     * @return string The path to configuration file
     */
    public function getConfigFilePath(): string
    {
        return $this->directoryList->getMagentoRoot() . '/app/etc/env.php';
    }
}
