<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Step\StepInterface;
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
    private $packageManager;

    /**
     * @param Merger $merger
     * @param LoggerInterface $logger
     * @param Manager $manager
     */
    public function __construct(Merger $merger, LoggerInterface $logger, Manager $manager)
    {
        $this->merger = $merger;
        $this->logger = $logger;
        $this->packageManager = $manager;
    }

    /**
     * @param array $scenarios
     * @throws ProcessorException
     */
    public function execute(array $scenarios): void
    {
        $this->logger->info(sprintf(
            'Starting scenario(s): %s %s',
            implode(', ', $scenarios),
            $this->packageManager->getPrettyInfo()
        ));

        try {
            $steps = $this->merger->merge($scenarios);

            array_walk($steps, function (StepInterface $step, string $name) {
                $this->logger->debug('Running step: ' . $name);

                $step->execute();

                $this->logger->debug(sprintf('Step "%s" finished', $name));
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
