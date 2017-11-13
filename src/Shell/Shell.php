<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Shell;

use Magento\MagentoCloud\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

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
        $this->logger->info('Command: ' . $command);

        $rootPathCommand = sprintf(
            'cd %s && %s 2>&1',
            $this->directoryList->getMagentoRoot(),
            $command
        );

        exec(
            $rootPathCommand,
            $output,
            $status
        );

        $this->logger->info('Status: ' . var_export($status, true));

        if ($output) {
            $this->logger->info('Output: ' . var_export($output, true));
        }

        if ($status != 0) {
            throw new \RuntimeException("Command $command returned code $status", $status);
        }

        return $output;
    }

    /**
     * @inheritdoc
     */
    public function backgroundExecute(string $command)
    {
        $command = "nohup {$command} 1>/dev/null 2>&1 &";

        $this->logger->info('Execute command in background: ' . $command);

        shell_exec($command);
    }
}
