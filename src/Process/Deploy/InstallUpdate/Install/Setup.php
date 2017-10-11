<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Psr\Log\LoggerInterface;

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
     * @var PasswordGenerator
     */
    private $passwordGenerator;

    /**
     * @param LoggerInterface $logger
     * @param UrlManager $urlManager
     * @param Environment $environment
     * @param ShellInterface $shell
     * @param PasswordGenerator $passwordGenerator
     */
    public function __construct(
        LoggerInterface $logger,
        UrlManager $urlManager,
        Environment $environment,
        ShellInterface $shell,
        PasswordGenerator $passwordGenerator
    ) {
        $this->logger = $logger;
        $this->urlManager = $urlManager;
        $this->environment = $environment;
        $this->shell = $shell;
        $this->passwordGenerator = $passwordGenerator;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->logger->info('Installing Magento.');

        $urlUnsecure = $this->urlManager->getUnSecureUrls()[''];
        $urlSecure = $this->urlManager->getSecureUrls()[''];

        $command =
            'php ./bin/magento setup:install'
            . ' --session-save=db --cleanup-database'
            . ' --currency=' . escapeshellarg($this->environment->getDefaultCurrency())
            . ' --base-url=' . escapeshellarg($urlUnsecure)
            . ' --base-url-secure=' . escapeshellarg($urlSecure)
            . ' --language=' . escapeshellarg($this->environment->getAdminLocale())
            . ' --timezone=America/Los_Angeles'
            . ' --db-host=' . escapeshellarg($this->environment->getDbHost())
            . ' --db-name=' . escapeshellarg($this->environment->getDbName())
            . ' --db-user=' . escapeshellarg($this->environment->getDbUser())
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
            . ' --use-secure-admin 1';

        $dbPassword = $this->environment->getDbPassword();
        if (strlen($dbPassword)) {
            $command .= ' --db-password=' . escapeshellarg($dbPassword);
        }

        $command .= $this->environment->getVerbosityLevel();

        $this->shell->execute(escapeshellcmd($command));
    }
}
