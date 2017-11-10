<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Config\Environment\Reader;
use Magento\MagentoCloud\App\Logger\HandlerFactory;
use Illuminate\Config\Repository;

/**
 * Log configuration.
 */
class Log
{
    const CONFIG_SECTION = 'log';

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
     * @throws \Exception
     */
    public function get(string $handler): Repository
    {
        if (!isset($this->getConfig()[$handler])) {
            throw new \Exception('Configuration for ' . $handler . ' is not found');
        }

        return new Repository($this->getConfig()[$handler]);
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        if ($this->config === null) {
            $this->config = [
                HandlerFactory::HANDLER_STREAM => ['stream' => 'php://stdout'],
                HandlerFactory::HANDLER_FILE => ['stream' => $this->fileList->getCloudLog()],
            ];

            $this->config += $this->reader->read()[static::CONFIG_SECTION] ?? [];
        }

        return $this->config;
    }
}
