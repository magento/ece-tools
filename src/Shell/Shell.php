<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Shell;

use Magento\MagentoCloud\App\Logger\Sanitizer;
use Magento\MagentoCloud\Filesystem\SystemList;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\LogicException;

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
     * @var Sanitizer
     */
    private $sanitizer;

    /**
     * @param LoggerInterface $logger
     * @param SystemList $systemList
     * @param ProcessFactory $processFactory
     * @param Sanitizer $sanitizer
     */
    public function __construct(
        LoggerInterface $logger,
        SystemList $systemList,
        ProcessFactory $processFactory,
        Sanitizer $sanitizer
    ) {
        $this->logger = $logger;
        $this->systemList = $systemList;
        $this->processFactory = $processFactory;
        $this->sanitizer = $sanitizer;
    }

    /**
     * {@inheritdoc}
     *
     * If your command contains pipe please use the next construction for correct logging:
     *
     * ```php
     * $this->shell->execute('/bin/bash -c "set -o pipefail; firstCommand | secondCommand"');
     * ```
     *
     *  `commandline` should be always a string as symfony/process package v2.x doesn't support array-type `commandLine`
     */
    public function execute(string $command, array $args = []): ProcessInterface
    {
        try {
            if ($args) {
                $command .= ' ' . implode(' ', array_map('escapeshellarg', $args));
            }

            $process = $this->processFactory->create([
                'commandline' => $command,
                'cwd' => $this->systemList->getMagentoRoot(),
                'timeout' => null
            ]);

            $this->logger->debug($process->getCommandLine());

            $process->execute();
        } catch (ProcessException $e) {
            throw new ShellException(
                $this->sanitizer->sanitize($e->getMessage()),
                $e->getCode()
            );
        }

        $this->handleOutput($process);

        return $process;
    }

    /**
     * Logs command output
     *
     * @param ProcessInterface $process
     * @return void
     */
    private function handleOutput(ProcessInterface $process)
    {
        try {
            if ($output = $process->getOutput()) {
                $this->logger->debug($output);
            }
        } catch (LogicException $exception) {
            $this->logger->error('Can\'t get command output: ' . $exception->getMessage());
        }
    }
}
