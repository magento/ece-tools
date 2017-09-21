<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\DeployStaticContent;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\ConnectionInterface;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\PackageManager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class GenerateFresh implements ProcessInterface
{
    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param ConnectionInterface $connection
     * @param File $file
     * @param DirectoryList $directoryList
     * @param PackageManager $packageManager
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        Environment $environment,
        ConnectionInterface $connection,
        File $file,
        DirectoryList $directoryList,
        PackageManager $packageManager
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->connection = $connection;
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->packageManager = $packageManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->file->touch($this->directoryList->getMagentoRoot() . '/pub/static/deployed_version.txt');
        $this->logger->info('Enabling Maintenance mode');
        $this->shell->execute("php ./bin/magento maintenance:enable {$this->environment->getVerbosityLevel()}");
        $this->logger->info('Extracting locales');

        $excludeThemesOptions = $this->getExcludeThemesOptions();
        $jobsOption = $this->environment->getStaticDeployThreadsCount()
            ? "--jobs={$this->environment->getStaticDeployThreadsCount()}"
            : '';
        $locales = implode(' ', $this->getLocales());
        $logMessage = $locales ? "Generating static content for locales: $locales" : 'Generating static content';

        $this->logger->info($logMessage);

        $deployParams = [];

        if ($this->packageManager->hasMagentoVersion('2.2')) {
            $deployParams[] = '-f';
        }

        $deployParams = array_merge($deployParams, [
            $jobsOption,
            $excludeThemesOptions,
            $locales,
            $this->environment->getVerbosityLevel(),
        ]);

        $this->shell->execute(
            'php ./bin/magento setup:static-content:deploy ' .
            implode(' ', $deployParams)
        );

        $this->shell->execute("php ./bin/magento maintenance:disable {$this->environment->getVerbosityLevel()}");
        $this->logger->info('Maintenance mode is disabled.');
    }

    /**
     * Gets locales from DB which are set to stores and admin users.
     * Adds additional default 'en_US' locale to result, if it does't exist yet in defined list.
     *
     * @return array List of locales. Returns empty array in case when no locales are defined in DB
     * ```php
     * [
     *     'en_US',
     *     'fr_FR'
     * ]
     * ```
     */
    private function getLocales(): array
    {
        $output = $this->connection->select(
            'SELECT value FROM core_config_data WHERE path=\'general/locale/code\' '
            . 'UNION SELECT interface_locale FROM admin_user'
        );

        $locales = array_column($output, 'value');

        if (!in_array($this->environment->getAdminLocale(), $locales)) {
            $locales[] = $this->environment->getAdminLocale();
        }

        return $locales;
    }

    /**
     * @return string
     */
    private function getExcludeThemesOptions(): string
    {
        $excludeThemesOptions = '';
        if ($this->environment->getStaticDeployExcludeThemes()) {
            $themes = preg_split("/[,]+/", $this->environment->getStaticDeployExcludeThemes());
            if (count($themes) > 1) {
                $excludeThemesOptions = "--exclude-theme=" . implode(' --exclude-theme=', $themes);
            } elseif (count($themes) === 1) {
                $excludeThemesOptions = "--exclude-theme=" . $themes[0];
            }
        }

        return $excludeThemesOptions;
    }
}
