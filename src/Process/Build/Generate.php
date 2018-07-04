<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Process\ProcessInterface;

/**
 * Composite process for build phase that includes all backup related processes.
 *
 * {@inheritdoc}
 */
class Generate implements ProcessInterface
{
    /**
     * @var ProcessInterface
     */
    private $process;

    /**
     * @param ProcessInterface $process
     */
    public function __construct(ProcessInterface $process)
    {
        $this->process = $process;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->process->execute();
    }
}
