<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Process\Deploy\InstallUpdate\ConfigUpdate\SearchEngine\ElasticSuite;

/**
 * Validates different aspects of ElasticSuite's configuration.
 */
class ElasticSuiteConfiguration implements ValidatorInterface
{
    /**
     * @var ElasticSuite
     */
    private $elasticSuite;

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
     * @param Validator\ResultFactory $resultFactory
     * @param DeployInterface $config
     */
    public function __construct(
        ElasticSuite $elasticSuite,
        Validator\ResultFactory $resultFactory,
        DeployInterface $config
    ) {
        $this->elasticSuite = $elasticSuite;
        $this->resultFactory = $resultFactory;
        $this->config = $config;
    }

    /**
     * @return Validator\ResultInterface
     */
    public function validate(): Validator\ResultInterface
    {
        if (!$this->elasticSuite->isInstalled()) {
            return $this->resultFactory->success();
        }

        if (!$this->elasticSuite->isAvailable()) {
            return $this->resultFactory->error('ElasticSuite is installed without available ElasticSearch service.');
        }

        $engine = $this->config->get(DeployInterface::VAR_SEARCH_CONFIGURATION)['engine'] ?? null;

        if ($engine && strtolower($engine) !== ElasticSuite::ENGINE_NAME) {
            return $this->resultFactory->error(sprintf(
                'ElasticSuite is installed but %s set as search engine.',
                $engine
            ));
        }

        return $this->resultFactory->success();
    }
}
