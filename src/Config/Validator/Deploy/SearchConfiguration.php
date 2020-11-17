<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\App\Error;
use Magento\MagentoCloud\App\GenericException;
use Magento\MagentoCloud\Config\ConfigMerger;
use Magento\MagentoCloud\Config\SearchEngine\ElasticSuite;
use Magento\MagentoCloud\Config\Stage\DeployInterface;
use Magento\MagentoCloud\Config\Validator;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\ValidatorException;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Service\ElasticSearch;

/**
 * Validates SEARCH_CONFIGURATION variable
 */
class SearchConfiguration implements ValidatorInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var DeployInterface
     */
    private $stageConfig;

    /**
     * @var ConfigMerger
     */
    private $configMerger;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @param ResultFactory $resultFactory
     * @param DeployInterface $stageConfig
     * @param ConfigMerger $configMerger
     * @param MagentoVersion $magentoVersion
     */
    public function __construct(
        ResultFactory $resultFactory,
        DeployInterface $stageConfig,
        ConfigMerger $configMerger,
        MagentoVersion $magentoVersion
    ) {
        $this->resultFactory = $resultFactory;
        $this->stageConfig = $stageConfig;
        $this->configMerger = $configMerger;
        $this->magentoVersion = $magentoVersion;
    }

    /**
     * Checks that SEARCH_CONFIGURATION variable contains at least 'engine' option if _merge was not set.
     * Checks that search engine for Magento 2.4 is set to elasticsearch
     *
     * {@inheritDoc}
     */
    public function validate(): Validator\ResultInterface
    {
        try {
            $searchConfig = $this->stageConfig->get(DeployInterface::VAR_SEARCH_CONFIGURATION);

            if ($this->magentoVersion->isGreaterOrEqual('2.4.0')
                && isset($searchConfig['engine'])
                && !in_array($searchConfig['engine'], [ElasticSearch::ENGINE_NAME, ElasticSuite::ENGINE_NAME])
            ) {
                return $this->resultFactory->errorByCode(Error::DEPLOY_WRONG_SEARCH_ENGINE);
            }

            if ($this->configMerger->isEmpty($searchConfig) || $this->configMerger->isMergeRequired($searchConfig)) {
                return $this->resultFactory->success();
            }

            if (!isset($searchConfig['engine'])) {
                return $this->resultFactory->error(
                    sprintf('Variable %s is not configured properly', DeployInterface::VAR_SEARCH_CONFIGURATION),
                    'At least engine option must be configured',
                    Error::DEPLOY_WRONG_CONFIGURATION_SEARCH
                );
            }
        } catch (GenericException $exception) {
            throw new ValidatorException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->resultFactory->success();
    }
}
