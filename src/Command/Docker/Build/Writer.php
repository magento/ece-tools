<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Command\Docker\Build;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Docker\ComposeInterface;
use Magento\MagentoCloud\Docker\Config\DistGenerator;
use Magento\MagentoCloud\Docker\ConfigurationMismatchException;
use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Symfony\Component\Yaml\Yaml;

class Writer
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var DistGenerator
     */
    private $distGenerator;

    /**
     * @param File $file
     * @param DistGenerator $distGenerator
     */
    public function __construct(File $file, DistGenerator $distGenerator)
    {
        $this->file = $file;
        $this->distGenerator = $distGenerator;
    }

    /**
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

        $this->distGenerator->generate();
    }
}
