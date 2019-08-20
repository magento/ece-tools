<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\Config\Environment;

/**
 * Returns Redis service configurations.
 */
class Redis implements ServiceInterface
{
    const RELATIONSHIP_KEY = 'redis';
    const RELATIONSHIP_SLAVE_KEY = 'redis-slave';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string
     */
    private $version;

    /**
     * @param Environment $environment
     */
    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(): array
    {
        return $this->environment->getRelationship(self::RELATIONSHIP_KEY)[0] ?? [];
    }

    /**
     * Returns service configuration for slave.
     *
     * @return array
     */
    public function getSlaveConfiguration(): array
    {
        return $this->environment->getRelationship(self::RELATIONSHIP_SLAVE_KEY)[0] ?? [];
    }

    /**
     * Returns version of the service.
     *
     * @return string
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = '0';

            $redisConfig = $this->getConfiguration();

            if (isset($redisConfig['type']) && strpos($redisConfig['type'], ':') !== false) {
                $this->version = explode(':', $redisConfig['type'])[1];
            }
        }

        return $this->version;
    }
}
