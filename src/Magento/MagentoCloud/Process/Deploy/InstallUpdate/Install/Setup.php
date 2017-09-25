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
            . ' --currency=' . $this->environment->getDefaultCurrency()
            . ' --base-url=' . $urlUnsecure
            . ' --base-url-secure=' . $urlSecure
            . ' --language=' . $this->environment->getAdminLocale()
            . ' --timezone=America/Los_Angeles'
            . ' --db-host=' . $this->environment->getDbHost()
            . ' --db-name=' . $this->environment->getDbName()
            . ' --db-user=' . $this->environment->getDbUser()
            . ' --backend-frontname=' . $this->environment->getAdminUrl()
            . ' --admin-user=' . $this->environment->getAdminUsername()
            . ' --admin-firstname=' . (empty($this->environment->getAdminFirstname())
                ? 'Changeme': $this->environment->getAdminFirstname())
            . ' --admin-lastname=' . (empty($this->environment->getAdminLastname())
                ? 'Changeme': $this->environment->getAdminLastname())
            . ' --admin-email=' . $this->environment->getAdminEmail()
            . ' --admin-password=' . (empty($this->environment->getAdminPassword())
                ? $this->passwordGenerator->generateRandomPassword() : $this->environment->getAdminPassword());

        $dbPassword = $this->environment->getDbPassword();
        if (strlen($dbPassword)) {
            $command = '--db-password=' . $dbPassword;
        }

        $command .= $this->environment->getVerbosityLevel();

        $this->shell->execute(escapeshellcmd($command));
    }
}
