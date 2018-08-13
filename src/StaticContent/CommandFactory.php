<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

use Magento\MagentoCloud\Config\GlobalSection;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Creates static deploy command
 */
class CommandFactory
{
    /**
     * A composer version constraint of versions that cannot use a static content deployment strategy.
     */
    const NO_SCD_VERSION_CONSTRAINT = '<2.2';

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var GlobalSection
     */
    private $globalConfig;

    /**
     * @param MagentoVersion $magentoVersion
     * @param GlobalSection $globalConfig
     */
    public function __construct(MagentoVersion $magentoVersion, GlobalSection $globalConfig)
    {
        $this->magentoVersion = $magentoVersion;
        $this->globalConfig = $globalConfig;
    }

    /**
     * Creates static deploy command based on given options
     *
     * @param OptionInterface $option
     * @param array $excludedThemes
     * @return string[]
     */
    public function create(OptionInterface $option, array $excludedThemes = []): array
    {
        $args = $this->build($option);
        $excludedThemes = array_unique(array_merge($option->getExcludedThemes(), $excludedThemes));

        if ($excludedThemes) {
            $args = array_merge($args, array_map(
                function ($theme) {
                    return '--exclude-theme=' . $theme;
                },
                $excludedThemes
            ));
        }

        if ($locales = $option->getLocales()) {
            $args = array_merge($args, $locales);
        }

        return $args;
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
        $argCollection = [
            $this->create($option, array_keys($matrix)),
        ];

        foreach ($matrix as $theme => $config) {
            if (empty($config['language'])) {
                continue;
            }

            $args = $this->build($option);
            $args[] = '--theme=' . $theme;
            $args = array_merge($args, $config['language']);

            $argCollection[] = $args;
        }

        return $argCollection;
    }

    /**
     * @param OptionInterface $option
     * @return string[]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function build(OptionInterface $option): array
    {
        $args = [];

        if ($option->isForce()) {
            $args[] = '-f';
        }

        if (!$this->magentoVersion->satisfies(static::NO_SCD_VERSION_CONSTRAINT)) {
            // Magento 2.1 doesn't have a "-s" option and can't take a strategy option.
            $strategy = $option->getStrategy();
            if (!empty($strategy)) {
                $args[] = '--strategy=' . $strategy;
            }
        }

        $verbosityLevel = $option->getVerbosityLevel();
        if ($verbosityLevel) {
            $args[] = $verbosityLevel;
        }

        $threadCount = $option->getThreadCount();
        if ($threadCount) {
            $args[] = '--jobs=' . $threadCount;
        }

        if (!$this->magentoVersion->satisfies(static::NO_SCD_VERSION_CONSTRAINT)
            && $this->globalConfig->get(GlobalSection::VAR_SKIP_HTML_MINIFICATION)
        ) {
            $args[] = '--no-html-minify';
        }

        return $args;
    }
}
