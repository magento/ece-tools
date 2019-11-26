<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Magento;

use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Shell\ShellFactory;

/**
 * Retrieves a value by running bin/magento config:show
 */
class System implements SystemInterface
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
     * Read a value from bin/magento config:show command.
     *
     * @inheritDoc
     */
    public function get(string $key): ?string
    {
        try {
            if (!$this->magentoVersion->isGreaterOrEqual('2.2.0')) {
                return null;
            }

            $magentoShell = $this->shellFactory->create(ShellFactory::STRATEGY_MAGENTO_SHELL);
            $process = $magentoShell->execute('config:show', [$key]);

            return $process->getOutput() ?? null;
        } catch (\Exception $exception) {
            return null;
        }
    }
}
