<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\DeployStaticContent;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
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
     * @var Adapter
     */
    private $adapter;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param Adapter $adapter
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        Environment $environment,
        Adapter $adapter,
        DirectoryList $directoryList
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->adapter = $adapter;
        $this->directoryList = $directoryList;
    }

    public function execute()
    {
        $this->shell->execute(
            'touch ' . $this->directoryList->getMagentoRoot() . '/pub/static/deployed_version.txt'
        );
        /* Enable maintenance mode */
        $this->logger->notice('Enabling Maintenance mode.');
        $this->shell->execute("php ./bin/magento maintenance:enable {$this->environment->getVerbosityLevel()}");

        /* Generate static assets */
        $this->logger->notice('Extract locales');

        $excludeThemesOptions = '';
        if ($this->environment->getStaticDeployExcludeThemes()) {
            $themes = preg_split("/[,]+/", $this->environment->getStaticDeployExcludeThemes());
            if (count($themes) > 1) {
                $excludeThemesOptions = "--exclude-theme=" . implode(' --exclude-theme=', $themes);
            } elseif (count($themes) === 1) {
                $excludeThemesOptions = "--exclude-theme=" . $themes[0];
            }
        }

        $jobsOption = $this->environment->getStaticDeployThreadsCount()
            ? "--jobs={$this->environment->getStaticDeployThreadsCount()}"
            : '';

        $locales = implode(' ', $this->getLocales());
        $logMessage = $locales ? "Generating static content for locales: $locales" : "Generating static content.";
        $this->logger->notice($logMessage);

        // @codingStandardsIgnoreStart
        $this->shell->execute(
            "php ./bin/magento setup:static-content:deploy  -f $jobsOption $excludeThemesOptions $locales {$this->environment->getVerbosityLevel()}"
        );
        // @codingStandardsIgnoreEnd

        /* Disable maintenance mode */
        $this->shell->execute("php ./bin/magento maintenance:disable {$this->environment->getVerbosityLevel()}");
        $this->logger->notice('Maintenance mode is disabled.');
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
    private function getLocales()
    {
        $locales = [];

        $query = 'SELECT value FROM core_config_data WHERE path=\'general/locale/code\' '
            . 'UNION SELECT interface_locale FROM admin_user;';
        $output = $this->adapter->execute($query);

        if (is_array($output) && count($output) > 1) {
            //first element should be shifted as it is the name of column
            array_shift($output);
            $locales = $output;

            if (!in_array($this->environment->getAdminLocale(), $locales)) {
                $locales[] = $this->environment->getAdminLocale();
            }
        }

        return $locales;
    }
}
