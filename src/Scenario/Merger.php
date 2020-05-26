<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

use Magento\MagentoCloud\Scenario\Collector\Scenario;
use Magento\MagentoCloud\Scenario\Collector\Step;
use Magento\MagentoCloud\Scenario\Collector\Step as StepCollector;
use Magento\MagentoCloud\Scenario\Collector\Action as ActionCollector;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;

/**
 * Merge given scenarios.
 */
class Merger
{
    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var StepCollector
     */
    private $stepCollector;

    /**
     * @var ActionCollector
     */
    private $actionCollector;

    /**
     * @var Scenario
     */
    private $scenarioCollector;

    /**
     * Required arguments for steps and actions
     *
     * @var array
     */
    private static $requiredArgs = [
        '@name',
        '@priority',
        '@type'
    ];

    /**
     * @param Resolver $resolver
     * @param StepCollector $stepCollector
     * @param ActionCollector $actionCollector
     * @param Scenario $scenarioCollector
     */
    public function __construct(
        Resolver $resolver,
        StepCollector $stepCollector,
        ActionCollector $actionCollector,
        Scenario $scenarioCollector
    ) {
        $this->resolver = $resolver;
        $this->stepCollector = $stepCollector;
        $this->actionCollector = $actionCollector;
        $this->scenarioCollector = $scenarioCollector;
    }

    /**
     * Merge an array of scenarios.
     *
     * @param array $scenarios
     * @return array
     * @throws ValidationException
     */
    public function merge(array $scenarios): array
    {
        $data = [
            'steps' => [],
            'actions' => [],
        ];

        foreach ($scenarios as $scenario) {
            $scenarioData = $this->scenarioCollector->collect($scenario);
            if (!isset($scenarioData['step'])) {
                throw new ValidationException(sprintf('Steps aren\'t exist in "%s" file', $scenario));
            }

            $steps = $this->normalizeItems($scenarioData['step']);

            foreach ($steps as $step) {
                $this->validate($step);

                $data['steps'][$step['@name']] = array_replace_recursive(
                    $data['steps'][$step['@name']] ?? [],
                    $this->stepCollector->collect($step)
                );
            }

            $actions = isset($scenarioData['onFail']['action'])
                ? $this->normalizeItems($scenarioData['onFail']['action'])
                : [];

            foreach ($actions as $action) {
                $this->validate($action);

                $data['actions'][$action['@name']] = array_replace_recursive(
                    $data['actions'][$action['@name']] ?? [],
                    $this->actionCollector->collect($action)
                );
            }
        }

        return $this->resolver->resolve($data);
    }

    /**
     * Normalizes items list.
     *
     * The xml parser can return a list of items or one single item from a xml file.
     * This method checks the data and make the list of one item if the parser returned only one item.
     *
     * @param array $items
     * @return array
     */
    private function normalizeItems(array $items): array
    {
        return is_array(reset($items)) ? $items : [$items];
    }

    /**
     * Validates if exists all required attributes.
     *
     * @param array $item
     * @throws ValidationException
     * @return void
     */
    private function validate(array $item): void
    {
        $isSkipped = isset($item['@skip']) && $item['@skip'] === 'true';
        $requiredAttributes = $isSkipped ? ['@name'] : self::$requiredArgs;

        if ($missedArgs = array_diff($requiredAttributes, array_keys($item))) {
            throw new ValidationException(sprintf(
                'Argument(s) "%s" are missed from item',
                implode(', ', $missedArgs)
            ));
        }
    }
}
