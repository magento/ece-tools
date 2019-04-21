<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Package\MagentoVersion;
use Psr\Log\LoggerInterface;

/**
 * Creates static deploy command
 */
class CommandFactory
{
    /**
     * A composer version constraint of versions which can use a static content deployment strategy
     * and max-execution-time option
     */
    const SCD_VERSION_CONSTRAINT = '>=2.2';

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var GlobalSection
     */
    private $globalConfig;

    /**
     * @var ThemeResolver
     */
    private $themeResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param MagentoVersion $magentoVersion
     * @param GlobalSection $globalConfig
     * @param ThemeResolver $themeResolver
     * @param LoggerInterface $logger
     */
    public function __construct(
        MagentoVersion $magentoVersion,
        GlobalSection $globalConfig,
        ThemeResolver $themeResolver,
        LoggerInterface $logger
    ) {
        $this->magentoVersion = $magentoVersion;
        $this->globalConfig = $globalConfig;
        $this->themeResolver = $themeResolver;
        $this->logger = $logger;
    }

    /**
     * Creates static deploy command based on given options
     *
     * @param OptionInterface $option
     * @param array $excludedThemes
     * @return string
     */
    public function create(OptionInterface $option, array $excludedThemes = []): string
    {
        $command = $this->build($option);
        $excludedThemes = array_unique(array_merge(
            $option->getExcludedThemes(),
            $excludedThemes
        ));
        foreach ($excludedThemes as $key => $aTheme) {
            $excludedThemes[$key] = $this->themeResolver->resolve($aTheme);
            if ('' === $excludedThemes[$key]) {
                unset($excludedThemes[$key]);
            }
        }
        if ($excludedThemes) {
            $command .= ' --exclude-theme ' . implode(' --exclude-theme ', $excludedThemes);
        }
        if ($locales = $option->getLocales()) {
            $command .= ' ' . implode(' ', $locales);
        }

        return $command;
    }

    /**
     * Creates set of SCD deployment commands within given matrix.
     *
     * @param OptionInterface $option
     * @param array $matrix
     * @return array
     */
    public function matrix(OptionInterface $option, array $matrix): array
    {
        $commands = [
            $this->create($option, array_keys($matrix)),
        ];

        foreach ($matrix as $theme => $config) {
            if (empty($config['language'])) {
                continue;
            }

            $resolvedTheme = $this->themeResolver->resolve($theme);
            if ('' === $resolvedTheme) {
                $this->logger->warning('Unable to resolve ' . $theme . ' to an available theme.');
                continue;
            }

            $command = $this->build($option);
            $command .= ' --theme ' . $resolvedTheme;
            $command .= ' ' . implode(' ', $config['language']);

            $commands[] = $command;
        }

        return $commands;
    }

    /**
     * @param OptionInterface $option
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function build(OptionInterface $option): string
    {
        $command = 'php ./bin/magento setup:static-content:deploy --ansi --no-interaction';

        if ($option->isForce()) {
            $command .= ' -f';
        }

        if ($this->magentoVersion->satisfies(static::SCD_VERSION_CONSTRAINT)) {
            // Magento 2.1 doesn't have a "-s" option and can't take a strategy option.
            $strategy = $option->getStrategy();
            if (!empty($strategy)) {
                $command .= ' -s ' . $strategy;
            }
        }

        $verbosityLevel = $option->getVerbosityLevel();
        if ($verbosityLevel) {
            $command .= ' ' . $verbosityLevel;
        }

        $threadCount = $option->getThreadCount();
        if ($threadCount) {
            $command .= ' --jobs ' . $threadCount;
        }

        if ($this->magentoVersion->satisfies(static::SCD_VERSION_CONSTRAINT)) {
            if ($this->globalConfig->get(GlobalSection::VAR_SKIP_HTML_MINIFICATION)) {
                $command .= ' --no-html-minify';
            }

            $maxExecTime = $option->getMaxExecutionTime();
            if ($maxExecTime !== null) {
                $command .= ' --max-execution-time ' . (int)$maxExecTime;
            }
        }

        return $command;
    }
}
