<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent\Build;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\Resolver\SharedConfig;
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

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var ThreadCountOptimizer
     */
    private $threadCountOptimizer;

    /**
     * @var BuildInterface
     */
    private $stageConfig;

    /**
     * @var SharedConfig
     */
    private $configResolver;

    /**
     * @var File
     */
    private $file;

    /**
     * @param Environment $environment
     * @param ArrayManager $arrayManager
     * @param MagentoVersion $magentoVersion
     * @param ThreadCountOptimizer $threadCountOptimizer
     * @param BuildInterface $stageConfig
     * @param SharedConfig $configResolver
     * @param File $file
     */
    public function __construct(
        Environment $environment,
        ArrayManager $arrayManager,
        MagentoVersion $magentoVersion,
        ThreadCountOptimizer $threadCountOptimizer,
        BuildInterface $stageConfig,
        SharedConfig $configResolver,
        File $file
    ) {
        $this->environment = $environment;
        $this->magentoVersion = $magentoVersion;
        $this->arrayManager = $arrayManager;
        $this->threadCountOptimizer = $threadCountOptimizer;
        $this->stageConfig = $stageConfig;
        $this->configResolver = $configResolver;
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function getThreadCount(): int
    {
        return $this->threadCountOptimizer->optimize(
            (int)$this->stageConfig->get(BuildInterface::VAR_SCD_THREADS),
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
        return $this->stageConfig->get(BuildInterface::VAR_SCD_STRATEGY);
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
        $configPath = $this->configResolver->resolve();
        $configuration = $this->file->isExists($configPath) ? $this->file->requireFile($configPath) : [];
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

    /**
     * @inheritdoc
     */
    public function getMaxExecutionTime()
    {
        return $this->stageConfig->get(BuildInterface::VAR_SCD_MAX_EXEC_TIME);
    }
}
