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
     */
    public function getThemes(): array
    {
        if (empty($this->themes)) {
            $this->themes = array_merge(
                $this->file->glob($this->directoryList->getPath(DirectoryList::DIR_DESIGN, true) . '/*/*/*/theme.xml'),
                $this->file->glob($this->directoryList->getPath(DirectoryList::DIR_VENDOR, true) . '/*/*/theme.xml')
            );
            foreach ($this->themes as &$themePath) {
                $themePath = $this->getThemeName(substr($themePath, 0, strrpos($themePath, 'theme.xml')));
            }
        }

        return $this->themes;
    }

    /**
     * Parses the registration file of the theme to get the name of the theme as registered.
     * @param string $themePath
     * @return string
     */
    public function getThemeName(string $themePath): string
    {
        try {
            $registrationFile = $this->file->fileGetContents($themePath . 'registration.php');
            $registrationParts = explode(PHP_EOL, $registrationFile);
            $themeName = $registrationParts[
                array_search(
                    '\Magento\Framework\Component\ComponentRegistrar::THEME,',
                    array_map('trim', $registrationParts)
                ) + 1
            ];

            return preg_replace(
                '/[^a-zA-Z\/]/',
                '',
                substr($themeName, strpos($themeName, '/') + 1)
            );
        } catch (FileSystemException $exception) {
            $this->logger->warning('Unable to find registration.php for theme '. $themePath);
            return '';
        }
    }
}
