<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Magento\MagentoCloud\Config\Log as LogConfig;

/**
 * The pool of handlers
 */
class Pool
{
    /**
     * @var HandlerInterface[]
     */
    private $handlers = [];

    /**
     * @var LogConfig
     */
    private $logConfig;

    /**
     * @var LineFormatter
     */
    private $formatter;

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
        $this->formatter = $lineFormatterFactory->create();
        $this->handlerFactory = $handlerFactory;

        $this->populateHandler();
    }

    /**
     * @return HandlerInterface[]
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    /**
     * Populates $this->handlers
     *
     * @return void
     */
    private function populateHandler()
    {
        foreach ($this->logConfig->getHandlers() as $handler) {
            $this->handlers[] = $this->handlerFactory->create($handler)
                ->setFormatter($this->formatter);
        }
    }
}
