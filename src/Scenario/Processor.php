<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Package\Manager;
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
     * @var Manager
     */
    private $manager;

    /**
     * @param Merger $merger
     * @param LoggerInterface $logger
     * @param Manager $manager
     */
    public function __construct(Merger $merger, LoggerInterface $logger, Manager $manager)
    {
        $this->merger = $merger;
        $this->logger = $logger;
        $this->manager = $manager;
    }

    /**
     * @param array $scenarios
     * @throws ProcessorException
     */
    public function execute(array $scenarios)
    {
        $this->logger->info(sprintf(
            'Starting scenario(s): %s %s',
            implode(', ', $scenarios),
            $this->manager->getPrettyInfo()
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

        $this->logger->info('Scenario(s) finished');
    }
}