<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Magento;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;
use Magento\MagentoCloud\Shell\ShellException;
use Magento\MagentoCloud\Shell\ShellFactory;

/**
 * Retrieves a value by running bin/magento config:show
 */
class System
{
    /**
     * @var ShellFactory
     */
    private $shellFactory;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ShellFactory $shellFactory
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(ShellFactory $shellFactory, MagentoVersion $magentoVersion)
    {
        $this->shellFactory = $shellFactory;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Read a value from bin/magento config:show and compare it to an expected value.
     *
     * @param string $key
     * @return string|null
     *
     * @throws UndefinedPackageException
     */
    public function get(string $key)
    {
        if (!$this->magentoVersion->isGreaterOrEqual('2.2.0')) {
            return null;
        }

        try {
            $magentoShell = $this->shellFactory->create(ShellFactory::STRATEGY_MAGENTO_SHELL);
            $process = $magentoShell->execute('config:show', [$key]);

            return $process->getOutput() ?? null;
        } catch (ShellException $shellException) {
            return null;
        }
    }
}
