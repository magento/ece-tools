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
        $command = $this->createBaseCommand($option);

        $locales = $option->getLocales();
        if (count($locales)) {
            $command .= ' ' . implode(' ', $locales);
        }

        $treadCount = $option->getTreadCount();
        if ($treadCount) {
            $command .= ' --jobs=' . $treadCount;
        }

        return $command;
    }

    /**
     * Creates static deploy command for running in parallel for xargs command
     *
     * @param OptionInterface $option
     * @return string
     * @deprecated This method should be removed after replacing xargs with --jobs parameter in build phase
     */
    public function createParallel(OptionInterface $option): string
    {
        $command = $this->createBaseCommand($option);

        $parallelCommands = '';
        foreach ($option->getLocales() as $locale) {
            $parallelCommands .= $command . ' ' . $locale . PHP_EOL;
        }

        return $parallelCommands;
    }

    /**
     * @param OptionInterface $option
     * @return string
     */
    private function createBaseCommand(OptionInterface $option): string
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

        $strategy = $option->getStrategy();
        if (!empty($strategy)) {
            $command .= ' -s ' . $strategy;
        }

        $verbosityLevel = $option->getVerbosityLevel();
        if ($verbosityLevel) {
            $command .= ' ' . $verbosityLevel;
        }

        return $command;
    }
}
