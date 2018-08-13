<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ExecBinMagento;
use Magento\MagentoCloud\Shell\ShellException;
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
     * @var ExecBinMagento
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
     * @param LoggerInterface $logger
     * @param UrlManager $urlManager
     * @param Environment $environment
     * @param ExecBinMagento $shell
     * @param PasswordGenerator $passwordGenerator
     * @param FileList $fileList
     * @param DeployInterface $stageConfig
     */
    public function __construct(
        LoggerInterface $logger,
        UrlManager $urlManager,
        Environment $environment,
        ExecBinMagento $shell,
        PasswordGenerator $passwordGenerator,
        FileList $fileList,
        DeployInterface $stageConfig
    ) {
        $this->logger = $logger;
        $this->urlManager = $urlManager;
        $this->environment = $environment;
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

        $args = [
            '--session-save=db',
            '--cleanup-database',
            '--currency=' . $this->environment->getDefaultCurrency(),
            '--base-url=' . $this->urlManager->getUnSecureUrls()[''],
            '--base-url-secure=' . $this->urlManager->getSecureUrls()[''],
            '--language=' . $this->environment->getAdminLocale(),
            '--timezone=America/Los_Angeles',
            '--db-host=' . $this->environment->getDbHost(),
            '--db-name=' . $this->environment->getDbName(),
            '--db-user=' . $this->environment->getDbUser(),
            '--backend-frontname=' . $this->environment->getAdminUrl() ?: Environment::DEFAULT_ADMIN_URL,
            '--admin-user=' . $this->environment->getAdminUsername() ?: Environment::DEFAULT_ADMIN_NAME,
            '--admin-firstname=' . $this->environment->getAdminFirstname() ?: Environment::DEFAULT_ADMIN_FIRSTNAME,
            '--admin-lastname=' . $this->environment->getAdminLastname() ?: Environment::DEFAULT_ADMIN_LASTNAME,
            '--admin-email=' . $this->environment->getAdminEmail(),
            '--admin-password=' . $this->environment->getAdminPassword()
                ?: $this->passwordGenerator->generateRandomPassword(),
            '--use-secure-admin=1',
        ];

        $dbPassword = $this->environment->getDbPassword();
        if ($dbPassword) {
            $args[] = '--db-password=' . $dbPassword;
        }

        if ($this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS)) {
            $args[] = $this->stageConfig->get(DeployInterface::VAR_VERBOSE_COMMANDS);
        }

        try {
            $output = $this->shell->execute('setup:install', $args);
        } catch (ShellException $e) {
            $output = $e->getOutupt();
            throw $e;
        } finally {
            file_put_contents($this->fileList->getInstallUpgradeLog(), $output, FILE_APPEND);
        }
    }
}
