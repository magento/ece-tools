<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\StaticContent;

use Magento\Framework\Component\ComponentRegistrar;
use Psr\Log\LoggerInterface;

/**
 * Resolves themes to their correct names
 */
class ThemeResolver
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string[]
     */
    private $themes;

    /**
     * Constructor method.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Resolve theme name.
     * Takes in name of a theme, compares it against the names and corrects if necessary.
     *
     * @param string $themeName
     * @return string
     * @throws \ReflectionException
     */
    public function resolve(string $themeName): string
    {
        $availableThemes = $this->getThemes();
        if (!in_array($themeName, $availableThemes)) {
            $this->logger->warning('Theme ' . $themeName . ' does not exist, attempting to resolve.');
            $themeNamePosition = array_search(
                strtolower($themeName),
                array_map('strtolower', $availableThemes)
            );
            if (false !== $themeNamePosition) {
                $this->logger->warning(
                    'Theme found as ' . $availableThemes[$themeNamePosition] . '.  Using corrected name instead.'
                );
                return $availableThemes[$themeNamePosition];
            } else {
                $this->logger->error('Unable to resolve theme.');
                return '';
            }
        }
        return $themeName;
    }

    /**
     * Get available themes.
     *
     * @return array
     * @codeCoverageIgnore
     * @throws \ReflectionException
     */
    protected function getThemes(): array
    {
        $this->logger->debug('Finding available themes.');
        if (empty($this->themes)) {
            if (class_exists(ComponentRegistrar::class)) {
                $reflectionClass = new \ReflectionClass(ComponentRegistrar::class);
                $property        = $reflectionClass->getProperty('paths');

                # Note: setAccessible(true) is deprecated in PHP 8.5 as properties are always accessible in PHP 8.1+
                # so removed the call to setAccessible(true)

                $this->themes = array_keys(
                    $property->getValue($reflectionClass)[ComponentRegistrar::THEME]
                );

                foreach ($this->themes as &$aTheme) {
                    $aTheme = substr(
                        $aTheme,
                        strpos($aTheme, '/') + 1
                    );
                }
            } else {
                $this->logger->warning('Unable to find themes, cannot find Magento class.');
            }
        }
        $this->logger->debug('End of finding available themes.');
        return $this->themes;
    }
}
