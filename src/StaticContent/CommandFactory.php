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
    const NO_SCD_VERSION_CONSTRAINT = "<2.2";

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
     * @param array $matrix
     * @return string
     */
    public function create(OptionInterface $option, array $matrix = []): string
    {
        return $matrix ? $this->createByMatrix($option, $matrix) : $this->createRegular($option);
    }

    /**
     * @param OptionInterface $option
     * @return string
     */
    private function createRegular(OptionInterface $option): string
    {
        $command = $this->build($option);
        $excludedThemes = $option->getExcludedThemes();

        if (count($excludedThemes) === 1) {
            $command .= ' --exclude-theme=' . $excludedThemes[0];
        } elseif (count($excludedThemes) > 1) {
            $command .= ' --exclude-theme=' . implode(' --exclude-theme=', $excludedThemes);
        }

        return $command;
    }

    /**
     * @param OptionInterface $option
     * @param array $matrix
     * @return string
     */
    private function createByMatrix(OptionInterface $option, array $matrix): string
    {
        $commands = [];

        foreach ($matrix as $theme => $config) {
            $command = $this->build($option);
            $command .= ' --theme ' . $theme;

            if ($config['language']) {
                foreach ((array)$config['language'] as $language) {
                    $command .= ' --language ' . $language;
                }
            }
        }

        return implode(' && ', $commands);
    }

    /**
     * @param OptionInterface $option
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function build(OptionInterface $option): string
    {
        $command = 'php ./bin/magento setup:static-content:deploy';

        if ($option->isForce()) {
            $command .= ' -f';
        }

        if (!$this->magentoVersion->satisfies(static::NO_SCD_VERSION_CONSTRAINT)) {
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

        $locales = $option->getLocales();
        if (count($locales)) {
            $command .= ' ' . implode(' ', $locales);
        }

        $treadCount = $option->getThreadCount();
        if ($treadCount) {
            $command .= ' --jobs=' . $treadCount;
        }

        if (!$this->magentoVersion->satisfies(static::NO_SCD_VERSION_CONSTRAINT)
            && $this->globalConfig->get(GlobalSection::VAR_SKIP_HTML_MINIFICATION)
        ) {
            $command .= ' --no-html-minify';
        }

        return $command;
    }
}
