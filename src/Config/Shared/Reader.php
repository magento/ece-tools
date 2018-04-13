<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Shared;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Filesystem\Reader\ReaderInterface;
use Magento\MagentoCloud\Filesystem\Resolver\SharedConfig;

/**
 * @inheritdoc
 */
class Reader implements ReaderInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var SharedConfig
     */
    private $resolver;

    /**
     * @param File $file
     * @param SharedConfig $resolver
     */
    public function __construct(File $file, SharedConfig $resolver)
    {
        $this->file = $file;
        $this->resolver = $resolver;
    }

    /**
     * @return array
     */
    public function read(): array
    {
        $configPath = $this->resolver->resolve();

        if (!$this->file->isExists($configPath)) {
            return [];
        }

        return require $configPath;
    }
}
