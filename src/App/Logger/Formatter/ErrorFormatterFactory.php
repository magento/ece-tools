<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\App\Logger\Formatter;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\App\ErrorInfo;
use Magento\MagentoCloud\App\Logger\Error\ReaderInterface;
use Monolog\Formatter\JsonFormatter;

/**
 * The factory for JsonErrorFormatter.
 */
class ErrorFormatterFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return JsonErrorFormatter
     */
    public function create(): JsonErrorFormatter
    {
        return new JsonErrorFormatter(
            $this->container->get(ErrorInfo::class),
            $this->container->get(ReaderInterface::class),
            JsonFormatter::BATCH_MODE_JSON,
            true
        );
    }
}
