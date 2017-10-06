<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use \Psr\Log\LoggerInterface;

/**
 * Class Logger
 */
class Logger
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var string Path to the deploy log file
     */
    private $deployLogPath;

    /**
     * @var string Path to the backup build log file
     */
    private $backupBuildLogPath;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
        $magentoRoot = $this->directoryList->getMagentoRoot();
        $this->deployLogPath = $magentoRoot . '/var/log/cloud.log';
        $this->backupBuildLogPath = $magentoRoot . '/init/var/log/cloud.log';

        $formatter = new LineFormatter("[%datetime%] %level_name%: %message% %context% %extra%\n", true, true);
        $this->logger = new \Monolog\Logger('default', [
            (new StreamHandler($this->deployLogPath))
                ->setFormatter($formatter),
            (new StreamHandler('php://stdout'))
                ->setFormatter($formatter),
        ]);
    }

    /**
     * @return string
     */
    public function getDeployLogPath(): string
    {
        return $this->deployLogPath;
    }

    /**
     * @return string
     */
    public function getBackupBuildLogPath(): string
    {
        return $this->backupBuildLogPath;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
