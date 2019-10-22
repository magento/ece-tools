<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud;

/**
 * Provides ability to statically register extensions for ece-tools.
 */
class ExtensionRegistrar
{
    /**
     * @var array
     */
    private static $paths = [];

    /**
     * Sets the location of an extension.
     *
     * @param string $componentName Fully-qualified component name
     * @param string $path Absolute file path to the component
     * @throws \LogicException
     * @return void
     */
    public static function register($componentName, $path): void
    {
        if (isset(self::$paths[$componentName])) {
            throw new \LogicException(sprintf(
                "%s from %s has been already defined in %s",
                $componentName,
                $path,
                self::$paths[$componentName]
            ));
        } else {
            self::$paths[$componentName] = str_replace('\\', '/', $path);
        }
    }

    /**
     * Returns array of registered extensions
     *
     * @return array
     */
    public static function getPaths(): array
    {
        return self::$paths;
    }

    /**
     * Returns extension path by given extension name
     *
     * @param string $componentName
     * @return string|null
     */
    public static function getPath(string $componentName): ?string
    {
        return self::$paths[$componentName] ?? null;
    }
}
