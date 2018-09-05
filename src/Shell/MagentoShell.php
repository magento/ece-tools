<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Magento\MagentoCloud\Filesystem\SystemList;
use Psr\Log\LoggerInterface;

class MagentoShell implements ShellInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @param LoggerInterface $logger
     * @param SystemList $systemList
     */
    public function __construct(LoggerInterface $logger, SystemList $systemList)
    {
        $this->logger = $logger;
        $this->systemList = $systemList;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $command, array $args = []): array
    {
        $fullCommand = 'php ./bin/magento ' . $command;

        if ($args) {
            $fullCommand .= implode(' ', $args);
        }

        $this->logger->info('Command: ' . $fullCommand);

        $fullCommand = sprintf(
            'cd %s && %s 2>&1',
            $this->systemList->getMagentoRoot(),
            $fullCommand
        );

        exec(
            $fullCommand,
            $output,
            $status
        );

        if ($command === 'config:show') {
            return $output;
        }

        if ($status !== 0) {
            throw new ShellException("Command $command returned code $status", $status);
        }

        return $output;
    }
}
