<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Filesystem;

/**
 * Resolver of file configurations.
 */
class FileList
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
    }

    /**
     * @return string
     */
    public function getConfig(): string
    {
        return $this->directoryList->getMagentoRoot() . '/app/etc/config.php';
    }

    /**
     * @return string
     */
    public function getEnv(): string
    {
        return $this->directoryList->getMagentoRoot() . '/app/etc/env.php';
    }
}
