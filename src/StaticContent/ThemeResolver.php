<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\Filesystem\Driver\File;

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
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Takes in name of a theme, compares it against the names and corrects if necessary.
     *
     * @param string $themeName
     * @return string
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
     * @return array
     * @codeCoverageIgnore
     * @throws \ReflectionException
     */
    protected function getThemes(): array
    {
        $this->logger->debug('Finding available themes.');
        if (empty($this->themes)) {
            if (class_exists(\Magento\Framework\Component\ComponentRegistrar::class)) {
                $reflectionClass = new \ReflectionClass(\Magento\Framework\Component\ComponentRegistrar::class);
                $property = $reflectionClass->getProperty('paths');
                $property->setAccessible(true);

                $this->themes = array_keys(
                    $property->getValue($reflectionClass)[\Magento\Framework\Component\ComponentRegistrar::THEME]
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
