<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Build;

use Magento\MagentoCloud\Process\ProcessInterface;

/**
 * Composite process for build phase that responsible for preparing codebase.
 *
 * {@inheritdoc}
 */
class Backup implements ProcessInterface
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
