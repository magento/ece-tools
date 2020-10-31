<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\DB\Data;

use Magento\MagentoCloud\Config\Environment;

/**
 * Returns database service configurations.
 */
class ConnectionTypes
{
    const RELATIONSHIP_KEY = 'database';
    const RELATIONSHIP_SLAVE_KEY = 'database-slave';

    const RELATIONSHIP_QUOTE_KEY = 'database-quote';
    const RELATIONSHIP_QUOTE_SLAVE_KEY = 'database-quote-slave';

    const RELATIONSHIP_SALES_KEY = 'database-sales';
    const RELATIONSHIP_SALES_SLAVE_KEY = 'database-sales-slave';

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     */
    public function __construct(
        Environment $environment
    ) {
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
     * Returns configuration for quote service.
     */
    public function getQuoteConfiguration(): array
    {
        return $this->environment->getRelationship(self::RELATIONSHIP_QUOTE_KEY)[0] ?? [];
    }

    /**
     * Returns configuration for quote slave service.
     *
     * @return array
     */
    public function getQuoteSlaveConfiguration(): array
    {
        return $this->environment->getRelationship(self::RELATIONSHIP_QUOTE_SLAVE_KEY)[0] ?? [];
    }

    /**
     * Returns configuration for sales service.
     */
    public function getSalesConfiguration(): array
    {
        return $this->environment->getRelationship(self::RELATIONSHIP_SALES_KEY)[0] ?? [];
    }

    /**
     * Returns configuration for slave sales service.
     *
     * @return array
     */
    public function getSalesSlaveConfiguration(): array
    {
        return $this->environment->getRelationship(self::RELATIONSHIP_SALES_SLAVE_KEY)[0] ?? [];
    }
}
