<?php

declare(strict_types=1);

namespace Magento\MagentoCloud\Filesystem;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Filesystem\DirectoryList;

class MountList
{
    /** @var Environment */
    private $environment;

    /** @var DirectoryList */
    private $directory;

    /**
     * @param Environment   $environment
     * @param DirectoryList $directory
     */
    public function __construct(Environment $environment, DirectoryList $directory)
    {
        $this->environment = $environment;
        $this->directory = $directory;
    }

    /**
     * @return string[]
     */
    public function getMountedDirectories(): array
    {
        $appData = $this->environment->getApplication();

        $mountsFull = $appData['mounts'];

        // Remove the metadata and only return the paths.
        $mountsSlash = array_keys($mountsFull);

        // Change the mount path strings with a leading slash into absolute path strings.
        return array_map(function ($mount) {
            return $this->directory->getMagentoRoot() . $mount;
        }, $mountsSlash);
    }
}
