<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

use Magento\MagentoCloud\OnFail\Action\ActionException;
use Magento\MagentoCloud\OnFail\Action\ActionInterface;
use Magento\MagentoCloud\Package\Manager;
use Magento\MagentoCloud\Step\StepException;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Scenario\Exception\ProcessorException;
use Psr\Log\LoggerInterface;
use Throwable;

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
     * @var array
     */
    private $mergedScenarios = [];

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
            $this->mergedScenarios = $this->merger->merge($scenarios);
            $steps = $this->mergedScenarios['steps'];

            array_walk($steps, function (StepInterface $step, string $name) {
                $this->logger->debug('Running step: ' . $name);

                $step->execute();

                $this->logger->debug(sprintf('Step "%s" finished', $name));
            });
        } catch (StepException $stepException) {
            try {
                $actions = $this->mergedScenarios['actions'];

                array_walk($actions, function (ActionInterface $action, string $name) {
                    $this->logger->debug('Running on fail action: ' . $name);

                    $action->execute();

                    $this->logger->debug(sprintf('On fail action "%s" finished', $name));
                });
            } catch (ActionException $actionException) {
                $this->logger->error($actionException->getMessage());
            }
            $this->handleException($stepException);
        } catch (Throwable $exception) {
            $this->handleException(
                $exception,
                sprintf('Unhandled error: %s', $exception->getMessage())
            );
        }

        $this->logger->info('Scenario(s) finished');
    }

    /**
     * Logs error message and throws ProcessorException
     *
     * @param Throwable $exception
     * @param string $message
     * @throws ProcessorException
     */
    private function handleException(Throwable $exception, string $message = ''): void
    {
        if (empty($message)) {
            $message = sprintf('%s', $exception->getMessage());
        }
        $this->logger->error($message, ['errorCode' => $exception->getCode()]);

        throw new ProcessorException(
            $message,
            $exception->getCode(),
            $exception
        );
    }
}
