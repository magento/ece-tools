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
     * @inheritdoc
     */
    public function execute(string $command)
    {
        $this->logger->info('Command: ' . $command);

        $rootPathCommand = sprintf(
            'cd %s && %s',
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

/*

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w"),  // stderr
        );
        $process = proc_open($rootPathCommand, $descriptorspec, $pipes);
        $output = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        return $output;
*/
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
