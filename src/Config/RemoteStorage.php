<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

use Magento\MagentoCloud\Config\Stage\DeployInterface;

/**
 * Config for remote storage.
 */
class RemoteStorage
{
    /**
     * @var DeployInterface
     */
    private $deployConfig;

    /**
     * @param DeployInterface $deployConfig
     */
    public function __construct(DeployInterface $deployConfig)
    {
        $this->deployConfig = $deployConfig;
    }

    /**
     * @return string
     */
    public function getAdapter(): string
    {
        return (string)($this->deployConfig->get(DeployInterface::VAR_REMOTE_STORAGE)['adapter'] ?? '');
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return (string)($this->deployConfig->get(DeployInterface::VAR_REMOTE_STORAGE)['prefix'] ?? '');
    }

    /**
     * @return string[]
     */
    public function getConfig(): array
    {
        return (array)($this->deployConfig->get(DeployInterface::VAR_REMOTE_STORAGE)['config'] ?? []);
    }
}
