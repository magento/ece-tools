<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;
use Magento\MagentoCloud\Util\ArrayManager;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
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
     * @var Environment
     */
    private $environment;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param BuildConfig $buildConfig
     * @param File $file
     * @param Environment $environment
     * @param DirectoryList $directoryList
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        BuildConfig $buildConfig,
        File $file,
        Environment $environment,
        DirectoryList $directoryList,
        ArrayManager $arrayManager
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->shell = $shell;
        $this->buildConfig = $buildConfig;
        $this->environment = $environment;
        $this->directoryList = $directoryList;
        $this->arrayManager = $arrayManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $configFile = $this->directoryList->getMagentoRoot() . '/app/etc/config.php';

        if (!$this->file->isExists($configFile) || $this->buildConfig->get(BuildConfig::BUILD_OPT_SKIP_SCD)) {
            $this->logger->notice('Skipping static content deploy');
            $this->environment->removeFlagStaticContentInBuild();

            return;
        }

        $config = include $configFile;

        $flattenedConfig = $this->arrayManager->flatten($config);
        $websites = $this->arrayManager->filter($flattenedConfig, 'scopes/websites', false);
        $stores = $this->arrayManager->filter($flattenedConfig, 'scopes/stores', false);
        if (count($stores) === 0 && count($websites) === 0) {
            $this->logger->info('Skipping static content deploy. No stores/website/locales found in config.php');
            $this->environment->removeFlagStaticContentInBuild();

            return;
        }

        $locales = $this->getLocales($flattenedConfig);
        $threads = (int)$this->buildConfig->get(BuildConfig::BUILD_OPT_SCD_THREADS, 0);

        try {
            $logMessage = 'Generating static content for locales: ' . implode(' ', $locales);
            $excludeThemesOptions = $this->getExcludeThemesOptions();
            $logMessage .= $excludeThemesOptions ? "\nExcluding Themes: $excludeThemesOptions" : '';
            $logMessage .= $threads ? "\nUsing $threads Threads" : '';

            $this->logger->info($logMessage);

            $parallelCommands = '';
            foreach ($locales as $locale) {
                $parallelCommands .= sprintf(
                    "php ./bin/magento setup:static-content:deploy -f %s %s %s\n",
                    $excludeThemesOptions,
                    $locale,
                    $this->buildConfig->getVerbosityLevel()
                );
            }
            $this->shell->execute(sprintf(
                "printf '%s' | xargs -I CMD -P %d bash -c CMD",
                $parallelCommands,
                $threads
            ));

            $this->environment->setFlagStaticDeployInBuild();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), 5);
        }
    }

    /**
     * @return string
     */
    private function getExcludeThemesOptions()
    {
        $excludeThemesOptions = '';
        if ($this->buildConfig->get(BuildConfig::BUILD_OPT_SCD_EXCLUDE_THEMES)) {
            $themes = preg_split(
                "/[,]+/",
                $this->buildConfig->get(BuildConfig::BUILD_OPT_SCD_EXCLUDE_THEMES)
            );
            if (count($themes) > 1) {
                $excludeThemesOptions = '--exclude-theme=' . implode(' --exclude-theme=', $themes);
            } elseif (count($themes) === 1) {
                $excludeThemesOptions = '--exclude-theme=' . $themes[0];
            }
        }

        return $excludeThemesOptions;
    }

    /**
     * Collects locales for static content deployment
     *
     * @param array $flattenedConfig
     * @return array
     */
    private function getLocales($flattenedConfig): array
    {
        $locales = [$this->environment->getAdminLocale()];
        $locales = array_merge($locales, $this->arrayManager->filter($flattenedConfig, 'general/locale/code'));
        $locales = array_merge(
            $locales,
            $this->arrayManager->filter($flattenedConfig, 'admin_user/locale/code', false)
        );
        $locales[] = 'en_US';
        $locales = array_unique($locales);

        return $locales;
    }
}
