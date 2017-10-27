<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\StaticContent\OptionInterface;
use Magento\MagentoCloud\StaticContent\ThreadCountOptimizer;
use Magento\MagentoCloud\Util\ArrayManager;
use Magento\MagentoCloud\Config\Build as BuildConfig;

/**
 * Options for static deploy command in deploy process
 */
class Option implements OptionInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @var ThreadCountOptimizer
     */
    private $threadCountOptimizer;

    /**
     * @param Environment $environment
     * @param ArrayManager $arrayManager
     * @param MagentoVersion $magentoVersion
     * @param DirectoryList $directoryList
     * @param BuildConfig $buildConfig
     * @param ThreadCountOptimizer $threadCountOptimizer
     */
    public function __construct(
        Environment $environment,
        ArrayManager $arrayManager,
        MagentoVersion $magentoVersion,
        DirectoryList $directoryList,
        BuildConfig $buildConfig,
        ThreadCountOptimizer $threadCountOptimizer
    ) {
        $this->environment = $environment;
        $this->magentoVersion = $magentoVersion;
        $this->arrayManager = $arrayManager;
        $this->directoryList = $directoryList;
        $this->buildConfig = $buildConfig;
        $this->threadCountOptimizer = $threadCountOptimizer;
    }

    /**
     * @inheritdoc
     */
    public function getTreadCount(): int
    {
        return $this->threadCountOptimizer->optimize(
            (int)$this->buildConfig->get(BuildConfig::OPT_SCD_THREADS, 1),
            $this->getStrategy()
        );
    }

    /**
     * @inheritdoc
     */
    public function getExcludedThemes(): array
    {
        $themes = preg_split('/[,]+/', $this->buildConfig->get(BuildConfig::OPT_SCD_EXCLUDE_THEMES));

        return array_filter(array_map('trim', $themes));
    }

    /**
     * @inheritdoc
     */
    public function getStrategy(): string
    {
        return $this->buildConfig->get(BuildConfig::OPT_SCD_STRATEGY, '');
    }

    /**
     * @inheritdoc
     */
    public function isForce(): bool
    {
        return $this->magentoVersion->isGreaterOrEqual('2.2');
    }

    /**
     * @inheritdoc
     */
    public function getLocales(): array
    {
        $configPath = require $this->directoryList->getMagentoRoot() . '/app/etc/config.php';
        $flattenedConfig = $this->arrayManager->flatten($configPath);

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
     * @inheritdoc
     */
    public function getVerbosityLevel(): string
    {
        return $this->buildConfig->getVerbosityLevel();
    }
}
