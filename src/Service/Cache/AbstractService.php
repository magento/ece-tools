<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service\Cache;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Service\ServiceException;
use Magento\MagentoCloud\Service\ServiceInterface;
use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Http\ClientFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Returns search service configurations for ElasticSearch family engines.
 */
abstract class AbstractService implements ServiceInterface
{

    const RELATIONSHIP_KEY = 'abstract';
    const RELATIONSHIP_SLAVE_KEY = 'abstract-slave';

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