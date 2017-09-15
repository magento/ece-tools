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

    public function __construct(
        LoggerInterface $logger,
        UrlManager $urlManager,
        Environment $environment,
        ShellInterface $shell
    ) {
        $this->logger = $logger;
        $this->urlManager = $urlManager;
        $this->environment = $environment;
        $this->shell = $shell;
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
            "php ./bin/magento setup:install \
            --session-save=db \
            --cleanup-database \
            --currency={$this->environment->getDefaultCurrency()} \
            --base-url=$urlUnsecure \
            --base-url-secure=$urlSecure \
            --language={$this->environment->getAdminLocale()} \
            --timezone=America/Los_Angeles \
            --db-host={$this->environment->getDbHost()} \
            --db-name={$this->environment->getDbName()} \
            --db-user={$this->environment->getDbUser()} \
            --backend-frontname={$this->environment->getAdminUrl()} \
            --admin-user={$this->environment->getAdminUsername()} \
            --admin-firstname={$this->environment->getAdminFirstname()} \
            --admin-lastname={$this->environment->getAdminLastname()} \
            --admin-email={$this->environment->getAdminEmail()} \
            --admin-password={$this->environment->getAdminPassword()}";

        $dbPassword = $this->environment->getDbPassword();
        if (strlen($dbPassword)) {
            $command .= " \
            --db-password={$dbPassword}";
        }

        $command .= $this->environment->getVerbosityLevel();

        $this->shell->execute($command);
    }
}
