<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Step\StepInterface;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;

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
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Resolve scenarios by their step arguments
     *
     * @param array $scenarios
     * @return StepInterface[]
     * @throws ValidationException
     */
    public function resolve(array $scenarios): array
    {
        $instances = [];

        foreach ($scenarios as $step) {
            $instance = $this->container->create(
                $step['type'],
                $this->resolveParams($step['arguments'] ?? [], $step['name'])
            );

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
                case Merger::XSI_TYPE_OBJECT:
                    $newData[$name] = $this->container->create($item[Merger::NODE_VALUE]);
                    break;
                case Merger::XSI_TYPE_STRING:
                    $newData[$name] = $item[Merger::NODE_VALUE];
                    break;
                case Merger::XSI_TYPE_ARRAY:
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
