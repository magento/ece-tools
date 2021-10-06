<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger\Gelf;

use Illuminate\Contracts\Config\Repository;
use Gelf\Publisher;
use Magento\MagentoCloud\App\LoggerException;

/**
 * Creates instance of Gelf handler.
 */
class HandlerFactory
{
    /**
     * @var TransportFactory
     */
    private $transportFactory;

    /**
     * @param TransportFactory $transportFactory
     */
    public function __construct(TransportFactory $transportFactory)
    {
        $this->transportFactory = $transportFactory;
    }

    /**
     * Creates instance of Gelf handler.
     *
     * @param Repository $configuration
     * @param mixed $minLevel
     * @return Handler
     * @throws LoggerException
     */
    public function create(Repository $configuration, $minLevel): Handler
    {
        $this->increaseSocketTimeout();

        $publisher = new Publisher();

        foreach ($configuration->get('transport') as $transportType => $transportConfig) {
            $publisher->addTransport(
                $this->transportFactory->create($transportType, $transportConfig)
            );
        }

        $messageFormatter = new MessageFormatter();
        $messageFormatter->setAdditional($configuration->get('additional', []));

        $handlerInstance = new Handler(
            $publisher,
            $minLevel
        );
        $handlerInstance->setFormatter($messageFormatter);

        return $handlerInstance;
    }

    /**
     * Increase socket timeout to avoid losing connection after long pauses between log messages.
     *
     * @return void
     */
    private function increaseSocketTimeout(): void
    {
        ini_set('default_socket_timeout', '3600');
    }
}
