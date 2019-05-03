<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MagentoCloud\Config\Validator\Deploy;

use Magento\MagentoCloud\Config\Environment;
use Magento\MagentoCloud\Config\ValidatorInterface;
use Magento\MagentoCloud\Config\Validator\ResultFactory;
use Magento\MagentoCloud\Config\Validator\ResultInterface;
use Magento\MagentoCloud\Package\MagentoVersion;
use Magento\MagentoCloud\Package\UndefinedPackageException;

/**
 * Validates Solr has not been configured for project.
 */
class SolrIntegrity implements ValidatorInterface
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
     * Validate that search engine config is not set to Solr
     *
     * @return ResultInterface
     *
     * @throws UndefinedPackageException
     */
    public function validate(): ResultInterface
    {
        $relationships = $this->environment->getRelationships();

        if (isset($relationships['solr'])) {
            $args['error'] = 'Configuration for Solr was found in .magento.app.yaml.';

            if ($this->magentoVersion->satisfies('2.1.*')) {
                $args['suggestion'] = 'Solr support has been deprecated in Magento 2.1. ' .
                    'Update your search engine to Elasticsearch and remove this relationship.';
            }

            if ($this->magentoVersion->satisfies('>=2.2')) {
                $args['suggestion'] = 'Solr is no longer supported by Magento 2.2 or later. ' .
                    'Remove this relationship and use Elasticsearch.';
            }

            return $this->resultFactory->create(ResultInterface::ERROR, $args);
        }

        return $this->resultFactory->create(ResultInterface::SUCCESS);
    }
}
