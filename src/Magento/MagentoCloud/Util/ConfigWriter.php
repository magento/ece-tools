<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;

class ConfigWriter
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        File $file,
        DirectoryList $directoryList
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * Writes given configuration to file.
     *
     * @param array $config
     * @param string $configPath
     */
    public function write(array $config, $configPath = null)
    {
        if ($configPath === null) {
            $configPath = $this->getConfigPath();
        }

        $updatedConfig = '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';';

        $this->file->filePutContents($configPath, $updatedConfig);
    }

    /**
     * Updates existence configuration.
     *
     * @param array $config
     * @param string $configPath
     */
    public function update(array $config, $configPath = null)
    {
        if ($configPath === null) {
            $configPath = $this->getConfigPath();
        }

        $oldConfig = include $configPath;

        $updatedConfig = array_replace_recursive($oldConfig, $config);
        $updatedConfig = '<?php' . PHP_EOL . 'return ' . var_export($updatedConfig, true) . ';';

        $this->file->filePutContents($configPath, $updatedConfig);
    }

    /**
     * Returns path to environment configuration file.
     *
     * @return string
     */
    public function getConfigPath()
    {
        return $this->directoryList->getMagentoRoot() . '/app/etc/env.php';
    }
}
