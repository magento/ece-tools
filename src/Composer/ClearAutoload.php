<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Composer;

use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Script\Event;

/**
 * Clears registration.php file of magento/magento-cloud-components package from composer autoload
 *
 * @codeCoverageIgnore
 */
class ClearAutoload
{
    /**
     * @param Event $event
     */
    public static function preAutoloadDump(Event $event)
    {
        $composer = $event->getComposer();

        $generator  = $composer->getAutoloadGenerator();
        $packages   = $composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $packageMap = $generator->buildPackageMap(
            $composer->getInstallationManager(),
            $composer->getPackage(),
            $packages
        );

        foreach ($packageMap as $item) {
            /** @var Package $package */
            $package = reset($item);

            if (!$package instanceof PackageInterface ||
                $package->getName() !== 'magento/magento-cloud-components'
            ) {
                continue;
            }

            $autoload = $package->getAutoload();

            if (!isset($autoload['files'])) {
                continue;
            }

            foreach ($autoload['files'] as $index => $fileName) {
                if ($fileName === 'registration.php') {
                    unset($autoload['files'][$index]);
                }
            }

            $package->setAutoload($autoload);
        }
    }
}
