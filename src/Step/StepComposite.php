<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Step;

/**
 * @inheritdoc
 */
class StepComposite implements StepInterface
{
    /**
     * @var StepInterface[]
     */
    private $steps;

    /**
     * @param StepInterface[] $steps
     */
    public function __construct(array $steps)
    {
        $this->steps = $steps;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        ksort($this->steps);

        array_walk($this->steps, function (StepInterface $step) {
            $step->execute();
        });
    }
}
