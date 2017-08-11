<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Config\DeploymentConfig;

/**
 * @inheritdoc
 */
class CreateConfigFile implements ProcessInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param File $file
     */
    public function __construct(DeploymentConfig $deploymentConfig, File $file)
    {
        $this->deploymentConfig = $deploymentConfig;
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $configFile = $this->deploymentConfig->getConfigFilePath();

        if ($this->file->isExists($configFile)) {
            return;
        }

        $updatedConfig = '<?php' . "\n" . 'return array();';
        $this->file->filePutContents($configFile, $updatedConfig);
    }
}
