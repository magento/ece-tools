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
     * @var Scenario
     */
    private $scenarioCollector;

    /**
     * @var array
     */
    private static $stepRequiredArgs = [
        '@name',
        '@priority',
        '@type'
    ];

    /**
     * @param Resolver $resolver
     * @param StepCollector $stepCollector
     * @param Scenario $scenarioCollector
     */
    public function __construct(Resolver $resolver, StepCollector $stepCollector, Scenario $scenarioCollector)
    {
        $this->resolver = $resolver;
        $this->stepCollector = $stepCollector;
        $this->scenarioCollector = $scenarioCollector;
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
            $scenarioData = $this->scenarioCollector->collect($scenario);
            if (!isset($scenarioData['step'])) {
                throw new ValidationException(sprintf('Steps aren\'t exist in "%s" file', $scenario));
            }

            $steps = is_array(reset($scenarioData['step'])) ? $scenarioData['step'] : [$scenarioData['step']];

            foreach ($steps as $step) {
                $this->validateStep($step);

                $data[$step['@name']] = array_replace_recursive(
                    $data[$step['@name']] ?? [],
                    $this->stepCollector->collect($step)
                );
            }
        }

        return $this->resolver->resolve($data);
    }

    /**
     * Validates if exists all required attributes.
     *
     * @param array $step
     * @throws ValidationException
     * @return void
     */
    private function validateStep(array $step): void
    {
        $isSkipped = isset($step['@skip']) && $step['@skip'] === 'true';

        $requiredAttributes = $isSkipped ? ['@name'] : self::$stepRequiredArgs;

        if ($missedArgs = array_diff($requiredAttributes, array_keys($step))) {
            throw new ValidationException(sprintf(
                'Argument(s) "%s" are missed from step',
                implode(', ', $missedArgs)
            ));
        }
    }
}
