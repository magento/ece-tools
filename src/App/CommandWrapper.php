<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App;

use Psr\Log\LoggerInterface;

/**
 * Wraps command execution to provide unified execution flow.
 */
class CommandWrapper
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
     * @param \Closure $closure
     * @return int|mixed
     */
    public function execute(\Closure $closure)
    {
        $exitCode = 0;

        \PHP_Timer::start();

        try {
            $closure();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            $exitCode = max(1, $exception->getCode());
        }

        \PHP_Timer::stop();

        $this->logger->debug(\PHP_Timer::resourceUsage());

        return $exitCode;
    }
}
