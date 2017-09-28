<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build\DeployStaticContent;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Shell\ShellInterface;
use Magento\MagentoCloud\Util\ArrayManager;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Config\Build as BuildConfig;

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
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ShellInterface $shell
     * @param LoggerInterface $logger
     * @param Environment $environment
     * @param DirectoryList $directoryList
     * @param BuildConfig $buildConfig
     * @param ArrayManager $arrayManager
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        ShellInterface $shell,
        LoggerInterface $logger,
        Environment $environment,
        DirectoryList $directoryList,
        BuildConfig $buildConfig,
        ArrayManager $arrayManager,
        MagentoVersion $magentoVersion
    ) {
        $this->shell = $shell;
        $this->logger = $logger;
        $this->environment = $environment;
        $this->directoryList = $directoryList;
        $this->buildConfig = $buildConfig;
        $this->arrayManager = $arrayManager;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $locales = $this->getLocales();
        $threads = (int)$this->buildConfig->get(BuildConfig::OPT_SCD_THREADS, 0);

        try {
            $logMessage = 'Generating static content for locales: ' . implode(' ', $locales);
            $excludeThemesOptions = $this->getExcludeThemesOptions();
            $logMessage .= $excludeThemesOptions ? "\nExcluding Themes: $excludeThemesOptions" : '';
            $logMessage .= $threads ? "\nUsing $threads Threads" : '';

            $this->logger->info($logMessage);

            $parallelCommands = '';
            $deployParams = array_merge(
                $this->getDeployParams(),
                [$excludeThemesOptions]
            );

            foreach ($locales as $locale) {
                $deployParamsLocale = array_merge(
                    $deployParams,
                    [$locale]
                );

                $parallelCommands .= sprintf(
                    "php ./bin/magento setup:static-content:deploy %s\n",
                    implode(' ', array_filter($deployParamsLocale))
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
    private function getExcludeThemesOptions(): string
    {
        $excludeThemesOptions = '';
        if ($this->buildConfig->get(BuildConfig::OPT_SCD_EXCLUDE_THEMES)) {
            $themes = preg_split(
                "/[,]+/",
                $this->buildConfig->get(BuildConfig::OPT_SCD_EXCLUDE_THEMES)
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
     * @return array
     */
    private function getLocales(): array
    {
        $flattenedConfig = $this->arrayManager->flatten(
            require $this->directoryList->getMagentoRoot() . '/app/etc/config.php'
        );

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


    /**
     * @return array
     */
    private function getDeployParams(): array
    {
        $params = [];

        if ($this->magentoVersion->isGreaterOrEqual('2.2')) {
            $params[] = '-f';
        }

        return array_merge($params, [
            $this->buildConfig->getVerbosityLevel(),
            $this->buildConfig->getScdStrategy(),
        ]);
    }
}
