<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Environment;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Psr\Log\LoggerInterface;

class DeployStaticContent implements ProcessInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var File
     */
    private $file;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param BuildConfig $buildConfig
     * @param File $file
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        BuildConfig $buildConfig,
        File $file
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->shell = $shell;
        $this->buildConfig = $buildConfig;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $configFile = Environment::MAGENTO_ROOT . 'app/etc/config.php';
        if (!$this->file->isExists($configFile) || $this->buildConfig->get(BuildConfig::BUILD_OPT_SKIP_SCD)) {
            $this->logger->info('Skipping static content deploy');
        }

        $config = include $configFile;

        $flattenedConfig = $this->flatten($config);
        $websites = $this->filter($flattenedConfig, 'scopes/websites', false);
        $stores = $this->filter($flattenedConfig, 'scopes/stores', false);

        $locales = [];
        $locales = array_merge($locales, $this->filter($flattenedConfig, 'general/locale/code'));
        $locales = array_merge(
            $locales,
            $this->filter($flattenedConfig, 'admin_user/locale/code', false)
        );
        $locales[] = 'en_US';
        $locales = array_unique($locales);

        if (count($stores) === 0 && count($websites) === 0) {
            $this->logger->info("No stores/website/locales found in config.php");
            $this->env->setStaticDeployInBuild(false);

            return;
        }

        $SCDLocales = implode(' ', $locales);

        $excludeThemesOptions = '';
        if ($this->buildConfig->get(BuildConfig::BUILD_OPT_SCD_EXCLUDE_THEMES)) {
            $themes = preg_split("/[,]+/", $this->getBuildOption(self::BUILD_OPT_SCD_EXCLUDE_THEMES));
            if (count($themes) > 1) {
                $excludeThemesOptions = "--exclude-theme=" . implode(' --exclude-theme=', $themes);
            } elseif (count($themes) === 1) {
                $excludeThemesOptions = "--exclude-theme=" . $themes[0];
            }
        }

        $threads = $this->getBuildOption(self::BUILD_OPT_SCD_THREADS)
            ? "{$this->getBuildOption(self::BUILD_OPT_SCD_THREADS)}"
            : '0';

        try {
            $logMessage = $SCDLocales
                ? "Generating static content for locales: $SCDLocales"
                : "Generating static content.";
            $logMessage .= $excludeThemesOptions ? "\nExcluding Themes: $excludeThemesOptions" : "";
            $logMessage .= $threads ? "\nUsing $threads Threads" : "";

            $this->logger->info($logMessage);

            $parallelCommands = "";
            foreach ($locales as $locale) {
                // @codingStandardsIgnoreStart
                $parallelCommands .= "php ./bin/magento setup:static-content:deploy -f $excludeThemesOptions $locale {$this->verbosityLevel}" . '\n';
                // @codingStandardsIgnoreEnd
            }
            $this->env->execute("printf '$parallelCommands' | xargs -I CMD -P " . (int)$threads . " bash -c CMD");


            $this->env->setStaticDeployInBuild(true);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
            exit(5);
        }
    }
}
