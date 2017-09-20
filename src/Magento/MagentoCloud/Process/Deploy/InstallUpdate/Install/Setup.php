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
            "php ./bin/magento setup:install"
            . " " . "--session-save=db"
            . " " . "--cleanup-database"
            . " " . escapeshellarg("--currency={$this->environment->getDefaultCurrency()}")
            . " " . escapeshellarg("--base-url=$urlUnsecure")
            . " " . escapeshellarg("--base-url-secure=$urlSecure")
            . " " . escapeshellarg("--language={$this->environment->getAdminLocale()}")
            . " " . escapeshellarg("--timezone=America/Los_Angeles")
            . " " . escapeshellarg("--db-host={$this->environment->getDbHost()}")
            . " " . escapeshellarg("--db-name={$this->environment->getDbName()}")
            . " " . escapeshellarg("--db-user={$this->environment->getDbUser()}")
            . " " . escapeshellarg("--backend-frontname={$this->environment->getAdminUrl()}")
            . " " . escapeshellarg("--admin-user={$this->environment->getAdminUsername()}")
            . " " . escapeshellarg("--admin-firstname={$this->environment->getAdminFirstname()}")
            . " " . escapeshellarg("--admin-lastname={$this->environment->getAdminLastname()}")
            . " " . escapeshellarg("--admin-email={$this->environment->getAdminEmail()}")
            . " " . escapeshellarg("--admin-password={$this->passwordGenerator->generateRandomPassword()}"); // Note: This password gets changed later in updateAdminCredentials

        $dbPassword = $this->environment->getDbPassword();
        if (strlen($dbPassword)) {
            $command .= " " . escapeshellarg("--db-password={$dbPassword}");
        }

        $command .= $this->environment->getVerbosityLevel();

        $this->shell->execute($command);
    }
}
