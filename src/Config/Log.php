<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Config\Environment\Reader;
use Magento\MagentoCloud\App\Logger\HandlerFactory;

/**
 * Log configuration.
 */
class Log
{
    const SECTION_CONFIG = 'log';

    /**
     * Log levels.
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_NOTICE = 'notice';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    const LEVEL_ALERT = 'alert';
    const LEVEL_EMERGENCY = 'emergency';

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
     * Returns array of handlers configs with keys as handler name.
     *
     * @return array
     */
    public function getHandlers(): array
    {
        return $this->getConfig();
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

        return $this->repositoryFactory->create(
            $this->getConfig()[$handler]
        );
    }

    /**
     * @return array
     */
    private function getConfig(): array
    {
        if ($this->config === null) {
            $this->config = array_replace_recursive(
                [
                    HandlerFactory::HANDLER_STREAM => ['stream' => 'php://stdout'],
                    HandlerFactory::HANDLER_FILE => ['file' => $this->fileList->getCloudLog()],
                ],
                $this->reader->read()[static::SECTION_CONFIG] ?? []
            );
        }

        return $this->config;
    }
}
