<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config;

/**
 * Config for remote storage.
 */
class RemoteStorage
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @return string
     */
    public function getAdapter(): string
    {
        return (string)$this->environment->getEnv('REMOTE_STORAGE_ADAPTER');
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return (string)$this->environment->getEnv('REMOTE_STORAGE_PREFIX');
    }

    /**
     * @return string[]
     */
    public function getConfig(): array
    {
        return [
            'bucket' => (string)$this->environment->getEnv('REMOTE_STORAGE_BUCKET'),
            'region' => (string)$this->environment->getEnv('REMOTE_STORAGE_REGION'),
            'prefix' => (string)$this->environment->getEnv('REMOTE_STORAGE_PREFIX'),
            'key' => (string)$this->environment->getEnv('REMOTE_STORAGE_KEY'),
            'secret' => (string)$this->environment->getEnv('REMOTE_STORAGE_SECRET')
        ];
    }
}
