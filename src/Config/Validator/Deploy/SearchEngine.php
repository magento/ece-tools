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

class SearchEngine implements ValidatorInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @param Environment $environment
     * @param ResultFactory $resultFactory
     */
    public function __construct(Environment $environment, ResultFactory $resultFactory)
    {
        $this->environment = $environment;
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

        if (isset($relationships['solr'])) {
            return $this->resultFactory->create(
                ResultInterface::ERROR,
                [
                    'error' => 'Configuration for Solr was found in .magento.app.yaml.',
                    'suggestion' => 'Solr is no longer supported by Magento 2.1 or later. ' .
                        'You should remove this relationship and use either MySQL or Elasticsearch.',
                ]
            );
        }

        return $this->resultFactory->create(ResultInterface::SUCCESS);
    }
}
