<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger;

use Magento\MagentoCloud\App\LoggerException;
use Monolog\Handler\HandlerInterface;
use Magento\MagentoCloud\Config\Log as LogConfig;
use Exception;

/**
 * The pool of handlers.
 */
class Pool
{
    /**
     * @var HandlerInterface[]
     */
    private $handlers;

    /**
     * @var LogConfig
     */
    private $logConfig;

    /**
     * @var LineFormatterFactory
     */
    private $lineFormatterFactory;

    /**
     * @var HandlerFactory
     */
    private $handlerFactory;

    /**
     * @param LogConfig $logConfig
     * @param LineFormatterFactory $lineFormatterFactory
     * @param HandlerFactory $handlerFactory
     */
    public function __construct(
        LogConfig $logConfig,
        LineFormatterFactory $lineFormatterFactory,
        HandlerFactory $handlerFactory
    ) {
        $this->logConfig = $logConfig;
        $this->lineFormatterFactory = $lineFormatterFactory;
        $this->handlerFactory = $handlerFactory;
    }

    /**
     * @return HandlerInterface[]
     * @throws LoggerException If can't create handlers from config.
     */
    public function getHandlers(): array
    {
        if (null !== $this->handlers) {
            return $this->handlers;
        }

        $this->handlers = [];

        try {
            foreach ($this->logConfig->getHandlers() as $handlerName => $handlerConfig) {
                $handler = $this->handlerFactory->create($handlerName);

                if (empty($handlerConfig['use_default_formatter'])) {
                    $handler->setFormatter($this->lineFormatterFactory->create());
                }

                $this->handlers[] = $handler;
            }
        } catch (Exception $exception) {
            throw new LoggerException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        return $this->handlers;
    }
}
