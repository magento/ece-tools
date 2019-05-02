<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install\Setup;

use Magento\MagentoCloud\Config\Database\MergedConfig;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\Util\PasswordGenerator;

/**
 * Generates command for magento installation
 */
class InstallCommandFactory
{
    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var Environment
     */
    private $environment;

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
     * @var MergedConfig
     */
    private $mergedConfig;

    /**
     * @param UrlManager $urlManager
     * @param Environment $environment
     * @param ConnectionFactory $connectionFactory
     * @param PasswordGenerator $passwordGenerator
     * @param DeployInterface $stageConfig
     * @param ElasticSuite $elasticSuite
     * @param MergedConfig $mergedConfig
     */
    public function __construct(
        UrlManager $urlManager,
        Environment $environment,
        ConnectionFactory $connectionFactory,
        PasswordGenerator $passwordGenerator,
        DeployInterface $stageConfig,
        ElasticSuite $elasticSuite,
        MergedConfig $mergedConfig
    ) {
        $this->urlManager = $urlManager;
        $this->environment = $environment;
        $this->connectionFactory = $connectionFactory;
        $this->passwordGenerator = $passwordGenerator;
        $this->stageConfig = $stageConfig;
        $this->elasticSuite = $elasticSuite;
        $this->mergedConfig = $mergedConfig;
    }

    /**
     * Creates magento install command according to configured variables
     *
     * @return string
     */
    public function create(): string
    {
        $command = $this->getBaseCommand();

        /**
         * Hack to prevent ElasticSuite from throwing exception.
         */
        if ($this->elasticSuite->isAvailable()) {
            $host = $this->elasticSuite->get()['es_client']['servers'] ?? null;

            if ($host) {
                $command .= ' --es-hosts=' . escapeshellarg($host);
            }
        }

        if ($this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)) {
            $command .= ' ' . $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);
        }

        return $command;
    }

    /**
     * Return base part of install command
     *
     * @return string
     */
    private function getBaseCommand(): string
    {
        $urlUnsecure = $this->urlManager->getUnSecureUrls()[''];
        $urlSecure = $this->urlManager->getSecureUrls()[''];
        $adminUrl = $this->environment->getAdminUrl() ?: Environment::DEFAULT_ADMIN_URL;

        $command = 'php ./bin/magento setup:install'
            . ' -n --session-save=db --cleanup-database'
            . ' --use-secure-admin=1 --use-rewrites=1 --ansi --no-interaction'
            . ' --currency=' . escapeshellarg($this->environment->getDefaultCurrency())
            . ' --base-url=' . escapeshellarg($urlUnsecure)
            . ' --base-url-secure=' . escapeshellarg($urlSecure)
            . ' --backend-frontname=' . escapeshellarg($adminUrl)
            . ' --language=' . escapeshellarg($this->environment->getAdminLocale())
            . ' --timezone=America/Los_Angeles'
            . ' --db-host=' . escapeshellarg($this->getConnectionData()->getHost())
            . ' --db-name=' . escapeshellarg($this->getConnectionData()->getDbName())
            . ' --db-user=' . escapeshellarg($this->getConnectionData()->getUser());

        $dbPassword = $this->getConnectionData()->getPassword();
        if (strlen($dbPassword)) {
            $command .= ' --db-password=' . escapeshellarg($dbPassword);
        }

        if ($table_prefix = $this->mergedConfig->get()['table_prefix'] ?? '') {
            $command .= ' --db-prefix=' . escapeshellarg($table_prefix);
        }

        if ($this->environment->getAdminEmail()) {
            $command .= $this->getAdminCredentials();
        }

        return $command;
    }

    /**
     * Returns part with admin credentials for install command
     *
     * @return string
     */
    private function getAdminCredentials(): string
    {
        return ' --admin-user=' . escapeshellarg($this->environment->getAdminUsername()
                ?: Environment::DEFAULT_ADMIN_NAME)
            . ' --admin-firstname=' . escapeshellarg($this->environment->getAdminFirstname()
                ?: Environment::DEFAULT_ADMIN_FIRSTNAME)
            . ' --admin-lastname=' . escapeshellarg($this->environment->getAdminLastname()
                ?: Environment::DEFAULT_ADMIN_LASTNAME)
            . ' --admin-email=' . escapeshellarg($this->environment->getAdminEmail())
            . ' --admin-password=' . escapeshellarg($this->environment->getAdminPassword()
                ?: $this->passwordGenerator->generateRandomPassword());
    }

    /**
     * Returns instance of ConnectionInterface
     *
     * @return ConnectionInterface
     */
    private function getConnectionData(): ConnectionInterface
    {
        if (!$this->connectionData instanceof ConnectionInterface) {
            $this->connectionData = $this->connectionFactory->create(ConnectionFactory::CONNECTION_MAIN);
        }

        return $this->connectionData;
    }
}
