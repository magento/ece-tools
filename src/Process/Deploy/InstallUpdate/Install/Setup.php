<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\Filesystem\FileList;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class Setup implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var FileList
     */
    private $fileList;

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
     * @param LoggerInterface $logger
     * @param UrlManager $urlManager
     * @param Environment $environment
     * @param ConnectionFactory $connectionFactory
     * @param ShellInterface $shell
     * @param PasswordGenerator $passwordGenerator
     * @param FileList $fileList
     * @param DeployInterface $stageConfig
     * @param ElasticSuite $elasticSuite
     */
    public function __construct(
        LoggerInterface $logger,
        UrlManager $urlManager,
        Environment $environment,
        ConnectionFactory $connectionFactory,
        ShellInterface $shell,
        PasswordGenerator $passwordGenerator,
        FileList $fileList,
        DeployInterface $stageConfig,
        ElasticSuite $elasticSuite
    ) {
        $this->logger = $logger;
        $this->urlManager = $urlManager;
        $this->environment = $environment;
        $this->connectionFactory = $connectionFactory;
        $this->shell = $shell;
        $this->passwordGenerator = $passwordGenerator;
        $this->fileList = $fileList;
        $this->stageConfig = $stageConfig;
        $this->elasticSuite = $elasticSuite;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Installing Magento.');

        $command = $this->getBaseCommand();

        $dbPassword = $this->getConnectionData()->getPassword();
        if (strlen($dbPassword)) {
            $command .= ' --db-password=' . escapeshellarg($dbPassword);
        }

        if ($this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)) {
            $command .= ' ' . $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);
        }

        /**
         * Hack to prevent ElasticSuite from throwing exception.
         */
        if ($this->elasticSuite->isAvailable()) {
            $host = $this->elasticSuite->get()['es_client']['servers'] ?? null;

            if ($host) {
                $command .= ' --es-hosts=' . escapeshellarg($host);
            }
        }

        try {
            $installUpgradeLog = $this->fileList->getInstallUpgradeLog();

            $this->shell->execute('echo \'Installation time: \'$(date) | tee -a ' . $installUpgradeLog);
            $this->shell->execute(sprintf(
                '/bin/bash -c "set -o pipefail; %s | tee -a %s"',
                escapeshellcmd($command),
                $installUpgradeLog
            ));
        } catch (ShellException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @return string
     */
    private function getBaseCommand(): string
    {
        $urlUnsecure = $this->urlManager->getUnSecureUrls()[''];
        $urlSecure = $this->urlManager->getSecureUrls()[''];

        return 'php ./bin/magento setup:install'
            . ' -n --session-save=db --cleanup-database'
            . ' --currency=' . escapeshellarg($this->environment->getDefaultCurrency())
            . ' --base-url=' . escapeshellarg($urlUnsecure)
            . ' --base-url-secure=' . escapeshellarg($urlSecure)
            . ' --language=' . escapeshellarg($this->environment->getAdminLocale())
            . ' --timezone=America/Los_Angeles'
            . ' --db-host=' . escapeshellarg($this->getConnectionData()->getHost())
            . ' --db-name=' . escapeshellarg($this->getConnectionData()->getDbName())
            . ' --db-user=' . escapeshellarg($this->getConnectionData()->getUser())
            . ' --backend-frontname=' . escapeshellarg($this->environment->getAdminUrl()
                ?: Environment::DEFAULT_ADMIN_URL)
            . ($this->environment->getAdminEmail() ? $this->getAdminCredentials() : '')
            . ' --use-secure-admin=1 --use-rewrites=1 --ansi --no-interaction';
    }

    /**
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
