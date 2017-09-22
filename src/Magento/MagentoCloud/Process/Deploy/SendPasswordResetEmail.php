<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Deploy as DeployConfig;
use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\EnvironmentAdmin;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\PasswordGenerator;
use Magento\MagentoCloud\Util\UrlManager;

/**
 * @inheritdoc
 */
class SendPasswordResetEmail implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var DeployConfig
     */
    private $deployConfig;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var EnvironmentAdmin
     */
    private $environmentAdmin;

    /**
     * @var PasswordGenerator
     */
    private $passwordGenerator;

    /**
     * @var UrlManager
     */
    private $urlManager;


    /**
     * @param LoggerInterface $logger
     * @param ShellInterface $shell
     * @param DeployConfig $deployConfig
     * @param Environment $environment
     * @param EnvironmentAdmin $environmentAdmin
     * @param PasswordGenerator $passwordGenerator
     * @param UrlManager $urlManager
     */
    public function __construct(
        LoggerInterface $logger,
        ShellInterface $shell,
        DeployConfig $deployConfig,
        Environment $environment,
        EnvironmentAdmin $environmentAdmin,
        PasswordGenerator $passwordGenerator,
        UrlManager $urlManager
    ) {
        $this->logger = $logger;
        $this->shell = $shell;
        $this->deployConfig = $deployConfig;
        $this->environment = $environment;
        $this->environmentAdmin = $environmentAdmin;
        $this->passwordGenerator = $passwordGenerator;
        $this->urlManager = $urlManager;
    }

    public function execute()
    {
        $urls = $this->urlManager->getUrls();
        if (!$this->deployConfig->isInstalling() || empty($this->environmentAdmin->getAdminEmail()) || !empty($this->environment->getVariables()["ADMIN_PASSWORD"])) {
            return;
        }
        $adminUrl = $urls['secure'][''] . $this->environmentAdmin->getAdminUrl();
        $adminEmail = $this->environmentAdmin->getAdminEmail();
        $adminUsername = $this->environmentAdmin->getAdminUsername();
        $this->logger->info("Emailing admin URL to admin user \"{$adminUsername}\" at $adminEmail");
        mail(
            $adminEmail,
            "Magento Commerce Cloud - Admin URL",
            "Welcome to Magento Commerce (Cloud)!\n"
                . "To properly log into your provisioned Magento installation Admin panel, you need to change your Admin password. To update your password, click this link to access the Admin Panel: {$adminUrl} . When the page opens, click the \"Forgot your password\" link. You should receive a password update email at {$adminEmail} . Just in case, check your spam box if you don't see the email immediately.\n"
                . "After the password is updated, you can login with the username {$adminUsername} and the new password.\n"
                . "Need help? Please see http://devdocs.magento.com/guides/v2.2/cloud/onboarding/onboarding-tasks.html\n"
                . "Thank you,\n"
                . "Magento Commerce (Cloud)\n",
            "From: Magento Cloud <accounts@magento.cloud>"
        );
    }
}
