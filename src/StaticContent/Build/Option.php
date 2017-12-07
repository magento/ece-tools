<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\StageConfigInterface;
use Magento\MagentoCloud\Filesystem\FileList;
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
     * @var FileList
     */
    private $fileList;

    /**
     * @var BuildConfig
     */
    private $buildConfig;

    /**
     * @var ThreadCountOptimizer
     */
    private $threadCountOptimizer;

    /**
     * @var StageConfigInterface
     */
    private $stageConfig;

    /**
     * @param Environment $environment
     * @param ArrayManager $arrayManager
     * @param MagentoVersion $magentoVersion
     * @param FileList $fileList
     * @param BuildConfig $buildConfig
     * @param ThreadCountOptimizer $threadCountOptimizer
     * @param StageConfigInterface $stageConfig
     */
    public function __construct(
        Environment $environment,
        ArrayManager $arrayManager,
        MagentoVersion $magentoVersion,
        FileList $fileList,
        BuildConfig $buildConfig,
        ThreadCountOptimizer $threadCountOptimizer,
        StageConfigInterface $stageConfig
    ) {
        $this->environment = $environment;
        $this->magentoVersion = $magentoVersion;
        $this->arrayManager = $arrayManager;
        $this->fileList = $fileList;
        $this->buildConfig = $buildConfig;
        $this->threadCountOptimizer = $threadCountOptimizer;
        $this->stageConfig = $stageConfig;
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
        return $this->stageConfig->getBuild(StageConfigInterface::VAR_SCD_STRATEGY);
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
        $configPath = $this->fileList->getConfig();
        $configuration = require $configPath;
        $flattenedConfig = $this->arrayManager->flatten($configuration);

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
