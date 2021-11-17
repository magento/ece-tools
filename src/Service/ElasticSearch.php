<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Composer\Semver\Semver;
use Magento\MagentoCloud\Service\Search\AbstractService;

/**
 * Returns ElasticSearch service configurations.
 */
class ElasticSearch extends AbstractService implements ServiceInterface
{
    protected const RELATIONSHIP_KEY = 'elasticsearch';
    protected const ENGINE_SHORT_NAME = 'ES';
    public const ENGINE_NAME = 'elasticsearch';

    /**
     * Return full engine name.
     *
     * @return string
     * @throws ServiceException
     */
    public function getFullEngineName(): string
    {
        $version = $this->getVersion();

        if (Semver::satisfies($version, '>= 5')) {
            return static::ENGINE_NAME . (int)$version;
        }

        return static::ENGINE_NAME;
    }
}
