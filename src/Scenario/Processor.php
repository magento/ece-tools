<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Process\ProcessInterface;
use Magento\MagentoCloud\Scenario\Exception\ProcessorException;
use Psr\Log\LoggerInterface;

/**
 * Process given scenarios.
 */
class Processor
{
    /**
     * @var Merger
     */
    private $merger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Merger $merger
     * @param LoggerInterface $logger
     */
    public function __construct(Merger $merger, LoggerInterface $logger)
    {
        $this->merger = $merger;
        $this->logger = $logger;
    }

    /**
     * @param array $scenarios
     * @throws ProcessorException
     */
    public function execute(array $scenarios)
    {
        $this->logger->info(sprintf(
            'Starting scenarios: %s',
            implode(', ', $scenarios)
        ));

        try {
            $steps = $this->merger->merge($scenarios);

            array_walk($steps, function (ProcessInterface $step, string $name) {
                $this->logger->debug('Running step: ' . $name);

                $step->execute();

                $this->logger->debug('Step finished');
            });
        } catch (GenericException $exception) {
            $this->logger->error($exception->getMessage());

            throw new ProcessorException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        $this->logger->info('Scenarios finished');
    }
}
