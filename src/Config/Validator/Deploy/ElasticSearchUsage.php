<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\SearchEngine;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\ServiceException;

/**
 * Validates that different search engine configured when elasticsearch service is installed.
 */
class ElasticSearchUsage implements ValidatorInterface
{
    /**
     * @var SearchEngine
     */
    private $searchEngine;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

    /**
     * @param SearchEngine $searchEngine
     * @param Validator\ResultFactory $resultFactory
     * @param ElasticSearch $elasticSearch
     */
    public function __construct(
        SearchEngine $searchEngine,
        Validator\ResultFactory $resultFactory,
        ElasticSearch $elasticSearch
    ) {
        $this->searchEngine = $searchEngine;
        $this->resultFactory = $resultFactory;
        $this->elasticSearch = $elasticSearch;
    }

    /**
     * Returns success when elasticsearch service not exist.
     * Returns success when elasticsearch service exist and search engine
     * configured as elasticsearch or elasticsuite.
     * Otherwise returns error.
     *
     * {@inheritDoc}
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            if (!$this->elasticSearch->isInstalled()) {
                return $this->resultFactory->success();
            }

            if ($this->searchEngine->isESFamily()) {
                return $this->resultFactory->success();
            }
        } catch (ServiceException $e) {
            throw new ValidatorException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->resultFactory->error(
            'Elasticsearch service is installed at infrastructure layer but is not used as a search engine.',
            'Consider removing the Elasticsearch service from the infrastructure layer for optimized resource usage.',
            Error::WARN_ES_INSTALLED_BUT_NOT_USED
        );
    }
}
