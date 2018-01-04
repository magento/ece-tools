<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Wraps command execution to provide unified execution flow.
 */
class Wrapper
{
    const CODE_SUCCESS = 0;
    const CODE_FAILURE = 1;

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
     * Executes CLI command via callback.
     *
     * @param \Closure $closure
     * @param OutputInterface $output
     * @return int
     */
    public function execute(\Closure $closure, OutputInterface $output): int
    {
        $exitCode = self::CODE_SUCCESS;

        try {
            \PHP_Timer::start();
            $closure();
            \PHP_Timer::stop();

            $this->logger->debug(\PHP_Timer::resourceUsage());
        } catch (\Exception $exception) {
            \PHP_Timer::stop();

            $exceptionMessage = $exception->getMessage();
            $exitCode = max(self::CODE_FAILURE, (int)$exception->getCode());

            $this->logger->critical($exceptionMessage);
            $this->logger->debug(\PHP_Timer::resourceUsage());

            $output->writeln('<error>' . $exceptionMessage . '</error>');
        }

        return $exitCode;
    }
}
