<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\DB\Adapter;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\StaticContentCleaner;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class DeployStaticContent implements ProcessInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var StaticContentCleaner
     */
    private $staticContentCleaner;

    /**
     * @param Environment $environment
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param Adapter $adapter
     */
    public function __construct(
        Environment $environment,
        ShellInterface $shell,
        LoggerInterface $logger,
        Adapter $adapter,
        StaticContentCleaner $staticContentCleaner
    ) {
        $this->environment = $environment;
        $this->shell = $shell;
        $this->logger = $logger;
        $this->adapter = $adapter;
        $this->staticContentCleaner = $staticContentCleaner;
    }

    /**
     * This function deploys the static content.
     * Moved this from processMagentoMode() to its own function because we changed the order to have
     * processMagentoMode called before the install.  Static content deployment still needs to happen after install.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->logger->info('Application mode is ' . $this->environment->getApplicationMode());

        if ($this->environment->getApplicationMode() == Environment::MAGENTO_PRODUCTION_MODE) {
            /* Workaround for MAGETWO-58594: disable redis cache before running static deploy, re-enable after */
            if ($this->environment->isDeployStaticContent()) {
                $this->deployStaticContent();
            }
        }
    }

    private function deployStaticContent()
    {
        // Clear old static content if necessary
        if ($this->environment->doCleanStaticFiles()) {
            $this->staticContentCleaner->clean();
        }
        $this->logger->info('Generating fresh static content');
        $this->generateFreshStaticContent();
    }

    private function generateFreshStaticContent()
    {
        $this->shell->execute('touch ' . MAGENTO_ROOT . 'pub/static/deployed_version.txt');
        /* Enable maintenance mode */
        $this->logger->info('Enabling Maintenance mode.');
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
