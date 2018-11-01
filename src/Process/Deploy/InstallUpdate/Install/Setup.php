<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\DB\Data\ConnectionFactory;
use Magento\MagentoCloud\DB\Data\ConnectionInterface;
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
     * @param LoggerInterface $logger
     * @param UrlManager $urlManager
     * @param Environment $environment
     * @param ConnectionFactory $connectionFactory
     * @param ShellInterface $shell
     * @param PasswordGenerator $passwordGenerator
     * @param FileList $fileList
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        UrlManager $urlManager,
        Environment $environment,
        ConnectionFactory $connectionFactory,
        ShellInterface $shell,
        PasswordGenerator $passwordGenerator,
        FileList $fileList,
        DeployInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->urlManager = $urlManager;
        $this->environment = $environment;
        $this->connectionData = $connectionFactory->create(ConnectionFactory::CONNECTION_MAIN);
        $this->shell = $shell;
        $this->passwordGenerator = $passwordGenerator;
        $this->fileList = $fileList;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Installing Magento.');

        $command = $this->getBaseCommand();

        $dbPassword = $this->connectionData->getPassword();
        if (strlen($dbPassword)) {
            $command .= ' --db-password=' . escapeshellarg($dbPassword);
        }

        if ($this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)) {
            $command .= ' ' . $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);
        }

        try {
            $this->shell->execute(sprintf(
                '/bin/bash -c "set -o pipefail; %s | tee -a %s"',
                escapeshellcmd($command),
                $this->fileList->getInstallUpgradeLog()
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
            . ' --db-host=' . escapeshellarg($this->connectionData->getHost())
            . ' --db-name=' . escapeshellarg($this->connectionData->getDbName())
            . ' --db-user=' . escapeshellarg($this->connectionData->getUser())
            . ' --backend-frontname=' . escapeshellarg($this->environment->getAdminUrl()
                ? $this->environment->getAdminUrl() : Environment::DEFAULT_ADMIN_URL)
            . ' --admin-user=' . escapeshellarg($this->environment->getAdminUsername()
                ? $this->environment->getAdminUsername() : Environment::DEFAULT_ADMIN_NAME)
            . ' --admin-firstname=' . escapeshellarg($this->environment->getAdminFirstname()
                ? $this->environment->getAdminFirstname() : Environment::DEFAULT_ADMIN_FIRSTNAME)
            . ' --admin-lastname=' . escapeshellarg($this->environment->getAdminLastname()
                ? $this->environment->getAdminLastname() : Environment::DEFAULT_ADMIN_LASTNAME)
            . ' --admin-email=' . escapeshellarg($this->environment->getAdminEmail())
            . ' --admin-password=' . escapeshellarg($this->environment->getAdminPassword()
                ? $this->environment->getAdminPassword() : $this->passwordGenerator->generateRandomPassword())
            . ' --use-secure-admin=1 --ansi --no-interaction';
    }
}
