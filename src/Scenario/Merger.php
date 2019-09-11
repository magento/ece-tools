<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

use Magento\MagentoCloud\Filesystem\Driver\File;
use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Filesystem\SystemList;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Merge given scenarios.
 *
 * @codeCoverageIgnore
 */
class Merger
{
    private const ROOT_NODE = 'scenario';

    public const XSI_TYPE_STRING = 'string';
    public const XSI_TYPE_OBJECT = 'object';
    public const XSI_TYPE_ARRAY = 'array';

    /**
     * @var File
     */
    private $file;

    /**
     * @var XmlEncoder
     */
    private $encoder;

    /**
     * @var SystemList
     */
    private $systemList;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var array
     */
    private static $stepRequiredArgs = [
        '@name',
        '@type',
    ];

    /**
     * @var array
     */
    private static $argumentRequiredArgs = [
        '@name',
        '@xsi:type'
    ];

    /**
     * @var array
     */
    private static $itemRequiredArgs = [
        '@name',
        '@xsi:type'
    ];

    /**
     * @param File $file
     * @param XmlEncoder $encoder
     * @param SystemList $systemList
     * @param Resolver $resolver
     */
    public function __construct(File $file, XmlEncoder $encoder, SystemList $systemList, Resolver $resolver)
    {
        $this->file = $file;
        $this->encoder = $encoder;
        $this->systemList = $systemList;
        $this->resolver = $resolver;
    }

    /**
     * Merge an array of scenarios
     *
     * @param array $scenarios
     * @return array
     * @throws ValidationException
     */
    public function merge(array $scenarios): array
    {
        $data = [];

        foreach ($scenarios as $scenario) {
            $scenario = $this->collectScenario($scenario);

            foreach ($scenario['step'] ?? [] as $step) {
                if ($missedArgs = array_diff(self::$stepRequiredArgs, array_keys($step))) {
                    throw new ValidationException(sprintf(
                        'Argument(s) "%s" are missed from step',
                        implode(', ', $missedArgs)
                    ));
                }

                $data[$step['@name']] = array_replace_recursive(
                    $data[$step['@name']] ?? [],
                    $this->collectStep($step)
                );
            }
        }

        return $this->resolver->resolve($data);
    }

    /**
     * Collect step data including child items
     *
     * @param array $step
     * @return array
     * @throws ValidationException
     */
    private function collectStep(array $step): array
    {
        $stepName = $step['@name'];

        $arguments = [];

        foreach ($step['arguments'][0]['argument'] ?? [] as $argument) {
            if ($missedArgs = array_diff(self::$argumentRequiredArgs, array_keys($argument))) {
                throw new ValidationException(sprintf(
                    'Argument(s) "%s" are missed from argument in step "%s"',
                    implode(', ', $missedArgs),
                    $stepName
                ));
            }

            $argumentName = $argument['@name'];
            $argumentType = $argument['@xsi:type'];

            if ($argumentType !== self::XSI_TYPE_ARRAY) {
                throw new ValidationException(sprintf(
                    'xsi:type "%s" not allowed in argument "%s"',
                    $argumentType,
                    $argumentName
                ));
            }

            $arguments[] = [
                'name' => $argumentName,
                'xsi:type' => $argumentType,
                'items' => $this->collectItems(
                    $argument['item'] ?: []
                )
            ];
        }

        $stepData = [
            'name' => $stepName,
            'type' => $step['@type'],
            'arguments' => $arguments
        ];

        return $stepData;
    }

    /**
     * Collect scenario data
     *
     * @param string $scenario
     * @return array
     * @throws ValidationException
     */
    private function collectScenario(string $scenario): array
    {
        $scenarioPath = $this->systemList->getRoot() . '/' . $scenario;

        if (!$this->file->isExists($scenarioPath)) {
            $scenarioPath = $this->systemList->getMagentoRoot() . '/' . $scenario;
        }

        if (!$this->file->isExists($scenarioPath)) {
            throw new ValidationException(sprintf(
                'Scenario %s does not exist',
                $scenario
            ));
        }

        try {
            return $this->encoder->decode(
                $this->file->fileGetContents($scenarioPath),
                XmlEncoder::FORMAT,
                [
                    XmlEncoder::AS_COLLECTION => true,
                    XmlEncoder::ROOT_NODE_NAME => self::ROOT_NODE,
                ]
            ) ?: [];
        } catch (FileSystemException $exception) {
            throw new ValidationException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Recursively collect items
     *
     * @param array $items
     * @return array
     * @throws ValidationException
     */
    private function collectItems(array $items): array
    {
        $newItems = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new ValidationException('Wrong formatted item provided');
            }

            $itemName = $item['@name'] ?? '';

            if ($missedArgs = array_diff(self::$itemRequiredArgs, array_keys($item))) {
                throw new ValidationException(sprintf(
                    'Argument(s) "%s" are missed from item "%s"',
                    implode(', ', $missedArgs),
                    $itemName
                ));
            }

            $newItem = [
                'name' => $itemName,
                'xsi:type' => $item['@xsi:type'],
            ];

            if (isset($item['#'])) {
                $newItem['#'] = $item['#'];
            } elseif (isset($item['item'])) {
                $newItem['items'] = $this->collectItems($item['item']);
            }

            $newItems[] = $newItem;
        }

        return $newItems;
    }
}
