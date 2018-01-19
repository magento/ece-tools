<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\ScdStrategyChecker;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\StaticContent\OptionInterface;
use Magento\MagentoCloud\StaticContent\ThreadCountOptimizer;
use Magento\MagentoCloud\Util\ArrayManager;

/**
 * Options for static deploy command in deploy process
 */
class Option implements OptionInterface
{
    /**
     * @var Environment
     */
    private $environment;

    private $scdStrategyChecker;

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
     * @var ThreadCountOptimizer
     */
    private $threadCountOptimizer;

    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @param Environment $environment
     * @param ArrayManager $arrayManager
     * @param MagentoVersion $magentoVersion
     * @param FileList $fileList
     * @param ThreadCountOptimizer $threadCountOptimizer
     * @param BuildInterface $stageConfig
     */
    public function __construct(
        Environment $environment,
        ScdStrategyChecker $scdStrategyChecker,
        ArrayManager $arrayManager,
        MagentoVersion $magentoVersion,
        FileList $fileList,
        ThreadCountOptimizer $threadCountOptimizer,
        BuildInterface $stageConfig
    ) {
        $this->environment = $environment;
        $this->scdStrategyChecker = $scdStrategyChecker;
        $this->magentoVersion = $magentoVersion;
        $this->arrayManager = $arrayManager;
        $this->fileList = $fileList;
        $this->threadCountOptimizer = $threadCountOptimizer;
        $this->stageConfig = $stageConfig;
    }

    /**
     * @inheritdoc
     */
    public function getThreadCount(): int
    {
        return $this->threadCountOptimizer->optimize(
            (int) $this->stageConfig->get(BuildInterface::VAR_SCD_THREADS),
            $this->getStrategy()
        );
    }

    /**
     * @inheritdoc
     */
    public function getExcludedThemes(): array
    {
        $themes = preg_split('/[,]+/', $this->stageConfig->get(BuildInterface::VAR_SCD_EXCLUDE_THEMES));

        return array_filter(array_map('trim', $themes));
    }

    /**
     * @inheritdoc
     */
    public function getStrategy(): string
    {
        $desiredStrategy = $this->stageConfig->get(BuildInterface::VAR_SCD_STRATEGY);
        $allowedStrategies = $this->stageConfig->get(BuildInterface::VAR_SCD_ALLOWED_STRATEGIES);

        return $this->scdStrategyChecker->getStrategy($desiredStrategy, $allowedStrategies);
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
        return $this->stageConfig->get(BuildInterface::VAR_VERBOSE_COMMANDS);
    }
}
