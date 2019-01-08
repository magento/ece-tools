<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\StaticContent;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\UndefinedPackageException;
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
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var string[]
     */
    private $themes;

    /**
     * @param LoggerInterface $logger
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(LoggerInterface $logger, File $file, DirectoryList $directoryList)
    {
        $this->logger = $logger;
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * Takes in name of a theme, compares it against the names and corrects if necessary.
     *
     * @param string $themeName
     * @return string
     * @throws UndefinedPackageException
     */
    public function resolve(string $themeName): string
    {
        $availableThemes = $this->getThemes();
        if (!in_array($themeName, $availableThemes)) {
            $this->logger->warning('Theme ' . $themeName . ' does not exist.');
            $themeNamePosition = array_search(
                strtolower($themeName),
                array_map('strtolower', $availableThemes)
            );
            if (false !== $themeNamePosition) {
                $this->logger->warning(
                    'Theme found as ' . $availableThemes[$themeNamePosition] . '.  Using corrected name instead.'
                );
                return $availableThemes[$themeNamePosition];
            }
        }
        return '';
    }

    /**
     * @return array
     * @throws UndefinedPackageException
     * @codeCoverageIgnore
     */
    protected function getThemes(): array
    {
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
            }

            return $this->themes;
        }
    }
}
