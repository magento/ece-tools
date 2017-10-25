<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process;

/**
 * @inheritdoc
 */
class ProcessComposite implements ProcessInterface
{
    /**
     * @var ProcessInterface[]
     */
    private $processes;

    /**
     * @param ProcessInterface[] $processes
     */
    public function __construct(array $processes)
    {
        $this->processes = $processes;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        ksort($this->processes);

        array_walk($this->processes, function (ProcessInterface $processor) {
            $processor->execute();
        });
    }
}
