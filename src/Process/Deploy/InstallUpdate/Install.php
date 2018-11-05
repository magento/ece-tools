<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Process\Deploy\InstallUpdate;

use Magento\MagentoCloud\Process\ProcessInterface;

/**
 * Performs application installation.
 */
class Install implements ProcessInterface
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
