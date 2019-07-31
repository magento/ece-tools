<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Filesystem\FileSystemException;
use Magento\MagentoCloud\Shell\MagentoShell;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;

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
     * @var MagentoShell
     */
    private $magentoShell;

    /**
     * @param ConfigInterface $config
     * @param ShellFactory $shellFactory
     */
    public function __construct(ConfigInterface $config, ShellFactory $shellFactory)
    {
        $this->config = $config;
        $this->magentoShell = $shellFactory->createMagento();
    }

    /**
     * Reconciling installed modules with shared config.
     * Returns list of new enabled modules or an empty array if no modules were enabled.
     *
     * @throws ShellException
     * @throws FileSystemException
     */
    public function refresh(): array
    {
        $moduleConfig = (array)$this->config->get('modules');

        $this->magentoShell->execute('module:enable --all');

        $this->config->reset();

        $updatedModuleConfig = (array)$this->config->get('modules');

        if ($moduleConfig) {
            $this->config->update(['modules' => $moduleConfig]);
        }

        return array_keys(array_diff_key($updatedModuleConfig, $moduleConfig));
    }
}
