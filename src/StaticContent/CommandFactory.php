<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

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
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(MagentoVersion $magentoVersion)
    {
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Creates static deploy command based on given options
     *
     * @param OptionInterface $option
     * @return string
     */
    public function create(OptionInterface $option): string
    {
        $command = 'php ./bin/magento setup:static-content:deploy';

        if ($option->isForce()) {
            $command .= ' -f';
        }

        $excludedThemes = $option->getExcludedThemes();
        if (count($excludedThemes) == 1) {
            $command .= ' --exclude-theme=' . $excludedThemes[0];
        } elseif (count($excludedThemes) > 1) {
            $command .= ' --exclude-theme=' . implode(' --exclude-theme=', $excludedThemes);
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

        return $command;
    }
}
