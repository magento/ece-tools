<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\Config\ConfigException;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Service\ElasticSearch;
use Magento\MagentoCloud\Service\OpenSearch;

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
     * @var OpenSearch
     */
    private $openSearch;

    /**
     * @var Validator\ResultFactory
     */
    private $resultFactory;

    /**
     * @var DeployInterface
     */
    private $config;

    /**
     * @param ElasticSuite $elasticSuite
     * @param ElasticSearch $elasticSearch
     * @param OpenSearch $openSearch
     * @param Validator\ResultFactory $resultFactory
     * @param DeployInterface $config
     */
    public function __construct(
        ElasticSuite $elasticSuite,
        ElasticSearch $elasticSearch,
        OpenSearch $openSearch,
        Validator\ResultFactory $resultFactory,
        DeployInterface $config
    ) {
        $this->elasticSuite = $elasticSuite;
        $this->elasticSearch = $elasticSearch;
        $this->openSearch = $openSearch;
        $this->resultFactory = $resultFactory;
        $this->config = $config;
    }

    /**
     * If ElasticSuite is absent - skip validation.
     * If ElasticSuite is present and no ElasticSearch or OpenSearch connection - fail validation.
     * If search engine is manually set to non-ElasticSuite it will fail after deploy - fail validation.
     *
     * Otherwise - validation is successful.
     *
     * @return Validator\ResultInterface
     *
     * {@inheritDoc}
     */
    public function validate(): Validator\ResultInterface
    {
        if (!$this->elasticSuite->isInstalled()) {
            return $this->resultFactory->success();
        }

        if (!$this->elasticSearch->isInstalled() && !$this->openSearch->isInstalled()) {
            return $this->resultFactory->error(
                'ElasticSuite is installed without available ElasticSearch or OpenSearch service.',
                '',
                Error::DEPLOY_ELASTIC_SUITE_WITHOUT_ES
            );
        }

        try {
            $engine = $this->config->get(DeployInterface::VAR_SEARCH_CONFIGURATION)['engine'] ?? null;
        } catch (ConfigException $e) {
            throw new ValidatorException($e->getMessage(), $e->getCode(), $e);
        }

        if ($engine && strtolower($engine) !== ElasticSuite::ENGINE_NAME) {
            return $this->resultFactory->error(
                sprintf('ElasticSuite is installed but %s set as search engine.', $engine),
                '',
                Error::DEPLOY_ELASTIC_SUITE_WRONG_ENGINE
            );
        }

        return $this->resultFactory->success();
    }
}
