<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Docker\Build;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Docker\ComposeInterface;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Yaml\Yaml;

/**
 * Writes Docker configuration.
 */
class Writer
{
    /**
     * @var File
     */
    private $file;

    /**
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Write configuration to file.
     *
     * @param ComposeInterface $compose
     * @param Repository $config
     * @throws ConfigurationMismatchException
     * @throws FileSystemException
     */
    public function write(ComposeInterface $compose, Repository $config)
    {
        $this->file->filePutContents(
            $compose->getPath(),
            Yaml::dump($compose->build($config), 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
        );
    }
}
