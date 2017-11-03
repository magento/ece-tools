<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger;

use Monolog\Formatter\LineFormatter;
use Magento\MagentoCloud\App\Logger\HandlerFactory;
use Magento\MagentoCloud\App\Logger;
use Magento\MagentoCloud\Filesystem\DirectoryList;
use Monolog\Handler\HandlerInterface;

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
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var LineFormatter
     */
    private $formatter;

    /**
     * @var HandlerFactory
     */
    private $handlerFactory;

    /**
     * @param DirectoryList $directoryList
     * @param ConfigReader $configReader
     * @param FormatterFactory $formatterFactory
     * @param HandlerFactory $handlerFactory
     */
    public function __construct(
        DirectoryList $directoryList,
        ConfigReader $configReader,
        FormatterFactory $formatterFactory,
        HandlerFactory $handlerFactory
    ) {
        $this->directoryList = $directoryList;
        $this->configReader = $configReader;
        $this->formatter = $formatterFactory->create();
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
        $this->handlers[] = $this->handlerFactory->create('stream', [
            'stream' => $this->directoryList->getMagentoRoot() . '/' . Logger::DEPLOY_LOG_PATH
        ])
            ->setFormatter($this->formatter);
        $this->handlers[] = $this->handlerFactory->create('stream')
            ->setFormatter($this->formatter);

        $handlers = $this->configReader->getHandlersConfig();
        foreach ($handlers as $handler => $configuration) {
            $this->handlers[] = $this->handlerFactory->create($handler, $configuration)
                ->setFormatter($this->formatter);
        }
    }
}
