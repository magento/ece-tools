<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Environment;

class ConfigFileCreator implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $env;

    /**
     * @var File
     */
    private $file;

    /**
     * @param Environment $env
     * @param File $file
     */
    public function __construct(Environment $env, File $file)
    {
        $this->env = $env;
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $configFile = $this->env->getConfigFilePath();
        if ($this->file->isExists($configFile)) {
            return;
        }

        $updatedConfig = '<?php' . "\n" . 'return array();';
        $this->file->filePutContents($configFile, $updatedConfig);
    }
}
