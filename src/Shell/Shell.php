<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

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
    public function execute(string $command, $args = []): array
    {
        $args = array_map(
            'escapeshellarg',
            array_filter((array)$args)
        );

        if ($args) {
            $command .= ' ' . implode(' ', $args);
        }

        $this->logger->info($command);

        $command = sprintf(
            'cd %s && %s 2>&1',
            $this->directoryList->getMagentoRoot(),
            $command
        );

        exec($command, $output, $status);

        /**
         * Edge case.
         */
        if ($status !== 0 && strpos($command, 'config:show') !== false) {
            return [];
        }

        return $this->handleOutput($command, $output, $status);
    }

    /**
     * @param string $command
     * @param array $output
     * @param int $status
     * @return array
     *
     * @throws ShellException
     */
    private function handleOutput(string $command, array $output, int $status): array
    {
        if ($output) {
            $message = array_reduce(
                $output,
                function ($message, $line) {
                    return $message . PHP_EOL . '  ' . $line;
                },
                ''
            );

            $this->logger->log($status !== 0 ? Logger::CRITICAL : Logger::DEBUG, $message);
        }

        if ($status !== 0) {
            throw new ShellException("Command $command returned code $status", $status);
        }

        return $output;
    }
}
