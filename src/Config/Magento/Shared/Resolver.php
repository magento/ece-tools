<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Magento\Shared;

use Magento\MagentoCloud\Filesystem\ConfigFileList;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;

/**
 * Resolves the correct config file depending on Magento version
 *
 * Possible files: app/etc/config.php or app/etc/config.php.local
 *
 * @deprecated should be removed with dropping Magento 2.1 support
 */
class Resolver
{
    /**
     * @var ConfigFileList
     */
    private $configFileList;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var File
     */
    private $file;

    /**
     * @param ConfigFileList $configFileList
     * @param MagentoVersion $magentoVersion
     * @param File $file
     */
    public function __construct(ConfigFileList $configFileList, MagentoVersion $magentoVersion, File $file)
    {
        $this->configFileList = $configFileList;
        $this->magentoVersion = $magentoVersion;
        $this->file = $file;
    }

    /**
     * @throws UndefinedPackageException
     */
    public function getPath(): string
    {
        return $this->magentoVersion->isGreaterOrEqual('2.2')
            ? $this->configFileList->getConfig()
            : $this->configFileList->getConfigLocal();
    }

    /**
     * @return array
     * @throws UndefinedPackageException
     */
    public function read(): array
    {
        $configPath = $this->getPath();

        if (!$this->file->isExists($configPath)) {
            return [];
        }

        $content = $this->file->requireFile($configPath);

        return is_array($content) ? $content : [];
    }
}
