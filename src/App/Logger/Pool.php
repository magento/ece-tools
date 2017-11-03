<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\App\Logger;

use Monolog\Formatter\LineFormatter;
use Magento\MagentoCloud\App\Logger\Handler\StreamFactory as StreamHandlerFactory;
use Magento\MagentoCloud\App\Logger\Handler\SlackFactory as SlackHandlerFactory;
use Magento\MagentoCloud\App\Logger\Handler\EmailFactory as EmailHandlerFactory;
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
     * @var StreamHandlerFactory
     */
    private $streamHandlerFactory;

    /**
     * @var SlackHandlerFactory
     */
    private $slackHandlerFactory;

    /**
     * @var EmailHandlerFactory
     */
    private $emailHandlerFactory;

    /**
     * @param DirectoryList $directoryList
     * @param ConfigReader $configReader
     * @param FormatterFactory $formatterFactory
     * @param StreamHandlerFactory $streamHandlerFactory
     * @param SlackHandlerFactory $slackHandlerFactory
     * @param EmailHandlerFactory $emailHandlerFactory
     */
    public function __construct(
        DirectoryList $directoryList,
        ConfigReader $configReader,
        FormatterFactory $formatterFactory,
        StreamHandlerFactory $streamHandlerFactory,
        SlackHandlerFactory $slackHandlerFactory,
        EmailHandlerFactory $emailHandlerFactory
    ) {
        $this->directoryList = $directoryList;
        $this->configReader = $configReader;
        $this->formatter = $formatterFactory->create(
            "[%datetime%] %level_name%: %message% %context% %extra%\n",
            null,
            true,
            true
        );

        $this->streamHandlerFactory = $streamHandlerFactory;
        $this->slackHandlerFactory = $slackHandlerFactory;
        $this->emailHandlerFactory = $emailHandlerFactory;

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
        $this->handlers[] = $this->streamHandlerFactory->create([
            'stream' => $this->directoryList->getMagentoRoot() . '/' . Logger::DEPLOY_LOG_PATH
        ])
            ->setFormatter($this->formatter);
        $this->handlers[] = $this->streamHandlerFactory->create()
            ->setFormatter($this->formatter);

        $handlers = $this->configReader->getHandlersConfig();
        foreach ($handlers as $handler => $configuration) {
            switch ($handler) {
                case 'slack':
                    $this->handlers[] = $this->slackHandlerFactory->create($configuration)
                        ->setFormatter($this->formatter);
                    break;
                case 'email':
                    $this->handlers[] = $this->emailHandlerFactory->create($configuration)
                        ->setFormatter($this->formatter);
                    break;
            }
        }
    }
}
