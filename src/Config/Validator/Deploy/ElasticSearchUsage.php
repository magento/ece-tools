<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\Config as SearchEngineConfig;

/**
 * Validates that different search engine configured when elasticsearch service is installed.
 */
class ElasticSearchUsage implements ValidatorInterface
{
    /**
     * @var SearchEngineConfig
     */
    private $searchEngineConfig;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment $environment
     * @param SearchEngineConfig $searchEngineConfig
     * @param Validator\ResultFactory $resultFactory
     */
    public function __construct(
        Environment $environment,
        SearchEngineConfig $searchEngineConfig,
        Validator\ResultFactory $resultFactory
    ) {
        $this->searchEngineConfig = $searchEngineConfig;
        $this->resultFactory = $resultFactory;
        $this->environment = $environment;
    }

    /**
     * Returns success when elasticsearch service not exist.
     * Returns success when elasticsearch service exist and search engine configured as elasticsearch.
     * Otherwise returns error.
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        $relationships = $this->environment->getRelationships();
        if (!isset($relationships['elasticsearch'])) {
            return $this->resultFactory->success();
        }

        $searchEngine = $this->searchEngineConfig->get()['engine'];
        if (strpos($searchEngine, 'elasticsearch') === 0) {
            return $this->resultFactory->success();
        }

        return $this->resultFactory->error(
            'Elasticsearch service is installed at infrastructure layer but is not used as a search engine.',
            'Consider removing elasticsearch service from infrastructure layer for optimized resource usage.'
        );
    }
}
