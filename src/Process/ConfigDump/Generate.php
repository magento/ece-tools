<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\ConfigDump;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Util\ArrayManager;

/**
 * @inheritdoc
 */
class Generate implements ProcessInterface
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var File
     */
    private $file;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var array
     */
    private $configKeys;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ConnectionInterface $connection
     * @param FileList $fileList
     * @param File $file
     * @param ArrayManager $arrayManager
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        ConnectionInterface $connection,
        FileList $fileList,
        File $file,
        ArrayManager $arrayManager,
        MagentoVersion $magentoVersion
    ) {
        $this->connection = $connection;
        $this->fileList = $fileList;
        $this->file = $file;
        $this->arrayManager = $arrayManager;
        $this->magentoVersion = $magentoVersion;
        if ($this->magentoVersion->isGreaterOrEqual('2.2')) {
            $this->configKeys = [
                'modules',
                'scopes',
                'system/default/general/locale/code',
                'system/default/dev/static/sign',
                'system/default/dev/front_end_development_workflow',
                'system/default/dev/template',
                'system/default/dev/js',
                'system/default/dev/css',
                'system/default/advanced/modules_disable_output',
                'system/stores',
                'system/websites',
            ];
        } else {  // Magento 2.0 and 2.1
            $this->configKeys = [
                'scopes',
                'system/default/general/locale/code',
                'system/default/dev/static/sign',
                'system/default/dev/front_end_development_workflow',
                'system/default/dev/template',
                'system/default/dev/js',
                'system/default/dev/css',
                'system/default/advanced/modules_disable_output',
                'system/stores',
            ];
        }
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->magentoVersion->isGreaterOrEqual('2.2')) {
            $configFile = $this->fileList->getConfig();
        } else { // In 2.0 and 2.1, we use config.local.php instead
            $configFile = $this->fileList->getConfigLocal();
        }
        $oldConfig = require $configFile;
        $newConfig = [];

        foreach ($this->configKeys as $requiredConfigKey) {
            $oldConfigCopy = $oldConfig;
            $configKeys = explode('/', $requiredConfigKey);

            /**
             * Get value of the config recursively.
             */
            foreach ($configKeys as $configKey) {
                $oldConfigCopy = isset($oldConfigCopy[$configKey])
                    ? $oldConfigCopy[$configKey]
                    : null;
            }

            /**
             * Setting value in new array.
             */
            if (isset($oldConfigCopy)) {
                $newConfig = $this->arrayManager->nest($newConfig, $configKeys, $oldConfigCopy);
            }
        }

        /**
         * Only saving general/locale/code.
         */
        $configLocales = isset($newConfig['system']['stores'])
            ? array_keys($newConfig['system']['stores'])
            : [];
        foreach ($configLocales as $configLocale) {
            if (isset($newConfig['system']['stores'][$configLocale]['general']['locale']['code'])) {
                $temp = $newConfig['system']['stores'][$configLocale]['general']['locale']['code'];
                unset($newConfig['system']['stores'][$configLocale]);
                $newConfig['system']['stores'][$configLocale]['general']['locale']['code'] = $temp;
            }
        }

        /**
         * Un-setting base_url.
         */
        unset($newConfig['system']['stores']['admin']['web']['secure']['base_url']);
        unset($newConfig['system']['stores']['admin']['web']['unsecure']['base_url']);

        /**
         * Adding locales for admin user.
         */
        $newConfig['admin_user']['locale']['code'] = array_column(
            $this->connection->select('SELECT DISTINCT `interface_locale` FROM `admin_user`'),
            'interface_locale'
        );

        $updatedConfig = '<?php' . "\n" . 'return ' . var_export($newConfig, true) . ";\n";

        $this->file->filePutContents($configFile, $updatedConfig);
    }
}
