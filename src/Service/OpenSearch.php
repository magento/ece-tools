<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Service;

use Magento\MagentoCloud\Service\Search\AbstractService;

/**
 * Returns OpenSearch service configurations.
 */
class OpenSearch extends AbstractService implements ServiceInterface
{
    protected const RELATIONSHIP_KEY = 'opensearch';
    protected const ENGINE_SHORT_NAME = 'OS';
    public const ENGINE_NAME = 'opensearch';

    /**
     * Return full engine name.
     *
     * @return string
     * @throws ServiceException
     */
    public function getFullEngineName(): string
    {
        return 'elasticsearch7';
    }
}
