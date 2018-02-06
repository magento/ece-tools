<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

/**
 * Creates static deploy command
 */
class CommandFactory
{
    /**
     * Creates static deploy command based on given options
     *
     * @param OptionInterface $option
     * @return string
     */
    public function create(OptionInterface $option): string
    {
        $command = 'php ./bin/magento setup:static-content:deploy';

        $excludedThemes = $option->getExcludedThemes();
        if (count($excludedThemes) == 1) {
            $command .= ' --exclude-theme=' . $excludedThemes[0];
        } elseif (count($excludedThemes) > 1) {
            $command .= ' --exclude-theme=' . implode(' --exclude-theme=', $excludedThemes);
        }

        $strategy = $option->getStrategy();
        if (!empty($strategy)) {
            $command .= ' -s ' . $strategy;
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
