<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Config\Environment\Reader;
use Magento\MagentoCloud\App\Logger\HandlerFactory;

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
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @param FileList $fileList
     * @param Reader $reader
     * @param RepositoryFactory $repositoryFactory
     */
    public function __construct(FileList $fileList, Reader $reader, RepositoryFactory $repositoryFactory)
    {
        $this->fileList = $fileList;
        $this->reader = $reader;
        $this->repositoryFactory = $repositoryFactory;
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
     * @return RepositoryInterface
     * @throws \Exception
     */
    public function get(string $handler): RepositoryInterface
    {
        if (!isset($this->getConfig()[$handler])) {
            throw new \Exception('Configuration for ' . $handler . ' is not found');
        }

        return $this->repositoryFactory->create(
            $this->getConfig()[$handler]
        );
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
