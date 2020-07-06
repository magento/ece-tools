<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\OnFail\Action\ActionInterface;
use Magento\MagentoCloud\OnFail\Action\SkipAction;
use Magento\MagentoCloud\Scenario\Collector\Step;
use Magento\MagentoCloud\Step\SkipStep;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Magento\MagentoCloud\App\ContainerException;

/**
 * Resolve step arguments.
 */
class Resolver
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Sorter
     */
    private $sorter;

    /**
     * @param ContainerInterface $container
     * @param Sorter $sorter
     */
    public function __construct(ContainerInterface $container, Sorter $sorter)
    {
        $this->container = $container;
        $this->sorter = $sorter;
    }

    /**
     * Resolve scenarios by their step arguments
     *
     * @param array $scenarios
     * @return array
     * @throws ValidationException
     * @throws ContainerException
     */
    public function resolve(array $scenarios): array
    {
        $this->sorter->sortScenarios($scenarios['steps']);
        $this->sorter->sortScenarios($scenarios['actions']);

        return [
            'steps' => $this->createStepInstances($scenarios['steps']),
            'actions' => $this->createActionInstances($scenarios['actions']),
        ];
    }

    /**
     * Creates Action instances
     *
     * @param array $actions
     * @return array
     * @throws ValidationException
     * @throws ContainerException
     */
    private function createActionInstances(array $actions): array
    {
        $instances = [];
        foreach ($actions as $action) {
            if ($action['skip']) {
                $instance = $this->container->create(SkipAction::class, [
                    $this->container->get(LoggerInterface::class),
                    $action['name']
                ]);
            } else {
                $instance = $this->container->create($action['type']);
            }

            if (!$instance instanceof ActionInterface) {
                throw new ValidationException(sprintf(
                    '%s is not instance of %s',
                    get_class($instance),
                    ActionInterface::class
                ));
            }

            $instances[$action['name']] = $instance;
        }

        return $instances;
    }

    /**
     * Creates Step instances
     *
     * @param array $steps
     * @return array
     * @throws ValidationException
     * @throws ContainerException
     */
    private function createStepInstances(array $steps): array
    {
        $instances = [];
        foreach ($steps as $step) {
            if ($step['skip']) {
                $instance = $this->container->create(SkipStep::class, [
                    $this->container->get(LoggerInterface::class),
                    $step['name']
                ]);
            } else {
                $instance = $this->container->create(
                    $step['type'],
                    $this->resolveParams($step['arguments'] ?? [], $step['name'])
                );
            }

            if (!$instance instanceof StepInterface) {
                throw new ValidationException(sprintf(
                    '%s is not instance of %s',
                    get_class($instance),
                    StepInterface::class
                ));
            }

            $instances[$step['name']] = $instance;
        }

        return $instances;
    }

    /**
     * Resolve arguments depending on the type
     *
     * @param array $data
     * @param string $stepName
     * @return array
     * @throws ValidationException
     * @throws ContainerException
     */
    private function resolveParams(array $data, string $stepName): array
    {
        $newData = [];

        foreach ($data as $item) {
            $type = $item['xsi:type'] ?? null;
            $name = $item['name'] ?? null;

            if (!$name) {
                throw new ValidationException(sprintf(
                    'Empty parameter name in step "%s"',
                    $stepName
                ));
            }

            switch ($type) {
                case Step::XSI_TYPE_OBJECT:
                    $newData[$name] = isset($item['skip']) && $item['skip'] ?
                        $this->container->create(SkipStep::class, [
                            $this->container->get(LoggerInterface::class),
                            $name
                        ]) :
                        $this->container->create($item[Step::NODE_VALUE]);
                    break;
                case Step::XSI_TYPE_STRING:
                    $newData[$name] = $item[Step::NODE_VALUE];
                    break;
                case Step::XSI_TYPE_ARRAY:
                    $newData[$name] = $this->resolveParams($item['items'], $stepName);
                    break;
                default:
                    throw new ValidationException(sprintf(
                        'Unknown xsi:type "%s" in step "%s"',
                        $type,
                        $stepName
                    ));
            }
        }

        return $newData;
    }
}
