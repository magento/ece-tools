<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Config\Deploy as DeployConfig;
use Magento\MagentoCloud\Filesystem\Driver\File;

class ConfigWriter
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @param File $file
     * @param DeployConfig $deployConfig
     */
    public function __construct(
        File $file,
        DeployConfig $deployConfig
    ) {
        $this->file = $file;
        $this->deployConfig = $deployConfig;
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
            $configPath = $this->deployConfig->getConfigFilePath();
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
            $configPath = $this->deployConfig->getConfigFilePath();
        }

        $oldConfig = include $configPath;

        $updatedConfig = array_replace_recursive($oldConfig, $config);
        $updatedConfig = '<?php' . PHP_EOL . 'return ' . var_export($updatedConfig, true) . ';';

        $this->file->filePutContents($configPath, $updatedConfig);
    }
}
