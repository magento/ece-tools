<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

use Magento\MagentoCloud\App\ContainerInterface;
use Magento\MagentoCloud\Process\ProcessInterface;
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
     * @param array $scenarios
     * @return ProcessInterface[]
     * @throws ValidationException
     */
    public function resolve(array $scenarios): array
    {
        $instances = [];

        foreach ($scenarios as $step) {
            $instance = $this->container->create(
                $step['type'],
                $this->resolveParams($step)
            );

            if (!$instance instanceof ProcessInterface) {
                throw new ValidationException(sprintf(
                    '%s is not instance of %s',
                    get_class($instance),
                    ProcessInterface::class
                ));
            }

            $instances[$step['name']] = $instance;
        }

        return $instances;
    }

    /**
     * @param array $step
     * @return array
     * @throws ValidationException
     */
    private function resolveParams(array $step): array
    {
        $params = [];

        foreach ($step['arguments'] ?? [] as $data) {
            $argName = $data['name'];

            $params[$argName] = [];

            foreach ($data['items'] as $itemData) {
                $params[$argName][$itemData['name']] = [];

                foreach ($itemData['items'] as $itemBData) {
                    $params[$argName][$itemData['name']][] = $this->resolveParam(
                        $itemBData['xsi:type'], $itemBData['#']
                    );
                }
            }
        }

        return $params;
    }

    /**
     * @param string $type
     * @param string $value
     * @return object|string
     * @throws ValidationException
     */
    private function resolveParam(string $type, string $value)
    {
        switch ($type) {
            case Merger::XSI_TYPE_OBJECT:
                return $this->container->create($value);
            case Merger::XSI_TYPE_STRING:
                return $value;
            default:
                throw new ValidationException('Unknown xsi:type ' . $type);
        }
    }
}
