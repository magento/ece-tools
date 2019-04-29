<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Magento\MagentoCloud\Filesystem\SystemList;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
     * @var SystemList
     */
    private $systemList;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param LoggerInterface $logger
     * @param SystemList $systemList
     * @param ProcessFactory $processFactory
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        LoggerInterface $logger,
        SystemList $systemList,
        ProcessFactory $processFactory,
        ResultFactory $resultFactory
    ) {
        $this->logger = $logger;
        $this->systemList = $systemList;
        $this->processFactory = $processFactory;
        $this->resultFactory = $resultFactory;
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
    public function execute(string $command, array $args = []): ResultInterface
    {
        try {
            $args = array_map('escapeshellarg', array_filter($args));

            if ($args) {
                $command .= ' ' . implode(' ', $args);
            }

            $this->logger->info($command);

            $process = $this->processFactory->create([
                'commandline' => $command,
                'cwd' => $this->systemList->getMagentoRoot(),
                'timeout' => 0
            ]);

            $process->mustRun();
        } catch (ProcessFailedException $e) {
            throw new ShellException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return $this->resultFactory->create($process);
    }
}
