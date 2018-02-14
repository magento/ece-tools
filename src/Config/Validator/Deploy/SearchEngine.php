<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Package\MagentoVersion;

/**
 * Validates Solr has not been configured for project
 */
class SearchEngine implements ValidatorInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param Environment $environment
     * @param MagentoVersion $version
     * @param ResultFactory $resultFactory
     */
    public function __construct(Environment $environment, MagentoVersion $version, ResultFactory $resultFactory)
    {
        $this->environment = $environment;
        $this->magentoVersion = $version;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Valdiate that search engine config is not set to Solr
     *
     * @return ResultInterface
     */
    public function validate(): ResultInterface
    {
        $relationships = $this->environment->getRelationships();

        if (isset($relationships['solr']) && $this->magentoVersion->satisfies('>=2.2')) {
            return $this->resultFactory->create(
                ResultInterface::ERROR,
                [
                    'error' => 'Configuration for Solr was found in .magento.app.yaml.',
                    'suggestion' => 'Solr is no longer supported by Magento 2.2 or later. ' .
                        'You should remove this relationship and use either MySQL or Elasticsearch.',
                ]
            );
        }

        return $this->resultFactory->create(ResultInterface::SUCCESS);
    }
}
