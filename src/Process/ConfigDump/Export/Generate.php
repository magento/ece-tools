<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\ConfigDump\Export;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
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
     * @var DirectoryList
     */
    private $directoryList;

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
     * @param ConnectionInterface $connection
     * @param DirectoryList $directoryList
     * @param File $file
     * @param ArrayManager $arrayManager
     * @param array $configKeys
     */
    public function __construct(
        ConnectionInterface $connection,
        DirectoryList $directoryList,
        File $file,
        ArrayManager $arrayManager,
        array $configKeys
    ) {
        $this->connection = $connection;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->arrayManager = $arrayManager;
        $this->configKeys = $configKeys;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $configFile = $this->directoryList->getMagentoRoot() . '/app/etc/config.php';
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
         * Only saving general/locale/code
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
