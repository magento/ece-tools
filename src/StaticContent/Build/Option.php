<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\StaticContent\Build;

use Magento\MagentoCloud\Config\AdminDataInterface;
use Magento\MagentoCloud\Config\Stage\BuildInterface;
use Magento\MagentoCloud\Config\Magento\Shared\Resolver;
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
     * @var AdminDataInterface
     */
    private $adminData;

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
     * @var Resolver
     */
    private $resolver;

    /**
     * @param AdminDataInterface $adminData
     * @param ArrayManager $arrayManager
     * @param MagentoVersion $magentoVersion
     * @param ThreadCountOptimizer $threadCountOptimizer
     * @param BuildInterface $stageConfig
     * @param Resolver $resolver
     */
    public function __construct(
        AdminDataInterface $adminData,
        ArrayManager $arrayManager,
        MagentoVersion $magentoVersion,
        ThreadCountOptimizer $threadCountOptimizer,
        BuildInterface $stageConfig,
        Resolver $resolver
    ) {
        $this->adminData = $adminData;
        $this->magentoVersion = $magentoVersion;
        $this->arrayManager = $arrayManager;
        $this->threadCountOptimizer = $threadCountOptimizer;
        $this->stageConfig = $stageConfig;
        $this->resolver = $resolver;
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
        $config = $this->resolver->read();
        $flattenedConfig = $this->arrayManager->flatten($config);

        $locales = [$this->adminData->getLocale()];
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

    /**
     * @inheritdoc
     */
    public function hasNoParent(): bool
    {
        return $this->stageConfig->get(BuildInterface::VAR_SCD_NO_PARENT);
    }
}
