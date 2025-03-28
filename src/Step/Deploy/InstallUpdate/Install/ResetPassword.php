<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Step\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Util\UrlManager;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\DirectoryList;

/**
 * Sends email with link to reset password.
 *
 * {@inheritdoc}
 */
class ResetPassword implements StepInterface
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
     * @var AdminDataInterface
     */
    private $adminData;

    /**
     * @var UrlManager
     */
    private $urlManager;

    /**
     * @param LoggerInterface $logger
     * @param AdminDataInterface $adminData
     * @param UrlManager $urlManager
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        LoggerInterface $logger,
        AdminDataInterface $adminData,
        UrlManager $urlManager,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->logger = $logger;
        $this->adminData = $adminData;
        $this->urlManager = $urlManager;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->adminData->getPassword() || !$this->adminData->getEmail()) {
            return;
        }

        $credentialsFile = $this->directoryList->getMagentoRoot() . '/var/credentials_email.txt';
        $templateFile = $this->directoryList->getViews() . '/reset_password.html';

        $adminEmail = $this->adminData->getEmail();
        $adminUsername = $this->adminData->getUsername() ?: AdminDataInterface::DEFAULT_ADMIN_NAME;
        $adminName = $this->adminData->getFirstName();

        $adminUrl = $this->urlManager->getUrls()['secure']['']
            . ($this->adminData->getUrl() ?: AdminDataInterface::DEFAULT_ADMIN_URL);

        try {
            $emailContent = strtr(
                $this->file->fileGetContents($templateFile),
                [
                    '{{ admin_url }}' => $adminUrl,
                    '{{ admin_email }}' => $adminEmail,
                    '{{ admin_name }}' => $adminName ?: $adminUsername,
                ]
            );
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_UNABLE_TO_READ_RESET_PASSWORD_TMPL, $e);
        }

        $this->logger->info('Emailing admin URL to admin user ' . $adminUsername . ' at ' . $adminEmail);

        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type:text/html;charset=UTF-8' . "\r\n";
        $headers .= 'From: Magento Cloud <accounts@magento.cloud>' . "\r\n";

        mail(
            $adminEmail,
            'Magento Commerce Cloud - Admin URL',
            $emailContent,
            $headers
        );
        $this->logger->info('Saving email with admin URL: ' . $credentialsFile);

        try {
            $this->file->filePutContents($credentialsFile, $emailContent);
        } catch (FileSystemException $e) {
            throw new StepException($e->getMessage(), Error::DEPLOY_FILE_CREDENTIALS_EMAIL_NOT_WRITABLE, $e);
        }
    }
}
