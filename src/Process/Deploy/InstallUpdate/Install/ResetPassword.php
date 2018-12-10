<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate\Install;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Process\ProcessException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\View\RendererInterface;
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
     * @var RendererInterface
     */
    private $renderer;

    /**
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param UrlManager $urlManager
     * @param File $file
     * @param DirectoryList $directoryList
     * @param RendererInterface $renderer
     */
    public function __construct(
        LoggerInterface $logger,
        Environment $environment,
        UrlManager $urlManager,
        File $file,
        DirectoryList $directoryList,
        RendererInterface $renderer
    ) {
        $this->logger = $logger;
        $this->environment = $environment;
        $this->urlManager = $urlManager;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->renderer = $renderer;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($this->environment->getAdminPassword() || !$this->environment->getAdminEmail()) {
            return;
        }

        $credentialsFile = $this->directoryList->getMagentoRoot() . '/var/credentials_email.txt';
        $urls = $this->urlManager->getUrls();
        $adminUrl = $urls['secure'][''] . ($this->environment->getAdminUrl() ?: Environment::DEFAULT_ADMIN_URL);
        $adminEmail = $this->environment->getAdminEmail();
        $adminUsername = $this->environment->getAdminUsername() ?: Environment::DEFAULT_ADMIN_NAME;
        $adminName = $this->environment->getAdminFirstname();

        $emailContent = $this->renderer->render('reset_password.html.twig', [
            'admin_url' => $adminUrl,
            'admin_email' => $adminEmail,
            'admin_name' => $adminName ?: $adminUsername,
        ]);

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
        } catch (FileSystemException $exception) {
            throw new ProcessException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
