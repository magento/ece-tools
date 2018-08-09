<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Shell\ExecBinMagento;
use Magento\MagentoCloud\Config\Shared as SharedConfig;

/**
 * Performs module management operations.
 */
class Module
{
    /**
     * @var SharedConfig
     */
    private $sharedConfig;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param Shared $sharedConfig
     * @param ExecBinMagento $shell
     */
    public function __construct(SharedConfig $sharedConfig, ExecBinMagento $shell)
    {
        $this->sharedConfig = $sharedConfig;
        $this->shell = $shell;
    }

    /**
     * Reconciling installed modules with shared config.
     *
     * @throws \RuntimeException
     */
    public function refresh()
    {
        $moduleConfig = (array)$this->sharedConfig->get('modules');

        $this->shell->execute('module:enable', ['--all']);

        if ($moduleConfig) {
            $this->sharedConfig->update(['modules' => $moduleConfig]);
        } else {
            $this->sharedConfig->reset();
        }
    }
}
