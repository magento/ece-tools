<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\LoggerException;
use Monolog\Handler\AbstractProcessingHandler;
use Magento\MagentoCloud\Config\Log as LogConfig;
use Exception;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * The pool of handlers.
 */
class Pool
{
    /**
     * @var AbstractProcessingHandler[]
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
     * @return AbstractProcessingHandler[]
     * @throws LoggerException
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

                if (!empty($handlerConfig['formatter'])) {
                    $handler->setFormatter($handlerConfig['formatter']);
                } elseif (empty($handlerConfig['use_default_formatter'])) {
                    $handler->setFormatter($this->lineFormatterFactory->create());
                }

                $this->handlers[] = $handler;
            }
        } catch (ParseException $e) {
            throw new LoggerException($e->getMessage(), Error::BUILD_CONFIG_PARSE_FAILED, $e);
        } catch (Exception $e) {
            throw new LoggerException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->handlers;
    }
}
