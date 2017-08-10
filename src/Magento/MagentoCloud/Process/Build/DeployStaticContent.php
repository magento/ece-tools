<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Config\Environment;
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
     * @var Environment
     */
    private $environment;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param BuildConfig $buildConfig
     * @param File $file
     * @param Environment $environment
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        BuildConfig $buildConfig,
        File $file,
        Environment $environment
    ) {
        $this->logger = $logger;
        $this->file = $file;
        $this->shell = $shell;
        $this->buildConfig = $buildConfig;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $configFile = Environment::MAGENTO_ROOT . 'app/etc/config.php';
        if (!$this->file->isExists($configFile) || $this->buildConfig->get(BuildConfig::BUILD_OPT_SKIP_SCD)) {
            $this->logger->notice('Skipping static content deploy');
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
            $this->environment->setStaticDeployInBuild(false);

            return;
        }

        $SCDLocales = implode(' ', $locales);

        $excludeThemesOptions = '';
        if ($this->buildConfig->get(BuildConfig::BUILD_OPT_SCD_EXCLUDE_THEMES)) {
            $themes = preg_split("/[,]+/", $this->buildConfig->get(BuildConfig::BUILD_OPT_SCD_EXCLUDE_THEMES));
            if (count($themes) > 1) {
                $excludeThemesOptions = "--exclude-theme=" . implode(' --exclude-theme=', $themes);
            } elseif (count($themes) === 1) {
                $excludeThemesOptions = "--exclude-theme=" . $themes[0];
            }
        }

        $threads = (int)$this->buildConfig->get(BuildConfig::BUILD_OPT_SCD_THREADS, 0);

        try {
            $logMessage = $SCDLocales
                ? "Generating static content for locales: $SCDLocales"
                : "Generating static content.";
            $logMessage .= $excludeThemesOptions ? "\nExcluding Themes: $excludeThemesOptions" : "";
            $logMessage .= $threads ? "\nUsing $threads Threads" : "";

            $this->logger->info($logMessage);

            $parallelCommands = "";
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

            $this->environment->setStaticDeployInBuild(true);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            exit(5);
        }
    }

    /**
     * @param array $array
     * @param string $prefix
     * @return array
     */
    private function flatten($array, $prefix = '') : array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->flatten($value, $prefix . $key . '/');
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param array $array
     * @param string $pattern
     * @param bool $ending
     * @return array
     */
    private function filter($array, $pattern, $ending = true) : array
    {
        $filteredResult = [];
        $length = strlen($pattern);
        foreach ($array as $key => $value) {
            if ($ending) {
                if (substr($key, -$length) === $pattern) {
                    $filteredResult[$key] = $value;
                }
            } else {
                if (substr($key, 0, strlen($pattern)) === $pattern) {
                    $filteredResult[$key] = $value;
                }
            }
        }

        return array_unique(array_values($filteredResult));
    }
}
