<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Command\ConfigDump;

use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Config\Magento\Shared\Resolver;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Util\ArrayManager;
use Magento\MagentoCloud\Util\PhpFormatter;

/**
 * @inheritdoc
 */
class Generate
{
    /**
     * @var array
     */
    private $configKeys = [
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

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var File
     */
    private $file;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var PhpFormatter
     */
    private $phpFormatter;

    /**
     * @param ConnectionInterface $connection
     * @param File $file
     * @param ArrayManager $arrayManager
     * @param MagentoVersion $magentoVersion
     * @param Resolver $resolver
     * @param PhpFormatter $phpFormatter
     */
    public function __construct(
        ConnectionInterface $connection,
        File $file,
        ArrayManager $arrayManager,
        MagentoVersion $magentoVersion,
        Resolver $resolver,
        PhpFormatter $phpFormatter
    ) {
        $this->connection = $connection;
        $this->file = $file;
        $this->arrayManager = $arrayManager;
        $this->resolver = $resolver;
        $this->magentoVersion = $magentoVersion;
        $this->phpFormatter = $phpFormatter;
    }

    /**
     * {@inheritdoc}
     *
     * @throws UndefinedPackageException
     * @throws FileSystemException
     */
    public function execute(): void
    {
        if ($this->magentoVersion->isGreaterOrEqual('2.2')) {
            $this->configKeys[] = 'modules';
        }

        $configFile = $this->resolver->getPath();
        $oldConfig = $this->resolver->read();

        $newConfig = [];

        foreach ($this->configKeys as $requiredConfigKey) {
            $oldConfigCopy = $oldConfig;
            $configKeys = explode('/', $requiredConfigKey);

            /**
             * Get value of the config recursively.
             */
            foreach ($configKeys as $configKey) {
                $oldConfigCopy = $oldConfigCopy[$configKey] ?? null;
            }

            /**
             * Setting value in new array.
             */
            if ($oldConfigCopy) {
                $newConfig = $this->arrayManager->nest($newConfig, $configKeys, $oldConfigCopy);
            }
        }

        /**
         * Only saving general/locale/code.
         */

        $newConfig = $this->filterSystemData($newConfig, 'stores');
        $newConfig = $this->filterSystemData($newConfig, 'websites');

        /**
         * Un-setting base_url.
         */
        unset(
            $newConfig['system']['stores']['admin']['web']['secure']['base_url'],
            $newConfig['system']['stores']['admin']['web']['unsecure']['base_url']
        );

        /**
         * Adding locales for admin user.
         */
        $newConfig['admin_user']['locale']['code'] = array_column(
            $this->connection->select(sprintf(
                'SELECT DISTINCT `%s` FROM `admin_user`',
                $this->connection->getTableName('interface_locale')
            )),
            'interface_locale'
        );

        $this->file->filePutContents(
            $configFile,
            $this->phpFormatter->format($newConfig)
        );
    }

    /**
     * Removes all data from provided scopes in system section, except general/locale/code
     *
     * @param array $config Config data
     * @param string $scope Name of scope: websites or stores
     * @return array Result of config data after filtering
     */
    private function filterSystemData($config, $scope): array
    {
        $scopeCodes = isset($config['system'][$scope])
            ? array_keys($config['system'][$scope])
            : [];

        foreach ($scopeCodes as $code) {
            if (isset($config['system'][$scope][$code]['general']['locale']['code'])) {
                $localeCode = $config['system'][$scope][$code]['general']['locale']['code'];
                unset($config['system'][$scope][$code]);
                $config['system'][$scope][$code]['general']['locale']['code'] = $localeCode;
            } else {
                unset($config['system'][$scope][$code]);
            }
        }

        return $config;
    }
}
