<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
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

        $output = $this->connection->listTables();

        if (!is_array($output) || count($output) <= 1) {
            return false;
        }

        if (!in_array('core_config_data', $output) || !in_array('setup_module', $output)) {
            throw new \Exception('Missing either core_config_data or setup_module table', 5);
        }

        try {
            $data = $this->getConfig();
            if (isset($data['install']['date'])) {
                $this->logger->info('Magento was installed on ' . $data['install']['date']);
            }
        } catch (FileSystemException $e) {
            $this->write(['install' => ['date' => date('r')]]);
        }

        return true;
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

    /**
     * Returns environment configuration.
     *
     * @return array
     * @throws FileSystemException If configuration file not found
     */
    public function getConfig()
    {
        $configPath = $this->getConfigFilePath();
        if (!$this->file->isExists($configPath)) {
            throw new FileSystemException(
                sprintf('Configuration file: %s not exists', $configPath)
            );
        }

        return include $configPath;
    }

    /**
     * Updates existence configuration.
     *
     * @param array $config
     */
    public function update(array $config)
    {
        try {
            $oldConfig = $this->getConfig();
        } catch (FileSystemException $e) {
            $oldConfig = [];
        }

        $updatedConfig = array_replace_recursive($oldConfig, $config);
        $updatedConfig = '<?php' . PHP_EOL . 'return ' . var_export($updatedConfig, true) . ';';

        $this->file->filePutContents($this->getConfigFilePath(), $updatedConfig);
    }

    /**
     * Writes given configuration to file.
     *
     * @param array $config
     */
    public function write(array $config)
    {
        $updatedConfig = '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';';

        $this->file->filePutContents($this->getConfigFilePath(), $updatedConfig);
    }
}
