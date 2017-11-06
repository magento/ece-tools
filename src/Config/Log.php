<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Config\Log\Reader;
use Illuminate\Config\Repository;

/**
 * Log configuration.
 */
class Log
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var array
     */
    private $config;

    /**
     * @param FileList $fileList
     * @param Reader $reader
     */
    public function __construct(FileList $fileList, Reader $reader)
    {
        $this->fileList = $fileList;
        $this->reader = $reader;
    }

    /**
     * @return array
     */
    public function getHandlers(): array
    {
        return array_keys($this->getConfig());
    }

    /**
     * @param string $handler
     * @return Repository
     */
    public function get(string $handler): Repository
    {
        return new Repository($this->getConfig()[$handler]);
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        if ($this->config === null) {
            $this->config = [
                'stream' => ['stream' => 'php://stdout'],
                'file' => ['stream' => $this->fileList->getDeployLog()],
            ];

            $this->config += $this->reader->read();
        }

        return $this->config;
    }
}
