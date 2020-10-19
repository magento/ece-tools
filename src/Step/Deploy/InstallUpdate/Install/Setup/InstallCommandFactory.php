<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install\Setup;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\Database\DbConfig;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\Config\RemoteStorage;

/**
 * Generates command for magento installation
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InstallCommandFactory
{
    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var AdminDataInterface
     */
    private $adminData;

    /**
     * @var PasswordGenerator
     */
    private $passwordGenerator;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ConnectionInterface
     */
    private $connectionData;

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @var ElasticSuite
     */
    private $elasticSuite;
    /**
     * @var DbConfig
     */
    private $dbConfig;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

    /**
     * @var RemoteStorage
     */
    private $remoteStorage;

    /**
     * @param UrlManager $urlManager
     * @param AdminDataInterface $adminData
     * @param ConnectionFactory $connectionFactory
     * @param PasswordGenerator $passwordGenerator
     * @param DeployInterface $stageConfig
     * @param ElasticSuite $elasticSuite
     * @param DbConfig $dbConfig
     * @param MagentoVersion $magentoVersion
     * @param ElasticSearch $elasticSearch
     * @param RemoteStorage $remoteStorage
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        UrlManager $urlManager,
        AdminDataInterface $adminData,
        ConnectionFactory $connectionFactory,
        PasswordGenerator $passwordGenerator,
        DeployInterface $stageConfig,
        ElasticSuite $elasticSuite,
        DbConfig $dbConfig,
        MagentoVersion $magentoVersion,
        ElasticSearch $elasticSearch,
        RemoteStorage $remoteStorage
    ) {
        $this->urlManager = $urlManager;
        $this->adminData = $adminData;
        $this->connectionFactory = $connectionFactory;
        $this->passwordGenerator = $passwordGenerator;
        $this->stageConfig = $stageConfig;
        $this->elasticSuite = $elasticSuite;
        $this->dbConfig = $dbConfig;
        $this->magentoVersion = $magentoVersion;
        $this->elasticSearch = $elasticSearch;
        $this->remoteStorage = $remoteStorage;
    }

    /**
     * @return string
     * @throws ConfigException
     */
    public function create(): string
    {
        $command = 'php ./bin/magento setup:install';

        if ($this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)) {
            $command .= ' ' . $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);
        }

        try {
            $options = array_replace(
                $this->getBaseOptions(),
                $this->getAdminOptions(),
                $this->getEsOptions(),
                $this->getRemoteStorageOptions()
            );
        } catch (GenericException $exception) {
            throw new ConfigException($exception->getMessage(), $exception->getCode(), $exception);
        }

        foreach ($options as $option => $value) {
            $command .= sprintf(' %s%s', $option, $value === null ? '' : '=' . escapeshellarg($value));
        }

        return $command;
    }

    /**
     * Return base part of install command
     *
     * @return array
     *
     * @throws ConfigException
     */
    private function getBaseOptions(): array
    {
        $urlUnSecure = $this->urlManager->getUnSecureUrls()[''];
        $urlSecure = $this->urlManager->getSecureUrls()[''];
        $adminUrl = $this->adminData->getUrl() ?: AdminDataInterface::DEFAULT_ADMIN_URL;

        $options = [
            '-n' => null,
            '--ansi' => null,
            '--no-interaction' => null,
            '--cleanup-database' => null,
            '--session-save' => 'db',
            '--use-secure-admin' => '1',
            '--use-rewrites' => '1',
            '--currency' => $this->adminData->getDefaultCurrency(),
            '--base-url' => $urlUnSecure,
            '--base-url-secure' => $urlSecure,
            '--backend-frontname' => $adminUrl,
            '--language' => $this->adminData->getLocale(),
            '--timezone' => 'America/Los_Angeles',
            '--db-host' => $this->getConnectionData()->getHost(),
            '--db-name' => $this->getConnectionData()->getDbName(),
            '--db-user' => $this->getConnectionData()->getUser(),
        ];

        if ($dbPassword = $this->getConnectionData()->getPassword()) {
            $options['--db-password'] = $dbPassword;
        }

        if ($tablePrefix = $this->dbConfig->get()['table_prefix'] ?? '') {
            $options['--db-prefix'] = $tablePrefix;
        }

        return $options;
    }

    /**
     * @return array
     */
    private function getAdminOptions(): array
    {
        if ($this->adminData->getEmail()) {
            return [
                '--admin-user' => $this->adminData->getUsername()
                    ?: AdminDataInterface::DEFAULT_ADMIN_NAME,
                '--admin-firstname' => $this->adminData->getFirstName()
                    ?: AdminDataInterface::DEFAULT_ADMIN_FIRST_NAME,
                '--admin-lastname' => $this->adminData->getLastName()
                    ?: AdminDataInterface::DEFAULT_ADMIN_LAST_NAME,
                '--admin-email' => $this->adminData->getEmail(),
                '--admin-password' => $this->adminData->getPassword()
                    ?: $this->passwordGenerator->generateRandomPassword(),
            ];
        }

        return [];
    }

    /**
     * Resolves Elasticsearch additional config options.
     *
     * @return array
     * @throws UndefinedPackageException
     * @throws ConfigException
     * @throws ServiceException
     */
    private function getEsOptions(): array
    {
        $options = [];

        if ($this->magentoVersion->isGreaterOrEqual('2.4.0')) {
            if (!$this->elasticSearch->isInstalled()) {
                throw new ConfigException('Elasticsearch service is required');
            }

            $options['--search-engine'] = $this->elasticSearch->getFullVersion();
            $options['--elasticsearch-host'] = $this->elasticSearch->getHost();
            $options['--elasticsearch-port'] = $this->elasticSearch->getPort();
        }

        /**
         * Hack to prevent ElasticSuite from throwing exception.
         */
        if ($this->elasticSuite->isAvailable() && $this->elasticSuite->getServers()) {
            $options['--es-hosts'] = $this->elasticSuite->getServers();
        }

        return $options;
    }

    /**
     * Provides install options for remote storage.
     *
     * @return array
     * @throws UndefinedPackageException
     */
    private function getRemoteStorageOptions(): array
    {
        $options = [];

        if ($this->magentoVersion->isGreaterOrEqual('2.4.2') && $this->remoteStorage->getAdapter()) {
            $adapter = $this->remoteStorage->getAdapter();
            $config = $this->remoteStorage->getConfig();

            $options['--remote-storage-adapter'] = $adapter;
            $options['--remote-storage-bucket'] = $config['bucket'];
            $options['--remote-storage-region'] = $config['region'];
            $options['--remote-storage-prefix'] = $config['prefix'];

            if (isset($config['key'], $config['secret'])) {
                $options['--remote-storage-key'] = $config['key'];
                $options['--remote-storage-secret'] = $config['secret'];
            }
        }

        return $options;
    }

    /**
     * Returns instance of ConnectionInterface
     *
     * @return ConnectionInterface
     * @throws ConfigException
     */
    private function getConnectionData(): ConnectionInterface
    {
        if (!$this->connectionData instanceof ConnectionInterface) {
            $this->connectionData = $this->connectionFactory->create(ConnectionFactory::CONNECTION_MAIN);
        }

        return $this->connectionData;
    }
}
