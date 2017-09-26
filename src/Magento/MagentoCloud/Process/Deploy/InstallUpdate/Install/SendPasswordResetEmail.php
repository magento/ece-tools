<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Process\ProcessInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\DB\ConnectionInterface;

class SendPasswordResetEmail implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * SendPasswordResetEmail constructor.
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param UrlManager $urlManager
     * @param ConnectionInterface $connection
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        UrlManager $urlManager,
        ConnectionInterface $connection
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->urlManager = $urlManager;
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->environment->getAdminPassword()) {
            $urls = $this->urlManager->getUrls();
            $adminUrl = $urls['secure'][''] . ($this->environment->getAdminUrl()
                    ? $this->environment->getAdminUrl() : Environment::DEFAULT_ADMIN_URL);
            $adminEmail = $this->environment->getAdminEmail();
            $adminUsername = $this->environment->getAdminUsername()
                ? $this->environment->getAdminUsername() : Environment::DEFAULT_ADMIN_NAME;
            $this->logger->info("Emailing admin URL to admin user \"{$adminUsername}\" at $adminEmail");
            mail(
                $adminEmail,
                "Magento Commerce Cloud - Admin URL",
                "Welcome to Magento Commerce (Cloud)!\n"
                    . "To properly log into your provisioned Magento installation Admin panel, you need to change "
                    . " your Admin password. To update your password, click this link to access the Admin Panel:"
                    . " {$adminUrl} . When the page opens, click the \"Forgot your password\" link. You should receive"
                    . " a password update email at {$adminEmail} . Just in case, check your spam box if you don't see"
                    . " the email immediately.\n"
                    . "After the password is updated, you can login with the username {$adminUsername}"
                    . " and the new password.\n"
                    . "Need help? Please see"
                    . " http://devdocs.magento.com/guides/v2.2/cloud/onboarding/onboarding-tasks.html\n"
                    . "Thank you,\n"
                    . "Magento Commerce (Cloud)\n",
                "From: Magento Cloud <accounts@magento.cloud>"
            );
        }
    }
}
