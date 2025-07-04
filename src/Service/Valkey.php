<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Service\Valkey\Version;

/**
 * Returns Valkey service configurations.
 */
class Valkey implements ServiceInterface
{
    const RELATIONSHIP_KEY = 'valkey';
    const RELATIONSHIP_SLAVE_KEY = 'valkey-slave';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Version
     */
    private $versionRetriever;

    /**
     * @var string
     */
    private $version;

    /**
     * @param Environment $environment
     * @param Version $versionRetriever
     */
    public function __construct(
        Environment $environment,
        Version $versionRetriever
    ) {
        $this->environment = $environment;
        $this->versionRetriever = $versionRetriever;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = $this->versionRetriever->getVersion($this->getConfiguration());
        }

        return $this->version;
    }
}
