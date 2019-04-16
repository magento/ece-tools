<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\SearchEngine;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSearch;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;

/**
 * Validates different aspects of ElasticSuite's configuration.
 */
class ElasticSuiteIntegrity implements ValidatorInterface
{
    /**
     * @var ElasticSuite
     */
    private $elasticSuite;

    /**
     * @var ElasticSearch
     */
    private $elasticSearch;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var SearchEngine
     */
    private $searchEngine;

    /**
     * @param ElasticSuite $elasticSuite
     * @param ElasticSearch $elasticSearch
     * @param Validator\ResultFactory $resultFactory
     * @param SearchEngine $searchEngine
     */
    public function __construct(
        ElasticSuite $elasticSuite,
        ElasticSearch $elasticSearch,
        Validator\ResultFactory $resultFactory,
        SearchEngine $searchEngine
    ) {
        $this->elasticSuite = $elasticSuite;
        $this->elasticSearch = $elasticSearch;
        $this->resultFactory = $resultFactory;
        $this->searchEngine = $searchEngine;
    }

    /**
     * If ElasticSuite is absent - skip validation.
     * If ElasticSuite is present and no ElasticSearch connection - fail validation.
     * If search engine is manually set to non-ElasticSuite it will fail after deploy - fail validation.
     *
     * Otherwise - validation is successful.
     *
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        if (!$this->elasticSuite->isInstalled()) {
            return $this->resultFactory->success();
        }

        if (!$this->elasticSearch->isInstalled()) {
            return $this->resultFactory->error('ElasticSuite is installed without available ElasticSearch service.');
        }

        $engine = $this->searchEngine->getName();

        if ($engine && strtolower($engine) !== ElasticSuite::ENGINE_NAME) {
            return $this->resultFactory->error(sprintf(
                'ElasticSuite is installed but %s set as search engine.',
                $engine
            ));
        }

        return $this->resultFactory->success();
    }
}
