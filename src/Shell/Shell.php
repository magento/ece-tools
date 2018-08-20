<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Shell;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

/**
 * @inheritdoc
 */
class Shell implements ShellInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     */
    public function __construct(LoggerInterface $logger, DirectoryList $directoryList)
    {
        $this->logger = $logger;
        $this->directoryList = $directoryList;
    }

    /**
     * {@inheritdoc}
     *
     * If your command contains pipe please use the next construction for correct logging:
     *
     * ```php
     * $this->shell->execute('/bin/bash -c "set -o pipefail; firstCommand | secondCommand"');
     * ```
     */
    public function execute(string $command)
    {
        $this->logger->info($command);

        $rootPathCommand = sprintf(
            'cd %s && %s 2>&1',
            $this->directoryList->getMagentoRoot(),
            $command
        );

        exec($rootPathCommand, $output, $status);

        if ($output) {
            $message = array_reduce(
                $output,
                function ($message, $line) {
                    return $message . PHP_EOL . '  ' . $line;
                },
                ''
            );

            $this->logger->log($status != 0 ? Logger::CRITICAL : Logger::DEBUG, $message);
        }

        if ($status != 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }

        return $output;
    }
}
