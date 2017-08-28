<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Shell;

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
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $command)
    {
        $this->logger->info('Command: ' . $command);

        $rootPathCommand = sprintf('cd %s && %s', MAGENTO_ROOT, $command);

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
