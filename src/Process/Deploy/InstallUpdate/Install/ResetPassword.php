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
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;

/**
 * Sends email with link to reset password.
 *
 * {@inheritdoc}
 */
class ResetPassword implements ProcessInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

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
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param UrlManager $urlManager
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        UrlManager $urlManager,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->urlManager = $urlManager;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->environment->getAdminPassword()) {
            return;
        }

        $credentialsFile = $this->directoryList->getMagentoRoot() . '/var/credentials_email.txt';
        $urls = $this->urlManager->getUrls();
        $adminUrl = $urls['secure'][''] . ($this->environment->getAdminUrl() ?: Environment::DEFAULT_ADMIN_URL);
        $adminEmail = $this->environment->getAdminEmail();
        $adminUsername = $this->environment->getAdminUsername() ?: Environment::DEFAULT_ADMIN_NAME;

        $emailContent = 'Welcome to Magento Commerce (Cloud)!' . PHP_EOL
            . 'To properly log into your provisioned Magento installation Admin panel, you need to change'
            . ' your Admin password. To update your password, click this link to access the Admin Panel:'
            . ' ' . $adminUrl . ' When the page opens, click the "Forgot your password" link. You should receive'
            . ' a password update email at ' . $adminEmail . ' Just in case, check your spam box if you don\'t see'
            . ' the email immediately.' . PHP_EOL
            . 'After the password is updated, you can login with the username ' . $adminUsername
            . ' and the new password.' . PHP_EOL
            . 'Need help? Please see'
            . ' http://devdocs.magento.com/guides/v2.2/cloud/onboarding/onboarding-tasks.html' . PHP_EOL
            . 'Thank you,' . PHP_EOL
            . 'Magento Commerce (Cloud)' . PHP_EOL;

        $this->logger->info('Emailing admin URL to admin user ' . $adminUsername . ' at ' . $adminEmail);
        mail(
            $adminEmail,
            'Magento Commerce Cloud - Admin URL',
            $emailContent,
            'From: Magento Cloud <accounts@magento.cloud>'
        );
        $this->logger->info('Saving email with admin URL: ' . $credentialsFile);
        $this->file->filePutContents($credentialsFile, $emailContent);
    }
}
