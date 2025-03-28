<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Illuminate\Contracts\Config\Repository;
use Magento\MagentoCloud\App\Logger\Formatter\ErrorFormatterFactory;
use Magento\MagentoCloud\Filesystem\FileList;
use Magento\MagentoCloud\Config\Environment\ReaderInterface;
use Magento\MagentoCloud\App\Logger\HandlerFactory;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Log configuration.
 */
class Log
{
    public const SECTION_CONFIG = 'log';

    /**
     * Log levels.
     */
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_NOTICE = 'notice';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_ALERT = 'alert';
    public const LEVEL_EMERGENCY = 'emergency';

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var ReaderInterface
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
     * @var ErrorFormatterFactory
     */
    private $errorFormatterFactory;

    /**
     * @param FileList $fileList
     * @param ReaderInterface $reader
     * @param RepositoryFactory $repositoryFactory
     * @param ErrorFormatterFactory $errorFormatterFactory
     */
    public function __construct(
        FileList $fileList,
        ReaderInterface $reader,
        RepositoryFactory $repositoryFactory,
        ErrorFormatterFactory $errorFormatterFactory
    ) {
        $this->fileList = $fileList;
        $this->reader = $reader;
        $this->repositoryFactory = $repositoryFactory;
        $this->errorFormatterFactory = $errorFormatterFactory;
    }

    /**
     * Returns array of handlers configs with keys as handler name.
     *
     * @return array
     * @throws ConfigException
     */
    public function getHandlers(): array
    {
        return $this->getConfig();
    }

    /**
     * @param string $handler
     * @return Repository
     * @throws ConfigException
     */
    public function get(string $handler): Repository
    {
        $config = $this->getConfig();

        if (!isset($config[$handler])) {
            throw new ConfigException('Configuration for ' . $handler . ' is not found');
        }

        return $this->repositoryFactory->create(
            $config[$handler]
        );
    }

    /**
     * @return array
     * @throws ConfigException
     */
    private function getConfig(): array
    {
        try {
            if ($this->config === null) {
                $this->config = array_replace_recursive(
                    [
                        HandlerFactory::HANDLER_STREAM => ['stream' => 'php://stdout'],
                        HandlerFactory::HANDLER_FILE => ['file' => $this->fileList->getCloudLog()],
                        HandlerFactory::HANDLER_FILE_ERROR => [
                            'file' => $this->fileList->getCloudErrorLog(),
                            'min_level' => self::LEVEL_WARNING,
                            'formatter' => $this->errorFormatterFactory->create()
                        ],
                    ],
                    $this->reader->read()[static::SECTION_CONFIG] ?? []
                );
            }
        } catch (ParseException | FileSystemException | UndefinedPackageException $exception) {
            throw new ConfigException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->config;
    }
}
