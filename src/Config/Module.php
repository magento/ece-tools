<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Shell\ShellInterface;
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
     * @param ShellInterface $shell
     */
    public function __construct(SharedConfig $sharedConfig, ShellInterface $shell)
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

        if (!$moduleConfig) {
            $this->shell->execute('php ./bin/magento module:enable --all --ansi --no-interaction');
            $this->sharedConfig->reset();

            return;
        }

        $this->shell->execute('php ./bin/magento module:enable --all --ansi --no-interaction');
        $this->sharedConfig->update(['modules' => $moduleConfig]);
    }
}
