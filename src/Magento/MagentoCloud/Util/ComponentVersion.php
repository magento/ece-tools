<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Util;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

class ComponentVersion
{
    /**
     * We only want to look up each component version once since it shouldn't change
     *
     * @var array
     */
    private $componentVersions = [];

    /**
     * @var File
     */
    private $file;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param File $file
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     */
    public function __construct(
        File $file,
        DirectoryList $directoryList,
        LoggerInterface $logger
    ) {
        $this->file = $file;
        $this->directoryList = $directoryList;
        $this->logger = $logger;
    }

    /**
     * Returns version of component if such component exists
     *
     * @param string $component The name of composer component
     * @param string $vendor
     * @return string|null
     */
    public function get($component, $vendor = 'magento')
    {
        if (isset($this->componentVersions[$component])) {
            return $this->componentVersions[$component];
        }

        $composerJsonPath = sprintf(
            '%s/vendor/%s/%s/composer.json',
            $this->directoryList->getMagentoRoot(),
            $vendor,
            $component
        );

        $version = null;
        try {
            if ($this->file->isExists($composerJsonPath)) {
                $fileContent = $this->file->fileGetContents($composerJsonPath);
                $componentInfo = json_decode($fileContent, true);
                if (is_array($componentInfo) && array_key_exists('version', $componentInfo)) {
                    $version = $componentInfo['version'];
                }
            }
        } catch (\Exception $e) {
            $this->logger->info(
                sprintf('Can\'t read version of component %s : %s', $component, $e->getMessage())
            );
        }

        $this->componentVersions[$component] = $version;
        return $this->componentVersions[$component];
    }
}
