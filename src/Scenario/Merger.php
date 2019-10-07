<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Scenario;

use Magento\MagentoCloud\Scenario\Collector\Scenario;
use Magento\MagentoCloud\Scenario\Collector\Step as StepCollector;
use Magento\MagentoCloud\Scenario\Exception\ValidationException;

/**
 * Merge given scenarios.
 *
 * @codeCoverageIgnore
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
            $scenario = $this->scenarioCollector->collect($scenario);

            foreach ($scenario['step'] ?? [] as $step) {
                if ($missedArgs = array_diff(self::$stepRequiredArgs, array_keys($step))) {
                    throw new ValidationException(sprintf(
                        'Argument(s) "%s" are missed from step',
                        implode(', ', $missedArgs)
                    ));
                }

                $data[$step['@name']] = array_replace_recursive(
                    $data[$step['@name']] ?? [],
                    $this->stepCollector->collect($step)
                );
            }
        }

        return $this->resolver->resolve($data);
    }
}
