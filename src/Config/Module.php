<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellInterface;

/**
 * Performs module management operations.
 */
class Module
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var ShellInterface
     */
    private $shell;

    /**
     * @param ConfigInterface $config
     * @param ShellInterface $shell
     */
    public function __construct(ConfigInterface $config, ShellInterface $shell)
    {
        $this->config = $config;
        $this->shell = $shell;
    }

    /**
     * Reconciling installed modules with shared config.
     * Returns list of enabled modules or an empty array if no modules were enabled.
     *
     * @throws ShellException
     * @throws FileSystemException
     */
    public function refresh(): array
    {
        $moduleConfig = (array)$this->config->get('modules');

        $this->shell->execute('php ./bin/magento module:enable --all --ansi --no-interaction');

        $this->config->reset();

        $updatedModuleConfig = (array)$this->config->get('modules');

        if ($moduleConfig) {
            $this->config->update(['modules' => $moduleConfig]);
        }

        return array_keys(array_diff_key($updatedModuleConfig, $moduleConfig));
    }
}
